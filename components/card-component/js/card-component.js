/**
 * Card Component JavaScript
 *
 * Provides interactive functionality for the card component:
 * - Filtering by layout and style
 * - Clickable card behavior
 * - Entrance animations via Intersection Observer
 * - Image loading states
 */

(function() {
  'use strict';

  /**
   * Card Component Class
   */
  class CardComponent {
    constructor() {
      this.cards = document.querySelectorAll('.card');
      this.layoutSelect = document.getElementById('layout-select');
      this.styleSelect = document.getElementById('style-select');
      this.init();
    }

    /**
     * Initialize the component
     */
    init() {
      this.setupFilters();
      this.setupClickableCards();
      this.setupIntersectionObserver();
      this.setupImageLoading();
    }

    /**
     * Setup filter controls
     */
    setupFilters() {
      if (this.layoutSelect) {
        this.layoutSelect.addEventListener('change', () => this.filterCards());
      }

      if (this.styleSelect) {
        this.styleSelect.addEventListener('change', () => this.filterCards());
      }
    }

    /**
     * Filter cards based on selected layout and style
     */
    filterCards() {
      const selectedLayout = this.layoutSelect ? this.layoutSelect.value : 'all';
      const selectedStyle = this.styleSelect ? this.styleSelect.value : 'all';

      this.cards.forEach(card => {
        const cardLayout = card.dataset.layout || '';
        const cardStyle = card.dataset.style || '';

        const matchesLayout = selectedLayout === 'all' || cardLayout === selectedLayout;
        const matchesStyle = selectedStyle === 'all' || cardStyle === selectedStyle;

        if (matchesLayout && matchesStyle) {
          card.removeAttribute('data-hidden');
          card.style.display = '';
        } else {
          card.setAttribute('data-hidden', 'true');
          card.style.display = 'none';
        }
      });

      // Update section visibility
      this.updateSectionVisibility();
    }

    /**
     * Update section visibility based on visible cards
     */
    updateSectionVisibility() {
      const sections = document.querySelectorAll('.demo-section');

      sections.forEach(section => {
        const visibleCards = section.querySelectorAll('.card:not([data-hidden="true"])');
        const dividerBefore = section.previousElementSibling;

        if (visibleCards.length === 0) {
          section.style.display = 'none';
          if (dividerBefore && dividerBefore.classList.contains('divider')) {
            dividerBefore.style.display = 'none';
          }
        } else {
          section.style.display = '';
          if (dividerBefore && dividerBefore.classList.contains('divider')) {
            dividerBefore.style.display = '';
          }
        }
      });
    }

    /**
     * Setup clickable card behavior
     */
    setupClickableCards() {
      const clickableCards = document.querySelectorAll('.card--clickable');

      clickableCards.forEach(card => {
        const overlayLink = card.querySelector('.card__overlay-link');
        const headingLink = card.querySelector('.card__heading-link');

        if (!overlayLink || !headingLink) return;

        // Handle mouse events for visual feedback
        card.addEventListener('mousedown', () => {
          card.classList.add('card--active');
        });

        card.addEventListener('mouseup', () => {
          card.classList.remove('card--active');
        });

        card.addEventListener('mouseleave', () => {
          card.classList.remove('card--active');
        });

        // Handle keyboard navigation
        card.addEventListener('keydown', (e) => {
          if ((e.key === 'Enter' || e.key === ' ') && e.target === card) {
            e.preventDefault();
            headingLink.click();
          }
        });
      });
    }

    /**
     * Setup Intersection Observer for entrance animations
     */
    setupIntersectionObserver() {
      // Check for reduced motion preference
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        // If reduced motion, just make all cards visible
        this.cards.forEach(card => card.classList.add('card--visible'));
        return;
      }

      const observerOptions = {
        root: null,
        rootMargin: '50px',
        threshold: 0.1
      };

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('card--visible');
            observer.unobserve(entry.target);
          }
        });
      }, observerOptions);

      this.cards.forEach(card => {
        observer.observe(card);
      });
    }

    /**
     * Setup image loading states
     */
    setupImageLoading() {
      const images = document.querySelectorAll('.card__image');

      images.forEach(img => {
        img.classList.add('card__image--loading');

        if (img.complete) {
          this.onImageLoaded(img);
        } else {
          img.addEventListener('load', () => this.onImageLoaded(img));
          img.addEventListener('error', () => this.onImageError(img));
        }
      });
    }

    /**
     * Handle image loaded
     */
    onImageLoaded(img) {
      img.classList.remove('card__image--loading');
      img.classList.add('card__image--loaded');
    }

    /**
     * Handle image error
     */
    onImageError(img) {
      img.classList.remove('card__image--loading');
      img.classList.add('card__image--error');
      console.warn('Card image failed to load:', img.src);
    }
  }

  /**
   * Utility: Create a card element programmatically
   *
   * @param {Object} options - Card configuration
   * @param {string} options.layout - 'vertical' | 'horizontal-left' | 'horizontal-right'
   * @param {string} options.style - 'default' | 'featured' | 'outline' | 'minimal' | 'elevated'
   * @param {string} options.eyebrow - Eyebrow text
   * @param {string} options.heading - Heading text
   * @param {string} options.description - Description text
   * @param {string} options.imageUrl - Image URL
   * @param {string} options.imageAlt - Image alt text
   * @param {string} options.icon - Icon class (e.g., 'fa-solid fa-star')
   * @param {Object} options.link - Link object { text: string, url: string }
   * @param {boolean} options.clickable - Make entire card clickable
   * @returns {HTMLElement} The created card element
   */
  window.createCard = function(options) {
    const {
      layout = 'vertical',
      style = 'default',
      eyebrow = '',
      heading = '',
      description = '',
      imageUrl = '',
      imageAlt = '',
      icon = '',
      link = null,
      clickable = false
    } = options;

    const hasImage = !!imageUrl;
    const hasIcon = !!icon;
    const hasLink = link && link.text && link.url;
    const isClickable = clickable && hasLink && !description;

    // Build classes
    const classes = [
      'card',
      `card--${layout}`,
      `card--${style}`,
      hasImage ? 'card--has-image' : '',
      hasIcon ? 'card--has-icon' : '',
      isClickable ? 'card--clickable' : ''
    ].filter(Boolean).join(' ');

    // Build media HTML
    let mediaHtml = '';
    if (hasImage) {
      mediaHtml = `
        <div class="card__media">
          <img src="${imageUrl}" alt="${imageAlt}" class="card__image" loading="lazy">
        </div>
      `;
    } else if (hasIcon) {
      mediaHtml = `
        <div class="card__media">
          <div class="card__icon-wrapper">
            <i class="${icon}" aria-hidden="true"></i>
          </div>
        </div>
      `;
    }

    // Build content HTML
    let headingHtml = heading;
    if (isClickable && hasLink) {
      headingHtml = `<a href="${link.url}" class="card__heading-link">${heading}</a>`;
    }

    let linkHtml = '';
    if (hasLink && !isClickable) {
      linkHtml = `
        <div class="card__actions">
          <a href="${link.url}" class="card__link">
            ${link.text}
            <svg class="card__link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
          </a>
        </div>
      `;
    }

    let overlayHtml = '';
    if (isClickable && hasLink) {
      overlayHtml = `<a href="${link.url}" class="card__overlay-link" aria-label="${heading}" tabindex="-1"></a>`;
    }

    const contentHtml = `
      <div class="card__content">
        ${eyebrow ? `<span class="card__eyebrow">${eyebrow}</span>` : ''}
        ${heading ? `<h3 class="card__heading">${headingHtml}</h3>` : ''}
        ${description ? `<div class="card__description"><p>${description}</p></div>` : ''}
        ${linkHtml}
      </div>
    `;

    // Build full card HTML based on layout
    let cardHtml = '';
    if (layout === 'horizontal-right') {
      cardHtml = contentHtml + mediaHtml + overlayHtml;
    } else {
      cardHtml = mediaHtml + contentHtml + overlayHtml;
    }

    // Create element
    const card = document.createElement('article');
    card.className = classes;
    card.dataset.layout = layout;
    card.dataset.style = style;
    card.innerHTML = cardHtml;

    return card;
  };

  /**
   * Utility: Equal height cards in a container
   *
   * @param {string} containerSelector - Selector for the card container
   */
  window.equalizeCardHeights = function(containerSelector) {
    const containers = document.querySelectorAll(containerSelector);

    containers.forEach(container => {
      const cards = container.querySelectorAll('.card');
      let maxHeight = 0;

      // Reset heights
      cards.forEach(card => {
        card.style.height = 'auto';
      });

      // Find max height
      cards.forEach(card => {
        const height = card.offsetHeight;
        if (height > maxHeight) {
          maxHeight = height;
        }
      });

      // Apply max height
      cards.forEach(card => {
        card.style.height = maxHeight + 'px';
      });
    });
  };

  /**
   * Utility: Reset card heights on window resize
   */
  let resizeTimeout;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function() {
      document.dispatchEvent(new CustomEvent('cardsResize'));
    }, 250);
  });

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new CardComponent());
  } else {
    new CardComponent();
  }

})();
