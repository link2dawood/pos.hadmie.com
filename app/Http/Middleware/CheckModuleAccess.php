<?php

namespace App\Http\Middleware;

use App\Utils\ModuleUtil;
use Closure;

class CheckModuleAccess
{
    protected $moduleUtil;

    /**
     * Create a new middleware instance.
     *
     * @param  ModuleUtil  $moduleUtil
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $module_name
     * @return mixed
     */
    public function handle($request, Closure $next, $module_name = null)
    {
        // If no module name provided, try to get it from route
        if (empty($module_name)) {
            $route = $request->route();
            if ($route && isset($route->action['module'])) {
                $module_name = $route->action['module'];
            } else {
                // Try to extract from route name or path
                $path = $request->path();
                $path_parts = explode('/', $path);
                
                // Check if path contains module name (e.g., /repair/, /superadmin/, etc.)
                foreach ($path_parts as $part) {
                    $part_ucfirst = ucfirst($part);
                    if ($this->moduleUtil->isModuleInstalled($part_ucfirst)) {
                        $module_name = $part_ucfirst;
                        break;
                    }
                }
            }
        }

        // If still no module name, allow access (backward compatibility)
        if (empty($module_name)) {
            return $next($request);
        }

        // Superadmin always has access
        if (auth()->check() && auth()->user()->can('superadmin')) {
            return $next($request);
        }

        // Get business ID from session
        $business_id = $request->session()->get('user.business_id');
        
        if (empty($business_id)) {
            abort(403, 'Business not found in session.');
        }

        // Check module access
        if (!$this->moduleUtil->hasModuleAccess($business_id, $module_name)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => 0,
                    'msg' => __('superadmin::lang.module_access_denied', ['module' => $module_name]),
                ], 403);
            }

            abort(403, __('superadmin::lang.module_access_denied', ['module' => $module_name]));
        }

        return $next($request);
    }
}

