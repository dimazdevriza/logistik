<?php

namespace App\Models;

use App\Support\ImportReconciliation;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ImportBatch extends Model
{
    protected $fillable = [
        'import_type',
        'file_name',
        'file_hash',
        'cutoff_date',
        'migration_mode',
        'user_id',
        'status',
        'total_rows',
        'successful_rows',
        'skipped_rows',
        'reconciliation_before',
        'reconciliation_after',
        'reconciliation_delta',
        'error_message',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'cutoff_date' => 'date',
            'reconciliation_before' => 'array',
            'reconciliation_after' => 'array',
            'reconciliation_delta' => 'array',
        ];
    }

    public static function run(string $type, UploadedFile $file, Closure $callback): mixed
    {
        $path = $file->getRealPath();
        $hash = hash_file('sha256', $path);

        if ($hash === false) {
            throw new RuntimeException('Berkas tidak dapat dibaca untuk pemeriksaan duplikat.');
        }

        $batch = static::where('import_type', $type)->where('file_hash', $hash)->first();

        if ($batch && $batch->status !== 'failed') {
            throw new RuntimeException('Berkas yang sama sudah pernah diimpor dan tidak diproses ulang.');
        }

        if ($batch) {
            $batch->update([
                'file_name' => $file->getClientOriginalName(),
                'user_id' => Auth::id(),
                'status' => 'processing',
                'error_message' => null,
                'completed_at' => null,
                'cutoff_date' => config('logistics.migration_cutoff_date'),
                'migration_mode' => config('logistics.migration_mode'),
            ]);
        } else {
            try {
                $batch = static::create([
                    'import_type' => $type,
                    'file_name' => $file->getClientOriginalName(),
                    'file_hash' => $hash,
                    'cutoff_date' => config('logistics.migration_cutoff_date'),
                    'migration_mode' => config('logistics.migration_mode'),
                    'user_id' => Auth::id(),
                    'status' => 'processing',
                ]);
            } catch (QueryException $exception) {
                if (static::where('import_type', $type)->where('file_hash', $hash)->exists()) {
                    throw new RuntimeException('Berkas yang sama sedang atau sudah pernah diimpor.', previous: $exception);
                }

                throw $exception;
            }
        }

        $before = ImportReconciliation::snapshot();
        $batch->update(['reconciliation_before' => $before]);

        try {
            return DB::transaction(function () use ($batch, $callback, $path, $before) {
                $result = $callback($path);
                $after = ImportReconciliation::snapshot();

                $batch->update([
                    'status' => 'completed',
                    'total_rows' => (int) ($result->totalRows ?? 0),
                    'successful_rows' => (int) ($result->successfulRows ?? 0),
                    'skipped_rows' => (int) ($result->skippedRows ?? 0),
                    'reconciliation_before' => $before,
                    'reconciliation_after' => $after,
                    'reconciliation_delta' => ImportReconciliation::delta($before, $after),
                    'completed_at' => now(),
                ]);

                return $result;
            });
        } catch (Throwable $exception) {
            $batch->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                'completed_at' => now(),
            ]);

            throw $exception;
        }
    }
}
