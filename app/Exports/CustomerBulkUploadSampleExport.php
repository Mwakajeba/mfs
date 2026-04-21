<?php

namespace App\Exports;

use App\Models\Region;
use App\Models\District;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CustomerBulkUploadSampleExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    public function array(): array
    {
        return [
            // A few example rows; users can delete these rows and keep the header.
            ['John Doe', 'NMB', '0123456789012', '255712345678', 'M', '', ''],
            ['Jane Smith', 'CRDB', '9876543210001', '255713222111', 'F', '', ''],
            ['Peter Ally', 'NBC', '1100220033004', '255714444555', 'M', '', ''],
        ];
    }

    public function headings(): array
    {
        return [
            'name',
            'bank_name',
            'bank_account',
            'phone1',
            'sex',
            'region_id',
            'district_id',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4472C4']
                ],
                'font' => ['color' => ['argb' => 'FFFFFFFF'], 'bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28,  // name
            'B' => 12,  // bank_name
            'C' => 18,  // bank_account
            'D' => 16,  // phone1
            'E' => 8,   // sex
            'F' => 22,  // region_id
            'G' => 22,  // district_id
        ];
    }

    public function title(): string
    {
        return 'Customers';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $spreadsheet = $sheet->getParent();
                
                // Get regions and districts for dropdowns
                $regions = Region::orderBy('name')->get();
                $districts = District::orderBy('name')->get();
                
                // Create helper sheet for dropdown data (hidden)
                $helperSheet = $spreadsheet->createSheet();
                $helperSheet->setTitle('Data');
                $helperSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
                
                // Regions list in Column A
                $helperSheet->setCellValue('A1', 'Regions');
                foreach ($regions as $idx => $region) {
                    $helperSheet->setCellValue('A' . ($idx + 2), $region->name);
                }
                $regionListEnd = count($regions) + 1;

                // Bank list in Column B
                $banks = ['NMB', 'CRDB', 'NBC', 'ABSA'];
                $helperSheet->setCellValue('B1', 'Banks');
                foreach ($banks as $idx => $bank) {
                    $helperSheet->setCellValue('B' . ($idx + 2), $bank);
                }
                $bankListEnd = count($banks) + 1;

                // Districts by region in columns starting at C
                // Create a named range per region so District validation can use INDIRECT.
                $regionColumnStartIndex = 3; // Column C
                $regionToNamedRange = [];

                foreach ($regions as $rIdx => $region) {
                    $safeName = Str::of($region->name)->upper()->replaceMatches('/[^A-Z0-9_]/', '_')->toString();
                    if (preg_match('/^[0-9]/', $safeName)) {
                        $safeName = 'R_' . $safeName;
                    }

                    $colIndex = $regionColumnStartIndex + $rIdx;
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $helperSheet->setCellValue($colLetter . '1', $safeName);

                    $regionDistricts = $districts->where('region_id', $region->id)->values();
                    foreach ($regionDistricts as $dIdx => $district) {
                        $helperSheet->setCellValue($colLetter . ($dIdx + 2), $district->name);
                    }

                    $endRow = max(2, count($regionDistricts) + 1);
                    $range = "Data!\${$colLetter}\$2:\${$colLetter}\${$endRow}";
                    $spreadsheet->addNamedRange(new NamedRange($safeName, $helperSheet, $range));
                    $regionToNamedRange[$region->name] = $safeName;
                }

                // Data rows: header row is 1, data starts at 2. Provide validations down to row 500.
                $dataStartRow = 2;
                $dataEndRow = 500;
                
                // Bank dropdown (Column B)
                $bankValidation = new DataValidation();
                $bankValidation->setType(DataValidation::TYPE_LIST);
                $bankValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $bankValidation->setAllowBlank(false);
                $bankValidation->setShowInputMessage(true);
                $bankValidation->setShowErrorMessage(true);
                $bankValidation->setShowDropDown(true);
                $bankValidation->setFormula1('Data!$B$2:$B$' . $bankListEnd);

                // Add sex dropdown (Column E)
                $sexValidation = new DataValidation();
                $sexValidation->setType(DataValidation::TYPE_LIST);
                $sexValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $sexValidation->setAllowBlank(false);
                $sexValidation->setShowInputMessage(true);
                $sexValidation->setShowErrorMessage(true);
                $sexValidation->setShowDropDown(true);
                $sexValidation->setFormula1('"M,F"');
                
                // Add region dropdown (Column F) using helper sheet
                $regionValidation = new DataValidation();
                $regionValidation->setType(DataValidation::TYPE_LIST);
                $regionValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $regionValidation->setAllowBlank(false);
                $regionValidation->setShowInputMessage(true);
                $regionValidation->setShowErrorMessage(true);
                $regionValidation->setShowDropDown(true);
                $regionValidation->setFormula1('Data!$A$2:$A$' . $regionListEnd);
                
                // Add district dropdown (Column G) dependent on selected region
                $districtValidation = new DataValidation();
                $districtValidation->setType(DataValidation::TYPE_LIST);
                $districtValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $districtValidation->setAllowBlank(false);
                $districtValidation->setShowInputMessage(true);
                $districtValidation->setShowErrorMessage(true);
                $districtValidation->setShowDropDown(true);
                // Formula will be set per-row to reference the region cell.
                
                // Apply validations to all data rows
                for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
                    // Bank dropdown
                    $sheet->getCell('B' . $row)->setDataValidation(clone $bankValidation);
                    // Sex dropdown
                    $sheet->getCell('E' . $row)->setDataValidation(clone $sexValidation);
                    // Region dropdown
                    $sheet->getCell('F' . $row)->setDataValidation(clone $regionValidation);
                    // District dropdown
                    $dv = clone $districtValidation;
                    // Use selected region (F) → sanitize to match named range.
                    // Example: =INDIRECT("R_"&SUBSTITUTE(UPPER($F2)," ","_")) if needed, but we stored names as sanitized region names.
                    $dv->setFormula1('=INDIRECT(SUBSTITUTE(SUBSTITUTE(UPPER($F' . $row . ')," ","_"),"-","_"))');
                    $sheet->getCell('G' . $row)->setDataValidation($dv);
                }
                
                // Add borders to header row (row 1) and freeze it
                $sheet->getStyle('A1:G1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->freezePane('A2');
            },
        ];
    }
}
