<?php
/**
 * Diagnostic Script for Business ID 73 POS Issues
 * 
 * This script checks for potential issues that could cause 
 * "Something went wrong. Please try again later" errors
 * without modifying any data.
 * 
 * Run this from the command line: php diagnose_business_73.php
 */

// Bootstrap Laravel
require __DIR__.'/bootstrap/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Business;
use App\BusinessLocation;
use App\InvoiceScheme;
use App\ReferenceCount;
use App\Contact;
use App\CashRegister;
use App\User;
use App\Product;
use App\Variation;
use App\VariationLocationDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// ========================================
// Configuration
// ========================================
$business_id = 73;
$report = [];
$errors = [];
$warnings = [];
$info = [];

echo "==========================================\n";
echo "POS Diagnostic Report for Business ID: {$business_id}\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n";
echo "==========================================\n\n";

// ========================================
// 1. Check if Business Exists
// ========================================
echo "[1] Checking Business Existence...\n";
$business = Business::find($business_id);
if (!$business) {
    $errors[] = "CRITICAL: Business ID {$business_id} does not exist!";
    echo "   ❌ Business not found!\n\n";
} else {
    $info[] = "Business found: {$business->name}";
    echo "   ✓ Business found: {$business->name}\n";
    echo "   - Status: " . ($business->is_active ? 'Active' : 'Inactive') . "\n";
    echo "   - Currency: {$business->currency_id}\n\n";
    
    if (!$business->is_active) {
        $errors[] = "Business is INACTIVE - this will prevent transactions";
    }
}

// ========================================
// 2. Check Business Locations
// ========================================
echo "[2] Checking Business Locations...\n";
$locations = BusinessLocation::where('business_id', $business_id)->get();
if ($locations->isEmpty()) {
    $errors[] = "CRITICAL: No business locations found for business {$business_id}";
    echo "   ❌ No business locations found!\n\n";
} else {
    echo "   ✓ Found {$locations->count()} location(s)\n";
    foreach ($locations as $location) {
        echo "   - Location ID: {$location->id} - {$location->name}\n";
        echo "     * Active: " . ($location->is_active ? 'Yes' : 'No') . "\n";
        echo "     * Invoice Scheme ID: " . ($location->invoice_scheme_id ?: 'Not Set') . "\n";
        echo "     * Sale Invoice Scheme ID: " . ($location->sale_invoice_scheme_id ?: 'Not Set') . "\n";
        
        if (!$location->is_active) {
            $warnings[] = "Location '{$location->name}' (ID: {$location->id}) is INACTIVE";
        }
        
        // Check if invoice scheme exists
        if (!empty($location->invoice_scheme_id)) {
            $scheme = InvoiceScheme::find($location->invoice_scheme_id);
            if (!$scheme) {
                $errors[] = "CRITICAL: Invoice scheme {$location->invoice_scheme_id} referenced by location '{$location->name}' does NOT exist";
                echo "     ⚠️  Invoice scheme MISSING!\n";
            }
        }
    }
    echo "\n";
}

// ========================================
// 3. Check Invoice Schemes
// ========================================
echo "[3] Checking Invoice Schemes...\n";
$invoice_schemes = InvoiceScheme::where('business_id', $business_id)->get();
if ($invoice_schemes->isEmpty()) {
    $errors[] = "CRITICAL: No invoice schemes found for business {$business_id}";
    echo "   ❌ No invoice schemes found!\n\n";
} else {
    echo "   ✓ Found {$invoice_schemes->count()} invoice scheme(s)\n";
    $has_default = false;
    foreach ($invoice_schemes as $scheme) {
        echo "   - Scheme ID: {$scheme->id} - {$scheme->name}\n";
        echo "     * Prefix: {$scheme->prefix}\n";
        echo "     * Scheme Type: {$scheme->scheme_type}\n";
        echo "     * Number Type: {$scheme->number_type}\n";
        echo "     * Start Number: {$scheme->start_number}\n";
        echo "     * Invoice Count: {$scheme->invoice_count}\n";
        echo "     * Total Digits: {$scheme->total_digits}\n";
        echo "     * Is Default: " . ($scheme->is_default ? 'Yes' : 'No') . "\n";
        
        if ($scheme->is_default) {
            $has_default = true;
        }
        
        // Check for potential overflow issues
        $current_number = $scheme->start_number + $scheme->invoice_count;
        $max_number = pow(10, $scheme->total_digits) - 1;
        if ($current_number > $max_number) {
            $errors[] = "CRITICAL: Invoice scheme '{$scheme->name}' has exceeded maximum digits (current: {$current_number}, max: {$max_number})";
            echo "     ❌ Number overflow detected!\n";
        }
    }
    
    if (!$has_default) {
        $warnings[] = "No default invoice scheme set for business {$business_id}";
        echo "   ⚠️  No default invoice scheme found\n";
    }
    echo "\n";
}

