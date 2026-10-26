# D'Royal Village Logistics Design Direction

## Product and audience

This is an operational inventory system for D'Royal Village construction work. Its users are logistics staff, site supervisors, and administrators who need to record material movement, tool loans, warehouse stock, houses, and costs accurately during a working day.

Reading this as: a field-and-office operations app for construction logistics, in a cultivated ledger visual language, dial **ENERGY 2 / RHYTHM 1 / MOTION 1**.

- **Energy 2:** the brand feels alive through leaf green and warm ivory, without competing with operational data.
- **Rhythm 1:** repeatable page structure helps staff move quickly between inventory, transaction, and history screens.
- **Motion 1:** use brief feedback and state transitions only. Movement must never distract from entering or checking records.

## Visual theme

The interface should feel like a precise construction ledger placed in a landscaped village: calm, orderly, and practical rather than corporate or futuristic. The D'Royal Village logo is the primary identity signal. Keep the existing restrained green dot field as a land-grid motif, the glass shell as the environmental layer, and bento composition for real operational summaries and quick actions. These are deliberate identity choices, not decoration to repeat inside every control.

Keep one clear focal point per screen:

- Inventory pages: the primary stock action or the data table.
- Transaction pages: the transaction form and its stock consequence.
- History pages: the date, type, and item identity needed to audit movement.
- Detail pages: the selected house, warehouse, material, or tool and its current state.

## Color palette and roles

| Token | Value | Role |
| --- | --- | --- |
| Linen canvas | `#F5F7F4` | Main light-mode page background. It softens long work sessions without reducing contrast. |
| Paper surface | `#FFFFFF` | Forms, tables, modals, and reading surfaces. |
| Ledger header | `#EBF1E8` | Table headers and quiet structural grouping. |
| Forest ink | `#1A241E` | Primary text and high-importance data. |
| Muted leaf | `#486150` | Supporting labels, helper text, and secondary metadata. |
| Structural border | `#D3DEC9` | Dividers and contained surfaces. |
| Leaf green | `#1F9B3A` | Primary action, active navigation, confirmed success, and focus. |
| Lime signal | `#78C800` | Limited supporting emphasis, never a replacement for primary green. |
| Royal gold | `#D4AF37` | Exceptional financial or attention state only, never decorative fill. |
| Night canvas | `#090A0A` | Existing dark-mode base. |

**Reason:** green belongs to the established village-and-land brand and naturally separates confirmed operational states from neutral records. Ivory and soft green preserve contrast and reduce visual noise in dense tables.

Do not introduce purple, neon blue, rainbow gradients, or a new accent color. Do not put colored glow behind ordinary content. The dot field belongs only on the outer page canvas, never inside a table, form, modal, or card.

## Typography

- **Outfit:** default body, page titles, actions, and form labels. Its rounded-but-grounded shapes match the approachable village identity and remain readable at data-entry sizes.
- **Geist:** compact section labels, table headers, and dense navigation metadata. It creates clear structural hierarchy without using decorative monospace headings.
- **System monospace:** codes, dates, quantities, prices, and transaction references only. Use tabular numerals when possible.
- **Language:** interface labels and validation messages remain Indonesian. Use operational terms staff already use, such as `Masuk`, `Keluar`, `Gudang`, `Rumah`, and `Penanggung Jawab`.

Use weight, size, and spacing for hierarchy. Avoid all-caps prose, massive headings, decorative monospace, marketing language, and emoji.

## Layout and navigation

- Desktop uses the existing 264px sidebar, with the 76px rail option for experienced users. The sidebar is navigation, not decoration.
- Mobile replaces the sidebar with the existing 56px top bar and expandable menu. Do not duplicate desktop navigation beside it.
- Keep page content within the app shell with predictable gutters. A page may use a wide operational table when the record requires it.
- Multi-column forms collapse to one column below 768px. Inputs and primary actions must remain at least 44px high on touch screens.
- Horizontal scrolling is allowed only for dense data tables in a labelled scroll region. Keep the item name or code sticky when it helps orientation.
- Bento grouping is allowed for real operational summaries, quick actions, and status blocks when the blocks have different information weight. Do not turn every page into equal cards.
- Do not add generic dashboard rows, charts, stat cards, or feature grids unless each one answers a real operations question.

## Components and interaction

### Buttons

- Use leaf green for one primary action per task area, such as `Tambah Material`, `Simpan`, or `Proses & Validasi Import`.
- Use quiet outline buttons for reversible or supporting actions.
- Use danger styling only for destructive actions and require the existing confirmation flow.
- Buttons use short, specific Indonesian verbs. Do not use generic calls to action such as `Explore` or `Learn More`.

**Reason:** a single clear primary action reduces mistakes during repetitive stock work.

### Forms

