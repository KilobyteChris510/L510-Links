<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('dashboard', [
            'user' => $request->user(),
            'groups' => $request->user()->linkGroups()->withCount('links')->latest()->latest('id')->paginate(10),
        ]);
    }
}
