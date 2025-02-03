<?php

namespace App\Http\Controllers;

use App\Models\Trial;
use Illuminate\Http\Request;

class TrialController extends Controller
{
    public function index()
    {
        $trials = Trial::latest()->paginate(10);
        return view('trials.index', compact('trials'));
    }

    public function create()
    {
        return view('trials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'm3u_link' => 'required|string|max:255'
        ]);

        Trial::create($validated);

        return redirect()->route('trials.index')
            ->with('success', 'Trial created successfully.');
    }

    public function edit(Trial $trial)
    {
        return response()->json($trial);
    }

    public function update(Request $request, Trial $trial)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'm3u_link' => 'required|string|max:255'
        ]);

        $trial->update($validated);

        return redirect()->route('trials.index')
            ->with('success', 'Trial updated successfully.');
    }

    public function destroy(Trial $trial)
    {
        $trial->delete();

        return redirect()->route('trials.index')
            ->with('success', 'Trial deleted successfully.');
    }
}
