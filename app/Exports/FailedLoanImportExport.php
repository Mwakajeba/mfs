<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FailedLoanImportExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected $failedRecords;

    public function __construct(array $failedRecords)
    {
        $this->failedRecords = $failedRecords;
    }

    public function array(): array
    {
        $exportData = [];
        
        foreach ($this->failedRecords as $record) {
            $exportData[] = [
                $record['row_number'],
                $record['customer_name'] ?? '',
                $record['bank_name'] ?? '',
                $record['bank_account'] ?? '',
                $record['reference'] ?? '',
                $record['amount'] ?? '',
                $record['period'] ?? '',
                $record['interest'] ?? '',
                $record['date_applied'] ?? '',
                $record['interest_cycle'] ?? '',
                $record['loan_officer'] ?? '',
                $record['sector'] ?? '',
                $record['error_reason'] ?? 'Unknown error',
            ];
        }

        return $exportData;
    }

    public function headings(): array
    {
        return [
            ['FAILED LOAN IMPORT RECORDS'],
            ['Generated: ' . now()->format('Y-m-d H:i:s')],
            ['Total Failed Records: ' . count($this->failedRecords)],
            [],
            [
                'Row Number',
                'Customer Name',
                'Bank',
                'Bank Account',
                'Reference',
                'Amount',
                'Period',
                'Interest',
                'Date Applied',
                'Interest Cycle',
                'Loan Officer',
                'Sector',
                'Error Reason',
            ]
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
            5 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFF0000'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 28,
            'C' => 12,
            'D' => 18,
            'E' => 18,
            'F' => 15,
            'G' => 10,
            'H' => 12,
            'I' => 15,
            'J' => 16,
            'K' => 22,
            'L' => 15,
            'M' => 50,
        ];
    }

    public function title(): string
    {
        return 'Failed Records';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                $sheet->mergeCells('A1:M1');
                
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                
                if ($highestRow > 5) {
                    $sheet->getStyle('A5:' . $highestColumn . $highestRow)
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);
                    
                    $sheet->getStyle('F6:F' . $highestRow)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('H6:H' . $highestRow)->getNumberFormat()->setFormatCode('#,##0.00');
                    
                    $sheet->getStyle('A6:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('G6:G' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('I6:I' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    
                    $sheet->getStyle('F6:F' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('H6:H' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    
                    $sheet->getStyle('M6:M' . $highestRow)->getAlignment()->setWrapText(true);
                }
            },
        ];
    }
}
