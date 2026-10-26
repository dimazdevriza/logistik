# Browser audit — 21 September 2026

## Long-run follow-up — 22 September 2026

- Switched the local browser through Mandor and Logistik sessions and completed a labelled material request from submission to dispatch, arrival proof, and approval. The queue recorded the source gudang, and the final state rendered as `Selesai` / `Disetujui`.
- Ran a separate labelled tool request through dispatch, arrival proof, and Logistik rejection. The queue rendered `Ditolak, menunggu kembali` and exposed `Catat barang kembali`; the native browser confirmation prompt stopped the automation before the final accept, while the stock-return mutation remains covered by the MySQL regression suite.
- At 390px, the Mandor request form reflowed to the mobile header and single-column form. Empty submission displayed Indonesian validation messages, including `unit rumah tujuan wajib diisi` and `material wajib diisi ketika jenis item bernilai material`.
- The assigned-cluster picker searched `A-40` to one house and submitted successfully. History rendered the new request with the responsive record table/card treatment.
- The allocation and dispatch screens were visually rechecked at desktop width with the shared header, action sizing, dark theme, and no page-level overflow. The final allocation screen remains open in the browser.

This is a local acceptance snapshot, not production sign-off. The checks used the running MySQL-backed application in the browser and left all created fixtures labelled `[TEST]`.

## Mandor dashboard follow-up — 23 September 2026

- `/dashboard` now opens a Mandor-specific overview with assigned-cluster context, request-status counts, dispatched requests that need arrival confirmation, and the six latest requests. Links open the existing request and history screens.
- Dashboard data is scoped to the signed-in requester. An unassigned Mandor gets a clear cluster notice and no unusable request action; `DashboardTest` verifies that state and excludes another Mandor's records.
- Browser review passed at desktop and 390px in dark and light themes. At mobile width the document had no horizontal overflow, actions measured 44px high, keyboard focus began at the mobile menu, and `Buat permintaan` opened the existing request form. The original dark theme and desktop viewport were restored afterward.
- Visual decisions follow the existing Mandor workflow header, ledger table, restrained green status treatment, and the dashboard's real action priority: arrival confirmation.
- `DashboardTest` passed four tests and 17 assertions; the full MySQL suite then passed 156 tests and 830 assertions. Blade caching and the Vite production build also passed.

## Results

| Role/session | Route | Result |
| --- | --- | --- |
| Admin | `/dashboard` | Loaded; operational summary, database state, SVG icons, and quick links rendered. |
| Admin | `/logistik/materials` | Loaded; inventory summary and material table rendered. |
| Admin | `/logistik/tools` | Loaded; tool summary and inventory table rendered. |
| Admin | `/logistik/warehouses` | Loaded; warehouse list rendered. |
| Admin | `/logistik/suppliers` | Loaded; supplier page rendered. |
| Admin | `/logistik/categories` | Loaded; category page rendered. |
| Admin | `/logistik/houses` | Loaded; house list rendered. |
| Admin | `/logistik/clusters` | Loaded; cluster page rendered. |
| Admin | `/logistik/transfers` | Loaded; transfer form and history rendered. |
| Admin | `/logistik/alokasi` | Loaded; house picker, allocation form, and impact summary rendered. |
| Admin | `/logistik/dispatches` | Loaded; sortable queue rendered. Rejected dispatched fixture showed `Ditolak, menunggu kembali` and `Catat barang kembali`; a fresh split-gudang tool request showed its recorded source warehouse. |
| Admin | `/logistik/material-log` | Loaded; material history page rendered. |
| Admin | `/logistik/tool-log` | Loaded; tool history page rendered. |
| Admin | `/admin/users` | Loaded; user-management page rendered. |
| Admin | `/admin/house-costs` | Loaded; house-cost summary rendered. |
| Admin | `/admin/playground` | Loaded; playground route rendered without a server or Livewire error. |
| Admin | `/settings/profile` | Loaded; profile settings rendered. |
| Admin | `/settings/password` | Loaded; password settings rendered. |
| Admin | `/settings/appearance` | Loaded; both theme choices rendered. Browser clicks switched to light mode and back to dark mode, with the active state and palette visibly changing. |
| Admin | `/settings/two-factor` | Loaded; two-factor settings route rendered. |
| Mandor | `/mandor/requests?view=request` | Loaded; request form, cluster-scoped house helper text, and per-lot material options rendered. |
| Mandor | `/mandor/requests?view=history` | Loaded; request history table, active sort control, status, and arrival action rendered. |

