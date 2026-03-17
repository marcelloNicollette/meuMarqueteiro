<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FederalProgramController extends Controller
{
    public function index()
    {
        $municipality = auth()->user()->municipality;
        $programs     = $municipality->federalPrograms()
            ->orderByDesc('match_score')
            ->orderBy('deadline')
            ->get();

        $total = $programs->count();

        return view('mayor.federal-programs.index', compact('municipality', 'programs', 'total'));
    }

    public function askAssistant(Request $request, $program)
    {
        return response()->json(['redirect' => route('mayor.chat.index')]);
    }
}
