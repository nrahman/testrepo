# Hero Block Module

A Drupal custom block module providing a flexible hero component with 3 layout options.

## Features

- **3 Layout Options:**
  1. **Full Width Overlay** - Hero banner image with centered text overlay
  2. **Text Left / Image Right** - Split layout with content on the left
  3. **Image Left / Text Right** - Split layout with image on the left

- **Configurable Fields:**
  - Layout selection (dropdown)
  - Heading (required)
  - Subheading (optional textarea)
  - Hero Image (managed file upload)
  - Image Alt Text (for accessibility)
  - Primary Button (title + URL)
  - Secondary Button (title + URL)

## Requirements

- Drupal 9, 10, or 11
- Block module (core)
- File module (core)
- Link module (core)

## Installation

1. Copy the `hero_block` folder to `modules/custom/`
2. Enable the module:
   ```bash
   drush en hero_block
   ```
3. Clear caches:
   ```bash
   drush cr
   ```

## Usage

1. Go to **Structure > Block layout**
2. Click "Place block" in your desired region
3. Search for "Hero Block" and click "Place block"
4. Configure the block:
   - Select your desired layout
   - Enter heading and subheading
   - Upload a hero image
   - Add button links (optional)
5. Save the block

## Theming

### Template Override

Copy `templates/hero-block.html.twig` to your theme's templates folder to customize:

```
mytheme/templates/block/hero-block.html.twig
```

### Layout-specific Templates

You can create layout-specific templates:
- `hero-block--full-width-overlay.html.twig`
- `hero-block--text-left-image-right.html.twig`
- `hero-block--image-left-text-right.html.twig`

### CSS Variables

Override these CSS custom properties in your theme:

```css
:root {
  --hero-primary: #2563eb;
  --hero-primary-hover: #1d4ed8;
  --hero-overlay: rgba(0, 0, 0, 0.55);
  --hero-max-width: 1280px;
  --hero-border-radius: 8px;
}
```

## File Structure

```
hero_block/
├── css/
│   └── hero-block.css           # Component styles
├── js/
│   └── hero-block.js            # JavaScript behaviors
├── src/
│   └── Plugin/
│       └── Block/
│           └── HeroBlock.php    # Block plugin class
├── templates/
│   └── hero-block.html.twig     # Twig template
├── hero_block.info.yml          # Module definition
├── hero_block.libraries.yml     # CSS/JS libraries
├── hero_block.module            # Theme hooks
└── README.md
```

## Available Twig Variables

| Variable | Type | Description |
|----------|------|-------------|
| `hero_layout` | string | Layout key (full_width_overlay, text_left_image_right, image_left_text_right) |
| `hero_layout_class` | string | CSS class for the layout |
| `hero_heading` | string | The heading text |
| `hero_subheading` | string | The subheading text |
| `hero_image_url` | string | URL to the uploaded image |
| `hero_image_alt` | string | Alt text for the image |
| `hero_button_1` | array | Primary button with 'title' and 'url' keys |
| `hero_button_2` | array | Secondary button with 'title' and 'url' keys |

## Accessibility

- Semantic HTML structure with proper heading hierarchy
- Alt text support for images
- Focus states for interactive elements
- Reduced motion support
- High contrast mode support
- Print styles included

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- IE11 (basic support, no animations)
