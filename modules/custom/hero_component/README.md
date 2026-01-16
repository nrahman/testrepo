# Hero Component Module

A Drupal paragraph type module providing a versatile hero component with 3 layout options.

## Features

- **3 Layout Options:**
  1. **Full Width Overlay** - Hero banner image with text overlayed on top
  2. **Text Left / Image Right** - Split layout with content on the left
  3. **Image Left / Text Right** - Split layout with image on the left

- **Common Fields (all layouts):**
  - Heading (required)
  - Subheading (optional)
  - Hero Image (required)
  - Primary Button (optional link)
  - Secondary Button (optional link)

## Requirements

- Drupal 9, 10, or 11
- Paragraphs module
- Link module (core)
- Image module (core)

## Installation

1. Copy the `hero_component` folder to `modules/custom/`
2. Enable the module: `drush en hero_component`
3. Clear caches: `drush cr`

## Usage

1. Add a Paragraphs field to your content type
2. Allow the "Hero Component" paragraph type
3. When creating content, add a Hero Component and select your layout option
4. Fill in the heading, subheading, image, and buttons

## Customization

### CSS Variables

Override these CSS custom properties in your theme:

```css
:root {
  --hero-primary-color: #0066cc;
  --hero-primary-hover: #0052a3;
  --hero-overlay-color: rgba(0, 0, 0, 0.5);
  --hero-max-width: 1200px;
}
```

### Template Override

Copy `templates/paragraph--hero-component.html.twig` to your theme's templates folder to customize the markup.

## File Structure

```
hero_component/
├── config/install/           # Configuration YAML files
├── css/
│   └── hero-component.css    # Component styles
├── js/
│   └── hero-component.js     # JavaScript behaviors
├── templates/
│   └── paragraph--hero-component.html.twig
├── hero_component.info.yml   # Module definition
├── hero_component.libraries.yml
├── hero_component.module     # PHP hooks
└── README.md
```
