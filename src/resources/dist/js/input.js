(() => {
  if (typeof Craft === 'undefined' || Craft.SuperImageMarkersInput) {
    return;
  }

  const elementLabel = (element) => {
    return element.title || element.label || element.name || `#${element.id}`;
  };

  const elementImageUrl = (element) => {
    return element.url || element.thumbUrl || element.imageUrl || null;
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

  const createUid = () => {
    if (window.crypto?.randomUUID) {
      return window.crypto.randomUUID();
    }

    return `marker-${Date.now()}-${Math.random().toString(16).slice(2)}`;
  };

  Craft.SuperImageMarkersInput = Garnish.Base.extend({
    init(settings) {
      this.settings = settings;
      this.$container = $(`#${settings.id}`);
      this.$input = this.$container.find('.sim-field-input');
      this.$imageSelect = $(`#${settings.imageSelectId}`);
      this.$stage = this.$container.find('.sim-stage');
      this.$image = this.$container.find('.sim-image');
      this.$layer = this.$container.find('.sim-marker-layer');
      this.$tableBody = this.$container.find('.sim-marker-table tbody');
      this.image = settings.image || null;
      this.markers = Array.isArray(settings.markers) ? settings.markers : [];
      this.dragState = null;

      this.addListener(this.$imageSelect.find('.add'), 'click', 'selectImage');
      this.addListener(this.$container.find('.sim-select-image'), 'click', 'selectImage');
      this.addListener(this.$container.find('.sim-add-marker'), 'click', 'addMarker');

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
        const marker = this.findMarker($(event.currentTarget).data('uid'));
        this.selectEntry(marker);
      });

      this.$tableBody.on('click', '[data-action="remove-marker"]', (event) => {
        event.preventDefault();
        this.removeMarker($(event.currentTarget).data('uid'));
      });

      this.render();
      this.syncInput();
    },

    selectImage() {
      Craft.createElementSelector('craft\\elements\\Asset', {
        storageKey: `super-image-markers-assets-${this.settings.fieldId || 'new'}`,
        sources: this.settings.assetSources,
        criteria: {
          kind: ['image'],
        },
        multiSelect: false,
        onSelect: (elements) => {
          const element = elements[0];

          if (!element) {
            return;
          }

          this.image = {
            id: element.id,
            title: elementLabel(element),
            url: elementImageUrl(element),
          };

          this.renderImageSelection();
          this.render();
          this.syncInput();
        },
      });
    },

    addMarker() {
      if (!this.image?.id) {
        Craft.cp.displayError(Craft.t('super-image-markers', 'Select an image before adding markers.'));
        return;
      }

      this.markers.push({
        uid: createUid(),
        x: 50,
        y: 50,
        entryId: null,
        entryTitle: null,
      });

      this.render();
      this.syncInput();
    },

    selectEntry(marker) {
      if (!marker) {
        return;
      }

      Craft.createElementSelector('craft\\elements\\Entry', {
        storageKey: `super-image-markers-entries-${this.settings.fieldId || 'new'}`,
        sources: this.settings.entrySources,
        multiSelect: false,
        onSelect: (elements) => {
          const element = elements[0];

          if (!element) {
            return;
          }

          marker.entryId = element.id;
          marker.entryTitle = elementLabel(element);

          this.render();
          this.syncInput();
        },
      });
    },

    startDrag(event) {
      if (!this.image?.id) {
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
      this.syncInput();
    },

    stopDrag() {
      this.dragState = null;
      $(document).off('.superImageMarkers');
    },

    removeMarker(uid) {
      this.markers = this.markers.filter((marker) => marker.uid !== uid);
      this.render();
      this.syncInput();
    },

    findMarker(uid) {
      return this.markers.find((marker) => marker.uid === uid);
    },

    render() {
      this.renderImage();
      this.renderMarkers();
      this.renderTable();
    },

    renderImageSelection() {
      const title = Craft.escapeHtml(this.image?.title || Craft.t('super-image-markers', 'Selected image'));
      const $list = this.$imageSelect.find('.elements');

      $list.html(`<div class="element small hasthumb" data-id="${this.image.id}"><div class="label"><span class="title">${title}</span></div></div>`);
      this.$imageSelect.find('.add').toggleClass('hidden', !!this.image?.id);
      this.$container.find('.sim-select-image').text(Craft.t('super-image-markers', 'Change image'));
    },

    renderImage() {
      this.$stage.toggleClass('is-empty', !this.image?.id);

      if (this.image?.url) {
        this.$image.attr('src', this.image.url);
      } else {
        this.$image.attr('src', '');
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
        this.$tableBody.append(`<tr><td colspan="4" class="light">${Craft.t('super-image-markers', 'No markers added yet.')}</td></tr>`);
        return;
      }

      for (const marker of this.markers) {
        const entryLabel = marker.entryTitle || Craft.t('super-image-markers', 'Select entry');
        const $row = $('<tr/>');
        const $entryCell = $('<td/>');

        $('<button/>', {
          type: 'button',
          class: 'btn small',
          text: entryLabel,
          'data-action': 'select-entry',
          'data-uid': marker.uid,
        }).appendTo($entryCell);

        $row.append($entryCell);
        $row.append($('<td/>', {text: formatPercentage(marker.x)}));
        $row.append($('<td/>', {text: formatPercentage(marker.y)}));
        $row.append(
          $('<td/>', {class: 'thin'}).append(
            $('<button/>', {
              type: 'button',
              class: 'delete icon',
              title: Craft.t('app', 'Remove'),
              'aria-label': Craft.t('app', 'Remove'),
              'data-action': 'remove-marker',
              'data-uid': marker.uid,
            })
          )
        );

        this.$tableBody.append($row);
      }
    },

    syncInput() {
      const payload = {
        imageId: this.image?.id || null,
        markers: this.markers.map((marker) => ({
          uid: marker.uid,
          x: clampPercentage(marker.x),
          y: clampPercentage(marker.y),
          entryId: marker.entryId || null,
        })),
      };

      this.$input.val(JSON.stringify(payload));
    },
  });
})();
