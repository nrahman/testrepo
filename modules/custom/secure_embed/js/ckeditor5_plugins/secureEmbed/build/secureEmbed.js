/**
 * @file
 * Secure Embed CKEditor 5 plugin.
 */

(function (Drupal, CKEditor5) {
  'use strict';

  /**
   * Secure Embed plugin for CKEditor 5.
   */
  class SecureEmbed {
    constructor(editor) {
      this.editor = editor;
    }

    /**
     * Initialize the plugin.
     */
    init() {
      const editor = this.editor;
      const t = editor.t;

      // Define the schema for secure embed elements.
      editor.model.schema.register('secureEmbed', {
        inheritAllFrom: '$blockObject',
        allowAttributes: ['dataSecureEmbed', 'class']
      });

      // Define the conversion from model to view (editing).
      editor.conversion.for('editingDowncast').elementToElement({
        model: 'secureEmbed',
        view: (modelElement, { writer }) => {
          const div = writer.createContainerElement('div', {
            class: 'secure-embed-token secure-embed-placeholder',
            'data-secure-embed': modelElement.getAttribute('dataSecureEmbed') || ''
          });

          // Add placeholder content.
          const content = writer.createRawElement('span', { class: 'secure-embed-label' }, function (domElement) {
            domElement.textContent = Drupal.t('[Secure Embed]');
          });

          writer.insert(writer.createPositionAt(div, 0), content);

          return div;
        }
      });

      // Define the conversion from model to view (data output).
      editor.conversion.for('dataDowncast').elementToElement({
        model: 'secureEmbed',
        view: (modelElement, { writer }) => {
          const embedData = modelElement.getAttribute('dataSecureEmbed') || '';
          let labelText = '[Secure Embed]';

          // Try to decode and extract title.
          try {
            const decoded = JSON.parse(atob(embedData));
            if (decoded.title) {
              labelText = '[Secure Embed: ' + decoded.title + ']';
            } else if (decoded.url) {
              const url = new URL(decoded.url);
              labelText = '[Secure Embed: ' + url.hostname + ']';
            }
          } catch (e) {
            // Use default label.
          }

          const div = writer.createContainerElement('div', {
            class: 'secure-embed-token',
            'data-secure-embed': embedData
          });

          const text = writer.createText(labelText);
          writer.insert(writer.createPositionAt(div, 0), text);

          return div;
        }
      });

      // Define the conversion from view to model (upcast).
      editor.conversion.for('upcast').elementToElement({
        view: {
          name: 'div',
          classes: 'secure-embed-token'
        },
        model: (viewElement, { writer }) => {
          return writer.createElement('secureEmbed', {
            dataSecureEmbed: viewElement.getAttribute('data-secure-embed') || ''
          });
        }
      });

      // Register the toolbar button.
      editor.ui.componentFactory.add('secureEmbed', locale => {
        const button = new CKEditor5.ui.ButtonView(locale);

        button.set({
          label: t('Secure Embed'),
          icon: this._getIcon(),
          tooltip: true,
          withText: false
        });

        // Execute command on button click.
        button.on('execute', () => {
          this._openDialog();
        });

        return button;
      });
    }

    /**
     * Get the toolbar button icon SVG.
     */
    _getIcon() {
      return '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19 4H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H5V6h14v12zM9.5 13l2.5 3.01L14.5 13l3 4H6.5l3-4z"/></svg>';
    }

    /**
     * Open the Secure Embed dialog.
     */
    _openDialog() {
      const editor = this.editor;
      const selection = editor.model.document.selection;
      let existingData = '';

      // Check if we're editing an existing embed.
      const selectedElement = selection.getSelectedElement();
      if (selectedElement && selectedElement.name === 'secureEmbed') {
        existingData = selectedElement.getAttribute('dataSecureEmbed') || '';
      }

      // Build dialog URL.
      let dialogUrl = Drupal.url('secure-embed/dialog');
      if (existingData) {
        dialogUrl += '?edit=' + encodeURIComponent(existingData);
      }

      // Open the dialog.
      Drupal.ckeditor5.openDialog(
        dialogUrl,
        (returnValues) => {
          if (returnValues && returnValues.embed_data) {
            editor.model.change(writer => {
              const embedElement = writer.createElement('secureEmbed', {
                dataSecureEmbed: this._encodeData(returnValues.embed_data)
              });

              // Insert or replace the element.
              if (selectedElement && selectedElement.name === 'secureEmbed') {
                writer.setSelection(selectedElement, 'on');
              }

              editor.model.insertContent(embedElement);
            });
          }
        },
        {
          title: Drupal.t('Insert Secure Embed'),
          dialogClass: 'secure-embed-dialog'
        }
      );
    }

    /**
     * Encode embed data to base64.
     */
    _encodeData(data) {
      try {
        return btoa(JSON.stringify(data));
      } catch (e) {
        console.error('Failed to encode embed data:', e);
        return '';
      }
    }

    /**
     * Plugin name.
     */
    static get pluginName() {
      return 'SecureEmbed';
    }

    /**
     * Required plugins.
     */
    static get requires() {
      return [];
    }
  }

  // Register the plugin with CKEditor 5.
  CKEditor5.secureEmbed = {
    SecureEmbed: SecureEmbed
  };

})(Drupal, CKEditor5);
