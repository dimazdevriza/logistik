# Production acceptance checklist

This checklist is the handoff boundary between local implementation and company cutover. It does not approve a migration, invent opening balances, or replace the company's physical count.

## 1. Owner decisions before import

- [ ] Confirm the workbook cutoff date.
- [ ] Confirm the migration policy: opening balance or chronological reconstruction.
- [x] Do not allow another active loan of the same tool to the same house while an earlier loan remains open. Owner confirmed 25 September 2026; existing validation enforces this rule.
- [x] Dispatch source rule: Logistik chooses source batches and may split a dispatch across gudang.
- [x] Use the existing void/correction path for mistaken consumed allocations. Partial physical material returns are outside the current workflow because allocation already means delivered and used.

Record each decision, approver, date, and any exception in the import batch notes.

## 2. Workbook and physical-count rehearsal

Read-only candidate inspection, 25 September 2026: `TESTES.xlsx..xlsx` (SHA-256 `41621387253D81EAB2E25E13A14166848B02034E0C04A8B725A0478B58D3538A`) has 33 sheets. `Katalog Barang` has 1,094 distinct item codes; 343 rows have no numeric price. `M.KELUAR` records dated withdrawals with house, cluster, item code, quantity, and price. `M.MASUK` stores receipts across date columns, including an apparent `2024-01-06` date among October 2024 columns. Summary sheets contain negative calculated stock and broken `#REF!` formulas (231 in `Material Masuk (Jangan di Ubah)`). Treat this as one candidate source, not an accepted opening balance. Confirm which sheets and dates are authoritative with the company, then compare against the latest physical count. No import or production data change was made during this inspection.

Source profiling: `M.KELUAR` has 31,920 rows with item codes, dated from 1 October 2024 to 30 January 2026; 190 coded rows lack a date, 23 lack a positive numeric volume, 1,868 lack a positive numeric price, and 2,407 lack a house block. All coded rows name `AMORA` as cluster; house-free work needs an explicit expense mapping. `M.MASUK` has 5,334 nonzero numeric receipt cells across 455 date columns. One receipt falls under `2024-01-06` amid October 2024 columns, and 21 fall under a `1900-10-29` header. Do not guess corrected dates. `Stok gudang` contains 165 coded rows but its entered dates stop on 2 August 2025; it is not evidence of the latest physical count. The existing flat-row import format cannot directly represent the receipt grid or resolve the missing dates and locations. Extract and review candidate rows only after source authority, warehouse mapping, and migration policy are agreed.

Plain material inventory rows in the standard importer are now recorded as selectable `opening_balance` batches with no arrival time or receipt proof; negative opening quantities are rejected for reconciliation. Tool inventory rows are visibly marked `ALT-OPEN`. This makes the application’s import behavior traceable; it does not validate or import this candidate workbook.

- [ ] Preserve the source workbook unchanged and record its filename, export date, and checksum.
- [ ] Copy the workbook into an isolated MySQL rehearsal environment, never the live `logistik` database.
- [ ] Map every gudang, cluster, house, material, tool, supplier, and outstanding tool loan explicitly.
- [ ] Resolve every ambiguous or unmapped row before import; do not silently choose the first gudang.
- [ ] Reconcile material lots by warehouse, name, unit, category, supplier, and price. Different prices must remain separate lots.
- [ ] Reconcile tool totals, broken quantities, per-gudang balances, and active house loans.
- [ ] Reconcile historical house costs and cluster expenses without counting transfers as consumption or charging cluster expenses to houses twice.
- [ ] Compare imported balances with the physical count and record every difference.
- [ ] Obtain company acceptance of the reconciliation report before production import.

## 3. Representative workflow acceptance

Run each workflow with labelled rehearsal records and record the resulting transaction code:

- [ ] Import opening stock and verify the before/after reconciliation snapshot.
- [x] Move a material lot between gudang and verify source, destination, price lot, and transfer audit links.
- [ ] Allocate material directly to one and multiple houses; verify stock deduction and house cost.
- [ ] Allocate and return a tool partially, including good, broken, and lost quantities.
- [ ] Submit, dispatch, approve, reject, and physically receive a material/tool request.
- [ ] Add a rental, extension charge, bill image, off-hire status, and cluster-cost total.
- [ ] Finish a house only after tools and requests are fully accounted for.
- [ ] Verify reports show house subtotal, direct cluster subtotal, and combined total without double counting.
- [ ] Verify duplicate submission, insufficient stock, forbidden cluster access, and completed-house restrictions.

## 4. Cutover readiness

- [x] Test a backup and restore into a separate MySQL database. Local rehearsal completed from isolated `logistik_test` into `logistik_restore_rehearsal_20260921`; production `logistik` was not touched.
- [ ] Confirm staff roles, assigned clusters, warehouse responsibilities, and escalation contacts.
- [ ] Agree the exact Excel freeze time and the first system transaction time.
- [ ] Keep the original workbook read-only after cutover; do not delete it or test fixtures without explicit authorization.
- [ ] Obtain written approval to switch daily operations from Excel to this system.

## Current implementation evidence

- MySQL feature suite: 156 passing tests, 830 assertions; focused workflow regression run: 38 passing tests, 207 assertions.
- Import reconciliation regression: 13 passing tests, 59 assertions. Browser import on port 8011 persisted per-warehouse value delta for a labeled `[TEST]` material in isolated `logistik_test`.
- Material transfer rehearsal, 25 September 2026: `[TEST] Browser Transfer Source 20260925`, 4.5 sak, moved from `Gudang Utama` to `[TEST] Gudang Transfer 20260925`. Transfer `TRF-01M3AYANSJ78T4G3HJ586PR03W` links receipt `RCV-01M3AY8W6JAK5GB7T9MAEAABTB` to its destination batch. Browser showed the frozen Rp 97.500 batch price after catalog price changed to Rp 125.000; material history classified it as a transfer with Rp 0 added cost. Focused `RemainingTasksTest`: 1 test / 32 assertions passed, including dispatch-picker eligibility and export classification. One existing browser tab on port 8011; isolated MySQL `logistik_test`; no console errors.
- Consumed-allocation correction regression: 2 passing tests, 13 assertions; void restores stock, reverses cost totals, keeps the audit row, and rejects double void.
- Focused Mandor/search regression suite: 9 passing tests, 54 assertions.
- Browser evidence: [`browser-audit-2026-09-21.md`](browser-audit-2026-09-21.md).
- Latest browser rehearsal: Mandor submitted a labelled material request, Logistik dispatched it from a recorded gudang, Mandor uploaded arrival proof, and Logistik approved it. A separate labelled tool request reached `Ditolak, menunggu kembali` after dispatch, arrival proof, and rejection. Physical-return completion remains covered by automated MySQL tests because the browser-native confirmation prompt did not expose an automation accept handle.
- Backup rehearsal evidence: `.tmp/backup-rehearsal/logistik_test-20260921.sql` (SHA-256 `F2C3D0672ADBD475F4299664E35D903ECABC256DD7699ECE6A3CBC4E75F9A144`); restore verification matched 31 tables and 47 migration rows in the separate MySQL database.
- Business rules and known limits: [`system.md`](../system.md).
- Open implementation and acceptance ledger: [`task.md`](../task.md).
