<?php

namespace App\Exports;

use App\Models\Cluster;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClusterCostsExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private Cluster $cluster) {}

    public function sheets(): array
    {
        $clusterTitle = mb_substr(preg_replace('/[\\\\\/?*\[\]:]/u', ' ', trim($this->cluster->name)), 0, 31);
        $clusterTitle = trim(preg_replace('/\s+/u', ' ', $clusterTitle), " '\"") ?: 'Cluster';

        return [
            new HouseListExport(
                year: (int) now()->year,
                clusterScope: (int) $this->cluster->id,
                sheetTitle: $clusterTitle,
                reportTitle: 'Laporan Biaya Cluster: '.$this->cluster->name,
                category: null,
            ),
            new ClusterMaterialExport($this->cluster),
            new ClusterToolExport($this->cluster),
            new ClusterVendorServiceExport($this->cluster),
            new ClusterVendorRentalExport($this->cluster),
        ];
    }
}
