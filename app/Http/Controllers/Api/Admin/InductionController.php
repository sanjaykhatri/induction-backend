<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Induction;
use Illuminate\Http\Request;

class InductionController extends Controller
{
    /**
     * Display a listing of inductions.
     */
    public function index()
    {
        $inductions = Induction::with('chapters')
            ->orderBy('display_order')
            ->get();

        return response()->json($inductions);
    }

    /**
     * Store a newly created induction.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ]);

        $induction = Induction::create($validated);

        return response()->json($induction, 201);
    }

    /**
     * Display the specified induction.
     */
    public function show(Induction $induction)
    {
        $induction->load(['chapters.questions']);
        return response()->json($induction);
    }

    /**
     * Update the specified induction.
     */
    public function update(Request $request, Induction $induction)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ]);

        $induction->update($validated);

        return response()->json($induction);
    }

    /**
     * Remove the specified induction.
     */
    public function destroy(Induction $induction)
    {
        $induction->delete();
        return response()->json(['message' => 'Induction deleted successfully']);
    }

    /**
     * Reorder inductions.
     */
    public function reorder(Request $request, Induction $induction)
    {
        $request->validate([
            'display_order' => 'required|integer',
        ]);

        $induction->update(['display_order' => $request->display_order]);

        return response()->json($induction);
    }
}
