<?php

namespace App\Exports;

use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LoanImportTemplateExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    public function array(): array
    {
        $branchId = auth()->user()->branch_id ?? null;
        $sampleOfficer = User::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->value('name') ?? '';

        return [
            ['John Borrower', 'NMB', '0123456789012', 'REF-001', 500000, 12.5, 12, 'monthly', Carbon::now()->subDays(5)->format('Y-m-d'), $sampleOfficer, 'Business'],
            ['Jane Borrower', 'CRDB', '9988776655443', '', 750000, 10, 6, 'monthly', Carbon::now()->subDays(10)->format('Y-m-d'), $sampleOfficer, 'Agriculture'],
        ];
    }

    public function headings(): array
    {
        return [
            'customer_name',
            'bank_name',
            'bank_account',
            'reference',
            'amount',
            'interest',
            'period',
            'interest_cycle',
            'date_applied',
            'loan_officer',
            'sector',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4472C4'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 12,
            'C' => 18,
            'D' => 18,
            'E' => 14,
            'F' => 12,
            'G' => 10,
            'H' => 16,
            'I' => 14,
            'J' => 28,
            'K' => 18,
        ];
    }

    public function title(): string
    {
        return 'Loans';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $spreadsheet = $sheet->getParent();

                $branchId = auth()->user()->branch_id ?? null;
                $officers = User::query()
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->orderBy('name')
                    ->get(['name']);

                if ($officers->isEmpty()) {
                    $officers = User::query()->orderBy('name')->limit(200)->get(['name']);
                }

                $helperSheet = $spreadsheet->createSheet();
                $helperSheet->setTitle('Data');
                $helperSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

                $interestCycles = ['daily', 'weekly', 'bimonthly', 'monthly', 'quarterly', 'semi_annually', 'annually', 'yearly'];
                $helperSheet->setCellValue('A1', 'Interest Cycles');
                foreach ($interestCycles as $i => $cycle) {
                    $helperSheet->setCellValue('A' . ($i + 2), $cycle);
                }
                $cycleEnd = count($interestCycles) + 1;

                $sectors = ['Agriculture', 'Business', 'Education', 'Health', 'Other'];
                $helperSheet->setCellValue('B1', 'Sectors');
                foreach ($sectors as $i => $sector) {
                    $helperSheet->setCellValue('B' . ($i + 2), $sector);
                }
                $sectorEnd = count($sectors) + 1;

                $banks = ['NMB', 'CRDB', 'NBC', 'ABSA'];
                $helperSheet->setCellValue('C1', 'Banks');
                foreach ($banks as $i => $bank) {
                    $helperSheet->setCellValue('C' . ($i + 2), $bank);
                }
                $bankEnd = count($banks) + 1;

                $helperSheet->setCellValue('D1', 'Loan Officers');
                foreach ($officers as $i => $officer) {
                    $helperSheet->setCellValue('D' . ($i + 2), $officer->name);
                }
                $officerEnd = max(2, $officers->count() + 1);

                $dataStartRow = 2;
                $dataEndRow = 500;

                for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
                    $bankValidation = new DataValidation();
                    $bankValidation->setType(DataValidation::TYPE_LIST);
                    $bankValidation->setErrorStyle(DataValidation::STYLE_STOP);
                    $bankValidation->setAllowBlank(false);
                    $bankValidation->setShowDropDown(true);
                    $bankValidation->setFormula1('Data!$C$2:$C$' . $bankEnd);
                    $sheet->getCell('B' . $row)->setDataValidation($bankValidation);

                    $cycleValidation = new DataValidation();
                    $cycleValidation->setType(DataValidation::TYPE_LIST);
                    $cycleValidation->setErrorStyle(DataValidation::STYLE_STOP);
                    $cycleValidation->setAllowBlank(false);
                    $cycleValidation->setShowDropDown(true);
                    $cycleValidation->setFormula1('Data!$A$2:$A$' . $cycleEnd);
                    $sheet->getCell('H' . $row)->setDataValidation($cycleValidation);

                    $sectorValidation = new DataValidation();
                    $sectorValidation->setType(DataValidation::TYPE_LIST);
                    $sectorValidation->setErrorStyle(DataValidation::STYLE_STOP);
                    $sectorValidation->setAllowBlank(false);
                    $sectorValidation->setShowDropDown(true);
                    $sectorValidation->setFormula1('Data!$B$2:$B$' . $sectorEnd);
                    $sheet->getCell('K' . $row)->setDataValidation($sectorValidation);

                    if ($officers->isNotEmpty()) {
                        $officerValidation = new DataValidation();
                        $officerValidation->setType(DataValidation::TYPE_LIST);
                        $officerValidation->setErrorStyle(DataValidation::STYLE_STOP);
                        $officerValidation->setAllowBlank(false);
                        $officerValidation->setShowDropDown(true);
                        $officerValidation->setFormula1('Data!$D$2:$D$' . $officerEnd);
                        $sheet->getCell('J' . $row)->setDataValidation($officerValidation);
                    }
                }

                $sheet->getStyle('A1:K1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->freezePane('A2');
            },
        ];
    }
}
