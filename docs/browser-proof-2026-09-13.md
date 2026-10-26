# Local browser verification — 13 September 2026

Tested the running website at http://127.0.0.1:8000 using its normal login, material creation/edit forms, Excel upload dialog, transaction history, and house-cost report. All inventory writes were performed through the browser, not seeded through SQL or component calls.

## Test records left for inspection

- Material: UJI BROWSER 20260913 Semen, code BB-001, ID 70.
- House: UJI BROWSER 20260913 Rumah, code 2026-UJIBROWSER20260913RUMAH.
- Date: 13 September 2026 (Asia/Jakarta).
- Final stock: 9 sak. Current price: Rp150.
- Opening receipt: 10 sak × Rp100 = Rp1,000.
- Imported receipt: 2 sak × Rp100 = Rp200.
- Old allocation: 2 sak × Rp100 = Rp200.
- New allocation after price edit: 1 sak × Rp150 = Rp150.

## Results

| Check | Actual result |
| --- | --- |
| Incoming XLSX Volume. 2, Jumlah 200 | Stock 10 → 12, not 210. Import summary reports restock 2 sak. |
| Valid outgoing XLSX, 2 sak | Stock 12 → 10; allocation visible in history. |
| Two-row outgoing XLSX, 1 sak then 20 sak | Rejected at Excel row 3: available 9, requested 20. Entire file rolled back. Stock remains 10; neither attempted allocation persisted. |
| Edit price Rp100 → Rp150 | Save succeeds without server error; stock remains 10. |
| Historical allocation | Browser history retains 2 sak × Rp100 = Rp200. |
| New allocation with no positive price override | Browser history shows 1 sak × current Rp150 = Rp150; stock becomes 9. |
| Opening/incoming receipt preservation | Read-only database check confirms receipts still 10/Rp1,000 and 2/Rp200 at original Rp100 price. Browser cannot show these receipts because of defect below. |

## New confirmed defect: supplier-less receipts hidden

Catatan Material shows only the two outgoing records for this test material. Both incoming receipts exist in the database and correctly affect stock, but have null supplier_id. The incoming query in app/Livewire/Logistik/MaterialLog.php uses an inner join to suppliers, excluding receipts without a supplier. The creation form allows leaving supplier empty. This is a reporting defect, not loss of the receipt records.

Product code was not changed during this verification-only run. Recommended next fix: preserve incoming rows with an optional supplier in the log query and add a regression test.

## Limits

## Fix verified on 14 September 2026

Changed the optional supplier join to a left join in the material log, export rows, and export total. Browser verification now shows both previously hidden receipts: opening 10 sak at Rp100 (Rp1,000) and imported 2 sak at Rp100 (Rp200), with supplier shown as `-`. Both outgoing rows remain visible. No re-import or data repair was needed.

The new regression failed before the fix and passed afterward. It checks combined/incoming/outgoing views, supplier filtering, export records and actual XLSX footer totals. The focused material suite passed 10 tests with 77 assertions. Production deployment was not performed.

This is focused proof of the recent material fixes, not a full-system certification. Tool workflows, concurrent requests, production deployment and authentication-provider setup were not tested. Test records remain in local MySQL and contribute Rp350 to house costs. Existing business records were not edited or deleted.
