/**
 * @file
 * Hero Component JavaScript behaviors.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Hero Component behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.heroComponent = {
    attach: function (context, settings) {
      // Process hero components once
      once('hero-component', '.hero-component', context).forEach(function (element) {
        // Add loaded class for CSS animations
        element.classList.add('hero-component--loaded');

        // Handle lazy loading for background images in full-width layout
        const fullWidthHero = element.querySelector('.hero__full-width');
        if (fullWidthHero) {
          // Preload the background image
          const bgImage = fullWidthHero.style.backgroundImage;
          if (bgImage) {
            const imageUrl = bgImage.replace(/url\(['"]?([^'"]+)['"]?\)/i, '$1');
            const img = new Image();
            img.onload = function () {
              fullWidthHero.classList.add('hero__full-width--loaded');
            };
            img.src = imageUrl;
          }
        }

        // Smooth scroll for anchor links within hero buttons
        const buttons = element.querySelectorAll('.hero__button[href^="#"]');
        buttons.forEach(function (button) {
          button.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
              e.preventDefault();
              targetElement.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
              });
            }
          });
        });
      });
    }
  };

})(Drupal, once);
