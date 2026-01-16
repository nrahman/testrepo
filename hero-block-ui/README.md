# Hero Block (UI-Based Custom Block)

A Drupal Hero Block created entirely through the UI (no custom module required). This package includes configuration files that can be imported via Drupal's config system, plus theme templates and assets.

## Features

- **3 Layout Options:**
  1. **Full Width Overlay** - Hero banner image with centered text overlay
  2. **Text Left / Image Right** - Split layout with content on the left
  3. **Image Left / Text Right** - Split layout with image on the left

- **Fields:**
  - Layout (select list)
  - Heading (text, required)
  - Subheading (long text, optional)
  - Hero Image (image upload, required)
  - Primary Button (link field, optional)
  - Secondary Button (link field, optional)

## Requirements

- Drupal 9, 10, or 11
- Block Content module (core)
- Link module (core)
- Image module (core)
- Options module (core)

## Installation

### Step 1: Import Configuration Files

You can import the configuration files using one of these methods:

#### Option A: Using Drush (Recommended)

```bash
# Copy config files to your config sync directory
cp hero-block-ui/config/*.yml /path/to/your/config/sync/

# Import the configuration
drush config:import --partial --source=/path/to/hero-block-ui/config

# Clear cache
drush cr
```

#### Option B: Using Config Import Module

