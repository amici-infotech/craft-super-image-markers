(() => {
  if (typeof Craft === 'undefined') {
    return;
  }

  const elementLabel = (element) => {
    return element.title || element.label || element.name || `#${element.id}`;
  };

  const formatPercentage = (value) => {
    const number = Number.parseFloat(value);

    if (!Number.isFinite(number)) {
      return '0';
    }

    return String(Math.round(number * 100) / 100);
  };

  const clampPercentage = (value) => {
    const number = Number.parseFloat(value);

    if (!Number.isFinite(number)) {
      return 0;
    }

    return Math.max(0, Math.min(100, Math.round(number * 100) / 100));
  };

  const normalizeColor = (value) => {
    return /^#[0-9a-f]{6}$/i.test(value || '') ? value.toLowerCase() : '#d92828';
  };

  const createUid = () => {
    if (window.crypto?.randomUUID) {
      return window.crypto.randomUUID();
    }

    return `marker-${Date.now()}-${Math.random().toString(16).slice(2)}`;
  };

  const readInputId = ($input) => {
    const value = $input.val();

    if (Array.isArray(value)) {
      return value.find((item) => Number.isFinite(Number.parseInt(item, 10))) || null;
    }

    return value || null;
  };

  Craft.SuperImageMarkersInput = Garnish.Base.extend({
    init(settings) {
      this.settings = settings;
      this.$container = $(`#${settings.id}`);
      this.$markersInput = this.$container.find('.sim-markers-input');
      this.$imageSelect = $(`#${settings.imageSelectId}`);
      this.$stage = this.$container.find('.sim-stage');
      this.$image = this.$container.find('.sim-image');
      this.$layer = this.$container.find('.sim-marker-layer');
      this.$tableBody = this.$container.find('.sim-marker-table tbody');
      this.image = settings.image || null;
      this.markers = Array.isArray(settings.markers) ? settings.markers : [];
      this.dragState = null;

      this.$container.on('click.superImageMarkers', '.sim-add-marker', (event) => {
        event.preventDefault();
        event.stopPropagation();
        this.addMarker();
      });

      this.observeImageSelector();

      this.$layer.on('pointerdown', '.sim-marker', (event) => {
        this.startDrag(event);
      });

      this.$layer.on('dblclick', '.sim-marker', (event) => {
        event.preventDefault();
        const marker = this.findMarker($(event.currentTarget).data('uid'));
        this.selectEntry(marker);
      });

      this.$tableBody.on('click', '[data-action="select-entry"]', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const marker = this.findMarker($(event.currentTarget).data('uid'));
        this.selectEntry(marker);
      });

      this.$tableBody.on('click', '[data-action="remove-marker"]', (event) => {
        event.preventDefault();
        this.removeMarker($(event.currentTarget).data('uid'));
      });

      this.$tableBody.on('input change', '.sim-color-input', (event) => {
        const marker = this.findMarker($(event.currentTarget).data('uid'));

        if (!marker) {
          return;
        }

        marker.color = normalizeColor(event.currentTarget.value);
        this.renderMarkers();
        $(event.currentTarget)
          .siblings('.sim-color-swatch')
          .css('background-color', marker.color);
        this.syncMarkersInput();
      });

      this.$tableBody.on('dragstart', '.sim-marker-row', (event) => {
        if (!$(event.originalEvent.target).closest('.sim-sort-handle').length) {
          event.preventDefault();
          return;
        }

        event.currentTarget.classList.add('is-dragging');
        event.originalEvent.dataTransfer.effectAllowed = 'move';
        event.originalEvent.dataTransfer.setData('text/plain', event.currentTarget.dataset.uid);
      });

      this.$tableBody.on('dragover', '.sim-marker-row', (event) => {
        event.preventDefault();
        event.originalEvent.dataTransfer.dropEffect = 'move';
      });

      this.$tableBody.on('drop', '.sim-marker-row', (event) => {
        event.preventDefault();
        this.reorderMarker(
          event.originalEvent.dataTransfer.getData('text/plain'),
          event.currentTarget.dataset.uid
        );
      });

      this.$tableBody.on('dragend', '.sim-marker-row', (event) => {
        event.currentTarget.classList.remove('is-dragging');
      });

      this.render();
      this.syncMarkersInput();
    },

    addMarker() {
      this.updateImageFromSelector(false);

      if (!this.hasSelectedImage()) {
        Craft.cp.displayError(Craft.t('super-image-markers', 'Select an image before adding markers.'));
        return;
      }

      this.openEntrySelector((element) => {
        this.markers.push({
          uid: createUid(),
          x: 50,
          y: 50,
          entryId: element.id,
          entryTitle: elementLabel(element),
          color: '#d92828',
        });

        this.render();
        this.syncMarkersInput();
      });
    },

    selectEntry(marker) {
      if (!marker) {
        return;
      }

      this.openEntrySelector((element) => {
        marker.entryId = element.id;
        marker.entryTitle = elementLabel(element);

        this.render();
        this.syncMarkersInput();
      });
    },

    openEntrySelector(onSelect) {
      Craft.createElementSelectorModal('craft\\elements\\Entry', {
        storageKey: `super-image-markers-entries-${this.settings.fieldId || 'new'}`,
        sources: this.settings.entrySources,
        multiSelect: false,
        hideOnSelect: true,
        modalTitle: Craft.t('super-image-markers', 'Select an entry'),
        onSelect: (elements) => {
          const element = elements[0];

          if (element) {
            onSelect(element);
          }
        },
      });
    },

    startDrag(event) {
      if (!this.hasSelectedImage()) {
        return;
      }

      const marker = this.findMarker($(event.currentTarget).data('uid'));

      if (!marker) {
        return;
      }

      event.preventDefault();
      event.currentTarget.setPointerCapture?.(event.originalEvent.pointerId);
      this.dragState = {marker};

      $(document)
        .on('pointermove.superImageMarkers', (moveEvent) => {
          this.dragMarker(moveEvent, marker);
        })
        .on('pointerup.superImageMarkers pointercancel.superImageMarkers', () => {
          this.stopDrag();
        });
    },

    dragMarker(event, marker) {
      const rect = this.$stage[0].getBoundingClientRect();

      marker.x = clampPercentage(((event.clientX - rect.left) / rect.width) * 100);
      marker.y = clampPercentage(((event.clientY - rect.top) / rect.height) * 100);

      this.positionMarker(marker);
      this.renderTable();
      this.syncMarkersInput();
    },

    stopDrag() {
      this.dragState = null;
      $(document).off('.superImageMarkers');
    },

    removeMarker(uid) {
      this.markers = this.markers.filter((marker) => marker.uid !== uid);
      this.render();
      this.syncMarkersInput();
    },

    reorderMarker(sourceUid, targetUid) {
      if (!sourceUid || !targetUid || sourceUid === targetUid) {
        return;
      }

      const sourceIndex = this.markers.findIndex((marker) => marker.uid === sourceUid);
      const targetIndex = this.markers.findIndex((marker) => marker.uid === targetUid);

      if (sourceIndex === -1 || targetIndex === -1) {
        return;
      }

      const [marker] = this.markers.splice(sourceIndex, 1);
      this.markers.splice(targetIndex, 0, marker);
      this.render();
      this.syncMarkersInput();
    },

    findMarker(uid) {
      return this.markers.find((marker) => marker.uid === uid);
    },

    render() {
      this.renderImage();
      this.renderMarkers();
      this.renderTable();
    },

    renderImage() {
      this.$stage.toggleClass('is-empty', !this.hasSelectedImage());

      if (this.image?.url) {
        this.$image.attr('src', this.image.url);
      } else {
        this.$image.attr('src', '');
      }
    },

    hasSelectedImage() {
      return !!(this.image?.id || this.image?.url || this.$image.attr('src'));
    },

    observeImageSelector() {
      if (!this.$imageSelect.length) {
        return;
      }

      const observer = new MutationObserver(() => {
        this.updateImageFromSelector();
      });

      observer.observe(this.$imageSelect[0], {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['value', 'data-id', 'data-url', 'data-label'],
      });

      this.$imageSelect.on('change.superImageMarkers input.superImageMarkers', 'input[type="hidden"]', () => {
        this.updateImageFromSelector();
      });
    },

    updateImageFromSelector(render = true) {
      const $element = this.$imageSelect.find('.element[data-id]').first();
      const id = Number.parseInt(
        readInputId(this.$imageSelect.find('input[type="hidden"]').filter((index, input) => input.value).first()) ||
          $element.data('id'),
        10
      );
      const url = $element.data('url') ||
        $element.find('img').first().attr('src') ||
        this.image?.url ||
        this.$image.attr('src') ||
        null;

      if (!Number.isFinite(id) && !url) {
        this.image = null;
        if (render) {
          this.render();
        }
        return;
      }

      this.image = {
        id: Number.isFinite(id) ? id : this.image?.id || null,
        title: $element.data('label') || $element.find('.title').text() || `#${id}`,
        url,
      };

      if (render) {
        this.render();
      }
    },

    renderMarkers() {
      this.$layer.empty();

      for (const marker of this.markers) {
        const $marker = $('<button/>', {
          type: 'button',
          class: 'sim-marker',
          'data-uid': marker.uid,
          title: marker.entryTitle || Craft.t('super-image-markers', 'Double-click to select an entry'),
          'aria-label': marker.entryTitle || Craft.t('super-image-markers', 'Image marker'),
        });
        $marker.css('background-color', normalizeColor(marker.color));

        this.$layer.append($marker);
        this.positionMarker(marker);
      }
    },

    positionMarker(marker) {
      this.$layer
        .find(`.sim-marker[data-uid="${marker.uid}"]`)
        .css({
          left: `${marker.x}%`,
          top: `${marker.y}%`,
        });
    },

    renderTable() {
      this.$tableBody.empty();

      if (!this.markers.length) {
        this.$tableBody.append(`<tr><td colspan="5" class="light">${Craft.t('super-image-markers', 'No markers added yet.')}</td></tr>`);
        return;
      }

      for (const [index, marker] of this.markers.entries()) {
        const entryLabel = marker.entryTitle || Craft.t('super-image-markers', 'Select entry');
        const hasEntry = !!marker.entryTitle;
        const $row = $('<tr/>', {
          class: 'sim-marker-row',
          'data-uid': marker.uid,
          draggable: true,
        });
        const $sortCell = $('<td/>', {class: 'thin sim-sort-cell'});
        const $entryCell = $('<td/>', {class: 'sim-entry-cell'});
        const $entryWrap = $('<div/>', {class: 'sim-entry-wrap'});
        const $entryChip = $('<div/>', {
          class: `sim-entry-chip ${hasEntry ? '' : 'is-empty'}`,
          role: 'button',
          tabindex: 0,
          'aria-label': Craft.t('super-image-markers', 'Select entry'),
        });

        $('<span/>', {
          class: hasEntry ? 'status enabled' : 'status pending',
        }).appendTo($entryChip);

        $('<span/>', {
          class: 'sim-entry-title',
          text: entryLabel,
        }).appendTo($entryChip);

        $entryChip.appendTo($entryWrap);

        $('<button/>', {
          type: 'button',
          class: 'btn action-btn small sim-entry-action',
          'data-action': 'select-entry',
          'data-uid': marker.uid,
          title: hasEntry ? Craft.t('super-image-markers', 'Change entry') : Craft.t('super-image-markers', 'Choose entry'),
          'aria-label': hasEntry ? Craft.t('super-image-markers', 'Change entry') : Craft.t('super-image-markers', 'Choose entry'),
        }).appendTo($entryWrap);

        $entryWrap.appendTo($entryCell);

        $('<div/>', {
          class: 'sim-sort-handle',
          title: Craft.t('app', 'Reorder'),
          text: index + 1,
        }).appendTo($sortCell);

        $row.append($sortCell);
        $row.append($entryCell);
        $row.append($('<td/>', {text: formatPercentage(marker.x)}));
        $row.append($('<td/>', {text: formatPercentage(marker.y)}));
        $row.append(
          $('<td/>', {class: 'thin sim-row-actions'})
            .append($('<div/>', {class: 'sim-row-actions-inner'}).append(
              $('<label/>', {
                class: 'sim-color-control',
                title: Craft.t('super-image-markers', 'Marker color'),
              }).append(
                $('<span/>', {
                  class: 'sim-color-swatch',
                }).css('background-color', normalizeColor(marker.color)),
                $('<input/>', {
                  type: 'color',
                  class: 'sim-color-input',
                  value: normalizeColor(marker.color),
                  'data-uid': marker.uid,
                  'aria-label': Craft.t('super-image-markers', 'Marker color'),
                })
              ),
              $('<button/>', {
                type: 'button',
                class: 'delete icon',
                title: Craft.t('app', 'Remove'),
                'aria-label': Craft.t('app', 'Remove'),
                'data-action': 'remove-marker',
                'data-uid': marker.uid,
              })
            ))
        );

        this.$tableBody.append($row);
      }
    },

    syncMarkersInput() {
      this.$markersInput.val(JSON.stringify(this.markers.map((marker) => ({
          uid: marker.uid,
          x: clampPercentage(marker.x),
          y: clampPercentage(marker.y),
          entryId: marker.entryId || null,
          color: normalizeColor(marker.color),
        }))));
    },
  });
})();
