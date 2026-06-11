# Mr Bee's Brewery — Design System

A reference document for the visual language, component patterns, and design decisions used across the Mr Bee's Brewery website.

---

## Colour Palette

| Token | Hex | Usage |
|---|---|---|
| `--honey` | `#e8a000` | Primary accent, CTAs, active states, progress bar |
| `--honey-lt` | `#f5c842` | Hover states, gold stars, featured highlights |
| `--honey-dk` | `#bf8500` | Section labels, muted amber text, links |
| `--amber` | `#c06000` | Secondary warm accent |
| `--cocoa` | `#1c110a` | Primary dark — headings, dark surfaces, nav text |
| `--cocoa-md` | `#3a2010` | Dark hover states |
| `--cream` | `#fdf6e8` | Default page background |
| `--cream-dk` | `#f5e8c8` | Announcement strip tint, value icon backgrounds |
| `--parchment` | `#f9f2e0` | Alternate section background (story, testimonials) |
| `--mist` | `#f2ede4` | Card top backgrounds, form inputs |
| `--stone` | `#7a6a54` | Muted body copy, secondary metadata |
| `--text-muted` | `#6b5a46` | Body paragraph text |
| Footer bg | `#130b04` | Deepest dark — footer only |

### Semantic border colours
- Light: `rgba(28,17,10,0.10)`
- Medium: `rgba(28,17,10,0.18)`
- Honey accent (on dark): `rgba(232,160,0,0.15)`

---

## Typography

### Typefaces

| Role | Family | Fallback |
|---|---|---|
| **Serif** (`--serif`) | Playfair Display | Georgia, serif |
| **Sans** (`--sans`) | DM Sans | Inter, system-ui, sans-serif |
| **Mono** (`--mono`) | DM Mono | Courier New, monospace |

### Scale

| Element | Size | Weight | Notes |
|---|---|---|---|
| `h1` | `clamp(3rem, 5.5vw, 5rem)` | 700 | Serif, `letter-spacing: -0.02em` |
| `h2` | `clamp(2.2rem, 3.8vw, 3.4rem)` | 700 | Serif |
| `h3` | `1.3rem` | 700 | Serif |
| Section label | `0.72rem` | 400 | Mono, `letter-spacing: 0.22em`, ALL CAPS, `--honey-dk` |
| Body / `p` | `1rem` | 400 | Sans, `--text-muted`, `line-height: 1.7` |
| Hero lead | `1.1rem` | 300–400 | Sans, `line-height: 1.75` |
| Mono metadata | `0.72–0.85rem` | 400–500 | Mono, ALL CAPS, `--stone` |
| Stat numbers | `clamp(2.8rem, 4.5vw, 4.2rem)` | 700 | Serif, `--honey` |

### Patterns
- Section labels always preceded by a 24 px `--honey` hairline rule via `::before`.
- `<em>` is italic and coloured `--honey-dk` for in-line flavour.
- Headings carry tight negative letter-spacing (`-0.02em`) for editorial feel.

---

## Spacing & Layout

| Token | Value |
|---|---|
| Max container | `1200px` (`--max`) |
| Container gutter | `min(100% - 3rem, var(--max))` |
| Section vertical padding | `clamp(5rem, 10vw, 8rem) 0` |
| Standard grid gap | `1.25rem` |

### Grid patterns
- **Hero**: `1.05fr 1fr` two-column, collapses to single at 900 px.
- **Brews**: `repeat(4, 1fr)` → 2-col at 1024 px → 1-col at 720 px.
- **Story**: `1fr 1fr` → single-column at 900 px.
- **Stats row**: Horizontal flex with `1px` dividers → vertical stack at 900 px.
- **Gallery**: 12-column CSS Grid, 2 rows with editorial spanning → 2-col at 900 px → 1-col at 520 px.
- **Testimonials**: `1fr 1.3fr 1fr` (featured centre enlarged) → single-column at 1024 px.
- **CTA**: `1fr 1fr` → single-column at 900 px.

---

## Border Radii

| Token | Value | Usage |
|---|---|---|
| `--r-sm` | `8px` | Buttons, nav links, input fields |
| `--r-md` | `14px` | Stat cards, testimonial avatars |
| `--r-lg` | `22px` | Brew cards, gallery tiles, testimonial cards |
| `--r-xl` | `32px` | Email signup form panel |

---

## Shadows

| Token | Value | Usage |
|---|---|---|
| `--sh-xs` | `0 1px 3px rgba(28,17,10,0.07)` | Subtle separators |
| `--sh-sm` | `0 4px 12px rgba(28,17,10,0.09)` | Scrolled nav |
| `--sh-md` | `0 8px 24px rgba(28,17,10,0.12)` | Stat float cards |
| `--sh-lg` | `0 20px 48px rgba(28,17,10,0.16)` | Mascot badge |
| `--sh-xl` | `0 32px 72px rgba(28,17,10,0.20)` | Featured testimonial, CTA form |

All shadows use the `--cocoa` base colour so they feel warm, not cold-grey.

---

## Buttons

Five variants, all using `font-weight: 600`, `font-size: 0.92rem`, `border-radius: --r-sm`, `transition: all 0.24s --ease`.

