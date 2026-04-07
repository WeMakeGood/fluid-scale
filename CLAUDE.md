# Fluid Scale — Claude Code Bootstrap

## What This Is

A WordPress plugin that injects a complete Utopia fluid design system (type scale, space scale, grid) as CSS custom properties, available to any theme or page builder without configuration.

**Repo:** https://github.com/WeMakeGood/fluid-scale
**Plugin dir:** `wp-content/plugins/fluid-scale/` (never work in WP root)
**Dev environment:** Local by Flywheel, PHP 8.2, WordPress 6.x

---

## Load These Before Doing Anything

@docs/utopia-math.md
@docs/architecture.md
@docs/wordpress-standards.md
@docs/builder-mappings.md
@docs/decisions.md

Read all five before writing or modifying any code. They contain the math formulas, file structure, security requirements, builder variable names, and the rationale behind key decisions. Do not rely on training memory for Utopia's formulas — use docs/utopia-math.md as the authoritative source.

---

## Critical Rules

1. **Never work in the WP root.** All work happens inside `wp-content/plugins/fluid-scale/`.
2. **Read before writing.** Read every file before editing it.
3. **Generator is pure PHP.** `includes/class-generator.php` must have zero WordPress dependencies.
4. **JS mirrors PHP math exactly.** The admin preview JS must produce identical clamp() values to the PHP generator. If you change the formula in PHP, update the JS too.
5. **All strings use text domain `fluid-scale`.** No bare English strings in output.
6. **Verify nonce and capability before every settings save.**
7. **Builder variable names in docs/builder-mappings.md are marked TODO where unverified.** Do not ship verified-sounding mappings for Divi 5 or Bricks without testing against a live instance first.
8. **Static file output only.** Never use `wp_head` or `wp_add_inline_style` for the main CSS output.

---

## Current State

Check `docs/decisions.md` for the latest decisions. Check git log for current progress:

```bash
git log --oneline -10
```

Check what's built vs. what's pending by reading the existing files in `includes/` and `admin/`.

---

## Environment

- WP-CLI: available at `/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/posix/wp`
- PHP: 8.2.29
- GitHub CLI: `gh` — authenticated as `cfrazier`, org `wemakegood`
- Run WP-CLI from: `/Users/chris.frazier.wemakegood.org/Local Sites/plugin-devlocal/app/public`
- Playwright inspector: `node /Users/chris.frazier.wemakegood.org/Local\ Sites/plugin-devlocal/app/inspect.js`

## Testing with Playwright

A Playwright-based inspector lives at `../../../app/inspect.js` (relative to plugin root), i.e. at `/Users/chris.frazier.wemakegood.org/Local Sites/plugin-devlocal/app/inspect.js`.

Use it to verify rendered CSS values, check custom properties on any page, and screenshot the result. The site uses a self-signed cert — the script handles this automatically.

```bash
# Check CSS custom properties on the front end
node ~/path/to/inspect.js https://plugin-dev.local/sample-page/ --css-vars --screenshot

# Check the admin settings page (auto-logs in)
node ~/path/to/inspect.js "https://plugin-dev.local/wp-admin/options-general.php?page=fluid-scale" --screenshot --wait 2000

# Check computed styles for a specific element
node ~/path/to/inspect.js https://plugin-dev.local/ --selector "h1" --css-vars
```

**Key things to verify after changing the generator:**
1. `--step-*` values are genuine clamp() with non-zero vw slope
2. `--space-*` values scale from min_base to max_base correctly
3. `--grid-gutter` references the correct space pair
4. No JS console errors on the admin page

**Reset settings to defaults:**
```bash
/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/posix/wp option delete fluid_scale_settings --path=/Users/chris.frazier.wemakegood.org/Local\ Sites/plugin-devlocal/app/public
```

---

## Key Decisions Already Made

- Full Utopia system: type + space + grid (not type-only)
- Custom space pairs: user-configurable via from/to dropdowns in admin
- Builder mapping: built-in definitions (Divi 5, Bricks), auto-detected, user toggles on/off
- Admin preview: live JS recalculation (no AJAX, no button press)
- PHP 8.0 minimum, WordPress 6.0 minimum
- Namespace: `FluidScale\`
- Option key: `fluid_scale_settings`
- Generated CSS: `wp-content/uploads/fluid-scale/fluid-scale.css`

See `docs/decisions.md` for full reasoning.

---

## What's Out of Scope for v1

- Container query (`cqi`) unit support
- WP-CLI commands
- REST API endpoint
- Gutenberg/FSE integration
- Hardcoded builder profiles beyond Divi 5 and Bricks

Document these as "Planned: v2" in readme.txt, but do not implement.
