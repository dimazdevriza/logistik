# To-do list

## Open findings — 18 September 2026 rescan

Code-review findings, pending reproduction tests and fixes. These expose gaps in the earlier hardening below; the 128 passing tests and 12-page browser smoke test did not cover these scenarios. Listed in implementation order.

- [x] **Prevent house imports from inflating tool stock (high).** Completed 18 September 2026: returns now require a matching active loan, reject excess quantities, preserve partial-loan remainders, and remain replay-safe.
- [x] **Require real stock for material consumption in house imports (high).** Completed 18 September 2026: house imports require a material already registered in the selected warehouse with sufficient stock; unknown or insufficient inventory rolls back the sheet transaction.
- [x] **Account for goods when dispatched (high).** Completed 18 September 2026: dispatch reserves material/tool stock immediately under a row lock; approval no longer deducts a second time, and rejection releases the reservation.
- [x] **Enforce house status transitions during Excel import (high).** Completed 18 September 2026: imports preserve omitted status, reject reopening completed houses, and reject completion while active tool loans remain.
- [x] **Reject malformed house-import quantities and dates (medium).** Completed 18 September 2026: fractional tool quantities and supplied invalid house dates now fail with row-specific errors before writes are committed.
- [x] **Use the operational date in dispatch transaction history (medium).** Completed 18 September 2026: material usage and tool checkout records use arrival date, then dispatch date, while approval time remains separate.

Verification: focused integrity/import suite passed (16 tests, 93 assertions); full suite passed (135 tests, 698 assertions); authenticated browser smoke test passed on 10 key pages with no 500/exception page.

For each item: reproduce the scenario, add a regression test, implement the fix, and verify the affected browser workflow while preserving existing business data.

## Remaining audit issues

Added 14 September 2026. Source: [system audit](system-audit-2026-09-10.md). Listed in recommended implementation order.

- [x] **Protect material transaction history from deletion (high priority).** Completed locally 14 September 2026: application deletion rejects materials with receipts, usages (including voided usages), or requests. Checks run before confirmation and again under a material-row lock. Browser verified rejection on BB-001; stock and all four transactions remain unchanged. Focused suite: 4 tests, 68 assertions. This protects the material deletion workflow, not direct database deletes or cascades from deleting other entities. Not deployed to production.
- [x] **Account for active tool loans when editing stock (high priority).** Completed locally 14 September 2026: save validates available + broken + active loans against total under a tool-row lock; returned and voided loans are excluded. The edit badge now reads actual active loans instead of deriving them from edited quantities. Browser verified ALT-HSE-20260831-A (total 1, loaned 1, available 0): changing available to 1 is rejected; cancelling preserves total 1, broken 0, available 0. Focused stock, transaction, and import suite: 25 tests, 146 assertions passed. Not deployed to production.
- [x] **Prevent duplicate transactions from modified Excel files (high priority).** Completed locally 17 September 2026: material receipts/usages and tool checkout/returns now use a transaction code. Staff may supply optional `Kode Transaksi`; otherwise the importer generates a stable fingerprint from the transaction fields. Database unique indexes prevent a changed irrelevant CSV/Excel field from replaying a transaction, while a new explicit code allows a legitimate matching transaction. Focused import-integrity suite: 8 tests, 32 assertions. Not deployed to production.
- [x] **Reject invalid import dates (medium priority).** Completed locally 17 September 2026: material and tool transactions accept only ISO, Indonesian-style, or Excel serial dates. Invalid/missing dates name the offending row and fail the batch before inventory changes. Browser verified an invalid material CSV was rejected and did not create `QA Browser Date Guard`; focused import-integrity suite: 8 tests, 32 assertions. Not deployed to production.
- [x] **Make tool Excel imports stock-safe (high priority).** Completed locally 17 September 2026: checkout verifies available stock under a tool lock, returns require an active loan, and partial returns retain the remaining loan rather than inflating stock. Re-uploaded checkout/return rows are skipped before stock checks. Focused import-integrity suite: 8 tests, 32 assertions. Not deployed to production.

For each fix: add focused regression coverage and verify the affected workflow in the local browser. Preserve existing business data during testing.

## Follow-up functionality hardening

Completed 18 September 2026 after the broader workflow audit:

- [x] **Protect operational history.** Materials, tools, houses, and users with receipts, usages, requests, returns, or inventory adjustments cannot be deleted through the application. Local MySQL also now uses restrictive foreign keys for operational records.
- [x] **Audit direct stock corrections.** Manual material and tool stock changes create `inventory_adjustments` records with the user, before/after values, delta, and reason.
- [x] **Harden house completion.** Completion accepts only valid actions, rejects foreign or stale usage selections, locks the house and usages, and preserves the selected house context in return records.
- [x] **Harden dispatch and approval.** Dispatch and approval recheck status, finished houses, stock, and active tool loans inside transactions; approved records retain the request code. Mandor requests are routed and proof access is requester-scoped.
- [x] **Harden house imports.** Invalid dates fail instead of becoming today, duplicate transaction fingerprints are skipped, known stock is deducted safely, finished houses are rejected, and cross-warehouse tool codes fail clearly.
- [x] **Harden tool imports.** Condition aliases map to the database enum and name matching is limited to the selected warehouse.

Verification: focused import suite passed (25 tests, 146 assertions); full suite passed (128 tests, 666 assertions); Blade cache, route registration, and whitespace checks passed; authenticated browser smoke test passed on 12 key pages against local MySQL. Local MySQL migrations `2026_09_18_000001` and `2026_09_18_000002` were applied successfully.
