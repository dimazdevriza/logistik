<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Excel migration policy
    |--------------------------------------------------------------------------
    |
    | Suggested default for a future opening-balance migration. The company
    | must confirm the cutoff and opening quantities before importing; older
    | movements must not be replayed against an accepted opening balance.
    |
    */
    'migration_cutoff_date' => env('LOGISTICS_MIGRATION_CUTOFF_DATE', '2026-09-19'),
    'migration_mode' => env('LOGISTICS_MIGRATION_MODE', 'opening_balance'),
];
