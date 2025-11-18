# Final Quiz Email CSS Analysis

This report documents the visual styling system implemented in `emails/final-quiz-email.php`, focusing on inline styles, `<style>` blocks, responsive behavior, and Gmail-safe patterns.

## A. Inline Styles
- **Body**: `margin:0; padding:0; background-color:#f6f6f6; font-family:Helvetica,Arial,sans-serif; color:#1c1c1c;` (sets base font and background).
- **Wrapper table (`#villegas-email-wrapper`)**: Uses `background`/`bgcolor` attributes with dynamic values; inline `style` mirrors dynamic wrapper styles.
- **Card table (`#villegas-email-card`)**: `width:100%; max-width:720px; margin:0 auto; background:#ffffff; border:1px solid #e5e5e5; border-radius:8px; font-family:Helvetica,Arial,sans-serif; color:#1c1c1c;`.
- **Header cell (`#villegas-email-encabezado`)**: `text-align:center; padding:0; background:black; border-radius:8px 8px 0px 0px;` (rounded top corners, black background).
- **Presentation cell (`#villegas-email-presentacion`)**: `padding:20px 48px 32px; text-align:center;`.
  - Completion date paragraph: `margin:0; font-size:14px; color:#6d6d6d;`.
  - Heading: `margin:12px 0 8px; font-size:26px; color:#111111; line-height:1;` with `<br>` break for the user name.
  - Supporting text wrapper: `font-size:18px; line-height:1.6;` with inner paragraph `margin:0; color:#1c1c1c;`.
- **Charts block cell**: `padding:0 32px;`.
  - Charts table (`#villegas-email-graficas`): `border-top:1px solid #f1f1f1; border-bottom:1px solid #f1f1f1; padding:32px 0; text-align:center;`.
  - Circle container cells: `padding:0 14px; text-align:center;`.
  - Circle titles (`h2`): `font-size:16px; margin-bottom:12px; color:#111111;`.
  - Chart images: `max-width:240px; height:auto;`.
- **CTA/closing cell (`#villegas-email-cta`)**: `padding:32px 48px; text-align:center;`.
  - CTA text wrapper: `font-size:18px; line-height:1.6; color:#333333;`.
  - Closing paragraph: `margin-top:28px; color:#666666;`.

## B. `<style>` Block Rules
- **500px breakpoint** (`@media only screen and (max-width: 500px)`):
  - `#villegas-email-graficas td`: `display:block !important; width:90% !important; margin:0 auto !important; text-align:center !important;`.
  - `#villegas-email-graficas h2`: `margin-top:24px !important;`.
- **Generic table rounding**: `table[id$="villegas-email-card"]` gets `border-radius:8px; overflow:hidden;`.
- **1024px and above** (`@media only screen and (min-width: 1024px)`): `#villegas-email-logo` set to `width:76% !important; height:170px !important;`.
- **Below 1024px** (`@media only screen and (max-width: 1023px)`): `#villegas-email-logo` set to `width:100% !important; height:140px !important;`.
- **600px breakpoint** (`@media only screen and (max-width: 600px)`):
  - `.villegas-circle-container, .villegas-circle-wrapper`: center via `margin-left/right:auto !important; text-align:center !important;`.
  - `.villegas-first-circle`: `margin-bottom:40px !important;` (adds spacing between donuts).
  - `#villegas-final-title-row td, #villegas-final-title-row`: `padding-top:40px !important;` (extra spacing for the second chart title).

**Selectors used**: IDs (`#villegas-email-graficas`, `#villegas-email-logo`, `#villegas-final-title-row`), classes (`.villegas-circle-container`, `.villegas-circle-wrapper`, `.villegas-first-circle`), attribute selector (`table[id$="villegas-email-card"]`).

**Gmail support notes**:
- ID and class selectors are generally respected in Gmail mobile apps when paired with media queries; attribute selectors may be less reliable, but here they target tables for border-radius (non-critical).
- Padding changes target `<td>` elements, which Gmail honors; margin-based spacing is avoided inside media queries for critical layout adjustments.

## C. Responsive Image Handling
- Logo uses `#villegas-email-logo` with two breakpoints:
  - `min-width: 1024px`: `width:76% !important; height:170px !important;`.
  - `max-width: 1023px`: `width:100% !important; height:140px !important;`.
- `!important` ensures overrides against inline dimensions or client defaults; keeps aspect ratio by not forcing height auto.

## D. Section Spacing Strategy
- Spacing primarily via `<td>` padding (Gmail-safe) rather than margins:
  - Presentation: `padding:20px 48px 32px`.
  - Charts block: outer cell `padding:0 32px`; inner table `padding:32px 0` plus top/bottom borders for separation.
  - CTA/closing: `padding:32px 48px`.
- Extra spacing between chart titles on mobile achieved with `padding-top` in media query.
- Margins are used for text spacing (e.g., `margin-bottom:12px`, `margin-top:24px` in media queries), acceptable for non-critical adjustments.

## E. Circle Layout and Alignment
- Circles placed in a two-column table within `#villegas-email-graficas`; each circle cell uses `padding:0 14px; text-align:center;`.
- Titles sit above images inside the same cell; spacing via `margin-bottom:12px`.
- Mobile adjustments:
  - At `max-width:500px`, circle `<td>`s become block-level with 90% width and centered margins.
  - At `max-width:600px`, circle containers/wrappers centered via auto margins; first circle gets bottom margin; second title row gets top padding to mimic vertical spacing.

## F. Typography System
- **Base font**: Helvetica/Arial/sans-serif.
- **Headings**:
  - `h1`: `font-size:26px; color:#111111; line-height:1; margin:12px 0 8px;` (centered via parent cell).
  - `h2`: `font-size:16px; margin-bottom:12px; color:#111111;`.
- **Paragraphs**:
  - Completion date: `font-size:14px; color:#6d6d6d; margin:0;`.
  - Supporting/CTA copy wrapper: `font-size:18px; line-height:1.6; color:#333333;` with inner paragraphs `color:#1c1c1c` or `#666666` and margins for spacing.
- **Line-height**: Tight for headings (`1`), looser for body copy (`1.6`).
- **Weight**: Defaults (likely normal) unless overridden by browser defaults; no explicit `font-weight` set in inline styles.
- **Responsive typography**: No font-size changes inside media queries; sizes remain constant across breakpoints.

## G. Gmail Mobile Compatibility
- Uses table-based layout with padding for spacing (preferred by Gmail).
- Media queries target IDs/classes applied to `<td>` elements; padding adjustments used instead of margins for reliable spacing.
- Attribute selector for table rounding is non-critical if ignored.
- Inline styles define default rendering; media queries supply mobile overrides with `!important` to counteract Gmail-specific CSS handling.
- Structural dependencies: `#villegas-email-graficas`, `.villegas-circle-container`, `.villegas-first-circle`, `#villegas-final-title-row`, and `#villegas-email-logo` IDs/classes drive responsive tweaks.
