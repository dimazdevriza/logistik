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
        if ($user && ! $user->canAccessAllClusters()) {
            if (! $user->cluster_id) {
                throw new RuntimeException("Baris {$rowNumber} ({$item}): Pengguna belum ditugaskan ke cluster.");
            }

            if ($clusterId && ! $user->canAccessCluster($clusterId)) {
                throw new RuntimeException("Baris {$rowNumber} ({$item}): Rumah berada di luar cluster tugas Anda.");
            }

            $clusterId ??= $user->soleClusterId();
        }

        $isInfra = in_array(strtolower($reference), ['-', '', '—', 'infra', 'infrastruktur', 'fasum', 'fasilitas umum'], true);
        if ($isInfra) {
            $infra = House::query()
                ->when($clusterId, fn ($q) => $q->where('cluster_id', $clusterId))
                ->where(function ($q) {
                    $q->where('category', 'public_work')
                      ->orWhere('house_code', 'LIKE', '%INFRA%')
                      ->orWhere('name', 'LIKE', '%Infrastruktur%');
                })
                ->first();

            if ($infra) {
                return $infra;
            }
        }

        $candidates = array_values(array_unique(array_filter([
            $reference,
            'Blok ' . $reference,
            'Blok ' . strtoupper($reference),
            'VIL-' . strtoupper($reference),
            'VIL-' . $reference,
            strtoupper($reference),
            House::generateCode($reference),
        ])));

        $queryForCandidates = fn (array $lookupCandidates) => House::query()
            ->where(function ($query) use ($lookupCandidates) {
                $query->whereIn('house_code', $lookupCandidates)
                      ->orWhereIn('name', $lookupCandidates);
            })
            ->when($clusterId, fn ($query) => $query->where('cluster_id', $clusterId))
            ->when(! $clusterId && $user && ! $user->canAccessAllClusters(), fn ($query) => $query->whereIn('cluster_id', $user->scopedClusterIds()))
            ->limit(2)
            ->get();

        $matches = $queryForCandidates($candidates);

        $normalizedCandidates = [];
        if ($matches->isEmpty() && preg_match('/^(?:blok\s*|vil[\s\-]*|\d{4}[\s\-]*)?([a-zA-Z]+)[\s\-]*0*(\d+)$/i', $reference, $m)) {
            $block = strtoupper($m[1]);
            $numUnpadded = (string) ((int) $m[2]);
            $numPadded = str_pad($numUnpadded, 2, '0', STR_PAD_LEFT);

            $normalizedCandidates = array_values(array_unique([
                "Blok {$block}{$numUnpadded}",
                "Blok {$block}-{$numPadded}",
                "Blok {$block}-{$numUnpadded}",
                "Blok {$block}{$numPadded}",
                "{$block}{$numUnpadded}",
                "{$block}-{$numPadded}",
                "{$block}-{$numUnpadded}",
                "{$block}{$numPadded}",
                "VIL-{$block}{$numUnpadded}",
                "VIL-{$block}{$numPadded}",
                House::generateCode("Blok {$block}-{$numPadded}"),
                House::generateCode("Blok {$block}{$numUnpadded}"),
            ]));

            $matches = $queryForCandidates($normalizedCandidates);
        }

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->count() > 1) {
            throw new RuntimeException("Baris {$rowNumber} ({$item}): Referensi rumah '{$reference}' tidak unik. Tambahkan cluster atau kode rumah yang tepat.");
        }

        $allCandidates = array_values(array_unique(array_merge($candidates, $normalizedCandidates)));
        if ($user && ! $user->canAccessAllClusters() && House::query()
            ->where(fn ($query) => $query->whereIn('house_code', $allCandidates)->orWhereIn('name', $allCandidates))
            ->exists()) {
            throw new RuntimeException("Baris {$rowNumber} ({$item}): Rumah berada di luar cluster tugas Anda.");
        }

        $location = $clusterName ? " di cluster '{$clusterName}'" : '';

        throw new RuntimeException("Baris {$rowNumber} ({$item}): Blok rumah '{$reference}'{$location} belum terdaftar di sistem. Harap masukkan blok rumah yang valid (contoh: A1 atau Blok A-01) yang sudah terdaftar sebelum impor.");
    }
}
