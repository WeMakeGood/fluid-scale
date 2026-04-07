# WordPress Plugin Standards Reference

## Sources
- Plugin Developer Handbook: https://developer.wordpress.org/plugins/
- Best Practices: https://developer.wordpress.org/plugins/plugin-basics/best-practices/
- Security: https://developer.wordpress.org/apis/security/
- i18n: https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/
- wp.org Guidelines: https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/

---

## Namespacing

All PHP must use the `FluidScale` namespace. No global functions or classes.

```php
namespace FluidScale;
```

Every file begins with:
```php
<?php
namespace FluidScale;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
```

---

## Plugin Header (fluid-scale.php)

Required fields for wp.org submission:
```php
/**
 * Plugin Name:       Fluid Scale
 * Plugin URI:        https://github.com/WeMakeGood/fluid-scale
 * Description:       Injects a mathematically coherent fluid typographic and spacing scale as CSS custom properties, available to any theme or page builder.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Make Good
 * Author URI:        https://wemakegood.org
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fluid-scale
 * Domain Path:       /languages
 */
```

**Minimum versions rationale:**
- WordPress 6.0: released May 2022, widely adopted, provides stable Settings API and modern hooks
- PHP 8.0: released Nov 2020, named arguments, match expressions, nullsafe operator; PHP 8.2 is what Local runs

---

## Security Functions

### Nonces
```php
// Create nonce field in form
wp_nonce_field( 'fluid_scale_save_settings', 'fluid_scale_nonce' );

// Verify on form submission
if ( ! isset( $_POST['fluid_scale_nonce'] ) ||
     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fluid_scale_nonce'] ) ), 'fluid_scale_save_settings' ) ) {
    wp_die( esc_html__( 'Security check failed.', 'fluid-scale' ) );
}
```

### Capability Check
```php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have permission to access this page.', 'fluid-scale' ) );
}
```

### Sanitization (on save)
```php
absint( $value )               // positive integers: viewport widths, columns
(float) $value                 // floats: base sizes, ratios — then range-check manually
sanitize_text_field( $value )  // short strings
sanitize_textarea_field( $value ) // multiline strings
```

### Escaping (on output)
```php
esc_html( $string )      // text content
esc_attr( $string )      // HTML attribute values
esc_url( $url )          // URLs
wp_kses_post( $html )    // HTML with allowed tags
// For CSS output written to a file: sanitize at input time; file write is trusted
```

---

## i18n

Text domain must match plugin slug: `fluid-scale`

```php
// Translate and return
__( 'String', 'fluid-scale' )

// Translate and echo
_e( 'String', 'fluid-scale' )

// Translate with context
_x( 'String', 'context description', 'fluid-scale' )

// Translate with escaping (preferred for output)
esc_html__( 'String', 'fluid-scale' )
esc_attr__( 'String', 'fluid-scale' )

// Plural
_n( 'One item', '%d items', $count, 'fluid-scale' )
```

Load translations on `init`:
```php
add_action( 'init', function() {
    load_plugin_textdomain( 'fluid-scale', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );
```

---

## Hooks

All hook callbacks must be named (not anonymous) so they can be removed by other plugins:

```php
// Good
add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_stylesheet' ] );

// Bad — cannot be removed
add_action( 'wp_enqueue_scripts', function() { ... } );
```

### Key hooks for this plugin

| Hook | Priority | Purpose |
|------|----------|---------|
| `wp_enqueue_scripts` | 1 (early) | Enqueue generated CSS before theme styles |
| `admin_menu` | default | Register settings page |
| `admin_enqueue_scripts` | default | Enqueue admin JS/CSS only on plugin page |
| `init` | default | Load textdomain |
| `update_option_fluid_scale_settings` | default | Trigger CSS file regeneration on save |

### Early enqueue rationale
Priority 1 on `wp_enqueue_scripts` ensures `:root` custom properties are defined before any theme or builder CSS references them.

---

## Settings Storage

Use `wp_options` via `get_option` / `update_option`. Single option key: `fluid_scale_settings`.

Structure (stored as array, serialized by WordPress):
```php
[
    'min_viewport'   => 320,
    'max_viewport'   => 1240,
    'min_base'       => 16.0,
    'max_base'       => 20.0,
    'ratio'          => 1.333,
    'negative_steps' => 2,
    'positive_steps' => 5,
    'custom_pairs'   => [ ['from' => 's', 'to' => 'l'], ... ],
    'grid_max_width' => 1240,
    'grid_columns'   => 12,
    'grid_gutter_pair' => 's-l',
    'builder_mapping' => 'auto',   // 'auto' | 'divi5' | 'bricks' | 'none'
    'last_generated' => 0,         // Unix timestamp
]
```

---

## Generated CSS File

- **Location:** `wp_upload_dir()['basedir'] . '/fluid-scale/fluid-scale.css'`
- **URL:** `wp_upload_dir()['baseurl'] . '/fluid-scale/fluid-scale.css'`
- **Write strategy:** Write to temp file → rename (atomic, avoids partial reads)
- **Version:** Unix timestamp of last save, used as `?ver=` query string for cache busting
- **Regenerate trigger:** `update_option` action after settings save

### File write pattern
```php
$upload_dir = wp_upload_dir();
$dir        = $upload_dir['basedir'] . '/fluid-scale';
$file       = $dir . '/fluid-scale.css';
$tmp        = $dir . '/fluid-scale.tmp.css';

wp_mkdir_p( $dir );
file_put_contents( $tmp, $css_string );
rename( $tmp, $file );
```

### Enqueue pattern
```php
$version = get_option( 'fluid_scale_settings' )['last_generated'] ?? '1';
wp_enqueue_style(
    'fluid-scale',
    $upload_url . '/fluid-scale/fluid-scale.css',
    [],
    $version
);
```

---

## Admin Page

- Location: **Settings > Fluid Scale** (submenu under Settings, not top-level)
- Capability: `manage_options`
- Page slug: `fluid-scale`
- Admin assets enqueued only when `$hook === 'settings_page_fluid-scale'`

---

## Uninstall

`uninstall.php` in plugin root. Must:
1. Check `WP_UNINSTALL_PLUGIN` constant
2. Delete `fluid_scale_settings` from `wp_options`
3. Delete `wp-content/uploads/fluid-scale/` directory and contents

---

## wp.org Submission Checklist

- [ ] `readme.txt` follows https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/
- [ ] No external HTTP requests from front end
- [ ] No minified code without unminified source
- [ ] All strings use text domain `fluid-scale`
- [ ] `wp_enqueue_scripts` used for front-end assets (not echo/print in head)
- [ ] `uninstall.php` cleans up all data and files
- [ ] GPL-2.0-or-later license, full text included or linked
- [ ] Submitting account has 2FA enabled on wordpress.org
