<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanPetugasPilihPml
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isPetugas() && ! session('nama_pml_dipilih')) {
            return redirect()->route('pilih-pml.create');
        }

        return $next($request);
    }
}