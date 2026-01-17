# Card Block (UI-Based Custom Block)

A versatile Drupal Card Block created through the UI with importable configuration files. No custom module required.

## Features

### Layout Options
| Layout | Description |
|--------|-------------|
| **Vertical** | Image on top, content below (default) |
| **Horizontal Left** | Image on left, content on right |
| **Horizontal Right** | Image on right, content on left |

### Style Variants
| Style | Description |
|-------|-------------|
| **Default** | Standard card with subtle shadow |
| **Featured** | Highlighted with accent color top border |
| **Outline** | Bordered without shadow |
| **Minimal** | No shadow or border |
| **Elevated** | Extra shadow for depth |

### Fields
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Layout | Select | Yes | Card layout orientation |
| Style | Select | Yes | Visual style variant |
| Eyebrow | Text | No | Small text above heading (category, tag) |
| Heading | Text | Yes | Main card title |
| Description | Long Text | No | Body text/description |
| Image | Image | No | Card image (16:9 recommended) |
| Link | Link | No | Call-to-action button |
| Icon | Text | No | Icon class (Font Awesome, etc.) |

## Requirements

- Drupal 9, 10, or 11
- Block Content module (core)
- Link module (core)
- Image module (core)
- Options module (core)
- Text module (core)

## Installation

### Step 1: Import Configuration Files

#### Option A: Using Drush (Recommended)

```bash
# Import configuration
drush config:import --partial --source=/path/to/card-block-ui/config

# Clear cache
drush cr
```

#### Option B: Manual UI Setup

1. Go to **Structure > Block layout > Custom block library > Block types**
2. Click "Add custom block type"
3. Create block type with machine name `card_block`
4. Add the fields as listed above

### Step 2: Add Templates to Your Theme

```bash
cp card-block-ui/templates/*.twig /path/to/your/theme/templates/block/
```

### Step 3: Add CSS/JS to Your Theme

1. Copy files:
```bash
cp card-block-ui/css/card-block.css /path/to/your/theme/css/
cp card-block-ui/js/card-block.js /path/to/your/theme/js/
```

2. Add to `mytheme.libraries.yml`:
```yaml
card-block:
  version: 1.x
  css:
    component:
      css/card-block.css: {}
  js:
    js/card-block.js: {}
  dependencies:
    - core/drupal
    - core/once
```

3. Attach library in `mytheme.theme`:
```php
/**
 * Implements hook_preprocess_block().
 */
function mytheme_preprocess_block(&$variables) {
  if (isset($variables['content']['#block_content'])) {
    $bundle = $variables['content']['#block_content']->bundle();
    if ($bundle == 'card_block') {
      $variables['#attached']['library'][] = 'mytheme/card-block';
    }
  }
}
```

### Step 4: Clear Cache

```bash
drush cr
```

## Usage

### Creating a Card Block

1. Go to **Structure > Block layout > Custom block library**
2. Click "Add custom block"
3. Select "Card Block"
4. Fill in the fields
5. Save

### Placing the Block

1. Go to **Structure > Block layout** or use **Layout Builder**
2. Place the card block in your desired region
3. Configure visibility settings
4. Save

### Using with Layout Builder

The card block works seamlessly with Layout Builder. Templates include proper `attributes` output to ensure contextual links work correctly.

## File Structure

```
card-block-ui/
├── config/
│   ├── block_content.type.card_block.yml
│   ├── field.storage.block_content.field_card_*.yml (7 files)
│   ├── field.field.block_content.card_block.field_card_*.yml (7 files)
│   ├── core.entity_form_display.block_content.card_block.default.yml
│   └── core.entity_view_display.block_content.card_block.default.yml
├── templates/
│   ├── block--block-content--card-block.html.twig
│   └── block-content--card-block.html.twig
├── css/
│   └── card-block.css
├── js/
│   └── card-block.js
└── README.md
```

## Customization

### CSS Variables

Override in your theme:

```css
:root {
  /* Colors */
  --card-bg: #ffffff;
  --card-text: #1f2937;
  --card-accent: #2563eb;
  --card-accent-hover: #1d4ed8;

  /* Spacing */
  --card-padding: 1.5rem;
  --card-radius: 12px;

  /* Effects */
  --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  --card-shadow-hover: 0 10px 25px rgba(0, 0, 0, 0.1);

  /* Image */
  --card-image-ratio: 56.25%; /* 16:9 */

  /* Icon */
  --card-icon-size: 3rem;
  --card-icon-color: #2563eb;
}
```

### Using Icons

The card supports icon classes from any icon library:

**Font Awesome:**
```
fa-solid fa-star
fa-regular fa-heart
fa-brands fa-drupal
```

**Bootstrap Icons:**
```
bi bi-star-fill
bi bi-heart
```

**Material Icons:**
```
material-icons (with icon name in text)
```

Make sure to include the icon library CSS in your theme.

### Card Grid Layout

Use the included grid helper classes:

```html
<div class="card-grid">
  <!-- Cards auto-fill, min 300px -->
</div>

<div class="card-grid--3">
  <!-- Fixed 3-column grid -->
</div>
```

## Twig Variables

| Variable | Description |
|----------|-------------|
| `layout` | Layout value (vertical, horizontal_left, horizontal_right) |
| `style` | Style value (default, featured, outline, minimal, elevated) |
| `eyebrow` | Eyebrow text |
| `heading` | Heading text |
| `description` | Description HTML |
| `image_url` | Image URL |
| `image_alt` | Image alt text |
| `link_title` | Link text |
| `link_url` | Link URL |
| `icon` | Icon class |
| `has_image` | Boolean - has image |
| `has_link` | Boolean - has link |
| `has_icon` | Boolean - has icon |

## JavaScript API

### Equal Height Cards

```javascript
// Equalize card heights in a container
equalizeCardHeights('.my-card-container');
```

### Events

```javascript
// Listen for resize event
document.addEventListener('cardsResize', function() {
  equalizeCardHeights('.my-card-container');
});
```

## Accessibility

- Semantic HTML with `<article>` element
- Proper heading hierarchy
- Alt text required for images
- Focus states for interactive elements
- Keyboard navigation support
- Reduced motion support
- High contrast mode support
- Screen reader friendly

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Examples

### Basic Card (Vertical)
- Layout: Vertical
- Style: Default
- Heading: "Card Title"
- Description: "Card description text..."
- Image: Upload image
- Link: "Read More" → /node/123

### Featured Card
- Layout: Vertical
- Style: Featured
- Eyebrow: "Featured"
- Heading: "Important Announcement"
- Description: "..."

### Icon Card (No Image)
- Layout: Vertical
- Style: Outline
- Icon: "fa-solid fa-lightbulb"
- Heading: "Quick Tip"
- Description: "..."

### Horizontal Card
- Layout: Horizontal Left
- Style: Default
- Image: Upload image
- Heading: "Article Title"
- Description: "..."
- Link: "Continue Reading"