- Label appears above the control, helper text below it, and validation errors directly below the affected control.
- Required fields are clear before submission. Do not rely on color alone.
- Focus uses the existing green ring and must stay visible for keyboard users.
- Preserve entered values when server validation fails whenever technically possible.
- **Searchable / Creatable Combobox Dropdowns** (search existing or write new to register on save, e.g. `Peruntukan` in [transaksi-logistik.blade.php](resources/views/livewire/logistik/transaksi-logistik.blade.php) and `Cluster` in [houses.blade.php](resources/views/livewire/logistik/houses.blade.php)):
  - Use a text `<input class="form-control pe-5" role="combobox">` with example-guided placeholder copy (`Pilih atau ketik <field> (mis. <Contoh A>, <Contoh B>)...`) and a right-aligned chevron button (`12x12` SVG that rotates `rotate-180` when open).
  - Input surface uses `--ledger-surface-input` (`#090b0a` in dark mode) with `--ledger-line-strong` border, and focus ring uses `#8fe3a3` border with `0 0 0 0.2rem rgba(143, 227, 163, 0.16)` shadow (both inside `.main-card-container` and teleported `.modal-content`).
  - Dropdown menu uses `<div class="card shadow-lg border rounded-3 position-absolute w-100 mt-1 bg-body overflow-hidden" style="max-height: 220px; z-index: 1050;">` on `--ledger-surface` (`#111513` in dark mode) with an inner scroll container (`class="p-1.5" style="max-height: 210px; overflow-y: auto;"`).
  - Option buttons use `class="dropdown-item rounded-2 py-2 px-3 text-start w-100"` (do not put `.d-flex` on the `x-show` button because Bootstrap `.d-flex` sets `display: flex !important` and blocks Alpine `x-show`) with `<span class="fw-medium text-truncate d-block">`. Do not pre-highlight a synthetic empty/none row when the input is blank; only highlight (`active bg-success text-white`) an explicitly matched option.
  - When typed text does not match any existing option, show the inline creation prompt at the bottom of the listbox: `<div class="px-3 py-2 extra-small text-secondary fst-italic border-top">Gunakan "<span class="fw-bold text-body" x-text="..."></span>" sebagai <field> baru</div>`.

### Tables

- Text is left-aligned. Quantities, money, and other comparable numbers are right-aligned with tabular numerals.
- Dates use one consistent display format: `DD/MM/YYYY HH:mm` where time matters.
- The active sort column alone shows its direction arrow. Inactive sortable columns show no decorative arrows.
- Standard record rows use the current comfortable 56px height. Use compact density only where scanning many rows is more important than reading notes.
- Table headers stay visible while scrolling. Code and item name may remain sticky on wide inventory tables.
- On desktop, secondary row actions may appear on hover or keyboard focus. On mobile, place them in a labelled kebab menu.
- Avoid stripes unless a table is exceptionally wide and the stripes demonstrably improve row tracking. Hover and focus states should provide the main row cue.

### Status and feedback

- Green means confirmed, available, completed, or incoming stock.
- Amber or gold means attention is needed, not success.
- Red means destructive, rejected, unavailable, or an error.
- Every data view needs a meaningful empty, loading, and error state. State the cause and the next useful action.
- Use badges only for real state, never as decorative labels.

### Surfaces

- Use glass for the application shell, mobile header, hero surface, and selected bento summary blocks. Keep tables, forms, and dense reading surfaces more opaque so the glass layer preserves hierarchy.
- Cards exist only when they group a task, contain a form, or establish a meaningful layer above the canvas.
- Most table and list content should use borders and whitespace rather than extra shadows.
- Radius communicates hierarchy: large shell surfaces may use 20px, ordinary grouped surfaces 12px, controls 8px, status chips only when their compact shape communicates status.

## Motion and accessibility

- Use 150ms to 220ms transitions for hover, focus, sidebar rail movement, menu opening, and row-action reveal.
- Animate only `opacity` and `transform`. Respect `prefers-reduced-motion`.
- No looping pulse, floating decoration, bounce, or scroll choreography.
- Every action must work with keyboard navigation. Dialogs close with Escape and retain a visible focus state.
- Maintain WCAG AA contrast: 4.5:1 for normal text and 3:1 for large text.
- Both light and dark modes are part of the product. A component is not complete until it is readable and functional in both.

## Explicit anti-patterns

- No generic blue-purple SaaS gradients, neon glows, or glass everywhere.
- No fabricated statistics, testimonials, activity feeds, people, suppliers, houses, or transaction records.
- No generic bento dashboard, fake terminal, three-column feature grid, or chart without a stated operational question. Bento is allowed when the block sizes reflect real operational priority.
- No decorative arrows, stars, sparkles, robot icons, or status dots.
- No dead buttons, ghost navigation, placeholder links, or mock controls presented as working features.
- No horizontal overflow outside intentional table scroll regions.
- No visual change without a one-line reason recorded in the implementation note, pull request, or task summary.

## Decision record for future UI work

Before changing UI, state the screen's job, its single primary decision, and why each major visual choice helps that job. Read this file first, then [antislop.md](antislop.md) for the delivery gate.
