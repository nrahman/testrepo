/**
 * @file
 * Card Block JavaScript behaviors.
 *
 * Provides enhanced interactions for the Card component.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Card Block behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.cardBlock = {
    attach: function (context, settings) {
      // Process cards once.
      once('card-block', '.card', context).forEach(function (card) {
        // Add loaded class.
        card.classList.add('card--loaded');

        // Setup clickable card behavior.
        if (card.classList.contains('card--clickable')) {
          setupClickableCard(card);
        }

        // Setup image lazy loading enhancement.
        var image = card.querySelector('.card__image');
        if (image) {
          setupImageLoading(image);
        }

        // Setup entrance animation.
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
          setupIntersectionObserver(card);
        }
      });
    }
  };

  /**
   * Setup clickable card behavior.
   *
   * Makes the entire card clickable while preserving
   * accessibility for the actual link.
   *
   * @param {HTMLElement} card
   *   The card element.
   */
  function setupClickableCard(card) {
    var overlayLink = card.querySelector('.card__overlay-link');
    var headingLink = card.querySelector('.card__heading-link');

    if (!overlayLink || !headingLink) {
      return;
    }

    // Handle keyboard interaction on the card.
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        // Only trigger if the card itself is focused, not a child link.
        if (e.target === card) {
          e.preventDefault();
          headingLink.click();
        }
      }
    });

    // Add visual feedback on card interaction.
    card.addEventListener('mousedown', function () {
      card.classList.add('card--active');
    });

    card.addEventListener('mouseup', function () {
      card.classList.remove('card--active');
    });

    card.addEventListener('mouseleave', function () {
      card.classList.remove('card--active');
    });
  }

  /**
   * Setup image lazy loading enhancement.
   *
   * @param {HTMLImageElement} image
   *   The image element.
   */
  function setupImageLoading(image) {
    // Add loading class.
    image.classList.add('card__image--loading');

    if (image.complete) {
      onImageLoaded(image);
    } else {
      image.addEventListener('load', function () {
        onImageLoaded(image);
      });

      image.addEventListener('error', function () {
        image.classList.remove('card__image--loading');
        image.classList.add('card__image--error');
        console.warn('Card image failed to load:', image.src);
      });
    }
  }

  /**
   * Handle image loaded state.
   *
   * @param {HTMLImageElement} image
   *   The image element.
   */
  function onImageLoaded(image) {
    image.classList.remove('card__image--loading');
    image.classList.add('card__image--loaded');

    // Trigger a subtle fade-in if the card is in view.
    var card = image.closest('.card');
    if (card) {
      card.classList.add('card--image-loaded');
    }
  }

  /**
   * Setup Intersection Observer for entrance animations.
   *
   * @param {HTMLElement} card
   *   The card element.
   */
  function setupIntersectionObserver(card) {
    var observerOptions = {
      root: null,
      rootMargin: '50px',
      threshold: 0.1
    };

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('card--visible');
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    observer.observe(card);
  }

  /**
   * Utility: Equal height cards in a row.
   *
   * Call this function to equalize card heights in a grid.
   *
   * @param {string} containerSelector
   *   Selector for the card container.
   */
  window.equalizeCardHeights = function (containerSelector) {
    var containers = document.querySelectorAll(containerSelector);

    containers.forEach(function (container) {
      var cards = container.querySelectorAll('.card');
      var maxHeight = 0;

      // Reset heights.
      cards.forEach(function (card) {
        card.style.height = 'auto';
      });

      // Find max height.
      cards.forEach(function (card) {
        var height = card.offsetHeight;
        if (height > maxHeight) {
          maxHeight = height;
        }
      });

      // Apply max height.
      cards.forEach(function (card) {
        card.style.height = maxHeight + 'px';
      });
    });
  };

  /**
   * Utility: Reset card heights on resize.
   */
  var resizeTimeout;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function () {
      // Dispatch custom event for height equalization.
      document.dispatchEvent(new CustomEvent('cardsResize'));
    }, 250);
  });

})(Drupal, once);