| Class | Background | Text | Border | Hover |
|---|---|---|---|---|
| `.btn-primary` | `--honey` | `--cocoa` | — | `--honey-lt` + `translateY(-1px)` + glow shadow |
| `.btn-ghost` | transparent | `--cocoa` | `1.5px --border-md` | `--cocoa` border, faint bg |
| `.btn-outline` | transparent | `--cocoa` | `1.5px --border-md` | `--honey` border, `--honey-dk` text |
| `.btn-dark` | `--cocoa` | `--cream` | — | `--cocoa-md` + `translateY(-1px)` |
| `.btn-outline-dark` | transparent | `--cream` | `1.5px rgba(cream,0.35)` | full cream border, faint bg |

`.btn-full` stretches to 100% width (form submit).

---

## Easing

| Token | Curve | Usage |
|---|---|---|
| `--ease` | `cubic-bezier(0.22, 1, 0.36, 1)` | All UI transitions (snappy out) |
| `--ease-in` | `cubic-bezier(0.4, 0, 1, 1)` | Entrance animations |

---

## Animations

| Name | Duration | Behaviour |
|---|---|---|
| `float` | 5–6 s | Mascot / badge slow vertical bob |
| `ring-spin` | 14–20 s | Orbital rings rotate in opposite directions |
| `flap` | 0.18 s | Wing scale flap, alternating delay |
| `bee-drift` | 8 s | Floating bees drift on translate + rotate path |
| `blip` | 2 s | Announcement dot opacity pulse |
| `cue-drop` | 1.8 s | Scroll cue line grows then shrinks |
| Scroll reveal | 0.7 s | `opacity 0 → 1`, `translateY(24px → 0)` via `.reveal` / `.reveal.in` |

All motion is suppressed via `@media (prefers-reduced-motion: reduce)`.

---

## Components

### Announcement Strip
Dark (`--cocoa`) full-width bar above the nav. Animated honey dot on the left. Monospace label + serif strong. `--honey` link with arrow icon.

### Navigation
Sticky, `backdrop-filter: blur(16px) saturate(160%)` frosted glass on `--cream` base. Brand mark: hexagon SVG with bee emoji + Playfair name + mono tagline. Links use `--text-muted` at rest, `--cocoa` on hover with subtle bg fill.

### Hero
Parchment-to-cream diagonal gradient. Hex-shaped SVG decorative elements float in the background at `0.07` opacity. Copy column leads with mono label + hairline rule. H1 uses an italic `<em>` for the second line in `--honey-dk`. Proof stats use Playfair numerals. Visual column: concentric rings + floating mascot + award badge card.

### Section Labels
Mono, ALL CAPS, `0.22em` tracking, `--honey-dk`. Preceded by an inline 24 px honey hairline. On dark backgrounds use `.light` modifier → `--honey-lt`.

### Brew Cards
White cards, `--r-lg`, 1 px border. Top panel: `--mist` background with CSS-gradient can mockup (using `--can-clr` custom property) and pill badge. Hover: `translateY(-6px)` + `--sh-xl` + can tilts. Body: name in Playfair, style in mono, description in body sans.

### Brew Cans
CSS-only cylinder: `linear-gradient` with `color-mix` for highlight/shadow tones. White streak via `::before`. ABV in Playfair italic.

### Gallery Grid
12-column editorial mosaic. Items use CSS gradient fills as image placeholders. Honeycomb dot pattern overlay via `radial-gradient`. Hover reveals overlay caption (mono tag + sans description) from opacity 0.

### Testimonials
Three-column layout: two side cards (white, bordered) flanking a featured dark card (`--cocoa`). Featured card has a radial honey glow behind the quote mark. Blockquotes use Playfair italic.

### Stats Section
Full-bleed `--cocoa` band with subtle `60deg` striped line texture. Stats in a bordered rounded row. Playfair numerals in `--honey` animate up from 0 on scroll entry.

### CTA / Visit Section
`--cocoa` background with honeycomb dot pattern (`radial-gradient` at 44 px × 50 px). Two-col grid: info column with visit details table (mono labels, honey-tinted borders) + white form card with `--r-xl`.

### Form Inputs
`--mist` background, `1.5px --border-md` border, `--r-sm`. Focus: `--honey` border + `rgba(honey, 0.15)` ring shadow.

### Footer
`#130b04` (deeper than `--cocoa`). Brand + italic serif strapline + social icons (bordered square, `--r-sm`). Nav in 3-col grid with mono uppercase headings. Bottom bar: legal copy in very low-opacity cream.

---

## Motifs & Visual Language

- **Hexagons** — the defining shape: brand mark, background decorations, story section media well, stats border. Always rendered as `<polygon>` SVG with `points="50,2 96,27 96,87 50,112 4,87 4,27"`.
- **Honey amber** as the hero accent colour — warm, food-safe, distinct from generic orange or yellow.
- **Monospace labels** as section signposting — creates a craft/technical contrast against the editorial serif headlines.
- **Floating + orbiting elements** in the hero communicate life and energy without photography.
- **Gradient-fill placeholders** in gallery tiles match the brand palette and work well before real photography is sourced.

---

## Responsive Breakpoints

| Breakpoint | Behaviour |
|---|---|
| `≤ 1024px` | Brews → 2 col; Testimonials → stacked |
| `≤ 900px` | Hero → single col; Story → single col; Stats → vertical; Gallery → 2 col; CTA → single col; Footer → single col |
| `≤ 720px` | Nav links hidden → hamburger menu; Brews → 1 col |
| `≤ 520px` | Gallery → 1 col; Footer nav → 2 col; Press bar → vertical |
