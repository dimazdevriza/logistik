# System audit — 10 September 2026

## Follow-up — 13 September 2026

Issue 4 is now fixed locally: incoming rows in the transaction template use `Volume` as quantity, never the monetary `Jumlah` column. Blank, nonnumeric, zero, or negative incoming volumes reject the file with a row error. Fractional quantities and `masuk`/`restock`/`in` aliases are covered; legacy inventory-format quantity handling is unchanged. Existing incorrect records have not been recalculated. Verification: 8 stock-import tests / 53 assertions passed on local MySQL with rollback; 17 related import tests / 114 assertions passed in the isolated suite. The four new incoming tests failed before the patch and passed after it.

Issue 3 is now fixed in `MaterialImport`: outgoing allocations lock the selected material, check available stock, and deduct the full accepted quantity. Insufficient stock throws a row-numbered error and rolls back the file through the existing import transaction; the batch is marked failed. Transaction aliases and house allocations can no longer initialize inventory from requested quantity. Outgoing templates now require existing stock (or preceding inventory/receipt rows). Existing test fixtures were updated to supply that legitimate opening stock. This change does not alter the separate house-import implementation or fix incoming-template quantity parsing.

Stock-import verification: 4 tests / 17 assertions passed on local MySQL using rollback transactions; 13 related importer tests / 78 assertions passed in the default isolated suite. Coverage includes cumulative shortage and rollback, exact/fractional deductions, the `out` alias loophole, and the visible import-modal error plus failed-batch status.

Issue 1 (material price-edit crash) is fixed locally. Material edits now update the inventory record without rewriting historical usages or opening receipts. This also removes the opening-receipt rewrite described in issue 2. The edit-form help and success message reflect this behavior. Existing historical data has not been repaired or recalculated; direct stock adjustments and the remaining audit findings are outside this fix.

Verification: reproduced the missing-class error on local MySQL before the change, then passed the price-edit regression on the same local MySQL connection (1 test, 12 assertions), using a transaction rolled back after testing and no schema refresh. The test proves existing receipt/usage records stay identical and a new allocation uses the changed price. The related regression/transaction suites also passed under the repository's default isolated test configuration (16 tests, 71 assertions). PHP syntax and diff whitespace checks passed.

Permanent regression: `tests/Feature/MaterialPriceEditTest.php`. Local MySQL rollback runner: `.tmp/mysql-price-edit.xml` and `.tmp/MysqlMaterialPriceEditTest.php`. The original audit below describes the pre-fix state.

## Conclusion

The application loads and its existing tests pass, but it is not yet reliable enough to be the sole inventory and transaction record. Additional targeted tests reproduced eight business/data-integrity defects and one SQLite compatibility failure. This audit did not implement application fixes.

## Environment and scope

- Browser: http://127.0.0.1:8000; administrator login succeeded using the previously supplied local credentials. http://logistik.test refused connections.
- Automated tests: PHP 8.5.8, SQLite in memory, RefreshDatabase; no company database mutations from these tests.
- Existing suite: `php artisan test` — **90 passed, 316 assertions**, 71.59 seconds.
- Production assets: `npm.cmd run build` — **passed**. Sandbox initially blocked esbuild; the approved build succeeded. No tracked application diff resulted.
- Isolated migrations: all migrations and seeders succeeded in a disposable test database before the working port 8000 was supplied. No second server was started.
- Additional audit checks: 11 distinct tests, **2 passed, 8 assertion failures and 1 runtime error** on their final runs. The eight business defects below account for seven assertion failures and the runtime error; the remaining failure is SQLite compatibility.
- Six export paths generated XLSX bytes and reopened successfully: material inventory, tool inventory, material log, tool log, house list, and house detail (material/tool sheets). This checks workbook generation and presence of rows, not every cell's business correctness.
- Staff role: admin users and cost pages returned 403; material inventory returned 200.
- Formatting gate: repository-wide Pint stopped on an inaccessible temporary document-render directory. A scoped check over application/configuration/test directories ran and found pre-existing formatting violations. No formatting changes were applied.

## Browser coverage

The following 18 application pages loaded without a visible server error: dashboard; material, tool, supplier and category lists; house list; transaction page; material and tool logs; house costs; account management; profile; password; 2FA settings; appearance; house detail; house completion; house cost detail. The password-confirmation page also rendered and accepted the existing password before 2FA settings.

Additional interactions checked: material search narrowed to Baja Ringan C75; filter modal exposed sorting/category/supplier/stock/photo choices; all three transaction tabs rendered; selecting Blok A-02 populated its three active tool loans. Transaction forms showed today's date (10 September 2026). Profile/sidebar desktop layout was visually inspected.

