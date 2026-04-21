<?php

namespace App\Console\Commands;

use App\Business;
use App\BusinessLocation;
use App\InvoiceScheme;
use App\ReferenceCount;
use App\Contact;
use App\CashRegister;
use App\User;
use App\Product;
use App\VariationLocationDetails;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseBusiness extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'business:diagnose {business_id : The business ID to diagnose}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose potential issues with a business POS system';

    protected $errors = [];
    protected $warnings = [];
    protected $info = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $business_id = $this->argument('business_id');
        
        $this->info("==========================================");
        $this->info("POS Diagnostic Report for Business ID: {$business_id}");
        $this->info("Generated: " . now()->toDateTimeString());
        $this->info("==========================================");
        $this->newLine();

        // Run all checks
        $this->checkBusinessExists($business_id);
        $this->checkBusinessLocations($business_id);
        $this->checkInvoiceSchemes($business_id);
        $this->checkReferenceCount($business_id);
        $this->checkCashRegisters($business_id);
        $this->checkUsers($business_id);
        $this->checkWalkInCustomer($business_id);
        $this->checkCustomers($business_id);
        $this->checkProductsAndStock($business_id);
        $this->checkDatabaseIntegrity($business_id);
        $this->checkRecentErrors($business_id);
        $this->testInvoiceGeneration($business_id);

        // Display summary
        $this->displaySummary();

        return 0;
    }

    protected function checkBusinessExists($business_id)
    {
        $this->info('[1] Checking Business Existence...');
        $business = Business::find($business_id);
        
        if (!$business) {
            $this->errors[] = "CRITICAL: Business ID {$business_id} does not exist!";
            $this->error('   ❌ Business not found!');
        } else {
            $this->line("   ✓ Business found: {$business->name}");
            $this->line("   - Status: " . ($business->is_active ? 'Active' : 'Inactive'));
            $this->line("   - Currency: {$business->currency_id}");
            
            if (!$business->is_active) {
                $this->errors[] = "Business is INACTIVE - this will prevent transactions";
            }
        }
        $this->newLine();
    }

    protected function checkBusinessLocations($business_id)
    {
        $this->info('[2] Checking Business Locations...');
        $locations = BusinessLocation::where('business_id', $business_id)->get();
        
        if ($locations->isEmpty()) {
            $this->errors[] = "CRITICAL: No business locations found for business {$business_id}";
            $this->error('   ❌ No business locations found!');
        } else {
            $this->line("   ✓ Found {$locations->count()} location(s)");
            foreach ($locations as $location) {
                $this->line("   - Location ID: {$location->id} - {$location->name}");
                $this->line("     * Active: " . ($location->is_active ? 'Yes' : 'No'));
                $this->line("     * Invoice Scheme ID: " . ($location->invoice_scheme_id ?: 'Not Set'));
                
                if (!$location->is_active) {
                    $this->warnings[] = "Location '{$location->name}' (ID: {$location->id}) is INACTIVE";
                }
                
                if (!empty($location->invoice_scheme_id)) {
                    $scheme = InvoiceScheme::find($location->invoice_scheme_id);
                    if (!$scheme) {
                        $this->errors[] = "CRITICAL: Invoice scheme {$location->invoice_scheme_id} referenced by location '{$location->name}' does NOT exist";
                        $this->warn('     ⚠️  Invoice scheme MISSING!');
                    }
                }
            }
        }
        $this->newLine();
    }

    protected function checkInvoiceSchemes($business_id)
    {
        $this->info('[3] Checking Invoice Schemes...');
        $invoice_schemes = InvoiceScheme::where('business_id', $business_id)->get();
        
        if ($invoice_schemes->isEmpty()) {
            $this->errors[] = "CRITICAL: No invoice schemes found for business {$business_id}";
            $this->error('   ❌ No invoice schemes found!');
        } else {
            $this->line("   ✓ Found {$invoice_schemes->count()} invoice scheme(s)");
            $has_default = false;
            
            foreach ($invoice_schemes as $scheme) {
                $this->line("   - Scheme ID: {$scheme->id} - {$scheme->name}");
                $this->line("     * Prefix: {$scheme->prefix}");
                $this->line("     * Start Number: {$scheme->start_number}");
                $this->line("     * Invoice Count: {$scheme->invoice_count}");
                $this->line("     * Total Digits: {$scheme->total_digits}");
                $this->line("     * Is Default: " . ($scheme->is_default ? 'Yes' : 'No'));
                
                if ($scheme->is_default) {
                    $has_default = true;
                }
                
                // Check for potential overflow
                $current_number = $scheme->start_number + $scheme->invoice_count;
                $max_number = pow(10, $scheme->total_digits) - 1;
                if ($current_number > $max_number) {
                    $this->errors[] = "CRITICAL: Invoice scheme '{$scheme->name}' has exceeded maximum digits";
                    $this->error('     ❌ Number overflow detected!');
                }
            }
            
            if (!$has_default) {
                $this->warnings[] = "No default invoice scheme set for business {$business_id}";
                $this->warn('   ⚠️  No default invoice scheme found');
            }
        }
        $this->newLine();
    }

    protected function checkReferenceCount($business_id)
    {
        $this->info('[4] Checking Reference Counts...');
        $ref_counts = ReferenceCount::where('business_id', $business_id)->get();
        
        if ($ref_counts->isEmpty()) {
            $this->line('   ℹ️  No reference counts found (will be auto-created)');
        } else {
            $this->line("   ✓ Found {$ref_counts->count()} reference count(s)");
            foreach ($ref_counts as $ref) {
                $this->line("   - Type: {$ref->ref_type} - Count: {$ref->ref_count}");
                
                if ($ref->ref_count > 999999) {
                    $this->warnings[] = "Reference count for '{$ref->ref_type}' is very high: {$ref->ref_count}";
                }
                if ($ref->ref_count < 0) {
                    $this->errors[] = "CRITICAL: Reference count for '{$ref->ref_type}' is negative: {$ref->ref_count}";
                }
            }
        }
        $this->newLine();
    }

    protected function checkCashRegisters($business_id)
    {
        $this->info('[5] Checking Cash Registers...');
        $cash_registers = CashRegister::where('business_id', $business_id)->get();
        
        if ($cash_registers->isEmpty()) {
            $this->warnings[] = "No cash registers found for business {$business_id}";
            $this->warn('   ⚠️  No cash registers found');
        } else {
            $this->line("   ✓ Found {$cash_registers->count()} cash register(s)");
            $open_registers = 0;
            
            foreach ($cash_registers as $register) {
                $status = $register->status;
                $location_name = $register->location ? $register->location->name : 'Unknown Location';
                $this->line("   - Register ID: {$register->id} - {$location_name}");
                $this->line("     * Status: {$status}");
                
                if ($status == 'open') {
                    $open_registers++;
                }
            }
            
            if ($open_registers == 0) {
                $this->errors[] = "CRITICAL: No open cash registers found - users cannot create POS sales";
                $this->error('   ❌ No OPEN cash registers!');
            } else {
                $this->line("   ✓ {$open_registers} open register(s) found");
            }
        }
        $this->newLine();
    }

    protected function checkUsers($business_id)
    {
        $this->info('[6] Checking Users...');
        $users = User::where('business_id', $business_id)->get();
        
        if ($users->isEmpty()) {
            $this->errors[] = "CRITICAL: No users found for business {$business_id}";
            $this->error('   ❌ No users found!');
        } else {
            $this->line("   ✓ Found {$users->count()} user(s)");
            $active_users = $users->where('status', 'active')->count();
            
            if ($active_users == 0) {
                $this->errors[] = "CRITICAL: No active users found for business {$business_id}";
            } else {
                $this->line("   ✓ {$active_users} active user(s)");
            }
        }
        $this->newLine();
    }

    protected function checkWalkInCustomer($business_id)
    {
        $this->info('[7] Checking Walk-in Customer...');
        $walk_in = Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->where('is_default', 1)
            ->first();

        if (!$walk_in) {
            $this->errors[] = "CRITICAL: No walk-in customer found for business {$business_id}";
            $this->error('   ❌ Walk-in customer not found!');
        } else {
            $this->line("   ✓ Walk-in customer found: {$walk_in->name} (ID: {$walk_in->id})");
        }
        $this->newLine();
    }

    protected function checkCustomers($business_id)
    {
        $this->info('[8] Checking Customers...');
        $customers = Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->count();
        $this->line("   ✓ Found {$customers} customer(s)");
        $this->newLine();
    }

    protected function checkProductsAndStock($business_id)
    {
        $this->info('[9] Checking Products and Stock...');
        $products = Product::where('business_id', $business_id)->count();
        $this->line("   ✓ Found {$products} product(s)");
        
        $locations = BusinessLocation::where('business_id', $business_id)->get();
        foreach ($locations as $location) {
            $negative_stock = VariationLocationDetails::join('variations', 'variation_location_details.variation_id', '=', 'variations.id')
                ->join('products', 'variations.product_id', '=', 'products.id')
                ->where('products.business_id', $business_id)
                ->where('variation_location_details.location_id', $location->id)
                ->where('variation_location_details.qty_available', '<', 0)
                ->count();
            
            if ($negative_stock > 0) {
                $this->warnings[] = "Location '{$location->name}' has {$negative_stock} product(s) with negative stock";
                $this->warn("   ⚠️  {$negative_stock} product(s) with negative stock in '{$location->name}'");
            }
        }
        $this->newLine();
    }

    protected function checkDatabaseIntegrity($business_id)
    {
        $this->info('[10] Checking Database Integrity...');
        
        $orphaned_locations = BusinessLocation::where('business_id', $business_id)
            ->whereNotNull('invoice_scheme_id')
            ->whereNotExists(function($query) {
                $query->select(DB::raw(1))
                    ->from('invoice_schemes')
                    ->whereRaw('invoice_schemes.id = business_locations.invoice_scheme_id');
            })
            ->count();

        if ($orphaned_locations > 0) {
            $this->errors[] = "CRITICAL: {$orphaned_locations} business location(s) reference non-existent invoice schemes";
            $this->error("   ❌ {$orphaned_locations} orphaned invoice scheme references");
        } else {
            $this->line('   ✓ No orphaned references found');
        }
        $this->newLine();
    }

    protected function checkRecentErrors($business_id)
    {
        $this->info('[11] Checking Recent Errors in Log...');
        $logs_dir = storage_path('logs');
        
        if (is_dir($logs_dir)) {
            // Get all log files sorted by modification time (newest first)
            $files = glob($logs_dir . '/laravel-*.log');
            if (!$files) {
                $files = glob($logs_dir . '/laravel.log');
            }
            
            if (!empty($files)) {
                // Sort by modification time, newest first
                usort($files, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                
                $this->line('   ✓ Found ' . count($files) . ' log file(s)');
                $this->line('   - Checking most recent 3 files for errors...');
                
                // Check the 3 most recent log files
                $files_to_check = array_slice($files, 0, 3);
                $error_count = 0;
                
                foreach ($files_to_check as $log_file) {
                    $modified = date('Y-m-d H:i:s', filemtime($log_file));
                    $this->line("     * " . basename($log_file) . " (modified: {$modified})");
                    
                    // Quick check for errors
                    $log_content = file_get_contents($log_file);
                    if (stripos($log_content, 'emergency') !== false || stripos($log_content, 'error') !== false) {
                        $error_count++;
                        $this->warn('       ⚠️  Contains error/emergency logs');
                    }
                }
                
                if ($error_count > 0) {
                    $this->warn("   ⚠️  {$error_count} log file(s) contain errors - review manually");
                } else {
                    $this->line('   ✓ No obvious errors found');
                }
            } else {
                $this->line('   ℹ️  No log files found');
            }
        } else {
            $this->line("   ℹ️  Logs directory not found at: {$logs_dir}");
        }
        $this->newLine();
    }

    protected function testInvoiceGeneration($business_id)
    {
        $this->info('[12] Testing Invoice Number Generation (Dry Run)...');
        
        try {
            $locations = BusinessLocation::where('business_id', $business_id)->get();
            $test_location = $locations->first();
            
            if ($test_location) {
                $scheme = null;
                
                if (!empty($test_location->invoice_scheme_id)) {
                    $scheme = InvoiceScheme::find($test_location->invoice_scheme_id);
                }
                
                if (!$scheme) {
                    $scheme = InvoiceScheme::where('business_id', $business_id)
                        ->where('is_default', 1)
                        ->first();
                }
                
                if ($scheme) {
                    $this->line("   ✓ Would use scheme: {$scheme->name} (ID: {$scheme->id})");
                    
                    $next_count = $scheme->start_number + $scheme->invoice_count;
                    $padded_count = str_pad($next_count, $scheme->total_digits, '0', STR_PAD_LEFT);
                    
                    if ($scheme->scheme_type == 'blank') {
                        $next_invoice = $scheme->prefix . $padded_count;
                    } else {
                        $separator = config('constants.invoice_scheme_separator', '-');
                        $next_invoice = $scheme->prefix . date('Y') . $separator . $padded_count;
                    }
                    
                    $this->line("   ✓ Next invoice number would be: {$next_invoice}");
                    
                    if (strlen($padded_count) > $scheme->total_digits) {
                        $this->errors[] = "CRITICAL: Next invoice number would overflow the configured digit limit!";
                        $this->error('   ❌ Invoice number would OVERFLOW!');
                    }
                } else {
                    $this->errors[] = "CRITICAL: Cannot generate invoice number - no valid invoice scheme found";
                    $this->error('   ❌ No valid invoice scheme available');
                }
            }
        } catch (\Exception $e) {
            $this->errors[] = "Error testing invoice generation: " . $e->getMessage();
            $this->error('   ❌ Error: ' . $e->getMessage());
        }
        $this->newLine();
    }

    protected function displaySummary()
    {
        $this->info("==========================================");
        $this->info("SUMMARY REPORT");
        $this->info("==========================================");
        $this->newLine();

        if (count($this->errors) > 0) {
            $this->error("🔴 CRITICAL ERRORS (" . count($this->errors) . "):");
            foreach ($this->errors as $i => $error) {
                $this->line("   " . ($i + 1) . ". {$error}");
            }
            $this->newLine();
        }

        if (count($this->warnings) > 0) {
            $this->warn("⚠️  WARNINGS (" . count($this->warnings) . "):");
            foreach ($this->warnings as $i => $warning) {
                $this->line("   " . ($i + 1) . ". {$warning}");
            }
            $this->newLine();
        }

        if (count($this->errors) == 0 && count($this->warnings) == 0) {
            $this->info("✅ All checks passed! No critical issues found.");
            $this->newLine();
            $this->line("If the issue persists, please:");
            $this->line("1. Check the Laravel log file for specific error messages");
            $this->line("2. Enable debug mode temporarily");
            $this->line("3. Check browser console for JavaScript errors");
            $this->line("4. Verify network connectivity and server resources");
        } else {
            $this->line("RECOMMENDED ACTIONS:");
            $this->line("1. Fix all CRITICAL ERRORS listed above immediately");
            $this->line("2. Review and address WARNINGS if applicable");
            $this->line("3. Check Laravel logs for specific error traces");
            $this->line("4. Test POS functionality after fixing issues");
        }

        $this->newLine();
        $this->info("==========================================");
        $this->info("Diagnostic complete.");
        $this->info("==========================================");
    }
}
