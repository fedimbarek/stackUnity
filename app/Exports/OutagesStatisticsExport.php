<?php

namespace App\Exports;

use App\Services\StatisticsService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class OutagesStatisticsExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private Carbon $from,
        private Carbon $to,
    ) {}

    public function collection()
    {
        return app(StatisticsService::class)->byNeighborhood($this->from, $this->to);
    }

    public function headings(): array
    {
        return ['Quartier', 'Nombre de coupures'];
    }

    public function title(): string
    {
        return 'Statistiques '.$this->from->format('d-m-Y').' au '.$this->to->format('d-m-Y');
    }
}