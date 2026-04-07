# Builder Mapping Reference

## Overview

The plugin auto-detects active builders/themes and offers to enable a mapping block that appends additional `:root {}` declarations to the generated CSS. These declarations map the plugin's canonical variables (`--step-*`, `--space-*`) to whatever variable names the builder expects.

Detection happens in PHP at CSS generation time. The user can toggle each detected mapping on/off from the settings page.

---

## Detection Logic

```php
// Divi 5
function is_divi5_active(): bool {
    return defined( 'ET_CORE_VERSION' ) && version_compare( ET_CORE_VERSION, '5.0', '>=' );
    // Also check: get_template() === 'Divi'
}

// Bricks
function is_bricks_active(): bool {
    return defined( 'BRICKS_VERSION' );
    // Also check: get_template() === 'bricks'
}
```

---

## Divi 5

### Status
Divi 5's CSS variable system generates variables dynamically from the visual builder — they are not defined in static theme files. The exact runtime variable names require verification against a live Divi 5 instance with design tokens configured.

### Known architecture
- Divi 5 uses CSS custom properties in the visual builder, but font sizes set via the builder are per-module inline styles or generated stylesheet entries, not global `:root` tokens by default.
- The Design Variables system (Global Design Options in Divi 5) does write to `:root`, but the exact property names depend on what the user has configured.

### Recommended mapping approach
Rather than mapping to Divi's internal variables (which are builder-output, not builder-input), the Divi 5 mapping should be documented as: **use the plugin's variables inside Divi's Custom CSS fields or Global CSS**.

Example to show in UI:
```css
/* Use Fluid Scale variables in Divi 5 */
/* In Divi > Theme Options > Custom CSS, or any module's Custom CSS: */
h1 { font-size: var(--step-5); }
h2 { font-size: var(--step-4); }
p  { font-size: var(--step-0); }
.section { padding: var(--space-l); }
```

### TODO
- [ ] Verify whether Divi 5 Design Variables write named custom properties to `:root` that can be overridden upstream
- [ ] Test: does a `:root { --divi-var: value; }` defined in an earlier stylesheet actually override Divi's own value?
- [ ] If yes: document the exact Divi 5 variable names for font sizes and populate the mapping table below

### Variable mapping table (UNVERIFIED — requires live Divi 5 testing)
```css
/* TODO: Verify these names against Divi 5 Design Variables */
:root {
  /* --divi-font-size-body: var(--step-0); */
  /* --divi-font-size-xl:   var(--step-3); */
}
```

---

## Bricks Builder

### Status
Bricks uses CSS custom properties for its global styles system. Variable names are stable across versions.

### Detection
```php
defined( 'BRICKS_VERSION' ) // Bricks defines this constant
```

### Known variable names
Bricks global typography variables (set in Bricks > Global Styles > Typography):

```css
/* Bricks uses these in :root when global styles are configured */
--bricks-color-*       /* colors */
--bricks-space-*       /* spacing — maps well to --space-* */
```

### TODO
- [ ] Inspect a live Bricks install to extract exact font-size custom property names
- [ ] Determine if Bricks reads `:root` variables defined upstream or only its own generated ones
- [ ] Confirm: does Bricks use `--bricks-font-size-*` naming or something else?

### Placeholder mapping (to be verified)
```css
/* TODO: Verify against live Bricks install */
:root {
  /* --bricks-font-size-xl:   var(--step-4); */
  /* --bricks-font-size-l:    var(--step-3); */
  /* --bricks-font-size-m:    var(--step-2); */
  /* --bricks-font-size-base: var(--step-0); */
  /* --bricks-font-size-s:    var(--step--1); */
}
```

---

## Adding New Builders

To add a builder definition:

1. Add detection function in `includes/class-builder-detector.php`
2. Add mapping definition in `includes/class-builder-mappings.php` as a new case
3. Add UI label and description in the settings page builder section
4. Document verified variable names here

---

## Architecture Notes

- Builder mappings are appended as a separate `:root {}` block *after* the canonical scale variables, so the cascade order is: plugin canonical → plugin builder aliases → theme → builder
- The mapping block uses `var(--step-*)` references, not hardcoded values, so changing scale parameters automatically updates all mappings
- If a builder is detected but mapping is disabled by the user, no mapping block is output
- If no builder is detected, the mapping section is hidden from the settings page (not disabled — hidden)
