<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Induction;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    /**
     * Display a listing of chapters for an induction.
     */
    public function index(Induction $induction)
    {
        $chapters = $induction->chapters()->with('questions')->get();
        return response()->json($chapters);
    }

    /**
     * Store a newly created chapter.
     */
    public function store(Request $request, Induction $induction)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'nullable|string', // Optional if video file is uploaded
            'video_file' => 'nullable|file|mimes:mp4,avi,mov,wmv,flv,webm|max:102400', // Max 100MB
            'display_order' => 'nullable|integer',
            'pass_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        $validated['induction_id'] = $induction->id;

        // Handle video file upload
        if ($request->hasFile('video_file')) {
            $file = $request->file('video_file');
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('videos', $filename, 'public');
            
            $validated['video_path'] = $path;
            $validated['video_filename'] = $file->getClientOriginalName();
            $validated['video_url'] = null; // Clear URL if file is uploaded
            // Note: video_duration would need to be extracted using a video processing library
        } elseif (!$request->has('video_url') || empty($request->video_url)) {
            return response()->json([
                'message' => 'Either video_url or video_file is required'
            ], 422);
        }

        $chapter = Chapter::create($validated);

        // Return chapter with video URL
        $chapter->load('questions');
        if ($chapter->video_path) {
            $chapter->video_url = \Storage::disk('public')->url($chapter->video_path);
        }

        return response()->json($chapter, 201);
    }

    /**
     * Update the specified chapter.
     */
    public function update(Request $request, Chapter $chapter)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'nullable|string',
            'video_file' => 'nullable|file|mimes:mp4,avi,mov,wmv,flv,webm|max:102400', // Max 100MB
            'display_order' => 'integer',
            'pass_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        // Handle video file upload
        if ($request->hasFile('video_file')) {
            // Delete old video file if exists
            if ($chapter->video_path && \Storage::disk('public')->exists($chapter->video_path)) {
                \Storage::disk('public')->delete($chapter->video_path);
            }

            $file = $request->file('video_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('videos', $filename, 'public');
            
            $validated['video_path'] = $path;
            $validated['video_filename'] = $file->getClientOriginalName();
            $validated['video_url'] = null; // Clear URL if file is uploaded
        }

        $chapter->update($validated);

        // Return chapter with video URL
        $chapter->load('questions');
        if ($chapter->video_path) {
            $chapter->video_url = \Storage::disk('public')->url($chapter->video_path);
        }

        return response()->json($chapter);
    }

    /**
     * Remove the specified chapter.
     */
    public function destroy(Chapter $chapter)
    {
        $chapter->delete();
        return response()->json(['message' => 'Chapter deleted successfully']);
    }

    /**
     * Reorder chapters.
     */
    public function reorder(Request $request, Chapter $chapter)
    {
        $request->validate([
            'display_order' => 'required|integer',
        ]);

        $chapter->update(['display_order' => $request->display_order]);

        return response()->json($chapter);
    }
}
