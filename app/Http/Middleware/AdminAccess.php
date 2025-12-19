<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login')->with('error', 'Anda harus login sebagai admin untuk mengakses halaman ini.');
        }

        $admin = Auth::guard('admin')->user();
        
        if ($admin->status !== 'active') {
            Auth::guard('admin')->logout();
            return redirect()->route('admin.login')->with('error', 'Akun admin Anda tidak aktif.');
        }

        return $next($request);
    }
}
