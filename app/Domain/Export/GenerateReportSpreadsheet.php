<?php

namespace App\Domain\Export;

use App\Domain\Ledger\CalculateNetAsset;
use App\Domain\Reports\GenerateTransactionReport;
use App\Enums\CategoryType;
use App\Models\Workspace;
use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenerateReportSpreadsheet
{
    public function __construct(
        private readonly GenerateTransactionReport $report,
        private readonly CalculateNetAsset $calculateNetAsset,
    ) {}

    /**
     * Build a multi-sheet workbook covering the given workspace-local
     * billing month: summary, category and merchant breakdowns, account
     * activity, and a net worth snapshot.
     *
     * @param  array{account_ids?: array<int, int>, category_ids?: array<int, int>, merchant_id?: ?int, tag_ids?: array<int, int>}  $filters
     */
    public function forMonth(Workspace $workspace, CarbonInterface $start, CarbonInterface $end, array $filters): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        $this->writeSummarySheet($spreadsheet->getActiveSheet(), $workspace, $start, $end, $filters);
        $this->writeCategorySheet($spreadsheet->createSheet(), $workspace, $start, $end, $filters);
        $this->writeMerchantSheet($spreadsheet->createSheet(), $workspace, $start, $end, $filters);
        $this->writeAccountSheet($spreadsheet->createSheet(), $workspace, $start, $end, $filters);
        $this->writeNetWorthSheet($spreadsheet->createSheet(), $workspace);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Build a single-sheet workbook covering the given workspace-local
     * calendar year, with one row per month.
     *
     * @param  array<string, array{income: array<string, int>, expense: array<string, int>, net: array<string, int>, count: int}>  $months  Keyed by `Y-m`.
     */
    public function forYear(array $months): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Monthly summary');

        $sheet->fromArray(['Month', 'Income', 'Expense', 'Net', 'Transactions'], null, 'A1');

        $row = 2;

        foreach ($months as $label => $data) {
            $sheet->fromArray([
                $label,
                $this->formatAmounts($data['income']),
                $this->formatAmounts($data['expense']),
                $this->formatAmounts($data['net']),
                $data['count'],
            ], null, "A{$row}");

            $row++;
        }

        return $spreadsheet;
    }

    public function stream(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * @param  array{account_ids?: array<int, int>, category_ids?: array<int, int>, merchant_id?: ?int, tag_ids?: array<int, int>}  $filters
     */
    private function writeSummarySheet(Worksheet $sheet, Workspace $workspace, CarbonInterface $start, CarbonInterface $end, array $filters): void
    {
        $sheet->setTitle('Summary');

        $summary = $this->report->summary($workspace, $start, $end, $filters);

        $sheet->fromArray(['Metric', 'Amount'], null, 'A1');
        $sheet->fromArray([
            ['Income', $this->formatAmounts($summary['income'])],
            ['Expense', $this->formatAmounts($summary['expense'])],
            ['Net', $this->formatAmounts($summary['net'])],
            ['Transactions', $summary['count']],
        ], null, 'A2');
    }

    /**
     * @param  array{account_ids?: array<int, int>, category_ids?: array<int, int>, merchant_id?: ?int, tag_ids?: array<int, int>}  $filters
     */
    private function writeCategorySheet(Worksheet $sheet, Workspace $workspace, CarbonInterface $start, CarbonInterface $end, array $filters): void
    {
        $sheet->setTitle('Categories');

        $sheet->fromArray(['Type', 'Category', 'Parent', 'Amount', 'Currency'], null, 'A1');

        $row = 2;

        foreach ([CategoryType::Expense, CategoryType::Income] as $type) {
            foreach ($this->report->byCategory($workspace, $start, $end, $type, $filters) as $category) {
                $sheet->fromArray([
                    $type->value,
                    $category['name'],
                    $category['parent_name'] ?? '',
                    $category['amount'],
                    $category['currency'],
                ], null, "A{$row}");

                $row++;
            }
        }
    }

    /**
     * @param  array{account_ids?: array<int, int>, category_ids?: array<int, int>, merchant_id?: ?int, tag_ids?: array<int, int>}  $filters
     */
    private function writeMerchantSheet(Worksheet $sheet, Workspace $workspace, CarbonInterface $start, CarbonInterface $end, array $filters): void
    {
        $sheet->setTitle('Merchants');

        $sheet->fromArray(['Merchant', 'Amount', 'Currency'], null, 'A1');

        $row = 2;

        foreach ($this->report->byMerchant($workspace, $start, $end, $filters) as $merchant) {
            $sheet->fromArray([
                $merchant['name'],
                $merchant['amount'],
                $merchant['currency'],
            ], null, "A{$row}");

            $row++;
        }
    }

    /**
     * @param  array{account_ids?: array<int, int>, category_ids?: array<int, int>, merchant_id?: ?int, tag_ids?: array<int, int>}  $filters
     */
    private function writeAccountSheet(Worksheet $sheet, Workspace $workspace, CarbonInterface $start, CarbonInterface $end, array $filters): void
    {
        $sheet->setTitle('Accounts');

        $sheet->fromArray(['Account', 'Currency', 'Opening balance', 'Closing balance', 'Change', 'Income', 'Expense', 'Transactions'], null, 'A1');

        $row = 2;

        foreach ($this->report->byAccount($workspace, $start, $end, $filters) as $account) {
            $sheet->fromArray([
                $account['name'],
                $account['currency_code'],
                $account['opening_balance'],
                $account['closing_balance'],
                $account['change'],
                $account['income'],
                $account['expense'],
                $account['count'],
            ], null, "A{$row}");

            $row++;
        }
    }

    private function writeNetWorthSheet(Worksheet $sheet, Workspace $workspace): void
    {
        $sheet->setTitle('Net worth');

        $summary = $this->calculateNetAsset->summary($workspace);

        $sheet->fromArray(['Metric', 'Amount'], null, 'A1');
        $sheet->fromArray([
            ['Assets', $this->formatAmounts($summary['assets'])],
            ['Liabilities', $this->formatAmounts($summary['liabilities'])],
            ['Net worth', $this->formatAmounts($summary['net'])],
        ], null, 'A2');
    }

    /**
     * @param  array<string, int>  $amounts
     */
    private function formatAmounts(array $amounts): string
    {
        if ($amounts === []) {
            return '';
        }

        $parts = [];

        foreach ($amounts as $currency => $amount) {
            $parts[] = "{$amount} {$currency}";
        }

        return implode(', ', $parts);
    }
}
