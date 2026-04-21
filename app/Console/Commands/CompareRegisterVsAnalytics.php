<?php

namespace App\Console\Commands;

use App\Utils\TransactionUtil;
use App\Utils\CashRegisterUtil;
use Illuminate\Console\Command;
use DB;

class CompareRegisterVsAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compare:register-analytics {--start=} {--end=} {--business_id=} {--location_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compare close register figures with home analytics';

    protected $transactionUtil;
    protected $cashRegisterUtil;

    public function __construct(TransactionUtil $transactionUtil, CashRegisterUtil $cashRegisterUtil)
    {
        parent::__construct();
        $this->transactionUtil = $transactionUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $start = $this->option('start') ?? '2025-12-13';
        $end = $this->option('end') ?? '2026-01-27';
        $business_id = $this->option('business_id') ?? 1;
        $location_id = $this->option('location_id') ?? null;

        $this->info("=== COMPARING CLOSE REGISTER VS HOME ANALYTICS ===");
        $this->info("Period: {$start} to {$end}");
        $this->info("Business ID: {$business_id}");
        $this->info("Location ID: " . ($location_id ?? "ALL"));
        $this->newLine();

        // Get HOME ANALYTICS totals
        $this->info("--- HOME ANALYTICS (getTotals) ---");
        $home_totals = $this->getHomeAnalytics($business_id, $start, $end, $location_id);
        $this->outputTotals('HOME ANALYTICS', $home_totals);
        $this->newLine();

        // Get CLOSE REGISTER totals using getRegisterTransactionDetails
        $this->info("--- CLOSE REGISTER (getRegisterTransactionDetails) ---");
        $register_totals = $this->getRegisterAnalytics($business_id, $start, $end);
        $this->outputTotals('CLOSE REGISTER', $register_totals);
        $this->newLine();

        // Compare
        $this->info("--- COMPARISON ---");
        $this->compareResults($home_totals, $register_totals);
        $this->newLine();

        // Debug: Show raw transaction counts
        $this->info("--- DEBUG: TRANSACTION COUNTS ---");
        $this->debugTransactionCounts($business_id, $start, $end);
    }

    private function getHomeAnalytics($business_id, $start, $end, $location_id)
    {
        // Query transactions directly like getSellTotals does
        $query = \App\Transaction::where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'sell')
                    ->where('transactions.status', 'final')
                    ->select(
                        DB::raw('SUM(final_total) as total_sell'),
                        DB::raw('SUM(final_total - tax_amount) as total_exc_tax'),
                        DB::raw('SUM(final_total - (SELECT COALESCE(SUM(IF(tp.is_return = 1, -1*tp.amount, tp.amount)), 0) FROM transaction_payments as tp WHERE tp.transaction_id = transactions.id) )  as total_due'),
                        DB::raw('SUM(total_before_tax) as total_before_tax'),
                        DB::raw('SUM(shipping_charges) as total_shipping_charges'),
                        DB::raw('SUM(additional_expense_value_1 + additional_expense_value_2 + additional_expense_value_3 + additional_expense_value_4) as total_expense')
                    );

