<?php

namespace App\Service;

use App\Entity\Job;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AlternanceExcelService
{
    private const EXCEL_PATH = 'var/exports/alternance_tracker.xlsx';

    private const HEADERS = [
        '# ID Offre',
        'Date ajout',
        'Intitulé du poste',
        'Nom entreprise',
        'Localisation',
        'Contact entreprise',
        'Lien offre',
        'Entretien fait ?',
        'Entretien obtenu ?',
        'Notes',
    ];

    private const COL_WIDTHS = [16, 14, 40, 30, 22, 30, 50, 18, 18, 35];

    public function __construct(private string $projectDir) {}
    public function appendJobs(array $jobs): string
    {
        $filePath = $this->projectDir . '/' . self::EXCEL_PATH;
        $this->ensureDir($filePath);

        if (file_exists($filePath)) {
            $spreadsheet = IOFactory::load($filePath);
            $sheet       = $spreadsheet->getActiveSheet();
            $nextRow     = $sheet->getHighestDataRow() + 1;
        } else {
            $spreadsheet = new Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Suivi Alternances');
            $this->writeHeaders($sheet);
            $nextRow = 2;
        }

        foreach ($jobs as $job) {
            $this->writeJobRow($sheet, $nextRow, $job);
            $nextRow++;
        }

        $lastCol = Coordinate::stringFromColumnIndex(count(self::HEADERS));
        $sheet->setAutoFilter("A1:{$lastCol}1");
        $sheet->freezePane('A2');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($filePath);

        return $filePath;
    }

    private function writeHeaders(Worksheet $sheet): void
    {
        foreach (self::HEADERS as $i => $label) {
            $col  = $i + 1;
            $sheet->getCell([$col, 1])->setValue($label);
            $sheet->getStyle([$col, 1])->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'name' => 'Arial', 'size' => 10],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1A1A2E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
            ]);
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth(self::COL_WIDTHS[$i]);
        }
        $sheet->getRowDimension(1)->setRowHeight(32);
    }

    private function writeJobRow(Worksheet $sheet, int $row, Job $job): void
    {
        $bgColor = ($row % 2 === 0) ? 'FFF0F4FF' : 'FFFFFFFF';
        $values  = [
            $job->getExternalId(),
            (new \DateTime())->format('d/m/Y'),
            $job->getTitle(),
            $job->getCompany()  ?? '',
            $job->getLocation() ?? '',
            $job->getContact()  ?? '',
            $job->getUrl()      ?? '',
            '',
            '',
            '',
        ];

        foreach ($values as $i => $value) {
            $col = $i + 1;
            $sheet->getCell([$col, $row])->setValue($value);
            $sheet->getStyle([$col, $row])->applyFromArray([
                'font'      => ['name' => 'Arial', 'size' => 10],
                'alignment' => [
                    'horizontal' => in_array($col, [1, 2, 8, 9]) ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
            ]);
        }
        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    private function ensureDir(string $filePath): void
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}