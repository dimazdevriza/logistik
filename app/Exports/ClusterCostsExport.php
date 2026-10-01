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
        $usedTitles = [];
        $sheets = [new HouseListExport(
            year: (int) now()->year,
            clusterScope: (int) $this->cluster->id,
            sheetTitle: $this->sheetTitle($this->cluster->name, $usedTitles),
            reportTitle: 'Laporan Biaya Cluster: '.$this->cluster->name,
        )];

        foreach ($this->cluster->houses()->orderBy('name')->orderBy('id')->get() as $house) {
            $sheets[] = new ClusterCostsSheet($this->cluster, $house, $this->sheetTitle($house->name, $usedTitles));
        }

        return $sheets;
    }

    private function sheetTitle(string $name, array &$usedTitles): string
    {
        $base = preg_replace('/[\\\\\/?*\[\]:]/u', ' ', trim($name));
        $base = trim(preg_replace('/\s+/u', ' ', $base), " '\"");
        $base = mb_substr($base !== '' ? $base : 'Biaya', 0, 31);
        $title = $base;
        $suffixNumber = 2;

        while (in_array(mb_strtolower($title), $usedTitles, true)) {
            $suffix = ' ('.$suffixNumber++.')';
            $title = mb_substr($base, 0, 31 - mb_strlen($suffix)).$suffix;
        }

        $usedTitles[] = mb_strtolower($title);

        return $title;
    }
}
