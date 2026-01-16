/**
 * Hero Component JavaScript
 *
 * Provides interactive functionality for the hero component:
 * - Layout switching for demo purposes
 * - Entrance animations
 * - Smooth scroll for anchor links
 * - Image lazy loading enhancement
 */

(function() {
  'use strict';

  /**
   * Hero Component Class
   */
  class HeroComponent {
    constructor() {
      this.heroes = document.querySelectorAll('.hero');
      this.layoutButtons = document.querySelectorAll('.layout-btn');
      this.init();
    }

    /**
     * Initialize the component
     */
    init() {
      this.setupLayoutSwitcher();
      this.setupSmoothScroll();
      this.setupAnimations();
      this.setupImageLoading();
    }

    /**
     * Layout Switcher (Demo functionality)
     */
    setupLayoutSwitcher() {
      if (!this.layoutButtons.length) return;

      this.layoutButtons.forEach(button => {
        button.addEventListener('click', (e) => {
          const layout = e.target.dataset.layout;
          this.switchLayout(layout);
          this.updateActiveButton(e.target);
        });
      });
    }

    /**
     * Switch between layouts
     */
    switchLayout(layout) {
      const layoutMap = {
        'full-width-overlay': 'hero-full-width-overlay',
        'text-left-image-right': 'hero-text-left-image-right',
        'image-left-text-right': 'hero-image-left-text-right'
      };

      // Hide all heroes
      this.heroes.forEach(hero => {
        hero.style.display = 'none';
        hero.classList.remove('hero--animated');
      });

      // Show selected layout
      const targetId = layoutMap[layout];
      const targetHero = document.getElementById(targetId);
      if (targetHero) {
        targetHero.style.display = 'block';
        // Trigger animation
        requestAnimationFrame(() => {
          targetHero.classList.add('hero--animated');
        });
      }
    }

    /**
     * Update active button state
     */
    updateActiveButton(activeButton) {
      this.layoutButtons.forEach(btn => btn.classList.remove('active'));
      activeButton.classList.add('active');
    }

    /**
     * Setup smooth scrolling for anchor links
     */
    setupSmoothScroll() {
      const anchorLinks = document.querySelectorAll('.hero__button[href^="#"]');

      anchorLinks.forEach(link => {
        link.addEventListener('click', (e) => {
          const targetId = link.getAttribute('href').substring(1);
          if (!targetId) return;

          const targetElement = document.getElementById(targetId);
          if (targetElement) {
            e.preventDefault();
            targetElement.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });

            // Update URL without jumping
            history.pushState(null, null, `#${targetId}`);
          }
        });
      });
    }

    /**
     * Setup entrance animations using Intersection Observer
     */
    setupAnimations() {
      // Check for reduced motion preference
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
      }

      const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
      };

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('hero--animated');
            observer.unobserve(entry.target);
          }
        });
      }, observerOptions);

      // Only observe heroes that are visible
      this.heroes.forEach(hero => {
        if (hero.style.display !== 'none') {
          observer.observe(hero);
        }
      });
    }

    /**
     * Enhanced image loading
     */
    setupImageLoading() {
      const heroImages = document.querySelectorAll('.hero__image');

      heroImages.forEach(img => {
        // Add loading class
        img.classList.add('hero__image--loading');

        if (img.complete) {
          img.classList.remove('hero__image--loading');
          img.classList.add('hero__image--loaded');
        } else {
          img.addEventListener('load', () => {
            img.classList.remove('hero__image--loading');
            img.classList.add('hero__image--loaded');
          });

          img.addEventListener('error', () => {
            img.classList.remove('hero__image--loading');
            img.classList.add('hero__image--error');
            console.warn('Hero image failed to load:', img.src);
          });
        }
      });

      // Preload background images for full-width heroes
      const fullWidthHeroes = document.querySelectorAll('.hero__background');
      fullWidthHeroes.forEach(hero => {
        const bgImage = hero.style.backgroundImage;
        if (bgImage) {
          const imageUrl = bgImage.replace(/url\(['"]?([^'"]+)['"]?\)/i, '$1');
          const preloadImg = new Image();
          preloadImg.onload = () => {
            hero.classList.add('hero__background--loaded');
          };
          preloadImg.src = imageUrl;
        }
      });
    }
  }

  /**
   * Utility: Create Hero Component programmatically
   *
   * @param {Object} options - Configuration options
   * @param {string} options.layout - 'full-width-overlay' | 'text-left-image-right' | 'image-left-text-right'
   * @param {string} options.heading - Heading text
   * @param {string} options.subheading - Subheading text
   * @param {string} options.imageUrl - Image URL
   * @param {string} options.imageAlt - Image alt text
   * @param {Object} options.button1 - Primary button { text: string, url: string }
   * @param {Object} options.button2 - Secondary button { text: string, url: string }
   * @param {HTMLElement} options.container - Container element to append to
   * @returns {HTMLElement} The created hero element
   */
  function createHeroComponent(options) {
    const {
      layout = 'full-width-overlay',
      heading = '',
      subheading = '',
      imageUrl = '',
      imageAlt = '',
      button1 = null,
      button2 = null,
      container = document.body
    } = options;

    const hero = document.createElement('section');
    hero.className = `hero hero--${layout}`;

    let buttonsHtml = '';
    if (button1 || button2) {
      buttonsHtml = '<div class="hero__buttons">';
      if (button1) {
        buttonsHtml += `<a href="${button1.url}" class="hero__button hero__button--primary">${button1.text}</a>`;
      }
      if (button2) {
        buttonsHtml += `<a href="${button2.url}" class="hero__button hero__button--secondary">${button2.text}</a>`;
      }
      buttonsHtml += '</div>';
    }

    const contentHtml = `
      ${heading ? `<h1 class="hero__heading">${heading}</h1>` : ''}
      ${subheading ? `<p class="hero__subheading">${subheading}</p>` : ''}
      ${buttonsHtml}
    `;

    if (layout === 'full-width-overlay') {
      hero.innerHTML = `
        <div class="hero__background" style="background-image: url('${imageUrl}');">
          <div class="hero__overlay"></div>
          <div class="hero__container">
            <div class="hero__content hero__content--centered">
              ${contentHtml}
            </div>
          </div>
        </div>
      `;
    } else if (layout === 'text-left-image-right') {
      hero.innerHTML = `
        <div class="hero__container">
          <div class="hero__row">
            <div class="hero__column hero__column--text">
              <div class="hero__content">
                ${contentHtml}
              </div>
            </div>
            <div class="hero__column hero__column--image">
              <img src="${imageUrl}" alt="${imageAlt}" class="hero__image" loading="lazy">
            </div>
          </div>
        </div>
      `;
    } else if (layout === 'image-left-text-right') {
      hero.innerHTML = `
        <div class="hero__container">
          <div class="hero__row">
            <div class="hero__column hero__column--image">
              <img src="${imageUrl}" alt="${imageAlt}" class="hero__image" loading="lazy">
            </div>
            <div class="hero__column hero__column--text">
              <div class="hero__content">
                ${contentHtml}
              </div>
            </div>
          </div>
        </div>
      `;
    }

    container.appendChild(hero);
    return hero;
  }

  // Expose utilities to global scope
  window.HeroComponent = HeroComponent;
  window.createHeroComponent = createHeroComponent;

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new HeroComponent());
  } else {
    new HeroComponent();
  }

})();
