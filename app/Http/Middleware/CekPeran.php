<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CekPeran
{
    public function handle(Request $request, Closure $next, string ...$peranDiizinkan): Response
    {
        $peranUser = $request->user()?->peran;

        if (! $peranUser || ! in_array($peranUser, $peranDiizinkan)) {
            abort(403, 'Kamu tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}