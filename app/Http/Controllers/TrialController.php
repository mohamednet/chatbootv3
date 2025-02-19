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
            'm3u_link' => 'required|string|max:255'
        ]);

        // Parse the M3U link
        $m3u_link = $validated['m3u_link'];
        $parsed_url = parse_url($m3u_link);
        
        if (!$parsed_url || !isset($parsed_url['query'])) {
            return redirect()->back()->with('error', 'Invalid M3U link format');
        }

        // Parse query parameters
        parse_str($parsed_url['query'], $query_params);
        
        // Check required parameters
        if (!isset($query_params['username']) || !isset($query_params['password'])) {
            return redirect()->back()->with('error', 'M3U link must contain username and password');
        }

        // Extract base URL
        $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];

        // Create trial with extracted data
        Trial::create([
            'username' => $query_params['username'],
            'password' => $query_params['password'],
            'url' => $base_url,
            'm3u_link' => $m3u_link
        ]);

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
