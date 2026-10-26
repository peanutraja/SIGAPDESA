<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CekSession
{
    public function handle(Request $request, Closure $next, $role = null)
    {
        if (!session('is_login')) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if ($role && session('user_role') !== $role) {
            
            if (session('user_role') === 'warga') {
                return redirect()->route('warga.dashboard');
            } elseif (session('user_role') === 'pengunjung') {
                return redirect()->route('pengunjung.dashboard');
            }
            
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}