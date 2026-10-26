# Alokasi UX verification

Date: 2026-09-14

Scope: `/logistik/alokasi`, its house-selection state, and allocation review dialogs. `/logistik/transaksi` remains a compatibility URL. The separate `antislop-ui` package was unavailable; the installed antislop core was applied during implementation. Existing app branding supplies the visual direction: an operations form for logistics staff, green accents, Outfit typography, ENERGY 1 / RHYTHM 1 / MOTION 1.

## Design decisions

- Compact transaction buttons keep all three workflows visible without repeating descriptions.
- A native dialog gives house selection a focused workspace with browser-managed modal behavior and Escape support.
- Search covers house name, code, and type, including names entered without hyphens; one block filter narrows the catalog.
- Eight house rows per page keep the rendered list bounded. Selection persists across pages and filters; bulk selection explicitly applies only to the current page.
- The selected-only filter supports reviewing and removing houses from large selections.
- A compact summary states quantity per house, total quantity, and total cost before confirmation.
- The return picker shows only houses with active loans, including completed houses that still hold tools. Houses leave the selection after their final return.
- Green preserves the existing identity; darker green provides readable action labels. Existing typography avoids another font dependency.
- Form spacing separates destination, transaction details, and review. Motion is limited to interaction feedback.

## Automated evidence

`php artisan test --compact tests/Feature/TransactionHousePickerTest.php tests/Feature/TransactionFlowHardeningTest.php`

Result: **24 focused tests passed, 134 assertions**. Tests use the isolated MySQL database configured by `phpunit.xml`; SQLite is not used.

The 500-house test checks eight-row pages, a four-row final page, natural ordering, normalized search, block filtering, persistent selections, current-page bulk selection, selected-only removal, clearing, and empty results. Return tests cover eligible completed houses, exclusion of returned/voided loans, tab changes, and removing a house after its last return. Existing allocation and partial-return regression checks also pass.

PHP syntax and `git diff --check` pass.

## Browser evidence

- House dialog opens with search focused; Escape and the close/done controls close it.
- Selecting A-01, paging forward, and searching B02 retains both selections. Selected-only shows both after clearing search.
- Filtering to block A and selecting the visible page selects exactly three houses.
- An unmatched search shows an explanation and zero results with disabled paging/bulk actions.
- Material review: 3 houses × 2 batang = 6 batang; Rp 75,000 per batang gives Rp 450,000. Escape closes confirmation.
- Tool review: 3 houses × 1 helmet = 3 units; available stock preview falls from 20 to 17. Cancel closes confirmation.
- Return search finds Molen Beton in the currently selected houses. Reviewing one normal return shows 1/1 unit and condition counts 1/0/0. Cancel closes confirmation.
- A quantity of 201 against stock of 200 shows a stock warning and disables review. Reset clears transaction fields.
- Final saves were exercised by automated tests; browser reviews were cancelled before writing inventory transactions.
- Desktop checked at 1186 × 794. Phone layouts checked at widths 390 and 360; document width did not exceed the viewport. The house dialog has a bounded scroll area and visible footer.
- Light and dark page/picker styles were checked. Dark secondary text and checkbox/button borders were adjusted. Original light preference and viewport were restored.
- No browser warnings or errors were captured during these checks.

## Antislop delivery gate

- R-02 PASS: transaction view contains no em dashes.
- R-03 PASS: desktop and phone checks show no horizontal overflow; form controls use 44px minimum height.
- R-17, R-18, R-36, R-38 PASS: displayed houses, stock, prices, and loans come from the application. No fabricated claims, testimonials, or production records were added.
- R-23, R-24 PASS: existing branding and navigation are reused; no new visual assets or destinations.
- R-25 PASS: action text contrast is 6.82:1; secondary text is 6.77:1 on light surfaces and 8.65:1 on the dark dialog surface.
- R-26 PASS: changed controls have working native or Livewire actions, verified above.
- R-27 PASS: empty results, delayed loading feedback, validation messages, and stock warnings are present. Failed server requests retain Livewire's existing error handling.
- R-28 PASS: no FAQ is included in this operational workflow.
- R-32 PASS: labeled inputs, native buttons, visible focus indicators, and modal dialogs with Escape behavior.
- R-33 PASS: changes were made directly in source with patches.
- R-34 PASS: existing light/dark tokens are preserved, with scoped contrast improvements.
- R-35 PASS: page rendered and exercised in the browser; focused automated checks pass.
- R-37 PASS: existing brand direction and calm operational dials are recorded above.
- R-01, R-04, R-06, R-07, R-08, R-09 PASS: brand color and typography serve hierarchy; no new decorative patterns, icons, arrows, or badges. The shared app shell is outside this page-only change.
- R-10, R-12, R-13, R-14, R-19, R-22 PASS: form sections group related controls; dialogs provide functional elevation; no new blur, glow, illustrations, or decorative motion.
- R-05, R-11, R-15, R-16, R-20, R-21, R-29, R-30, R-31 PASS: workflow-specific structure and review labels, consistent radii and palette, existing theme support, and written reasons for major decisions.
- Liveliness / craftsmanship PASS: the form is the focal point; spacing separates tasks; the green selection/action accent preserves identity; real content and tested interaction states drive the composition.

## Scale boundary

Only eight house choices are rendered per page. House metadata is currently loaded and filtered on the server per render, which keeps this change small for the requested 500-house case. For substantially larger catalogs, move block/search filtering and pagination into indexed database queries. No new dependencies were introduced.
