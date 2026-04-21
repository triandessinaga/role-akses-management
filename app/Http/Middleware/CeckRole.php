<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CeckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // return $next($request);
        if (!auth()->check()) {
            abort(403, 'Akses Ditolak: Anda harus login untuk mengakses halaman ini.');
            return redirect()->route('login');
        }
        if (!in_array(auth()->user()->role, ['admin', 'user','superadmin'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

    }
}
