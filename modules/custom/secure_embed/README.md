# Secure Embed Module

A Drupal module that provides a secure way to embed external content (iframes) via CKEditor with domain whitelisting and sanitized parameters.

## Overview

This module prevents users from adding raw iframe/embed code directly into CKEditor, which can be a security risk (XSS attacks, malicious content). Instead, it provides:

1. A dedicated CKEditor 5 toolbar button for adding embeds
2. A dialog form where users input embed parameters (URL, dimensions, etc.)
3. Server-side validation against a configurable domain whitelist
4. Sanitized iframe generation with security attributes (sandbox, CSP)
5. Responsive embed support with multiple aspect ratios

## Security Features

### Domain Whitelisting
- Only URLs from approved domains can be embedded
- Wildcard subdomain support (e.g., `*.youtube.com`)
- Configurable via admin interface

### Iframe Sandbox
- Applies `sandbox` attribute to restrict iframe capabilities
- Configurable sandbox permissions (scripts, forms, popups, etc.)
- Prevents iframe from accessing parent page by default

### Allow Attribute (Permissions Policy)
- Controls what features the iframe can access
- Configurable permissions (camera, microphone, fullscreen, etc.)

### Parameter Sanitization
- All user input is sanitized before storage and output
- URL validation with format and protocol checks
- HTML escaping for all attributes

## Requirements

- Drupal 9, 10, or 11
- CKEditor 5 module (core)
- Filter module (core)

## Installation

1. Copy the `secure_embed` folder to your `modules/custom` directory.

2. Enable the module:
   ```bash
   drush en secure_embed
   ```

3. Clear caches:
   ```bash
   drush cr
   ```

4. Configure the module at `/admin/config/content/secure-embed`.

5. Add the filter to your text format:
   - Go to `/admin/config/content/formats`
   - Edit your text format (e.g., "Full HTML")
   - Enable "Secure Embed Filter"
   - Add "Secure Embed" to the CKEditor toolbar

## Configuration

### Admin Settings

Navigate to **Configuration > Content authoring > Secure Embed Settings**.

#### Allowed Domains
Enter one domain per line. Use wildcards for subdomains:

```
youtube.com
*.youtube.com
youtu.be
vimeo.com
player.vimeo.com
google.com
*.google.com
maps.google.com
spotify.com
open.spotify.com
soundcloud.com
w.soundcloud.com
```

#### Default Settings
- **Default Width**: Default width for embeds (e.g., `100%`, `640px`)
- **Default Height**: Default height for embeds (e.g., `400px`)
- **Default Aspect Ratio**: Default ratio for responsive embeds
- **Responsive by default**: Enable responsive mode by default
- **Lazy loading by default**: Enable lazy loading for performance

#### Security Settings
- **Enable iframe sandbox**: Apply sandbox restrictions
- **Sandbox Permissions**: Select which sandbox permissions to grant
- **Allow Permissions Policy**: Select which features to allow

## Usage

### Adding an Embed

1. In CKEditor, click the "Secure Embed" toolbar button
2. Enter the embed URL (e.g., YouTube video URL)
3. Optionally add a title for accessibility
4. Configure display options (responsive, dimensions)
5. Click "Insert Embed"

### Editing an Embed

1. Click on the embed placeholder in CKEditor
2. Click the "Secure Embed" button again
3. Modify settings and save

### Supported Platforms (Default)

The default configuration includes common embed platforms:
- YouTube / YouTube Shorts
- Vimeo
- Google Maps
- Google Drive / Docs
- Spotify
- SoundCloud
- Loom
- Wistia
- Microsoft Office embeds
- Canva

## File Structure

```
secure_embed/
├── config/
│   ├── install/
│   │   └── secure_embed.settings.yml
│   └── schema/
│       └── secure_embed.schema.yml
├── css/
│   ├── secure-embed.css
│   ├── secure-embed.admin.css
│   └── secure-embed.dialog.css
├── js/
│   ├── ckeditor5_plugins/
│   │   └── secureEmbed/
│   │       └── build/
│   │           └── secureEmbed.js
│   ├── secure-embed.js
│   └── secure-embed.dialog.js
├── src/
│   ├── Form/
│   │   ├── SecureEmbedDialogForm.php
│   │   └── SecureEmbedSettingsForm.php
│   └── Plugin/
│       └── Filter/
│           └── SecureEmbedFilter.php
├── templates/
│   └── secure-embed.html.twig
├── secure_embed.ckeditor5.yml
├── secure_embed.info.yml
├── secure_embed.libraries.yml
├── secure_embed.links.menu.yml
├── secure_embed.module
├── secure_embed.permissions.yml
├── secure_embed.routing.yml
└── README.md
```

## Permissions

- **Administer Secure Embed**: Access the admin settings form

## API Functions

### secure_embed_validate_url($url)
Validates a URL against the domain whitelist.

```php
if (secure_embed_validate_url('https://www.youtube.com/embed/VIDEO_ID')) {
  // URL is allowed
}
```

### secure_embed_sanitize_params($params)
Sanitizes embed parameters for safe storage and output.

```php
$clean_params = secure_embed_sanitize_params([
  'url' => $url,
  'title' => $title,
  'width' => '100%',
]);
```

### secure_embed_encode_data($data)
Encodes embed data to base64 for storage in content.

```php
$encoded = secure_embed_encode_data($embed_data);
```

### secure_embed_decode_data($encoded)
Decodes embed data from base64.

```php
$data = secure_embed_decode_data($encoded_string);
```

## Theming

### CSS Variables

Override in your theme:

```css
:root {
  --secure-embed-bg: #000;
  --secure-embed-border-radius: 8px;
  --secure-embed-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
```

### Template Override

Copy `secure-embed.html.twig` to your theme's templates directory to customize the output.

## Troubleshooting

### Embed not showing
1. Verify the domain is in the allowed list
2. Check that the filter is enabled on the text format
3. Clear caches

### CKEditor button not appearing
1. Ensure the module is enabled
2. Check that the text format has the CKEditor plugin enabled
3. Clear caches

### Iframe blocked by browser
1. Check browser console for CSP errors
2. Ensure the source site allows embedding
3. Verify HTTPS is used

## Security Considerations

1. **Always use HTTPS** for embed URLs
2. **Limit allowed domains** to only necessary platforms
3. **Keep sandbox enabled** unless absolutely necessary
4. **Review permissions** regularly
5. **Update the module** when security patches are released

## Changelog

### 1.0.0
- Initial release
- CKEditor 5 integration
- Domain whitelisting
- Responsive embed support
- Sandbox and permissions policy support

## License

GPL-2.0+
