<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\VideoCompletion;
use Illuminate\Http\Request;

class UserProgressController extends Controller
{
    /**
     * Get user's progress for all inductions.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get all submissions with their inductions
        $submissions = Submission::where('user_id', $user->id)
            ->with(['induction', 'videoCompletions.chapter'])
            ->get();

        $progress = [];

        foreach ($submissions as $submission) {
            $induction = $submission->induction;
            $snapshot = $submission->induction_snapshot;

            if (!$snapshot || !isset($snapshot['chapters'])) {
                continue;
            }

            $chapters = [];
            $totalChapters = count($snapshot['chapters']);
            $completedChapters = 0;

            foreach ($snapshot['chapters'] as $chapterData) {
                $completion = VideoCompletion::where('user_id', $user->id)
                    ->where('chapter_id', $chapterData['id'])
                    ->where('submission_id', $submission->id)
                    ->first();

                $isCompleted = $completion?->is_completed ?? false;
                if ($isCompleted) {
                    $completedChapters++;
                }

                $chapters[] = [
                    'id' => $chapterData['id'],
                    'title' => $chapterData['title'],
                    'description' => $chapterData['description'] ?? null,
                    'is_completed' => $isCompleted,
                    'progress_percentage' => $completion?->progress_percentage ?? 0,
                    'completed_at' => $completion?->completed_at ?? null,
                ];
            }

            $progress[] = [
                'submission_id' => $submission->id,
                'induction_id' => $induction->id,
                'induction' => [
                    'id' => $induction->id,
                    'title' => $induction->title,
                    'description' => $induction->description,
                ],
                'status' => $submission->status,
                'chapters' => $chapters,
                'chapters_completed' => $completedChapters,
                'total_chapters' => $totalChapters,
                'completed_at' => $submission->completed_at,
                'progress' => [
                    'total_chapters' => $totalChapters,
                    'completed_chapters' => $completedChapters,
                    'completion_percentage' => $totalChapters > 0 
                        ? round(($completedChapters / $totalChapters) * 100, 2) 
                        : 0,
                ],
                'submission_completed_at' => $submission->completed_at,
                'started_at' => $submission->created_at,
            ];
        }

        return response()->json($progress);
    }

    /**
     * Get user's progress for a specific submission.
     */
    public function show(Request $request, Submission $submission)
    {
        // Ensure user owns this submission
        if ($submission->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = $request->user();
        $snapshot = $submission->induction_snapshot;

        if (!$snapshot || !isset($snapshot['chapters'])) {
            return response()->json(['message' => 'Invalid submission data'], 400);
        }

        $chapters = [];
        $totalChapters = count($snapshot['chapters']);
        $completedChapters = 0;

        foreach ($snapshot['chapters'] as $chapterData) {
            $completion = VideoCompletion::where('user_id', $user->id)
                ->where('chapter_id', $chapterData['id'])
                ->where('submission_id', $submission->id)
                ->first();

            $isCompleted = $completion?->is_completed ?? false;
            if ($isCompleted) {
                $completedChapters++;
            }

            $chapters[] = [
                'id' => $chapterData['id'],
                'title' => $chapterData['title'],
                'description' => $chapterData['description'] ?? null,
                'video_url' => $chapterData['video_url'] ?? null,
                'is_completed' => $isCompleted,
                'progress_percentage' => $completion?->progress_percentage ?? 0,
                'completed_at' => $completion?->completed_at ?? null,
                'questions_count' => count($chapterData['questions'] ?? []),
            ];
        }

        return response()->json([
            'submission' => $submission->load(['induction', 'user']),
            'chapters' => $chapters,
            'progress' => [
                'total_chapters' => $totalChapters,
                'completed_chapters' => $completedChapters,
                'completion_percentage' => $totalChapters > 0 
                    ? round(($completedChapters / $totalChapters) * 100, 2) 
                    : 0,
            ],
        ]);
    }
}