## Interaction evidence

- Automated evidence after this browser pass: `php artisan test --compact` completed with 154 passing tests and 816 assertions; `npm run build` completed successfully; `php artisan migrate:status` reported no pending migrations.
- A browser-created `[TEST]` dispatched tool request was rejected. The queue visibly changed to `Ditolak, menunggu kembali` and exposed `Catat barang kembali`.
- The dispatch action column is now sticky on the dense desktop table, and narrow screens reflow each dispatch into labelled record blocks so the workflow action remains reachable without horizontal hunting.
- A 390px mobile viewport exposed three dispatch-card defects: shared nowrap rules clipped long request codes, a global 56px row height hid every cell after the first, and long rejection badges clipped. The selectors and row markup were corrected, then the full card—including status and return action—was rechecked in the browser.
- The return action opens a native confirmation dialog. The automation driver stopped at that confirmation; the method's stock and audit behavior is covered by the MySQL feature tests.
- The appearance selector was exercised in the browser: light mode was selected and visually checked, then dark mode was restored.
- A fresh `[TEST]` split-batch house was opened through the real allocation page. The browser selected the active tool, recorded one good unit, selected a different receiving warehouse, opened the return confirmation, and saved it. The modal closed with the success message; a direct MySQL check confirmed the source balance remained at one available unit, the destination balance gained one available unit, the aggregate tool availability returned to two, and the return log recorded the destination warehouse.
- The mandor history table exposed one active sort control and kept the action column in the table region. The large-house picker exposes search and cluster filtering instead of requiring a 500-row native select scan.
- A fresh authenticated route sweep navigated every static admin/logistics/settings route listed above without a navigation exception or server error. The mandor session separately navigated both request views.
- A continuation pass navigated the visible admin sidebar links as a real user, including dashboard, inventory, warehouse, house, allocation, dispatch, history, cost, user, and settings routes. Each route rendered its expected heading with no page-level horizontal overflow. The tool-history check initially matched the legitimate value `Rp 500.000`; inspecting the page confirmed no error surface and no browser console logs, so it was recorded as a detector false positive rather than an application defect.
- Parameterized routes for house detail, house completion, warehouse detail, cluster expenses, and house-cost detail were also opened with a live `[TEST]` fixture; each rendered its expected page title without a 403 or server-error surface.
- At 390px, the mandor request form collapsed to the mobile navigation and single-column workflow. Its searchable house picker narrowed `A-40` to one matching house, while an unmatched query displayed a clear empty state; the browser console reported no warning or error entries during this check.
- The authenticated admin route sweep was repeated in light mode. All listed static routes rendered with the light theme active (`body.bg-body`) and no forbidden/server-error surface; dark mode was then restored.
- The authenticated admin session was denied access to `/mandor/requests?view=request` with the expected 403 role boundary (`User does not have the right roles.`); no protected Mandor surface leaked into the admin navigation.
- The allocation page was rechecked from the visible admin navigation: the owner-approved dark allocation surface rendered without overflow or console errors, and its primary `Tinjau alokasi` action was correctly disabled until required house/material input exists.
- A rendered accessibility scan found visible unlabeled filters and transfer fields on the category, warehouse-transfer, and house-cost routes. Those controls now use explicit `label[for]` associations or ARIA names; the browser retest found zero visible unlabeled fields, no overflow, and no console errors on all three routes. Blade view caching passed after the markup change.
- The touch-target audit found shared sidebar controls and dense table actions below the 44px field-use target. Shared CSS now gives navigation, rail controls, account actions, and visible table actions a 44px hit area; expanded and collapsed sidebar checks plus material/category/house-cost routes remained overflow-free with zero console errors.
- Keyboard focus was checked on the dispatch queue: the sidebar controls and route links received visible solid focus outlines while tabbing, including the allocation and dispatch links.
- The desktop route sweep was repeated against every known admin, logistics, and settings route. Each page rendered its expected title without a server-error surface; numeric values such as prices were excluded from the error detector to avoid false positives.
- The complete set of 20 known admin/logistics/settings routes was also opened at a 390px viewport. All 20 rendered an expected page title with no server-error surface; the viewport was reset afterward.
- At a 390px viewport, the high-risk admin pages (materials, tools, transfers, allocation, dispatches, material log, tool log, and house costs) all rendered. Tool history and house-cost reports now switch from wide tables to labelled record cards: long codes and house names wrap, desktop sticky columns are disabled, and the report container does not create a nested vertical scrollbar.
- Material history now uses the same labelled-card treatment. A fresh `[TEST]` material usage with a long transaction code, house name, and construction note wrapped correctly through the real `/logistik/material-log` route; the status badge and cancel action remained reachable.
- A fresh mobile sweep found a returned-status badge in tool history that preserved desktop no-wrap sizing. The badge now wraps within its labelled record; `/logistik/tool-log` was retested at 390px with no nested or page-level overflow.
- The mandor request and history views were retested at the same mobile viewport and rendered without a server-error surface.
- A fresh seeded Mandor browser session was logged in through the visible login form. The request view loaded, the assigned-cluster picker returned one result for `A-50`, and an out-of-cluster `B-01` search returned the explicit empty state. The history view then opened through the visible `Riwayat Permintaan` navigation link with its sort control and arrival action intact; both views had no page-level overflow or browser console errors. The session was logged out and the admin dashboard was restored afterward.
- The public login page was retested after adding its missing recovery affordance. `Lupa kata sandi?` is now visible beside the password label, opens `/forgot-password`, and exposes the recovery email field and `Kirim Tautan Pemulihan` action without horizontal overflow. The login page and admin session were restored afterward. Focused auth tests passed 9/9 (22 assertions), Blade view compilation passed, and the asset build passed.
- Transfer and cluster-expense forms now expose targeted Livewire progress labels (`Menyimpan transfer…` and `Menyimpan biaya…`) while disabling only the active save action. Browser checks confirmed both labels are present in the rendered controls; an empty cluster-expense save returned the expected amount/description validation errors without creating data or causing overflow.
- The same targeted loading-label pattern now covers the primary save actions for categories, clusters, houses, materials/restocks, tools, warehouses, suppliers, users, allocations/returns, profile, and password settings. Representative category validation, settings rendering, and allocation validation were exercised after reload; the allocation check surfaced the expected required-notes and insufficient-stock messages for an exhausted `[TEST]` lot, and the form was reset without creating a record.
- 2FA settings were opened through the password-confirmation gate with the seeded admin account; the enable control rendered its targeted `Menyiapkan 2FA…` state. The account-deletion confirmation was opened and closed without submitting it; its destructive button rendered the targeted `Menghapus…` state. Neither security-changing action was executed.
- The material table was retested through its visible sort buttons: after selecting `Kode`, exactly one `.table-sort-arrow` remained, and its accessible label reported `saat ini naik`; inactive columns had no arrow. Focused sorting regression tests passed 3/3 (45 assertions).
- Rental expense validation was exercised in the browser without uploading a bill. The save stayed blocked, the form now visibly reports `The bill image field is required.`, no `[TEST]` expense row was created, and the modal remained overflow-free. The focused rental-evidence regression passed 1/1 (7 assertions).
- The same rental form was retested with an empty vendor, zero quantity, and a due date before the start date. It visibly reported all three field errors plus the missing bill error, kept the modal open, created no `[TEST]` row, and remained overflow-free.
- A fresh `[TEST]` tool was split one unit in Gudang Browser A and two units in Gudang Browser B. Through the real dispatch queue, staff clicked `Kirim Barang`; the toast confirmed success, the row changed to `Dikirim (Menunggu Mandor)`, and the row displayed `Gudang sumber: [TEST] Gudang Browser B`. A direct MySQL check confirmed source warehouse 2 moved from 2 available to 0, warehouse 1 stayed at 1, the aggregate stayed at 1, and the request stored `source_warehouse_id = 2`.
- The mandor form was retested with `Alat` selected. The live tool selector showed only tools with a positive warehouse balance, listed each available gudang for split tools, and displayed the helper that Logistik chooses the source during dispatch. The same form was visually checked at phone width with no horizontal overflow or clipped control.
- The Mandor history record was refined after the mobile pass: request code/date and house/item values now use a two-column labelled layout instead of squeezing each value into half a row. At 390px the code, date, status badge, and arrival action remained readable.
- The large-house picker was tested with a 250-house assigned-cluster fixture. Searching `A-50` returned one selectable house; searching out-of-cluster `B-01` returned the explicit empty state instead of retaining the previously selected house as a misleading result. A selected house from another cluster was also excluded by the Livewire regression test.
- A read-only viewport check measured `scrollWidth === clientWidth` on the mobile dashboard, inventory, warehouse, house, cluster, transfer, allocation, dispatch, log, cost, settings, and Mandor history routes. No page-level horizontal overflow was detected.
- The parameterized house-cost detail route was visually retested at 390px after its long material table was converted to labelled record rows. Long material names and notes wrap inside the card, the nested table has no horizontal overflow, and the page reports no nested or page-level overflow.
- Mobile inventory actions were retested after the dense table’s sticky identity column was covering the kebab control. The action rail now stays above the scrolled table, the material/tool menus open without triggering row edit, and deleting a transferred `[TEST]` tool is rejected in-browser with the transfer-history protection message; the fixture remains listed.
- A browser-created `[TEST]` material transfer produced separate source and receiving lots. Both remained visible with their warehouse-specific quantities; attempting to delete the receiving lot exposed the transfer-history protection message and kept the lot listed. The transfer record now links both material rows through its source and destination IDs.
- The transfer form was retested with two same-name `[TEST]` material lots in one warehouse. The item selector showed both prices separately (`Rp 110.000` and `Rp 100.000`), and the transfer-history row displayed its preserved source-lot price.
- The transfer history was then checked at 390px. Its dense desktop table now becomes labelled record cards; long transfer codes, warehouse names, notes, and price-lot values wrap without page-level or nested vertical overflow.
- The final settled admin route sweep revisited all 16 visible sidebar routes after navigation timing was allowed to settle. Every page showed its expected heading, reported no page-level horizontal overflow, and exposed no server-error surface; the browser console had zero errors. The only initial detector match was a legitimate `Rp 500.000` tool value.
- Final verification gate: the latest `php artisan test --compact --colors=never` passed 154 tests and 816 assertions in 45.12s after the final dialog and Indonesian-validation changes; `php artisan migrate:status` showed every migration through `2026_09_21_000005` as `Ran`; Blade cache and the latest Vite production build completed successfully.
- A separate MySQL backup/restore rehearsal used only `logistik_test`: the 49,167-byte SQL dump restored into `logistik_restore_rehearsal_20260921`, with matching 31-table and 47-migration counts. The live `logistik` database was not used or modified.
- A parameterized `[TEST]` house detail route exposed an 846px material log table and a long house-code badge on mobile. Both house-detail material/tool logs now become labelled cards, the code wraps inside the header, and the detail plus completion routes were retested at 390px with no page-level or nested overflow.
- A parameterized `[TEST]` cluster expense route exposed a 1,245px history table on mobile. It now becomes labelled cards with rental evidence, status, amounts, and actions intact; the add-expense modal was opened and closed at 390px with no page overflow.
- House-cost detail was checked in both states at 390px: the empty-state message now wraps instead of clipping, and a populated `[TEST]` record with a long material name/note remains readable in the labelled card with no page-level or nested overflow.
- Supplier, category, cluster, and user-management routes were retested at 390px. Their dense tables now become labelled cards with edit/delete or expense actions visible; the supplier empty state wraps correctly, and the user creation modal opened and closed without field or page overflow.

## Verification limits

- This pass did not certify every route at every breakpoint, every keyboard/focus path, every loading/error state, or every interaction in both themes.
- The isolated MySQL backup/restore rehearsal is complete; production backup policy, the real company workbook, physical opening balances, cutover approval, and staff acceptance remain external production gates.
- The tool-balance model is now per warehouse, but production acceptance still requires reconciling the real workbook and physical counts against those balances.
- The browser sweep covers route rendering and selected interactions, not every loading/error/empty state or every form mutation on every breakpoint.
