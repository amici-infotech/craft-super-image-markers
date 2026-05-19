(() => {
  // Craft is only available in the Control Panel. Exit quietly if loaded elsewhere.
  if (typeof Craft === 'undefined') {
    return;
  }

  /**
   * Returns the best display label available from Craft's element selector payload.
   */
  const elementLabel = (element) => {
    return element.title || element.label || element.name || `#${element.id}`;
  };

  /**
   * Formats a percentage for table display without unnecessary trailing precision.
   */
  const formatPercentage = (value) => {
    const number = Number.parseFloat(value);

    if (!Number.isFinite(number)) {
      return '0';
    }

    return String(Math.round(number * 100) / 100);
  };

  /**
   * Clamps coordinates to the valid percentage range used by field storage.
   */
  const clampPercentage = (value) => {
    const number = Number.parseFloat(value);

    if (!Number.isFinite(number)) {
      return 0;
    }

    return Math.max(0, Math.min(100, Math.round(number * 100) / 100));
  };

  /**
   * Accepts valid hex colors and falls back to the plugin default color.
   */
  const normalizeColor = (value) => {
    return /^#[0-9a-f]{6}$/i.test(value || '') ? value.toLowerCase() : '#d92828';
  };

  /**
   * Creates a stable client-side marker ID for new unsaved markers.
   */
  const createUid = () => {
    if (window.crypto?.randomUUID) {
      return window.crypto.randomUUID();
    }

    return `marker-${Date.now()}-${Math.random().toString(16).slice(2)}`;
  };

  /**
   * Reads the first selected element ID from Craft's native element selector input.
   */
  const readInputId = ($input) => {
    const value = $input.val();

    if (Array.isArray(value)) {
      return value.find((item) => Number.isFinite(Number.parseInt(item, 10))) || null;
    }

    return value || null;
  };

  /**
   * Makes Craft's native asset upload button target our configured upload folder.
   */
  const patchAssetSelectUploads = () => {
    if (!Craft.AssetSelectInput?.prototype || Craft.AssetSelectInput.prototype._superImageMarkersPatched) {
      return;
    }

    const originalAttachUploader = Craft.AssetSelectInput.prototype._attachUploader;

    Craft.AssetSelectInput.prototype._attachUploader = function () {
      originalAttachUploader.apply(this, arguments);

      const uploadFolderId = Number.parseInt(
        this.$container.closest('.sim-field').data('upload-folder-id'),
        10
      );

      if (!Number.isFinite(uploadFolderId) || !this.uploader) {
        return;
      }

      this.uploader.setParams({
        folderId: uploadFolderId,
      });
    };

    Craft.AssetSelectInput.prototype._superImageMarkersPatched = true;
  };

  patchAssetSelectUploads();

  /**
   * Garnish controller for one Super Image Markers field input.
   */
  Craft.SuperImageMarkersInput = Garnish.Base.extend({
    /**
     * Initializes DOM references, event handlers, and initial rendering.
     */
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

      // Add marker button opens the entry selector before inserting the marker.
      this.$container.on('click.superImageMarkers', '.sim-add-marker', (event) => {
        event.preventDefault();
        event.stopPropagation();
        this.addMarker();
      });

      this.observeImageSelector();

      // Pointer events support mouse, pen, and touch dragging.
      this.$layer.on('pointerdown', '.sim-marker', (event) => {
        this.startDrag(event);
      });

      // Double-clicking a marker changes its related entry.
      this.$layer.on('dblclick', '.sim-marker', (event) => {
        event.preventDefault();
        const marker = this.findMarker($(event.currentTarget).data('uid'));
        this.selectEntry(marker);
      });

      // The table edit icon uses the same entry selector as marker double-click.
      this.$tableBody.on('click', '[data-action="select-entry"]', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const marker = this.findMarker($(event.currentTarget).data('uid'));
        this.selectEntry(marker);
      });

      // Remove marker rows from both the table and the image layer.
      this.$tableBody.on('click', '[data-action="remove-marker"]', (event) => {
        event.preventDefault();
        this.removeMarker($(event.currentTarget).data('uid'));
      });

      // Color changes update the marker preview, table swatch, and hidden JSON immediately.
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

      // Coordinate inputs let editors fine-tune marker placement from the table.
      this.$tableBody.on('input change', '.sim-coordinate-input', (event) => {
        const $input = $(event.currentTarget);
        const marker = this.findMarker($input.data('uid'));
        const axis = $input.data('axis');

        if (!marker || !['x', 'y'].includes(axis)) {
          return;
        }

        marker[axis] = clampPercentage($input.val());
        this.positionMarker(marker);
        this.syncMarkersInput();

        if (event.type === 'change') {
          $input.val(formatPercentage(marker[axis]));
        }
      });

      // Table row drag-and-drop only starts from the sort handle.
      this.$tableBody.on('dragstart', '.sim-marker-row', (event) => {
        if (!$(event.originalEvent.target).closest('.sim-sort-handle').length) {
          event.preventDefault();
          return;
        }

        event.currentTarget.classList.add('is-dragging');
        event.originalEvent.dataTransfer.effectAllowed = 'move';
        event.originalEvent.dataTransfer.setData('text/plain', event.currentTarget.dataset.uid);
      });

      // Allow dropping marker rows onto other marker rows.
      this.$tableBody.on('dragover', '.sim-marker-row', (event) => {
        event.preventDefault();
        event.originalEvent.dataTransfer.dropEffect = 'move';
      });

      // Reorder the marker array using the dragged row UID and target row UID.
      this.$tableBody.on('drop', '.sim-marker-row', (event) => {
        event.preventDefault();
        this.reorderMarker(
          event.originalEvent.dataTransfer.getData('text/plain'),
          event.currentTarget.dataset.uid
        );
      });

      // Clean up the visual dragging state regardless of drop success.
      this.$tableBody.on('dragend', '.sim-marker-row', (event) => {
        event.currentTarget.classList.remove('is-dragging');
      });

      this.render();
      this.syncMarkersInput();
    },

    /**
     * Adds a marker at the image center after selecting a related entry.
     */
    addMarker() {
      this.updateImageFromSelector(false);

      if (!this.hasSelectedImage()) {
        Craft.cp.displayError(Craft.t('super-image-markers', 'Select an image before adding markers.'));
        return;
      }

      this.openEntrySelector((element) => {
        // New markers start centered; editors can drag them to the final coordinate.
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

    /**
     * Changes the related entry for an existing marker.
     */
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

    /**
     * Opens Craft's native entry selector modal.
     */
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
            // The callback lets callers decide whether to add or update a marker.
            onSelect(element);
          }
        },
      });
    },

    /**
     * Starts dragging a marker on the image preview.
     */
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

      // Bind document-level handlers so dragging continues if the pointer leaves the marker.
      $(document)
        .on('pointermove.superImageMarkers', (moveEvent) => {
          this.dragMarker(moveEvent, marker);
        })
        .on('pointerup.superImageMarkers pointercancel.superImageMarkers', () => {
          this.stopDrag();
        });
    },

    /**
     * Updates marker coordinates while the user drags.
     */
    dragMarker(event, marker) {
      const rect = this.$stage[0].getBoundingClientRect();

      // Convert rendered image coordinates into responsive percentages.
      marker.x = clampPercentage(((event.clientX - rect.left) / rect.width) * 100);
      marker.y = clampPercentage(((event.clientY - rect.top) / rect.height) * 100);

      this.positionMarker(marker);
      this.renderTable();
      this.syncMarkersInput();
    },

    /**
     * Stops marker dragging and removes temporary document handlers.
     */
    stopDrag() {
      this.dragState = null;
      $(document).off('.superImageMarkers');
    },

    /**
     * Removes a marker by UID.
     */
    removeMarker(uid) {
      this.markers = this.markers.filter((marker) => marker.uid !== uid);
      this.render();
      this.syncMarkersInput();
    },

    /**
     * Reorders markers after a table row drag-and-drop action.
     */
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

    /**
     * Finds a marker by its stable UID.
     */
    findMarker(uid) {
      return this.markers.find((marker) => marker.uid === uid);
    },

    /**
     * Renders the image preview, marker buttons, and marker table.
     */
    render() {
      this.renderImage();
      this.renderMarkers();
      this.renderTable();
    },

    /**
     * Updates the image preview state.
     */
    renderImage() {
      this.$stage.toggleClass('is-empty', !this.hasSelectedImage());

      if (this.image?.url) {
        this.$image.attr('src', this.image.url);
      } else {
        this.$image.attr('src', '');
      }
    },

    /**
     * Checks whether the field currently has a selected image.
     */
    hasSelectedImage() {
      return !!(this.image?.id || this.image?.url || this.$image.attr('src'));
    },

    /**
     * Watches Craft's native asset selector for changes.
     */
    observeImageSelector() {
      if (!this.$imageSelect.length) {
        return;
      }

      const observer = new MutationObserver(() => {
        this.updateImageFromSelector();
      });

      // Native element chips update through DOM changes rather than plugin events.
      observer.observe(this.$imageSelect[0], {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['value', 'data-id', 'data-url', 'data-label'],
      });

      // Hidden input changes catch direct updates after uploads or replacement.
      this.$imageSelect.on('change.superImageMarkers input.superImageMarkers', 'input[type="hidden"]', () => {
        this.updateImageFromSelector();
      });
    },

    /**
     * Reads image metadata from Craft's native asset selector DOM.
     */
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
        // No selected chip or URL means the image was removed.
        this.image = null;
        if (render) {
          this.render();
        }
        return;
      }

      // Store enough data to render the preview immediately without another request.
      this.image = {
        id: Number.isFinite(id) ? id : this.image?.id || null,
        title: $element.data('label') || $element.find('.title').text() || `#${id}`,
        url,
      };

      if (render) {
        this.render();
      }
    },

    /**
     * Renders marker buttons over the image.
     */
    renderMarkers() {
      this.$layer.empty();

      for (const marker of this.markers) {
        // Buttons are used so markers are focusable and accessible in the CP.
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

    /**
     * Positions one rendered marker at its saved percentage coordinates.
     */
    positionMarker(marker) {
      this.$layer
        .find(`.sim-marker[data-uid="${marker.uid}"]`)
        .css({
          left: `${marker.x}%`,
          top: `${marker.y}%`,
        });
    },

    /**
     * Creates an editable coordinate cell with a percentage suffix.
     */
    coordinateCell(marker, axis) {
      return $('<td/>').append(
        $('<div/>', {class: 'sim-coordinate-control'}).append(
          $('<input/>', {
            type: 'number',
            class: 'text small sim-coordinate-input',
            min: 0,
            max: 100,
            step: 0.01,
            value: formatPercentage(marker[axis]),
            'data-axis': axis,
            'data-uid': marker.uid,
            'aria-label': Craft.t('super-image-markers', axis === 'x' ? 'X position' : 'Y position'),
          }),
          $('<span/>', {
            class: 'sim-coordinate-suffix',
            text: '%',
          })
        )
      );
    },

    /**
     * Renders the Craft-style marker table below the image.
     */
    renderTable() {
      this.$tableBody.empty();

      if (!this.markers.length) {
        // Keep an empty row so the table layout remains visible before markers exist.
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
        // Build cells with jQuery to avoid injecting unescaped editor-controlled entry titles.
        const $sortCell = $('<td/>', {class: 'thin sim-sort-cell'});
        const $entryCell = $('<td/>', {class: 'sim-entry-cell'});
        const $entryWrap = $('<div/>', {class: 'sim-entry-wrap'});
        const $entryChip = $('<div/>', {
          class: `sim-entry-chip ${hasEntry ? '' : 'is-empty'}`,
          role: 'button',
          tabindex: 0,
          'aria-label': Craft.t('super-image-markers', 'Select entry'),
        });

        // Craft-like status dot shows whether a marker has an entry selected.
        $('<span/>', {
          class: hasEntry ? 'status enabled' : 'status pending',
        }).appendTo($entryChip);

        $('<span/>', {
          class: 'sim-entry-title',
          text: entryLabel,
        }).appendTo($entryChip);

        $entryChip.appendTo($entryWrap);

        // Dedicated edit icon keeps the entry title display from looking like a button.
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

        // Row order maps directly to the order returned by `markers.all()` in Twig.
        $row.append($sortCell);
        $row.append($entryCell);
        $row.append(this.coordinateCell(marker, 'x'));
        $row.append(this.coordinateCell(marker, 'y'));
        $row.append(
          $('<td/>', {class: 'thin sim-row-actions'})
            .append($('<div/>', {class: 'sim-row-actions-inner'}).append(
              // The color input is visually represented by a Craft-style swatch.
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
              // Removing a marker only affects this field value; it does not delete the entry.
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

    /**
     * Writes the marker array to the hidden JSON input Craft submits.
     */
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
