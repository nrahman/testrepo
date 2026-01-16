/**
 * @file
 * Hero Block JavaScript behaviors.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Hero Block behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.heroBlock = {
    attach: function (context, settings) {
      // Process hero blocks once.
      once('hero-block', '.hero', context).forEach(function (element) {
        // Add loaded class for potential CSS animations.
        element.classList.add('hero--loaded');

        // Setup entrance animations if not in reduced motion mode.
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
          setupIntersectionObserver(element);
        }

        // Handle lazy loading for background images in full-width layout.
        const fullWidthBackground = element.querySelector('.hero__background');
        if (fullWidthBackground) {
          preloadBackgroundImage(fullWidthBackground);
        }

        // Setup smooth scroll for anchor links.
        setupSmoothScroll(element);
      });
    }
  };

  /**
   * Setup Intersection Observer for entrance animations.
   *
   * @param {HTMLElement} element
   *   The hero element.
   */
  function setupIntersectionObserver(element) {
    const observerOptions = {
      root: null,
      rootMargin: '0px',
      threshold: 0.1
    };

    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('hero--animated');
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    observer.observe(element);
  }

  /**
   * Preload background image for full-width hero.
   *
   * @param {HTMLElement} backgroundElement
   *   The background element with inline style.
   */
  function preloadBackgroundImage(backgroundElement) {
    const bgImage = backgroundElement.style.backgroundImage;
    if (bgImage) {
      const imageUrl = bgImage.replace(/url\(['"]?([^'"]+)['"]?\)/i, '$1');
      const preloadImg = new Image();

      preloadImg.onload = function () {
        backgroundElement.classList.add('hero__background--loaded');
      };

      preloadImg.onerror = function () {
        console.warn('Hero background image failed to load:', imageUrl);
      };

      preloadImg.src = imageUrl;
    }
  }

  /**
   * Setup smooth scrolling for anchor links within hero buttons.
   *
   * @param {HTMLElement} element
   *   The hero element.
   */
  function setupSmoothScroll(element) {
    const anchorLinks = element.querySelectorAll('.hero__button[href^="#"]');

    anchorLinks.forEach(function (link) {
      link.addEventListener('click', function (e) {
        const targetId = link.getAttribute('href').substring(1);
        if (!targetId) {
          return;
        }

        const targetElement = document.getElementById(targetId);
        if (targetElement) {
          e.preventDefault();
          targetElement.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });

          // Update URL without jumping.
          if (history.pushState) {
            history.pushState(null, null, '#' + targetId);
          }
        }
      });
    });
  }

})(Drupal, once);
