<?php

namespace App\Services;

use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class ImportFileReader
{
    /**
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>, has_formula: bool, row_count: int}
     */
    public function read(string $path, string $format): array
    {
        $reader = $format === 'xlsx' ? new XlsxReader : new CsvReader;
        $reader->open($path);

        $headers = [];
        $rows = [];
        $hasFormula = false;
        $rowCount = 0;
        $maxRows = (int) config('imports.max_rows', 5000);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();
                $values = [];
                foreach ($cells as $cell) {
                    if ($cell instanceof FormulaCell) {
                        $hasFormula = true;
                    }
                    $values[] = $cell->getValue();
                }

                if ($headers === []) {
                    $headers = array_map(static fn ($value): string => trim((string) $value), $values);

                    continue;
                }

                $rowCount++;
                if ($rowCount > $maxRows) {
                    $reader->close();
                    throw new \RuntimeException('Import row limit exceeded.');
                }
                $rows[] = $values;
            }
            break;
        }

        $reader->close();

        return [
            'headers' => $headers,
            'rows' => $rows,
            'has_formula' => $hasFormula,
            'row_count' => $rowCount,
        ];
    }
}
