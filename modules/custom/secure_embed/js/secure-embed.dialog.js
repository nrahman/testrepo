/**
 * @file
 * Secure Embed dialog JavaScript.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Dialog form behaviors.
   */
  Drupal.behaviors.secureEmbedDialog = {
    attach: function (context) {
      // URL validation on blur.
      const urlInput = $(context).find('input[name="url"]');
      if (urlInput.length) {
        urlInput.once('secure-embed-url').on('blur', function () {
          Drupal.secureEmbedDialog.validateUrl($(this));
        });
      }

      // Auto-populate title from URL.
      urlInput.once('secure-embed-autotitle').on('blur', function () {
        const titleInput = $(context).find('input[name="title"]');
        if (titleInput.length && !titleInput.val()) {
          Drupal.secureEmbedDialog.suggestTitle($(this).val(), titleInput);
        }
      });

      // Toggle responsive options.
      const responsiveCheckbox = $(context).find('input[name="display[responsive]"]');
      if (responsiveCheckbox.length) {
        Drupal.secureEmbedDialog.toggleResponsiveOptions(responsiveCheckbox);
        responsiveCheckbox.once('secure-embed-responsive').on('change', function () {
          Drupal.secureEmbedDialog.toggleResponsiveOptions($(this));
        });
      }
    }
  };

  /**
   * Dialog utilities.
   */
  Drupal.secureEmbedDialog = Drupal.secureEmbedDialog || {};

  /**
   * Validate URL format.
   *
   * @param {jQuery} $input
   *   The URL input element.
   */
  Drupal.secureEmbedDialog.validateUrl = function ($input) {
    const url = $input.val();
    const $formItem = $input.closest('.form-item');

    if (!url) {
      return;
    }

    // Basic URL validation.
    try {
      const parsed = new URL(url);

      // Must be HTTPS.
      if (parsed.protocol !== 'https:') {
        $formItem.addClass('form-item--warning');
        Drupal.secureEmbedDialog.showMessage(
          Drupal.t('For security, HTTPS URLs are recommended.'),
          'warning'
        );
      } else {
        $formItem.removeClass('form-item--warning form-item--error');
      }
    } catch (e) {
      $formItem.addClass('form-item--error');
    }
  };

  /**
   * Suggest a title based on URL.
   *
   * @param {string} url
   *   The embed URL.
   * @param {jQuery} $titleInput
   *   The title input element.
   */
  Drupal.secureEmbedDialog.suggestTitle = function (url, $titleInput) {
    if (!url) {
      return;
    }

    try {
      const parsed = new URL(url);
      let suggestion = '';

      // Platform-specific suggestions.
      if (parsed.hostname.includes('youtube.com') || parsed.hostname.includes('youtu.be')) {
        suggestion = 'YouTube Video';
      } else if (parsed.hostname.includes('vimeo.com')) {
        suggestion = 'Vimeo Video';
      } else if (parsed.hostname.includes('spotify.com')) {
        suggestion = 'Spotify Embed';
      } else if (parsed.hostname.includes('google.com/maps')) {
        suggestion = 'Google Map';
      } else if (parsed.hostname.includes('soundcloud.com')) {
        suggestion = 'SoundCloud Audio';
      } else {
        suggestion = 'Embedded Content from ' + parsed.hostname.replace('www.', '');
      }

      $titleInput.attr('placeholder', suggestion);
    } catch (e) {
      // Invalid URL, ignore.
    }
  };

  /**
   * Toggle responsive dimension options.
   *
   * @param {jQuery} $checkbox
   *   The responsive checkbox element.
   */
  Drupal.secureEmbedDialog.toggleResponsiveOptions = function ($checkbox) {
    const isResponsive = $checkbox.is(':checked');
    const $aspectRatio = $checkbox.closest('form').find('[name="display[aspect_ratio]"]').closest('.form-item');
    const $width = $checkbox.closest('form').find('[name="display[width]"]').closest('.form-item');
    const $height = $checkbox.closest('form').find('[name="display[height]"]').closest('.form-item');

    if (isResponsive) {
      $aspectRatio.show();
      $width.hide();
      $height.hide();
    } else {
      $aspectRatio.hide();
      $width.show();
      $height.show();
    }
  };

  /**
   * Show a message in the dialog.
   *
   * @param {string} message
   *   The message text.
   * @param {string} type
   *   The message type (status, warning, error).
   */
  Drupal.secureEmbedDialog.showMessage = function (message, type) {
    const $container = $('#secure-embed-messages');
    if ($container.length) {
      $container.html('<div class="messages messages--' + type + '">' + Drupal.checkPlain(message) + '</div>');
    }
  };

  /**
   * Clear messages.
   */
  Drupal.secureEmbedDialog.clearMessages = function () {
    $('#secure-embed-messages').empty();
  };

})(jQuery, Drupal);
