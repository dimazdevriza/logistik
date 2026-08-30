<?php

namespace App\Models;

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
        'user_id',
        'status',
        'total_rows',
        'successful_rows',
        'skipped_rows',
        'error_message',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
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
            ]);
        } else {
            try {
                $batch = static::create([
                    'import_type' => $type,
                    'file_name' => $file->getClientOriginalName(),
                    'file_hash' => $hash,
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

        try {
            return DB::transaction(function () use ($batch, $callback, $path) {
                $result = $callback($path);

                $batch->update([
                    'status' => 'completed',
                    'total_rows' => (int) ($result->totalRows ?? 0),
                    'successful_rows' => (int) ($result->successfulRows ?? 0),
                    'skipped_rows' => (int) ($result->skippedRows ?? 0),
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
