# Utopia Math Reference

## Overview

Utopia (https://utopia.fyi) defines a unified fluid design system across three interconnected scales:
1. **Type scale** — fluid font sizes
2. **Space scale** — fluid spacing values, derived from the type base
3. **Grid** — max-width, gutter (references a space pair), columns

All three share the same viewport parameters (min/max viewport width, min/max base size). The math is an independent PHP implementation of these principles. Credit: James Gilyead and Trys Mudford (Utopia); Tim Brown (Modular Scale, https://www.modularscale.com).

---

## Shared Parameters

| Parameter | Description | Default |
|-----------|-------------|---------|
| `min_viewport` | Minimum viewport width in px | 320 |
| `max_viewport` | Maximum viewport width in px | 1240 |
| `min_base` | Base font size in px at min viewport | 16 |
| `max_base` | Base font size in px at max viewport | 20 |
| `ratio` | Scale ratio (same for both viewports) | 1.333 (Perfect Fourth) |
| `negative_steps` | Steps below base (step--1, step--2, ...) | 2 |
| `positive_steps` | Steps above base (step-1, step-2, ...) | 5 |

---

## Type Scale

### Formula

For each step `n` (negative or positive integer):

```
min_size_px  = min_base × ratio^n
max_size_px  = max_base × ratio^n
min_size_rem = min_size_px / 16
max_size_rem = max_size_px / 16

slope        = (max_size_rem - min_size_rem) / (max_viewport - min_viewport)
intercept    = min_size_rem - (slope × min_viewport)
preferred    = {intercept}rem + {slope × 100}vw

output       = clamp({min_size_rem}rem, {preferred}, {max_size_rem}rem)
```

Round all values to **4 decimal places**.

### Property Names (Utopia convention — must match exactly)

```css
--step--2: clamp(...);   /* negative steps: double hyphen */
--step--1: clamp(...);
--step-0:  clamp(...);   /* base */
--step-1:  clamp(...);
--step-2:  clamp(...);
--step-3:  clamp(...);
--step-4:  clamp(...);
--step-5:  clamp(...);
```

### Semantic Aliases (derived, not independently configured)

```css
--fs-xs:   var(--step--2);
--fs-sm:   var(--step--1);
--fs-base: var(--step-0);
--fs-md:   var(--step-1);
--fs-lg:   var(--step-2);
--fs-xl:   var(--step-3);
--fs-2xl:  var(--step-4);
--fs-3xl:  var(--step-5);

--fs-body: var(--step-0);
--fs-h6:   var(--step-1);
--fs-h5:   var(--step-1);
--fs-h4:   var(--step-2);
--fs-h3:   var(--step-3);
--fs-h2:   var(--step-4);
--fs-h1:   var(--step-5);
```

### Common Ratios

| Name | Value |
|------|-------|
| Minor Second | 1.067 |
| Major Second | 1.125 |
| Minor Third | 1.2 |
| Major Third | 1.25 |
| Perfect Fourth | 1.333 |
| Augmented Fourth | 1.414 |
| Perfect Fifth | 1.5 |
| Golden Ratio | 1.618 |

---

## Space Scale

### Relationship to Type Scale

The space scale uses `--step-0` (the base size) as its foundation. Each space step is a multiplier of that base. This creates mathematical harmony between type and spacing — they share the same root unit.

`space-s` = 1× the base = identical to `--step-0` values at min and max viewport.

### Multipliers (relative to space-s = 1×)

| Step | Multiplier |
|------|-----------|
| `--space-3xs` | 0.25× |
| `--space-2xs` | 0.5× |
| `--space-xs` | 0.75× |
| `--space-s` | 1× (= step-0 base) |
| `--space-m` | 1.5× |
| `--space-l` | 2× |
| `--space-xl` | 3× |
| `--space-2xl` | 4× |
| `--space-3xl` | 6× |

### Formula

For each space step with multiplier `m`:

```
min_space_rem = (min_base / 16) × m
max_space_rem = (max_base / 16) × m

slope         = (max_space_rem - min_space_rem) / (max_viewport - min_viewport)
intercept     = min_space_rem - (slope × min_viewport)
preferred     = {intercept}rem + {slope × 100}vw

output        = clamp({min_space_rem}rem, {preferred}, {max_space_rem}rem)
```

Round to **4 decimal places**.

### One-Up Pairs (always generated)

Pairs two consecutive steps: the min value from the smaller step, the max value from the larger step. Used for contexts where spacing should vary more dramatically across viewports.

```
--space-3xs-2xs: clamp({min of 3xs}, ..., {max of 2xs})
--space-2xs-xs:  clamp({min of 2xs}, ..., {max of xs})
--space-xs-s:    clamp({min of xs},  ..., {max of s})
--space-s-m:     clamp({min of s},   ..., {max of m})
--space-m-l:     clamp({min of m},   ..., {max of l})
--space-l-xl:    clamp({min of l},   ..., {max of xl})
--space-xl-2xl:  clamp({min of xl},  ..., {max of 2xl})
--space-2xl-3xl: clamp({min of 2xl}, ..., {max of 3xl})
```

For a pair `{from}-{to}`:
```
clamp({min_size_of_from}rem, {intercept}rem + {slope}vw, {max_size_of_to}rem)
```

Slope and intercept computed from `min_size_of_from` and `max_size_of_to`.

### Custom Pairs (user-defined)

Users define arbitrary pairs (e.g. `s-l`, `xs-xl`). Same formula as one-up pairs but with any two steps as endpoints. Generated property name: `--space-{from}-{to}`.

UI: "Add pair" button → two dropdowns (From / To) → name auto-generated → delete button per pair.

---

## Grid

Minimal set of three properties. Gutter references a space pair (user-selectable, defaults to `--space-s-l`).

```css
--grid-max-width: {max_width}rem;
--grid-gutter:    var(--space-s-l, clamp(...));  /* fallback is the raw clamp value */
--grid-columns:   {columns};
```

Also generates utility classes:

```css
.u-container {
  max-width: var(--grid-max-width);
  padding-inline: var(--grid-gutter);
  margin-inline: auto;
}

.u-grid {
  display: grid;
  gap: var(--grid-gutter);
}
```

### Grid Parameters

| Parameter | Description | Default |
|-----------|-------------|---------|
| `max_width` | Container max-width in px (converted to rem) | 1240 |
| `columns` | Number of grid columns | 12 |
| `gutter_pair` | Which space pair to use as gutter | `s-l` |

---

## Output Structure

The plugin generates one CSS file. Section order:

```css
/* === Fluid Scale: Type === */
:root {
  --step--2: clamp(...);
  /* ... */
  --fs-body: var(--step-0);
  /* ... semantic aliases ... */
}

/* === Fluid Scale: Space === */
:root {
  --space-3xs: clamp(...);
  /* ... individual steps ... */
  --space-3xs-2xs: clamp(...);
  /* ... one-up pairs ... */
  --space-s-l: clamp(...);
  /* ... custom pairs ... */
}

/* === Fluid Scale: Grid === */
:root {
  --grid-max-width: ...rem;
  --grid-gutter: var(--space-s-l, clamp(...));
  --grid-columns: 12;
}

.u-container { ... }
.u-grid { ... }

/* === Builder Mappings === */
:root {
  /* auto-generated from active builder detection */
}
```
