<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, string $role = 'super_admin'): Response
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login')->with('error', 'Anda harus login sebagai admin.');
        }

        $admin = Auth::guard('admin')->user();
        
        if ($role === 'super_admin' && !$admin->isSuperAdmin()) {
            abort(403, 'Akses ditolak. Hanya Super Admin yang dapat mengakses halaman ini.');
        }

        return $next($request);
    }
}