Existing company records were not edited, imported, deleted, or used to submit transactions through the browser. Mutation scenarios ran in isolated automated tests. This was not an exhaustive click-by-click browser exercise of every control.

## Reproduced defects

### 1. High — changing material price crashes

`app/Livewire/Logistik/Materials.php:251` calls `MaterialUsage` without importing its model. Editing a material price from 100 to 150 raises `Class "App\Livewire\Logistik\MaterialUsage" not found`.

The unreachable loop also intends to reprice prior usage records. When fixing the crash, preserve original transaction price snapshots and provide a separate recorded correction workflow if required. The earlier code-only assessment overstated repricing as working behavior; the runtime audit establishes that this branch currently errors.

### 2. High — renaming material rewrites the opening receipt

`app/Livewire/Logistik/Materials.php:263-274`. Fixture: opening receipt 10, usage 2, current stock 8. Change only the name and save. The opening receipt quantity changes from **10 to 8**. Editing descriptive fields must not rewrite historical stock movement.

### 3. High — outgoing material import does not reconcile with available stock

`app/Imports/MaterialImport.php:195-220`. Fixture: stock 8; import an outgoing volume of 20. The importer records usage 20 but deducts only 8, leaving zero. Remaining stock plus issued quantity becomes 20 rather than the opening 8. Normal operational imports need stock validation; historical migration, if supported, needs an explicit reconciliation mode.

### 4. High — incoming material template uses money as quantity

`app/Imports/MaterialImport.php:160`. Fixture: `jenis=masuk`, `volume=2`, `harga_satuan=100`, `jumlah=200`. The importer records **200 units instead of 2**. The outgoing-format template recognizes volume, but the incoming branch reads jumlah. This inflates stock and receipt value.

### 5. Medium — invalid import dates silently become today

`app/Imports/MaterialImport.php`, date parsing catch block. Import `tanggal=not-a-date`: a transaction is saved instead of rejecting the row. Report an actionable row error instead of substituting a date.

### 6. High — deleting material deletes transaction history

`app/Livewire/Logistik/Materials.php:310` and `database/migrations/2026_03_04_000006_create_material_usages_table.php`. Delete a material with one usage through its component action. Usage count falls from **1 to 0** due to cascading deletion. Archive referenced materials or prevent their deletion; historical reports must retain their source records.

### 7. High — tool edits ignore active loans

`app/Livewire/Logistik/Tools.php`, save validation. Fixture: total 5, available 2, active loan 3. Editing available to 5 succeeds because the guard checks only available plus broken. The system then represents **8 units against a total of 5**. Include active loans in reconciliation and use recorded adjustment actions.

### 8. High — changed workbook can duplicate transactions

`app/Models/ImportBatch.php` and `app/Imports/MaterialImport.php`. Import two workbooks containing the same transaction, changing only an unused `audit_revision` column. Both pass the whole-file hash check; usage count becomes **2 instead of 1**. Exact-file duplicate prevention works, but transaction-level identity/deduplication is missing for this scenario.

## Environment-specific failure

`app/Livewire/Admin/HouseCosts.php:81` uses `YEAR(usage_date)` directly. `/admin/house-costs` fails in SQLite tests with `no such function: YEAR`; the other 16 routes in the extended route check passed. The running local website's cost page loaded successfully. Treat this as a portability/test-coverage issue, not a confirmed failure on the running website's database.

## Reproduction artifacts

- `.tmp/SystemAuditTest.php` — eleven isolated checks, intentionally left failing where a defect is present.
- `.tmp/system-audit-results.xml` — first seven integrity checks.
- `.tmp/system-audit-extra.xml` — exports/access checks and initial extra-check run.
- `.tmp/system-audit-followup.xml` — final route matrix and revised duplicate reproduction. This supersedes the initial duplicate test, whose empty-row change produced identical workbook bytes and correctly triggered the existing duplicate-file guard.

Run all additional checks: `php vendor/phpunit/phpunit/phpunit .tmp/SystemAuditTest.php`.

## Limits and next work

No production deployment, hosting backup/restore drill, real Google OAuth exchange, real Authenticator enrollment/code entry, simultaneous-user load test, or mobile-device matrix was performed. Existing automated authentication tests include mocked Google behavior and 2FA rendering/redirects; those do not prove external provider configuration. Existing test passes are not proof of concurrent-write correctness.

Prioritize receipt quantity parsing, history preservation/deletion protection, price-edit crash, stock/tool reconciliation, transaction duplicate prevention, and invalid-date rejection. Add the reproduced cases to permanent regression coverage when fixing them. Stok opname and broader audit history remain useful improvements after these defects are addressed.