// ========================================
// 4. Check Reference Counts
// ========================================
echo "[4] Checking Reference Counts...\n";
$ref_counts = ReferenceCount::where('business_id', $business_id)->get();
if ($ref_counts->isEmpty()) {
    $info[] = "No reference counts found - will be created on first use";
    echo "   ℹ️  No reference counts found (will be auto-created)\n\n";
} else {
    echo "   ✓ Found {$ref_counts->count()} reference count(s)\n";
    foreach ($ref_counts as $ref) {
        echo "   - Type: {$ref->ref_type} - Count: {$ref->ref_count}\n";
        
        // Check for suspicious values
        if ($ref->ref_count > 999999) {
            $warnings[] = "Reference count for '{$ref->ref_type}' is very high: {$ref->ref_count}";
        }
        if ($ref->ref_count < 0) {
            $errors[] = "CRITICAL: Reference count for '{$ref->ref_type}' is negative: {$ref->ref_count}";
        }
    }
    echo "\n";
}

// ========================================
// 5. Check Cash Registers
// ========================================
echo "[5] Checking Cash Registers...\n";
$cash_registers = CashRegister::where('business_id', $business_id)->get();
if ($cash_registers->isEmpty()) {
    $warnings[] = "No cash registers found for business {$business_id}";
    echo "   ⚠️  No cash registers found\n\n";
} else {
    echo "   ✓ Found {$cash_registers->count()} cash register(s)\n";
    $open_registers = 0;
    foreach ($cash_registers as $register) {
        $status = $register->status;
        $register_location_name = $register->location ? $register->location->name : 'N/A';
        echo "   - Register ID: {$register->id} - {$register_location_name}\n";
        echo "     * Status: {$status}\n";
        echo "     * User: " . ($register->user ? $register->user->username : 'N/A') . "\n";
        
        if ($status == 'open') {
            $open_registers++;
            echo "     * Opened At: {$register->created_at}\n";
        }
        
        if ($status == 'close' && $register->closed_at) {
            echo "     * Closed At: {$register->closed_at}\n";
        }
    }
    
    if ($open_registers == 0) {
        $errors[] = "CRITICAL: No open cash registers found - users cannot create POS sales";
        echo "   ❌ No OPEN cash registers!\n";
    } else {
        echo "   ✓ {$open_registers} open register(s) found\n";
    }
    echo "\n";
}

// ========================================
// 6. Check Users
// ========================================
echo "[6] Checking Users...\n";
$users = User::where('business_id', $business_id)->get();
if ($users->isEmpty()) {
    $errors[] = "CRITICAL: No users found for business {$business_id}";
    echo "   ❌ No users found!\n\n";
} else {
    echo "   ✓ Found {$users->count()} user(s)\n";
    $active_users = 0;
    foreach ($users as $user) {
        $status = $user->status == 'active' ? '✓ Active' : '❌ Inactive';
        echo "   - User ID: {$user->id} - {$user->username} ({$user->first_name} {$user->last_name}) - {$status}\n";
        
        if ($user->status == 'active') {
            $active_users++;
        }
    }
    
    if ($active_users == 0) {
        $errors[] = "CRITICAL: No active users found for business {$business_id}";
    }
    echo "\n";
}

// ========================================
// 7. Check Walk-in Customer
// ========================================
echo "[7] Checking Walk-in Customer...\n";
$walk_in = Contact::where('business_id', $business_id)
    ->where('type', 'customer')
    ->where('is_default', 1)
    ->first();