1. Install the [Config Import](https://www.drupal.org/project/config_import) module
2. Go to **Admin > Configuration > Development > Config Import**
3. Upload each YAML file from the `config/` folder

#### Option C: Manual UI Setup

If you prefer to create the block type manually through the UI:

1. Go to **Structure > Block layout > Custom block library > Block types**
2. Click "Add custom block type"
3. Enter:
   - Name: `Hero Block`
   - Machine name: `hero_block`
   - Description: `A hero block with 3 layout options`
4. Save
5. Add the following fields to the Hero Block type:

| Field Label | Machine Name | Field Type | Required |
|-------------|--------------|------------|----------|
| Layout | field_hero_layout | List (text) | Yes |
| Heading | field_hero_heading | Text (plain) | Yes |
| Subheading | field_hero_subheading | Text (plain, long) | No |
| Hero Image | field_hero_image | Image | Yes |
| Primary Button | field_hero_button_1 | Link | No |
| Secondary Button | field_hero_button_2 | Link | No |

For the Layout field, add these allowed values:
- `full_width_overlay` | Full Width Overlay
- `text_left_image_right` | Text Left / Image Right
- `image_left_text_right` | Image Left / Text Right

### Step 2: Add Templates to Your Theme

Copy the template files to your theme's templates directory:

```bash
cp hero-block-ui/templates/*.twig /path/to/your/theme/templates/block/
```

Your theme structure should look like:
```
mytheme/
├── templates/
│   └── block/
│       ├── block--block-content--hero-block.html.twig
│       └── block-content--hero-block.html.twig
```

### Step 3: Add CSS/JS to Your Theme

#### Option A: Add to Theme Library

1. Copy CSS and JS files:
```bash
cp hero-block-ui/css/hero-block.css /path/to/your/theme/css/
cp hero-block-ui/js/hero-block.js /path/to/your/theme/js/
```

2. Add to your theme's `*.libraries.yml`:

```yaml
# mytheme.libraries.yml
hero-block:
  version: 1.x
  css:
    component:
      css/hero-block.css: {}
  js:
    js/hero-block.js: {}
  dependencies:
    - core/drupal
    - core/once
```

3. Attach the library in your theme's `*.theme` file:

```php
/**
 * Implements hook_preprocess_block().
 */
function mytheme_preprocess_block(&$variables) {
  // Attach hero block library.
  if (isset($variables['content']['#block_content'])) {
    $block_content = $variables['content']['#block_content'];
    if ($block_content->bundle() == 'hero_block') {
      $variables['#attached']['library'][] = 'mytheme/hero-block';
    }
  }
}
```

#### Option B: Add to Global Styles

Simply include the CSS in your theme's global stylesheet:

```css
/* In your theme's main CSS file */
@import 'hero-block.css';
```

### Step 4: Clear Cache

```bash
drush cr
```

## Usage

### Creating a Hero Block

1. Go to **Structure > Block layout > Custom block library**
2. Click "Add custom block"
3. Select "Hero Block"
4. Fill in the fields:
   - **Block description**: Internal name (e.g., "Homepage Hero")
   - **Layout**: Select your preferred layout
   - **Heading**: Enter the main title
   - **Subheading**: Enter description text (optional)
   - **Hero Image**: Upload an image
   - **Primary Button**: Enter button text and URL (optional)
   - **Secondary Button**: Enter button text and URL (optional)
5. Save

### Placing the Block

1. Go to **Structure > Block layout**
2. Find your desired region (e.g., "Content" or "Header")
3. Click "Place block"
4. Search for your hero block by its description
5. Configure visibility settings if needed
6. Save

## File Structure

```
hero-block-ui/
├── config/                         # Drupal configuration files
│   ├── block_content.type.hero_block.yml
│   ├── field.storage.block_content.field_hero_layout.yml
│   ├── field.storage.block_content.field_hero_heading.yml
│   ├── field.storage.block_content.field_hero_subheading.yml
│   ├── field.storage.block_content.field_hero_image.yml
│   ├── field.storage.block_content.field_hero_button_1.yml
│   ├── field.storage.block_content.field_hero_button_2.yml
│   ├── field.field.block_content.hero_block.field_hero_layout.yml
│   ├── field.field.block_content.hero_block.field_hero_heading.yml
│   ├── field.field.block_content.hero_block.field_hero_subheading.yml
│   ├── field.field.block_content.hero_block.field_hero_image.yml
│   ├── field.field.block_content.hero_block.field_hero_button_1.yml
│   ├── field.field.block_content.hero_block.field_hero_button_2.yml
│   ├── core.entity_form_display.block_content.hero_block.default.yml
│   └── core.entity_view_display.block_content.hero_block.default.yml
├── templates/                      # Twig templates for theme
│   ├── block--block-content--hero-block.html.twig
│   └── block-content--hero-block.html.twig
├── css/
│   └── hero-block.css              # Component styles
├── js/
│   └── hero-block.js               # JavaScript behaviors
└── README.md
```

## Customization

### CSS Variables

Override these in your theme's CSS:

```css
:root {
  --hero-primary: #your-brand-color;
  --hero-primary-hover: #your-hover-color;
  --hero-overlay: rgba(0, 0, 0, 0.6);
  --hero-max-width: 1400px;
  --hero-border-radius: 12px;
}
```

### Template Customization

The templates use Twig to extract field values. You can modify the markup in your theme's copy of the templates.

### Adding Theme Suggestions

To add more template suggestions, add this to your theme's `.theme` file:

```php
/**
 * Implements hook_theme_suggestions_block_content_alter().
 */
function mytheme_theme_suggestions_block_content_alter(array &$suggestions, array $variables) {
  $block_content = $variables['elements']['#block_content'];
  if ($block_content->bundle() == 'hero_block') {
    // Add layout-specific suggestion.
    if ($block_content->hasField('field_hero_layout')) {
      $layout = $block_content->get('field_hero_layout')->value;
      $suggestions[] = 'block_content__hero_block__' . $layout;
    }
  }
}
```

## Troubleshooting

### Templates Not Being Used

1. Clear cache: `drush cr`
2. Verify template names match exactly (hyphens, not underscores)
3. Check template location in your theme's `templates/` or `templates/block/` folder
4. Enable Twig debugging in `settings.local.php`:
   ```php
   $settings['twig_debug'] = TRUE;
   ```

### Fields Not Rendering

1. Check field permissions
2. Verify field display settings at **Structure > Block types > Hero Block > Manage display**
3. Ensure fields are not set to "Hidden"

### Styles Not Loading

1. Verify library is attached (check page source)
2. Check browser console for errors
3. Verify file paths in `*.libraries.yml`

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