        if (!empty($start) && !empty($end)) {
            $query->whereDate('transactions.transaction_date', '>=', $start)
                ->whereDate('transactions.transaction_date', '<=', $end);
        }

        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }

        $sell_details = $query->first();

        // Get sell returns
        $query_returns = \App\Transaction::where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'sell_return')
                    ->where('transactions.status', 'final')
                    ->select(DB::raw('SUM(final_total) as total_sell_return_inc_tax'));

        if (!empty($start) && !empty($end)) {
            $query_returns->whereDate('transactions.transaction_date', '>=', $start)
                ->whereDate('transactions.transaction_date', '<=', $end);
        }

        if (!empty($location_id)) {
            $query_returns->where('transactions.location_id', $location_id);
        }

        $sell_returns = $query_returns->first();

        $total_sell_inc_tax = $sell_details->total_sell ?? 0;
        $total_sell_return_inc_tax = $sell_returns->total_sell_return_inc_tax ?? 0;
        $invoice_due = $sell_details->total_due ?? 0;
        $total_expense = $sell_details->total_expense ?? 0;

        return [
            'total_sell' => $total_sell_inc_tax,
            'total_sell_return' => $total_sell_return_inc_tax,
            'invoice_due' => $invoice_due,
            'total_expense' => $total_expense,
            'net' => ($total_sell_inc_tax - $total_sell_return_inc_tax) - $invoice_due - $total_expense,
        ];
    }

    private function getRegisterAnalytics($business_id, $start_date, $end_date)
    {
        $this->line("  [DEBUG] Querying with business_id=$business_id, dates: $start_date to $end_date");
        
        // Replicate getRegisterTransactionDetails logic
        $transaction_details = \App\Transaction::where('transactions.business_id', $business_id)
                ->whereBetween('transactions.transaction_date', [$start_date, $end_date])
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->select(
                    DB::raw('SUM(tax_amount) as total_tax'),
                    DB::raw('SUM(IF(discount_type = "percentage", total_before_tax*discount_amount/100, discount_amount)) as total_discount'),
                    DB::raw('SUM(final_total) as total_sales'),
                    DB::raw('SUM(shipping_charges) as total_shipping_charges')
                )
                ->first();

        $this->line("  [DEBUG] Query SQL: " . $transaction_details->toSql());

        return [
            'total_sales' => $transaction_details->total_sales ?? 0,
            'total_tax' => $transaction_details->total_tax ?? 0,
            'total_discount' => $transaction_details->total_discount ?? 0,
            'total_shipping' => $transaction_details->total_shipping_charges ?? 0,
        ];
    }

    private function outputTotals($label, $data)
    {
        $this->line("$label:");
        foreach ($data as $key => $value) {
            $value_formatted = number_format($value, 2);
            $this->line("  {$key}: {$value_formatted}");
        }
    }

    private function compareResults($home, $register)
    {
        $this->line("Total Sales (Home): " . number_format($home['total_sell'], 2));
        $this->line("Total Sales (Register): " . number_format($register['total_sales'], 2));
        $diff = abs($home['total_sell'] - $register['total_sales']);
        $match = $diff < 0.01 ? '✓ MATCH' : '✗ MISMATCH';
        $this->line("Difference: " . number_format($diff, 2) . " {$match}");
    }

    private function debugTransactionCounts($business_id, $start, $end)
    {
        // Count transactions by type FOR THIS BUSINESS
        $sells = \App\Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereBetween('transaction_date', [$start, $end])
                ->count();
        
        $sell_returns = \App\Transaction::where('business_id', $business_id)
                ->where('type', 'sell_return')
                ->where('status', 'final')
                ->whereBetween('transaction_date', [$start, $end])
                ->count();

        $direct_sales = \App\Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->where('is_direct_sale', 1)
                ->whereBetween('transaction_date', [$start, $end])
                ->count();

        $this->line("Total Sell transactions (Business $business_id): {$sells}");
        $this->line("Total Sell Return transactions (Business $business_id): {$sell_returns}");
        $this->line("Total Direct Sale transactions (Business $business_id): {$direct_sales}");

        // Show sum breakdown
        $sell_sum = \App\Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('final_total');

        $indirect_sell_sum = \App\Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->where('is_direct_sale', 0)
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('final_total');

        $direct_sell_sum = \App\Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->where('is_direct_sale', 1)
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('final_total');

        $this->line("Total Sells (all - Business $business_id): " . number_format($sell_sum, 2));
        $this->line("Total Sells (indirect - is_direct_sale=0 - Business $business_id): " . number_format($indirect_sell_sum, 2));
        $this->line("Total Sells (direct - is_direct_sale=1 - Business $business_id): " . number_format($direct_sell_sum, 2));
    }
}