if (!$walk_in) {
    $errors[] = "CRITICAL: No walk-in customer found for business {$business_id}";
    echo "   ❌ Walk-in customer not found!\n\n";
} else {
    echo "   ✓ Walk-in customer found: {$walk_in->name} (ID: {$walk_in->id})\n";
    echo "   - Customer ID: {$walk_in->contact_id}\n";
    echo "   - Credit Limit: " . ($walk_in->credit_limit ?: 'None') . "\n\n";
}

// ========================================
// 8. Check Customers
// ========================================
echo "[8] Checking Customers...\n";
$customers = Contact::where('business_id', $business_id)
    ->where('type', 'customer')
    ->count();
echo "   ✓ Found {$customers} customer(s)\n";

// Check for customers with issues
$customers_with_exceeded_credit = Contact::where('business_id', $business_id)
    ->where('type', 'customer')
    ->whereNotNull('credit_limit')
    ->where('credit_limit', '>', 0)
    ->get()
    ->filter(function($contact) {
        $balance = $contact->balance ?? 0;
        return $balance > $contact->credit_limit;
    });

if ($customers_with_exceeded_credit->count() > 0) {
    echo "   ⚠️  {$customers_with_exceeded_credit->count()} customer(s) have exceeded credit limit\n";
    foreach ($customers_with_exceeded_credit as $customer) {
        $warnings[] = "Customer '{$customer->name}' (ID: {$customer->id}) has exceeded credit limit";
    }
}
echo "\n";

// ========================================
// 9. Check Products and Stock
// ========================================
echo "[9] Checking Products and Stock...\n";
$products = Product::where('business_id', $business_id)->count();
echo "   ✓ Found {$products} product(s)\n";

// Check for products with negative stock
foreach ($locations as $location) {
    $negative_stock = VariationLocationDetails::join('variations', 'variation_location_details.variation_id', '=', 'variations.id')
        ->join('products', 'variations.product_id', '=', 'products.id')
        ->where('products.business_id', $business_id)
        ->where('variation_location_details.location_id', $location->id)
        ->where('variation_location_details.qty_available', '<', 0)
        ->count();
    
    if ($negative_stock > 0) {
        $warnings[] = "Location '{$location->name}' has {$negative_stock} product(s) with negative stock";
        echo "   ⚠️  {$negative_stock} product(s) with negative stock in location '{$location->name}'\n";
    }
}
echo "\n";

// ========================================
// 10. Check Database Integrity
// ========================================
echo "[10] Checking Database Integrity...\n";

// Check for orphaned records
$orphaned_locations = BusinessLocation::where('business_id', $business_id)
    ->whereNotNull('invoice_scheme_id')
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
            ->from('invoice_schemes')
            ->whereRaw('invoice_schemes.id = business_locations.invoice_scheme_id');
    })
    ->count();

if ($orphaned_locations > 0) {
    $errors[] = "CRITICAL: {$orphaned_locations} business location(s) reference non-existent invoice schemes";
    echo "   ❌ {$orphaned_locations} orphaned invoice scheme references\n";
}

// Check for locked tables (MySQL specific)
try {
    $locked_tables = DB::select("SHOW OPEN TABLES WHERE In_use > 0");
    if (count($locked_tables) > 0) {
        $warnings[] = "Database has " . count($locked_tables) . " locked table(s)";
        echo "   ⚠️  " . count($locked_tables) . " locked table(s) detected\n";
    } else {
        echo "   ✓ No locked tables\n";
    }
} catch (\Exception $e) {
    echo "   ℹ️  Could not check for locked tables: " . $e->getMessage() . "\n";
}

echo "\n";

// ========================================
// 11. Check Log Files for Recent Errors
// ========================================
echo "[11] Checking Recent Errors in Log...\n";
$logs_dir = storage_path('logs');
$recent_errors = [];
$business_related_errors = 0;

