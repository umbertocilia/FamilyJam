# FamilyJam UI Guidelines

## Direction
- Mobile-first before desktop refinement.
- Calm premium look: high contrast surfaces, restrained accent color, spacious cards.
- Information density should stay readable on smartphone without hidden critical actions.

## Tokens
- Rounded surfaces with clear hierarchy: `surface`, `surface-strong`, `surface-muted`.
- Accent color reserved for primary CTA, active state and key charts.
- Success, warning, danger and info tones are semantic only.

## Layout
- Desktop uses sidebar + sticky topbar.
- Mobile uses compact topbar + bottom navigation for primary modules.
- Content width stays constrained with generous breathing room.

## Components
- Primary metrics use `metric-card`.
- Lists use `list-table` rows with compact metadata on the right.
- Empty states should always explain what is missing and suggest the next action.
- Alerts should be short, dismissible and tone-coded.
- Slide-over is preferred to modal for notifications and contextual details.

## Forms
- Keep labels visible above fields.
- Group long forms into sections with short step labels.
- Use hint text sparingly and keep error messages local to the field when possible.

## Accessibility
- Focus ring must remain visible in both light and dark mode.
- Interactive hit areas should stay comfortable on touch devices.
- Never rely on color alone for status; pair with text or badge label.
