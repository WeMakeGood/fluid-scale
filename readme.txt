=== Fluid Scale ===
Contributors: wemakegood
Tags: typography, fluid, css, custom-properties, utopia
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Injects a complete Utopia fluid design system — type, space, and grid — as CSS custom properties available to any theme or page builder.

== Description ==

Fluid Scale generates a mathematically coherent set of CSS custom properties based on the [Utopia](https://utopia.fyi) fluid design system, created by James Gilyead and Trys Mudford. The custom properties are injected as a static stylesheet before any theme or builder CSS runs, making them available everywhere on your site without any per-component configuration.

= What It Generates =

**Fluid type scale** — a set of `--step-*` variables that interpolate smoothly between a small-screen and large-screen modular scale. No breakpoints. No manual size definitions per element.

**Fluid space scale** — a set of `--space-*` variables derived from the same base as the type scale, covering everything from `--space-3xs` through `--space-3xl`. Also generates one-up pairs (`--space-s-m`, etc.) and any custom pairs you define.

**Grid variables** — `--grid-max-width`, `--grid-gutter`, and `--grid-columns`, plus `.u-container` and `.u-grid` utility classes.

= How It Works =

1. Configure your scale in **Settings > Fluid Scale**: set your viewport range, base sizes, ratio, and any custom space pairs.
2. The plugin calculates the `clamp()` values and writes them to a static CSS file in your uploads directory.
3. That file is enqueued before your theme loads — every element on every page has access to the variables immediately.
4. Use the variables in your theme, page builder custom CSS, or any stylesheet: `font-size: var(--step-2);`, `padding: var(--space-m);`

= Type Scale Variables =

```
--step--2   Smallest (e.g. fine print)
--step--1   Small (e.g. captions)
--step-0    Base body size
--step-1    Slightly larger
--step-2    Subheadings
--step-3    Headings
--step-4    Large headings
--step-5    Display / hero
```

Semantic aliases are also generated: `--fs-body`, `--fs-h1` through `--fs-h6`, `--fs-xs` through `--fs-3xl`.

= Space Scale Variables =

```
--space-3xs  --space-2xs  --space-xs
--space-s    --space-m    --space-l
--space-xl   --space-2xl  --space-3xl
```

One-up pairs (`--space-xs-s`, `--space-s-m`, etc.) and custom pairs you define are also generated.

= Builder Compatibility =

Fluid Scale works with any theme or page builder. When Divi 5 is detected, the plugin automatically overrides Divi's layout variables (`--content-max-width`, `--section-padding`, `--section-gutter`, `--row-gutter-horizontal`, `--row-gutter-vertical`, `--module-gutter`) with your fluid scale values. The four space-based mappings are configurable from the Builder Mapping panel — choose which space step maps to each Divi variable. The grid variables (`--content-max-width`, `--row-gutter-horizontal`) are fixed to your plugin grid settings.

Bricks Builder detection is in place; variable mapping for Bricks requires verification against a live install and is planned for a future release.

= Caching Compatible =

The generated CSS is a static file served from your uploads directory. It is fully compatible with WP Rocket, W3 Total Cache, and CDN setups. The file is only regenerated when you save new settings, and the enqueued stylesheet URL includes a version parameter that changes on each save to bust CDN caches.

= Credit =

This plugin implements the fluid design system methodology developed by [Utopia](https://utopia.fyi) (James Gilyead and Trys Mudford). The modular scale concept builds on earlier work by Tim Brown ([Modular Scale](https://www.modularscale.com)). The math is an independent implementation; no Utopia code is included.

== Installation ==

1. Upload the `fluid-scale` directory to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **Settings > Fluid Scale** to configure your scale.
4. The default settings produce a good result for most sites without any changes — save once to generate the CSS file.

== Frequently Asked Questions ==

= Do I need to know what a modular scale is? =

No. The default settings produce a harmonious type and space scale that works well for most WordPress sites. You only need to understand the parameters if you want to customize beyond the defaults.

= Will this work with my theme? =

Yes. The plugin injects CSS custom properties into `:root`, which makes them available globally. Any theme or builder that accepts CSS can reference them. The plugin does not modify your theme's CSS.

= Will this work with my page builder? =

Any builder that supports custom CSS can use these variables directly. Divi 5 users also get automatic layout variable mapping — the plugin overrides Divi's spacing and grid defaults with fluid values from your scale. See the Builder Mapping panel in settings.

= How do I use the variables? =

In any CSS context (theme stylesheet, builder Custom CSS field, child theme, Additional CSS): `font-size: var(--step-3);` or `margin-bottom: var(--space-m);`.

= What happens if I deactivate the plugin? =

The generated CSS file stops being enqueued. Any styles that referenced the custom properties will fall back to the browser's default or inherit from a parent element. Nothing is deleted on deactivation.

= What happens if I delete the plugin? =

The uninstaller removes all plugin settings from the database and deletes the generated CSS file from your uploads directory. Nothing is left behind.

= Is this the same as Utopia's calculator? =

The math produces identical results to Utopia's calculator. This plugin is not affiliated with Utopia — it is an independent implementation of the same principles that automates the output and injects it into WordPress without any copy-paste step.

== Screenshots ==

1. Settings page — configure viewport range, base sizes, ratio, and custom space pairs.
2. Type specimen preview — see each step rendered at a representative viewport before saving.
3. Space scale preview — visual representation of the spacing system.
4. Generated CSS — the readonly output showing the full set of custom properties.

== Changelog ==

= 1.0.0 =
* Initial release.
* Fluid type scale: `--step--2` through `--step-5` with semantic aliases.
* Fluid space scale: `--space-3xs` through `--space-3xl`, one-up pairs, user-defined custom pairs.
* Grid variables (`--grid-max-width`, `--grid-gutter`, `--grid-columns`) and utility classes.
* Live admin preview with light/dark toggle — type specimen, space scale, and page mockup tabs.
* Divi 5 layout variable mapping: overrides `--content-max-width`, `--row-gutter-horizontal`, `--section-padding`, `--section-gutter`, `--row-gutter-vertical`, and `--module-gutter` with fluid scale values. Configurable per-site from the Builder Mapping panel.
* Bricks Builder detection (mapping implementation planned for future release).
* Static file output, fully caching-compatible.

== Upgrade Notice ==

= 1.0.0 =
Initial release.

== Planned for Future Versions ==

* Fluid spacing scale exposed as standalone spacing tokens for block editor
* Container query (`cqi`) unit support
* WP-CLI commands for regenerating the CSS file from the command line
* REST API endpoint for retrieving the current scale configuration
* Additional builder mapping profiles