if (is_dir($logs_dir)) {
    // Get all log files sorted by modification time (newest first)
    $files = glob($logs_dir . '/laravel-*.log');
    if (!$files) {
        $files = glob($logs_dir . '/laravel.log');
    }
    
    if (empty($files)) {
        echo "   ℹ️  No log files found in: {$logs_dir}\n";
    } else {
        // Sort by modification time, newest first
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        echo "   ✓ Found " . count($files) . " log file(s)\n";
        echo "   - Checking most recent 3 files...\n";
        
        // Check the 3 most recent log files
        $files_to_check = array_slice($files, 0, 3);
        
        foreach ($files_to_check as $log_file) {
            $size = filesize($log_file);
            $modified = date('Y-m-d H:i:s', filemtime($log_file));
            echo "     * " . basename($log_file) . " (" . ($size / 1024) . " KB, modified: {$modified})\n";
            
            // Read file (get last 2000 lines if file is large)
            $log_content = file_get_contents($log_file);
            $lines = explode("\n", $log_content);
            
            if (count($lines) > 2000) {
                $recent_lines = array_slice($lines, -2000);
            } else {
                $recent_lines = $lines;
            }
            
            foreach ($recent_lines as $line) {
                if (stripos($line, 'emergency') !== false || stripos($line, 'error') !== false) {
                    if (stripos($line, "business_id={$business_id}") !== false || 
                        stripos($line, "business/{$business_id}") !== false ||
                        stripos($line, "Business {$business_id}") !== false ||
                        stripos($line, "business 73") !== false) {
                        $business_related_errors++;
                        $recent_errors[] = trim($line);
                    }
                }
            }
        }
        
        if ($business_related_errors > 0) {
            echo "   ⚠️  Found {$business_related_errors} error(s) related to business {$business_id}\n";
            echo "   - Showing last 5 errors:\n";
            foreach (array_slice($recent_errors, -5) as $error) {
                echo "     " . substr($error, 0, 150) . "...\n";
            }
        } else {
            echo "   ✓ No recent errors found for this business in logs\n";
        }
    }
} else {
    echo "   ℹ️  Logs directory not found at: {$logs_dir}\n";
}
echo "\n";

// ========================================
// 12. Test Invoice Number Generation
// ========================================
echo "[12] Testing Invoice Number Generation (Dry Run)...\n";
try {
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
            echo "   ✓ Would use scheme: {$scheme->name} (ID: {$scheme->id})\n";
            
            $next_count = $scheme->start_number + $scheme->invoice_count;
            $padded_count = str_pad($next_count, $scheme->total_digits, '0', STR_PAD_LEFT);
            
            if ($scheme->scheme_type == 'blank') {
                $next_invoice = $scheme->prefix . $padded_count;
            } else {
                $next_invoice = $scheme->prefix . date('Y') . config('constants.invoice_scheme_separator') . $padded_count;
            }
            
            echo "   ✓ Next invoice number would be: {$next_invoice}\n";
            
            // Check if this would overflow
            if (strlen($padded_count) > $scheme->total_digits) {
                $errors[] = "CRITICAL: Next invoice number would overflow the configured digit limit!";
                echo "   ❌ Invoice number would OVERFLOW!\n";
            }
        } else {
            $errors[] = "CRITICAL: Cannot generate invoice number - no valid invoice scheme found";
            echo "   ❌ No valid invoice scheme available\n";
        }
    }
} catch (\Exception $e) {
    $errors[] = "Error testing invoice generation: " . $e->getMessage();
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// ========================================
// SUMMARY REPORT
// ========================================
echo "==========================================\n";
echo "SUMMARY REPORT\n";
echo "==========================================\n\n";

if (count($errors) > 0) {
    echo "🔴 CRITICAL ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $i => $error) {
        echo "   " . ($i + 1) . ". {$error}\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠️  WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $i => $warning) {
        echo "   " . ($i + 1) . ". {$warning}\n";
    }
    echo "\n";
}

if (count($errors) == 0 && count($warnings) == 0) {
    echo "✅ All checks passed! No critical issues found.\n\n";
    echo "If the issue persists, please:\n";
    echo "1. Check the Laravel log file for specific error messages\n";
    echo "2. Enable debug mode temporarily in production\n";
    echo "3. Check browser console for JavaScript errors\n";
    echo "4. Verify network connectivity and server resources\n";
} else {
    echo "RECOMMENDED ACTIONS:\n";
    echo "1. Fix all CRITICAL ERRORS listed above immediately\n";
    echo "2. Review and address WARNINGS if applicable\n";
    echo "3. Check Laravel logs for specific error traces\n";
    echo "4. Test POS functionality after fixing issues\n";
}

echo "\n==========================================\n";
echo "Diagnostic complete.\n";
echo "==========================================\n";
