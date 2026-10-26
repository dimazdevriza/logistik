# D'Royal Village Anti-Slop Rules

## Purpose

This file keeps future UI work specific to D'Royal Village logistics operations. It is a quality filter, not a replacement for [DESIGN.md](DESIGN.md). Read `DESIGN.md` first for direction, then apply these rules before changing a page, component, style, or UI copy.

The full reusable policy and specialist guidance live in `.agents/skills/antislop/`. This project version records the rules that matter most for this application.

## Non-negotiable rules

1. **Use real operations data.** Do not invent stock counts, suppliers, houses, users, testimonials, activity, performance claims, or security claims. Show an honest empty state when data does not exist.
2. **Make controls work.** Every visible button, link, filter, sort control, menu, upload, and form must have a real destination or behavior. Remove controls that are not ready.
3. **Build for field conditions.** Preserve strong contrast, large touch targets, keyboard access, clear validation, and mobile layouts without accidental horizontal overflow.
4. **Keep the design grounded.** Use the D'Royal green, ivory, and forest palette from `DESIGN.md`. Do not add generic SaaS gradients, neon glow, decorative glass, or a new accent color.
5. **Let data lead.** Tables, forms, stock state, and audit history are the product. Do not add dashboard cards, charts, badges, or decoration that does not help a staff member make an operational decision.
6. **Keep motion quiet.** Motion exists only to confirm interaction, preserve orientation, or reveal state. No infinite animation, bouncing arrows, pulsing dots, or decorative entrance effects.
7. **State a reason.** Every major choice involving color, type, layout, spacing, cards, icons, illustration, or motion needs a one-line purpose in the task summary or implementation note.

## Execution rules

- Use MySQL for runtime and tests. Tests must target isolated `logistik_test`, never live `logistik`; SQLite is not an allowed project database.
- Verify changes in the local browser with labeled test records. After fixing any browser-found bug, rerun both automated tests and the browser workflow.
- Read `DESIGN.md` before UI work. Keep D'Royal Village's existing ledger language; reject generic dashboard decoration and ungrounded copy.
- Use SVG for every new icon. Do not use emoji, icon fonts, or generic image icons as interface icons.

## D'Royal Village visual guardrails

- The logo is the identity signal. Do not replace it with generated assets, initials, or decorative symbols.
- The outer dot field is a land-grid motif. Keep it outside active work surfaces and never layer it beneath text-heavy tables or forms.
- Leaf green represents the primary action and confirmed state. Lime supports emphasis. Gold is exceptional. Red is reserved for error or destruction.
- Use Outfit for readable operational copy and Geist for compact structural labels. Use monospace only for codes, dates, quantities, and prices.
- One screen gets one focal task. A crowded screen needs stronger grouping or fewer elements, not more cards.
- Tables place text left and comparable numbers right. Show a sort arrow only on the active column.
- Status chips must describe a real state. Decorative `New`, `Secure`, `Fast`, or similar labels are not allowed.

## Required state coverage

Every changed data view must account for:

- Normal content
- Empty results
- Loading or pending state
- Validation or request failure
- Keyboard focus
- Mobile layout
- Light and dark theme, when the component appears in both

## Before delivery

- Confirm page structure comes from the actual task, not a default dashboard or landing-page template.
- Confirm no claims, statistics, people, or records were fabricated.
- Confirm contrast and focus states are visible.
- Confirm controls have been exercised in the browser or inspected when browser testing is impossible.
- Confirm no new horizontal overflow appears at mobile width outside an intentional table scroll region.
- Confirm the visual decision record explains any gradient, blur, shadow, icon, badge, card, or motion used.

## Escalate instead of guessing

Ask the project owner before creating a new logo, photo, illustration, customer claim, navigation destination, statistical metric, or unfamiliar operational workflow. Use an explicit placeholder rather than a realistic invention when a decision is missing.
