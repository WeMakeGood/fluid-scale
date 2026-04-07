# Builder Mapping Reference

## Overview

When a supported builder is detected, the plugin outputs a `<style>` block late in `wp_head` (priority 104) that maps Fluid Scale canonical variables to the builder's own `:root` custom property names. This block lands after all Divi-generated inline CSS, so our values win the cascade for `:root`-level vars.

The user can toggle mapping on/off from the settings page. If no builder is detected, the mapping section is hidden from the UI.

---

## Cascade Strategy

Divi 5 outputs its layout variables in two places:

1. **`divi-dynamic-css`** (linked stylesheet, enqueued early) — sets `--row-gutter-horizontal`, `--section-padding`, etc. on `:root`
2. **`ET_Core_PageResource::head_late_output_cb`** (priority 103) — outputs `et-critical-inline-css`, which re-sets those same vars and adds per-row generated rules

Our mapping fires at `wp_head` priority **104**, after both, so our `:root` overrides win.

---

## Divi 5

### Status: Verified

Verified against live Divi 5.2.0 with Playwright. The `:root` vars below are written by Divi's critical and dynamic CSS and are successfully overridden by our mapping block.

### What we override

| Divi variable | Fluid Scale mapping | Notes |
|---|---|---|
| `--content-max-width` | `var(--grid-max-width)` | Fixed — keeps content width in sync with plugin grid settings |
| `--row-gutter-horizontal` | `var(--grid-gutter)` | Fixed — replaces Divi's arbitrary `5.5%` with the fluid gutter |
| `--section-padding` | `var(--space-{user choice})` | Default: `--space-xl` |
| `--section-gutter` | `var(--space-{user choice})` | Default: `--space-xl` |
| `--row-gutter-vertical` | `var(--space-{user choice})` | Default: `--space-l` |
| `--module-gutter` | `var(--space-{user choice})` | Default: `--space-m` |

The four space-based mappings are configurable per-site from the Builder Mapping panel in settings.

### Known limitation: column width calc fallbacks

Divi's generated column width rules use hardcoded fallback values:

```css
.et_flex_column_8_24 {
    width: calc(33.3333% - var(--horizontal-gap-parent, 5.5%) * 0.66667);
}
```

The `5.5%` fallback is baked into Divi's generated stylesheet — it cannot be overridden via custom properties. The actual `column-gap` on flex rows may also be set directly by per-row generated rules in `et-critical-inline-css`, bypassing the `var(--horizontal-gap)` chain.

**Resolution:** Use Divi's Design System option group presets to set column gutters to match the fluid scale. This is a Divi builder operation, not something the plugin can automate.

### What we don't map

- `--content-width: 80%` — percentage-based, no meaningful fluid equivalent
- `--gcid-*` color vars — outside this plugin's scope
- Font size vars — Divi 5 sets font sizes per-module, not via `:root` tokens

---

## Bricks Builder

### Status: Unverified

Detection logic is in place. Variable name mapping requires a live Bricks install to verify.

### Detection
```php
defined( 'BRICKS_VERSION' ) || get_template() === 'bricks'
```

### TODO
- [ ] Install Bricks on the dev environment
- [ ] Inspect `:root` output to confirm custom property names
- [ ] Determine if Bricks reads upstream `:root` vars or only its own generated ones
- [ ] Confirm naming convention (`--bricks-font-size-*` or otherwise)
- [ ] Implement and verify mapping in `class-builder-mappings.php`

---

## Adding New Builders

1. Add detection method in `includes/class-builder-detector.php`
2. Add `get_active_builders()` entry
3. Add mapping method in `includes/class-builder-mappings.php`
4. Add `wp_head` output logic in `fluid-scale.php` (follow the Divi pattern — determine correct priority by inspecting the builder's hook priorities)
5. Add UI label and description to the Builder Mapping panel in `admin/views/settings-page.php`
6. Document verified variable names and any cascade limitations here
