# Decision Log

Running log of non-obvious decisions and their reasoning. Add entries here when a choice is made that isn't self-evident from the code.

---

## 2026-04-07

### Full Utopia system (type + space + grid), not type-only
**Decision:** Implement all three Utopia calculators — type scale, space scale, and grid.
**Why:** Owner wants a consistent layout system applicable across the entire builder, not just fonts. Space scale is mathematically derived from the same base as the type scale, so the shared parameter set makes this a natural extension rather than a separate feature.
**Effect on scope:** Space scale generator, custom pair UI, grid parameter fields added. Design doc's "v1 does not include fluid spacing" is superseded.

### Custom space pairs: user-configurable via from/to dropdowns
**Decision:** Let users define custom space pairs via a UI (add-pair button, two dropdowns, auto-generated name). Not a freeform textarea, not a fixed set.
**Why:** Pairs are just two named steps — the UI can make this trivial without requiring CSS knowledge. Auto-generating the property name (`--space-{from}-{to}`) removes a decision from the user.

### Builder mapping: built-in definitions with auto-detection, not freeform textarea
**Decision:** Ship Divi 5 and Bricks definitions as code. Auto-detect which is active. User toggles on/off.
**Why:** User explicitly said builder mapping should not be done by hand. Definitions should be maintained in the plugin, not written by the user per-site.
**Caveat:** Divi 5's exact runtime variable names are unverified — the mapping is stubbed with TODO comments pending live testing. See builder-mappings.md.

### Static file output, not wp_add_inline_style
**Decision:** Write generated CSS to `uploads/fluid-scale/fluid-scale.css`, enqueue as stylesheet.
**Why:** Inline CSS via wp_head is not cached by WP Rocket and similar plugins. Static file is fully cacheable, CDN-compatible, and gets a version-busted URL only when settings change.

### PHP 8.0 minimum, WordPress 6.0 minimum
**Decision:** PHP 8.0+ (dev environment runs 8.2), WordPress 6.0+.
**Why:** PHP 8.0 provides named arguments, match expressions, and nullsafe operator — features that improve the generator's clarity. WordPress 6.0 (May 2022) is widely deployed and provides stable APIs. No reason to support older versions for a new plugin.

### Namespace: `FluidScale\`
**Decision:** PHP namespace `FluidScale`, prefix `fluid_scale_` for option keys and hook names.
**Why:** WordPress best practices require unique namespacing. `FluidScale` is unambiguous and doesn't conflict with any known plugin.

### JS preview mirrors PHP generator math
**Decision:** Admin preview recalculates in browser (no AJAX). JS duplicates the PHP formula.
**Why:** Real-time feedback without server round-trips. The math is simple enough that JS duplication is maintainable. PHP remains authoritative; JS is display-only.

### Generator class has zero WordPress dependencies
**Decision:** `class-generator.php` accepts a plain array and returns a string. No `get_option`, no WordPress functions.
**Why:** Makes the generator unit-testable in isolation. Separates math from infrastructure. A contributor can improve the math without touching WordPress internals.
