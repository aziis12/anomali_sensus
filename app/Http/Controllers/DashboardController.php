<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isSuperadmin()) {
            return view('dashboard.superadmin');
        }

        return redirect()->route('data-anomali.index');
    }
}