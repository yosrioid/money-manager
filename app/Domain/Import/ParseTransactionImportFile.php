<?php

namespace App\Domain\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ParseTransactionImportFile
{
    /**
     * The recognised import columns, in the order produced by the
     * transaction CSV export.
     *
     * @var array<int, string>
     */
    public const COLUMNS = ['date', 'type', 'description', 'memo', 'merchant', 'account', 'category', 'amount', 'currency', 'tags'];

    /**
     * Parse an uploaded CSV or Excel file into rows keyed by column name.
     *
     * @return array<int, array<string, string>>
     */
    public function parse(string $path, string $extension): array
    {
        $rows = in_array(strtolower($extension), ['csv', 'txt'], true)
            ? $this->parseCsv($path)
            : $this->parseSpreadsheet($path);

        if ($rows === []) {
            return [];
        }

        $header = array_map(fn (string $value): string => strtolower(trim($value)), array_shift($rows));

        return array_values(array_filter(
            array_map(fn (array $row): array => $this->mapRow($header, $row), $rows),
            fn (array $row): bool => implode('', $row) !== ''
        ));
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(fn (?string $value): string => trim($value ?? ''), $row);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseSpreadsheet(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = [];

        foreach ($sheet->toArray(null, true, true, false) as $row) {
            $rows[] = array_map(fn (mixed $value): string => trim((string) ($value ?? '')), $row);
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, string>  $row
     * @return array<string, string>
     */
    private function mapRow(array $header, array $row): array
    {
        $row = array_pad($row, count($header), '');

        $values = array_combine($header, array_slice($row, 0, count($header)));

        $mapped = [];

        foreach (self::COLUMNS as $column) {
            $mapped[$column] = $values[$column] ?? '';
        }

        return $mapped;
    }
}
