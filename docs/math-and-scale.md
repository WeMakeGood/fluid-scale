# Fluid Type Scale: Math Reference

## Intellectual Lineage

This plugin implements the fluid modular scale approach originated by **Utopia** (https://utopia.fyi), created by James Gilyead and Trys Mudford. The modular scale concept itself predates Utopia — see Tim Brown's Modular Scale (https://www.modularscale.com). The math here is an independent PHP implementation of the same principles.

---

## Modular Scale

A modular scale starts with a **base size** (in px, converted to rem) and a **ratio**. Each step above the base multiplies the previous value by the ratio. Each step below divides.

```
step_size = base_size × ratio^step
```

Examples with base=16px, ratio=1.333 (Perfect Fourth):
- step-2: 16 × 1.333² = 28.43px
- step-1: 16 × 1.333¹ = 21.33px
- step-0: 16px (base)
- step--1: 16 ÷ 1.333¹ = 12.00px
- step--2: 16 ÷ 1.333² = 9.00px

---

## Two-Scale Fluid System (The Utopia Insight)

Define one scale for the **minimum viewport** and a second for the **maximum viewport**. Each can have a different base size (the ratio stays the same). Interpolate between them using CSS `clamp()`.

This keeps the scale proportionally coherent at every viewport width — no breakpoints needed.

---

## Interpolation Formula

For each step, compute the size on both the min-viewport scale and max-viewport scale, then produce a `clamp()` value:

```
min_size  = base_min × ratio^step        (in px, then converted to rem ÷ 16)
max_size  = base_max × ratio^step        (in px, then converted to rem ÷ 16)

slope     = (max_size - min_size) / (max_viewport - min_viewport)
intercept = min_size - (slope × min_viewport)

preferred = {intercept}rem + {slope × 100}vw

clamp()   = clamp({min_size}rem, {preferred}, {max_size}rem)
```

All values rounded to **4 decimal places** (matches Utopia's precision).

### Concrete Example

Parameters: min_vp=320, max_vp=1240, base_min=16px, base_max=20px, ratio=1.333, step=1

```
min_size  = 16 × 1.333 / 16 = 1.333rem
max_size  = 20 × 1.333 / 16 = 1.6663rem

slope     = (1.6663 - 1.333) / (1240 - 320) = 0.0003622
intercept = 1.333 - (0.0003622 × 320) = 1.2170rem
preferred = 1.2170rem + 0.0362vw

output    = clamp(1.333rem, 1.217rem + 0.0362vw, 1.6663rem)
```

---

## Property Naming Convention

Follow Utopia's naming exactly — users migrating from Utopia expect these names.

```css
:root {
  --step--2: clamp(...);   /* negative steps: double hyphen */
  --step--1: clamp(...);
  --step-0:  clamp(...);   /* base */
  --step-1:  clamp(...);
  --step-2:  clamp(...);
  --step-3:  clamp(...);
  --step-4:  clamp(...);
  --step-5:  clamp(...);
}
```

### Semantic Aliases (derived from steps, not independently configured)

```css
:root {
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
}
```

---

## Common Ratios

| Name | Value |
|------|-------|
| Minor Second | 1.067 |
| Major Second | 1.125 |
| Minor Third | 1.2 |
| Major Third | 1.25 |
| Perfect Fourth | 1.333 |
| Augmented Fourth / Tritone | 1.414 |
| Perfect Fifth | 1.5 |
| Golden Ratio | 1.618 |

---

## Default Parameters (for UI pre-population)

| Parameter | Default | Notes |
|-----------|---------|-------|
| Min viewport | 320px | Common mobile floor |
| Max viewport | 1240px | Common desktop target |
| Min base size | 16px | 1rem baseline |
| Max base size | 20px | Slightly larger on desktop |
| Ratio | 1.333 (Perfect Fourth) | Good all-purpose scale |
| Positive steps | 5 (step-1 through step-5) | |
| Negative steps | 2 (step--1 through step--2) | |

These defaults should produce a good result for a typical WordPress site without any changes — satisfying Story 1 (the designer who doesn't want to think about math).
