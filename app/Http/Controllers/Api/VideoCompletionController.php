<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Submission;
use App\Models\VideoCompletion;
use Illuminate\Http\Request;

class VideoCompletionController extends Controller
{
    /**
     * Update video progress for a chapter.
     */
    public function updateProgress(Request $request, Chapter $chapter)
    {
        $request->validate([
            'submission_id' => 'required|exists:submissions,id',
            'progress_percentage' => 'required|numeric|min:0|max:100',
            'watched_seconds' => 'required|integer|min:0',
            'total_seconds' => 'nullable|integer|min:0',
        ]);

        $submission = Submission::findOrFail($request->submission_id);

        // Ensure user owns this submission
        if ($submission->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $isCompleted = $request->progress_percentage >= 100;

        $completion = VideoCompletion::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'chapter_id' => $chapter->id,
                'submission_id' => $request->submission_id,
            ],
            [
                'is_completed' => $isCompleted,
                'progress_percentage' => $request->progress_percentage,
                'watched_seconds' => $request->watched_seconds,
                'total_seconds' => $request->total_seconds,
                'completed_at' => $isCompleted ? now() : null,
            ]
        );

        return response()->json($completion);
    }

    /**
     * Mark video as completed.
     */
    public function markCompleted(Request $request, Chapter $chapter)
    {
        $request->validate([
            'submission_id' => 'required|exists:submissions,id',
            'total_seconds' => 'nullable|integer|min:0',
        ]);

        $submission = Submission::findOrFail($request->submission_id);

        // Ensure user owns this submission
        if ($submission->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $completion = VideoCompletion::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'chapter_id' => $chapter->id,
                'submission_id' => $request->submission_id,
            ],
            [
                'is_completed' => true,
                'progress_percentage' => 100.00,
                'watched_seconds' => $request->total_seconds ?? 0,
                'total_seconds' => $request->total_seconds,
                'completed_at' => now(),
            ]
        );

        return response()->json($completion);
    }

    /**
     * Check if video is completed for a chapter.
     */
    public function checkCompletion(Request $request, Chapter $chapter)
    {
        $request->validate([
            'submission_id' => 'required|exists:submissions,id',
        ]);

        $submission = Submission::findOrFail($request->submission_id);

        // Ensure user owns this submission
        if ($submission->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $completion = VideoCompletion::where('user_id', $request->user()->id)
            ->where('chapter_id', $chapter->id)
            ->where('submission_id', $request->submission_id)
            ->first();

        return response()->json([
            'is_completed' => $completion?->is_completed ?? false,
            'progress_percentage' => $completion?->progress_percentage ?? 0,
            'completion' => $completion,
        ]);
    }
}
