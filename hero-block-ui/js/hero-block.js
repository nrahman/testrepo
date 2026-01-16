/**
 * @file
 * Hero Block JavaScript behaviors.
 *
 * Add this to your theme's library for entrance animations and interactions.
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
        // Add loaded class.
        element.classList.add('hero--loaded');

        // Setup entrance animations if not in reduced motion mode.
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
          setupIntersectionObserver(element);
        }

        // Handle lazy loading for background images.
        var fullWidthBackground = element.querySelector('.hero__background');
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
    var observerOptions = {
      root: null,
      rootMargin: '0px',
      threshold: 0.1
    };

    var observer = new IntersectionObserver(function (entries) {
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
    var bgImage = backgroundElement.style.backgroundImage;
    if (bgImage) {
      var imageUrl = bgImage.replace(/url\(['"]?([^'"]+)['"]?\)/i, '$1');
      var preloadImg = new Image();

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
    var anchorLinks = element.querySelectorAll('.hero__button[href^="#"]');

    anchorLinks.forEach(function (link) {
      link.addEventListener('click', function (e) {
        var targetId = link.getAttribute('href').substring(1);
        if (!targetId) {
          return;
        }

        var targetElement = document.getElementById(targetId);
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
