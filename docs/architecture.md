# Plugin Architecture

## File Structure

```
fluid-scale/
├── fluid-scale.php              # Plugin header, bootstrap, constants
├── uninstall.php                # Cleanup on plugin deletion
├── readme.txt                   # WordPress.org readme
├── composer.json
├── .gitignore
├── CLAUDE.md                    # Claude Code bootstrap instructions
│
├── includes/
│   ├── class-generator.php      # Pure PHP: takes params, returns CSS string (no WP deps)
│   ├── class-settings.php       # get/update/sanitize wp_options settings
│   ├── class-file-writer.php    # Writes CSS to uploads/fluid-scale/fluid-scale.css
│   ├── class-enqueue.php        # wp_enqueue_style for the generated file
│   ├── class-builder-detector.php  # Detects active builders (Divi 5, Bricks)
│   └── class-builder-mappings.php  # Returns CSS mapping blocks per builder
│
├── admin/
│   ├── class-admin-page.php     # Settings page registration and rendering
│   └── views/
│       └── settings-page.php    # HTML template for the settings page
│
├── assets/
│   ├── css/
│   │   └── admin.css            # Admin page styles
│   └── js/
│       └── admin.js             # Live preview JS (recalculates clamp() in browser)
│
├── languages/                   # .pot and translation files
│
└── docs/                        # Reference documentation (loaded by CLAUDE.md)
    ├── utopia-math.md
    ├── wordpress-standards.md
    ├── builder-mappings.md
    ├── architecture.md          # This file
    └── decisions.md             # Running log of non-obvious decisions
```

---

## Separation of Concerns

### `class-generator.php` — Pure PHP, no WordPress
- Accepts a settings array, returns a CSS string
- No `get_option()`, no `wp_upload_dir()`, no hooks
- Independently unit-testable
- Generates: type scale, space scale, grid, semantic aliases, utility classes
- Input validation is the caller's responsibility (generator trusts its inputs)

### `class-settings.php` — WordPress settings layer
- `get_settings()` → returns sanitized array with defaults merged
- `save_settings( array $raw )` → sanitizes each field by type, calls `update_option`
- `get_defaults()` → returns default parameter set
- Fires `do_action( 'fluid_scale_settings_saved', $settings )` after successful save

### `class-file-writer.php` — Disk I/O
- `write( string $css )` → atomic write (tmp → rename)
- `get_file_path()` / `get_file_url()` → paths derived from `wp_upload_dir()`
- `delete()` → used by uninstall
- Returns `WP_Error` on failure, logs via `error_log()`

### `class-enqueue.php` — Front-end output
- Hooks to `wp_enqueue_scripts` at priority 1
- Enqueues `uploads/fluid-scale/fluid-scale.css` with `last_generated` as version
- Falls back gracefully if file doesn't exist (no error, no output)

### `class-builder-detector.php` — Detection only
- `get_active_builders()` → returns array of detected builder slugs
- Each detection is a separate method, no side effects

### `class-builder-mappings.php` — Mapping CSS
- `get_mapping_css( string $builder )` → returns CSS string for that builder's `:root` block
- Data-only; no file I/O, no WP calls

### `class-admin-page.php` — Admin UI
- Registers settings page under Settings menu
- Enqueues admin assets only on `settings_page_fluid-scale`
- Handles form submission: verify nonce → check capability → call `class-settings` → regenerate CSS

---

## Data Flow

```
User saves settings form
  → admin-page.php: verify nonce, check capability
  → class-settings.php: sanitize, update_option
  → fluid_scale_settings_saved action fires
  → class-generator.php: generate CSS string from saved settings
  → class-builder-mappings.php: append mapping blocks if enabled
  → class-file-writer.php: atomic write to uploads/fluid-scale/fluid-scale.css
  → last_generated timestamp saved

Front end page load
  → class-enqueue.php: wp_enqueue_style with last_generated as cache-bust version
  → Browser loads fluid-scale.css (static file, fully cacheable)
  → :root variables available to all theme and builder CSS
```

---

## Admin Preview Data Flow

```
User changes a parameter in the settings form (on change/blur)
  → admin.js: reads all current form values
  → admin.js: recalculates clamp() values using same formula as PHP generator
  → admin.js: updates preview DOM (type specimen + space visualization)
  → No server round-trip required
```

The JS preview duplicates the generator math in JavaScript. This is intentional — it avoids AJAX latency and works offline. The JS and PHP implementations must produce identical results. The PHP generator is the authoritative implementation; the JS is the preview mirror.

---

## Key Constants (defined in fluid-scale.php)

```php
define( 'FLUID_SCALE_VERSION', '1.0.0' );
define( 'FLUID_SCALE_FILE',    __FILE__ );
define( 'FLUID_SCALE_DIR',     plugin_dir_path( __FILE__ ) );
define( 'FLUID_SCALE_URL',     plugin_dir_url( __FILE__ ) );
```

---

## Enqueue Priority Rationale

`wp_enqueue_scripts` at priority **1** (default is 10). This ensures the generated stylesheet is in the `<head>` before any theme or builder enqueues their own stylesheets, so `:root` custom properties are defined when theme/builder CSS references them. The plugin does not modify theme or builder CSS — it only adds to `:root`. CSS custom property inheritance means order matters for definition, not for specificity.
