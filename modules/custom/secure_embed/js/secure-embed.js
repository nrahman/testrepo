/**
 * @file
 * Secure Embed frontend JavaScript.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Initialize secure embeds.
   */
  Drupal.behaviors.secureEmbed = {
    attach: function (context) {
      const embeds = once('secure-embed', '.secure-embed', context);

      embeds.forEach(function (embed) {
        const iframe = embed.querySelector('iframe');
        if (!iframe) {
          return;
        }

        // Add loading state.
        embed.classList.add('secure-embed--loading');

        // Remove loading state when iframe loads.
        iframe.addEventListener('load', function () {
          embed.classList.remove('secure-embed--loading');
        });

        // Handle iframe errors.
        iframe.addEventListener('error', function () {
          embed.classList.remove('secure-embed--loading');
          embed.classList.add('secure-embed--error');
        });

        // Lazy loading fallback for older browsers.
        if (!('loading' in HTMLIFrameElement.prototype) && iframe.dataset.src) {
          Drupal.secureEmbed.lazyLoad(embed, iframe);
        }
      });
    }
  };

  /**
   * Secure Embed utilities.
   */
  Drupal.secureEmbed = Drupal.secureEmbed || {};

  /**
   * Lazy load fallback using Intersection Observer.
   *
   * @param {HTMLElement} wrapper
   *   The embed wrapper element.
   * @param {HTMLIFrameElement} iframe
   *   The iframe element.
   */
  Drupal.secureEmbed.lazyLoad = function (wrapper, iframe) {
    if (!('IntersectionObserver' in window)) {
      // Load immediately if IntersectionObserver not supported.
      if (iframe.dataset.src) {
        iframe.src = iframe.dataset.src;
      }
      return;
    }

    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          if (iframe.dataset.src) {
            iframe.src = iframe.dataset.src;
          }
          observer.unobserve(wrapper);
        }
      });
    }, {
      rootMargin: '200px 0px'
    });

    observer.observe(wrapper);
  };

  /**
   * Resize iframe to match content height (for same-origin iframes).
   *
   * @param {HTMLIFrameElement} iframe
   *   The iframe element.
   */
  Drupal.secureEmbed.resizeToContent = function (iframe) {
    try {
      const doc = iframe.contentDocument || iframe.contentWindow.document;
      iframe.style.height = doc.body.scrollHeight + 'px';
    } catch (e) {
      // Cross-origin iframe, cannot resize.
      console.warn('Secure Embed: Cannot resize cross-origin iframe.');
    }
  };

})(Drupal, once);
