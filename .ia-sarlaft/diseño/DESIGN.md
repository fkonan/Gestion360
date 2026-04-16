# Design System Specification: Corporate Management Portal

## 1. Overview & Creative North Star
**Creative North Star: "The Architectural Ledger"**
This design system moves beyond the standard "Bootstrap" utility aesthetic to create a high-end, authoritative digital environment for Copetran. Rather than a flat series of tables, we treat the interface as an architectural ledger—where information is organized through structural depth, editorial typography, and intentional tonal shifts. 

The system rejects the "boxed-in" feeling of traditional portals. By utilizing high-contrast primary headers against a multi-tiered neutral canvas, we create a sense of operational command. Asymmetry is used strategically in the sidebar and navigation to break the grid, ensuring the user's eye is always drawn to the most critical data and actions.

---

## 2. Color Strategy
Our palette is rooted in a deep, institutional blue, balanced by functional signals and a sophisticated range of neutral surfaces.

### Tonal Foundations
*   **The "No-Line" Rule:** 1px solid borders are strictly prohibited for defining sections. Structure must be achieved through background shifts. For example, the Sidebar uses `surface-container-low` while the main Content Area uses `surface`, creating a clear boundary without a single line.
*   **Surface Hierarchy:**
    *   **Level 0 (Base):** `surface` (#f8f9fa) for the main application background.
    *   **Level 1 (Navigation):** `surface-container-low` (#f3f4f5) for the sidebar to provide a soft grounding.
    *   **Level 2 (In-Page Elements):** `surface-container-lowest` (#ffffff) for cards and data tables to make them "pop" forward.
*   **Signature Textures:** The Header utilizes a subtle gradient from `primary` (#003f87) to `primary_container` (#0056b3) to add depth and "soul" to the institutional blue, avoiding a flat, dated appearance.

### Core Swatches
*   **Primary:** `#003f87` (The authoritative brand anchor)
*   **Secondary (Success/Go):** `#006d41` (Used for 'Regresar' and affirmative actions)
*   **Tertiary (Attention/Action):** `#553e00` / `#fabd00` (Used for 'Registrar Persona' to drive high-contrast task completion)
*   **Error:** `#ba1a1a` (Strictly for destructive actions and critical failures)

---

## 3. Typography: Editorial Authority
We use a dual-font system to balance industrial efficiency with modern legibility.

*   **Headlines (Work Sans):** Used for `display` and `headline` tiers. The slight geometric width of Work Sans conveys stability and scale.
    *   *Headline-LG:* 2rem. Used for main page titles (e.g., "Personas registradas").
*   **Functional Text (Inter):** Used for `title`, `body`, and `label` tiers. Inter's high x-height ensures that dense data tables remain legible at small sizes.
    *   *Body-MD:* 0.875rem. The workhorse for table data.
    *   *Label-MD:* 0.75rem. For sidebar navigation and breadcrumbs, ensuring clear information architecture.

---

## 4. Elevation & Depth
Depth is not a decoration; it is a communication tool.

*   **Tonal Layering:** The UI is "stacked." The sidebar sits at a lower tonal tier than the main content. Within the content area, data tables are placed on `surface-container-lowest` (pure white) to indicate their priority.
*   **The Ghost Border Fallback:** If containment is needed (e.g., in breadcrumb containers), use `outline-variant` (#c2c6d4) at 20% opacity. Never use 100% opaque borders.
*   **Ambient Shadows:** For floating elements like tooltips or dropdowns, use a 16px blur with 6% opacity using a tint of `#001a40` (on-primary-fixed). This mimics natural light rather than a "drop shadow" effect.
*   **Glassmorphism:** The breadcrumb bar and navigation toggles should utilize a `backdrop-blur: 10px` effect over the surface background to maintain a lightweight, modern feel.

---

## 5. Components

### Buttons
*   **Action Primary (Yellow):** Background: `tertiary_fixed_dim` (#fabd00). Text: `on_tertiary_fixed` (#261a00). Roundedness: `DEFAULT` (0.25rem). 
*   **Action Secondary (Green):** Background: `secondary` (#006d41). Text: `on_secondary` (#ffffff).
*   **Interaction:** On hover, buttons should not just change color but gain a subtle "lift" through an ambient shadow rather than a border-color change.

### The Management Table
*   **Header:** Must use `primary_container` (#0056b3) with `on_primary` text. This provides a heavy visual anchor for data.
*   **Rows:** Alternate between `surface_container_lowest` and `surface_container_low`. 
*   **No Dividers:** Eliminate horizontal lines between rows. Use the 8px vertical spacing scale to separate data points.

### Sidebar Navigation
*   **Inactive State:** Transparent background, `on_surface_variant` (#424752) text and icons.
*   **Active State:** `primary_fixed` (#d7e2ff) background with a left-aligned 4px "accent" bar in `primary`. This provides clear visual orientation.

### Breadcrumbs
*   **Container:** A rounded-pill shape using `surface_container_lowest` with a "Ghost Border."
*   **Typography:** `label-md` in Inter. The "Home" or "Inicio" link should use `primary` to indicate clickability.

---

## 6. Do's and Don'ts

### Do
*   **Do** use ample white space (1.5rem to 2rem) between major sections to let the "Architectural Ledger" breathe.
*   **Do** use icons in the sidebar to create a visual "scan line" for the user.
*   **Do** use color to signal intent: Blue for structure, Green for movement, Yellow for creation.

### Don't
*   **Don't** use 1px solid black or dark grey borders. If you need a divider, use a background color shift.
*   **Don't** use standard Bootstrap blue (#0d6efd). Always use the custom `primary` (#003f87) or `primary_container` (#0056b3).
*   **Don't** crowd the table cells. Maintain a minimum padding of 12px (0.75rem) on all cell sides to ensure high-end readability.