<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TabularExportService
{
    public function download(array $rows, string $format, string $name)
    {
        if ($format === 'pdf') {
            $html = '<h1>GN-POMS — '.e($name).'</h1><table style="width:100%;border-collapse:collapse;font-size:9px">';
            foreach ($rows as $index => $row) {
                $html .= '<tr>';
                foreach ($row as $value) $html .= '<td style="border:1px solid #ddd;padding:5px">'.e((string) $value).'</td>';
                $html .= '</tr>';
            }
            return Pdf::loadHTML($html.'</table>')->setPaper('a4', 'landscape')->download($name.'.pdf');
        }
        $sheet = new Spreadsheet;
        foreach ($rows as $r => $row) {
            foreach (array_values($row) as $c => $value) {
                $sheet->getActiveSheet()->setCellValueExplicit(Coordinate::stringFromColumnIndex($c + 1).($r + 1), (string) $value, DataType::TYPE_STRING);
            }
        }
        return response()->streamDownload(function () use ($sheet) {
            (new Xlsx($sheet))->save('php://output');
            $sheet->disconnectWorksheets();
        }, $name.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
