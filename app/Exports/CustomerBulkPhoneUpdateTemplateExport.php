<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CustomerBulkPhoneUpdateTemplateExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    public function array(): array
    {
        return [
            ['John Doe', 'NMB', '0123456789012', '0712345678'],
            ['Jane Smith', 'CRDB', '9876543210001', '+255713222111'],
            ['Peter Ally', 'NBC', '1100220033004', '255714444555'],
        ];
    }

    public function headings(): array
    {
        return [
            'name',
            'bank_name',
            'bank_account',
            'phone1',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E88E5'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 14,
            'C' => 20,
            'D' => 16,
        ];
    }

    public function title(): string
    {
        return 'Phone Update';
    }
}

