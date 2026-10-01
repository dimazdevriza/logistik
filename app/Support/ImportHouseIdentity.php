<?php

namespace App\Support;

use App\Models\Cluster;
use App\Models\House;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

final class ImportHouseIdentity
{
    public static function resolve(string $reference, int $rowNumber, string $item, ?string $clusterName = null): House
    {
        $reference = trim($reference);
        $cluster = $clusterName ? Cluster::where('name', trim($clusterName))->first() : null;

        if ($clusterName && ! $cluster) {
            throw new RuntimeException("Baris {$rowNumber} ({$item}): Cluster '{$clusterName}' tidak ditemukan.");
        }

        $user = Auth::user();
        $clusterId = $cluster?->id;
        if ($user && $user->role !== 'admin') {
            if (! $user->cluster_id) {
                throw new RuntimeException("Baris {$rowNumber} ({$item}): Pengguna belum ditugaskan ke cluster.");
            }

            if ($clusterId && (int) $clusterId !== (int) $user->cluster_id) {
                throw new RuntimeException("Baris {$rowNumber} ({$item}): Rumah berada di luar cluster tugas Anda.");
            }

            $clusterId = (int) $user->cluster_id;
        }

        $matches = House::query()
            ->where(fn ($query) => $query->where('house_code', $reference)->orWhere('name', $reference))
            ->when($clusterId, fn ($query) => $query->where('cluster_id', $clusterId))
            ->limit(2)
            ->get();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->count() > 1) {
            throw new RuntimeException("Baris {$rowNumber} ({$item}): Referensi rumah '{$reference}' tidak unik. Tambahkan cluster atau kode rumah yang tepat.");
        }

        if ($user && $user->role !== 'admin' && House::query()
            ->where(fn ($query) => $query->where('house_code', $reference)->orWhere('name', $reference))
            ->exists()) {
            throw new RuntimeException("Baris {$rowNumber} ({$item}): Rumah berada di luar cluster tugas Anda.");
        }

        $location = $clusterName ? " di cluster '{$clusterName}'" : '';

        throw new RuntimeException("Baris {$rowNumber} ({$item}): Rumah '{$reference}'{$location} belum terdaftar. Cocokkan kode rumah dan cluster sebelum impor.");
    }
}
