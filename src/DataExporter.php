<?php

namespace ContentCrawler;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class DataExporter
{
    /**
     * Export data to JSON format
     */
    public function exportToJson($data, $filename = null)
    {
        $filename = $filename ?: 'menu_data_' . date('Y-m-d_H-i-s') . '.json';

        $jsonData = [
            'export_info' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'total_items' => count($data),
                'format' => 'JSON'
            ],
            'menu_items' => $data
        ];

        $json = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return [
            'success' => true,
            'filename' => $filename,
            'content' => $json,
            'size' => strlen($json)
        ];
    }

    /**
     * Export data to CSV format
     */
    public function exportToCsv($data, $filename = null)
    {
        $filename = $filename ?: 'menu_data_' . date('Y-m-d_H-i-s') . '.csv';

        $output = fopen('php://temp', 'r+');

        // Write BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");

        // Write headers
        $headers = ['Ürün Adı', 'Açıklama', 'Fiyat', 'Resim URL', 'Kategori', 'Kaynak URL'];
        fputcsv($output, $headers);

        // Write data
        foreach ($data as $item) {
            $row = [
                $item['name'] ?? '',
                $item['description'] ?? '',
                $item['price'] ?? '',
                $item['image'] ?? '',
                $item['category'] ?? '',
                $item['source_url'] ?? ''
            ];
            fputcsv($output, $row);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return [
            'success' => true,
            'filename' => $filename,
            'content' => $content,
            'size' => strlen($content)
        ];
    }

    /**
     * Export data to Excel format
     */
    public function exportToExcel($data, $filename = null)
    {
        $filename = $filename ?: 'menu_data_' . date('Y-m-d_H-i-s') . '.xlsx';

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers
            $headers = ['Ürün Adı', 'Açıklama', 'Fiyat', 'Resim URL', 'Kategori', 'Kaynak URL'];
            $sheet->fromArray($headers, null, 'A1');

            // Style headers
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ];
            $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

            // Add data
            $row = 2;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item['name'] ?? '');
                $sheet->setCellValue('B' . $row, $item['description'] ?? '');
                $sheet->setCellValue('C' . $row, $item['price'] ?? '');
                $sheet->setCellValue('D' . $row, $item['image'] ?? '');
                $sheet->setCellValue('E' . $row, $item['category'] ?? '');
                $sheet->setCellValue('F' . $row, $item['source_url'] ?? '');
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Save to memory
            $writer = new Xlsx($spreadsheet);
            $output = fopen('php://temp', 'r+');
            $writer->save($output);

            rewind($output);
            $content = stream_get_contents($output);
            fclose($output);

            return [
                'success' => true,
                'filename' => $filename,
                'content' => $content,
                'size' => strlen($content)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Excel export hatası: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Save exported data to file
     */
    public function saveToFile($exportResult, $directory = 'exports')
    {
        if (!$exportResult['success']) {
            return $exportResult;
        }

        // Create exports directory if it doesn't exist
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filepath = $directory . '/' . $exportResult['filename'];

        $bytesWritten = file_put_contents($filepath, $exportResult['content']);

        if ($bytesWritten === false) {
            return [
                'success' => false,
                'error' => 'Dosya kaydedilemedi: ' . $filepath
            ];
        }

        return [
            'success' => true,
            'filename' => $exportResult['filename'],
            'filepath' => $filepath,
            'size' => $bytesWritten
        ];
    }

    /**
     * Download file directly to browser
     */
    public function downloadFile($exportResult)
    {
        if (!$exportResult['success']) {
            return false;
        }

        $filename = $exportResult['filename'];
        $content = $exportResult['content'];

        // Determine content type
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $contentTypes = [
            'json' => 'application/json',
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        $contentType = $contentTypes[$extension] ?? 'application/octet-stream';

        // Set headers for download
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        echo $content;
        return true;
    }
}
