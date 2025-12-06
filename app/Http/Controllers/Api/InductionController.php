<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Induction;
use App\Models\Submission;
use Illuminate\Http\Request;

class InductionController extends Controller
{
    /**
     * Get all active inductions.
     */
    public function active()
    {
        $inductions = Induction::active()
            ->orderBy('display_order')
            ->get();

        return response()->json($inductions);
    }

    /**
     * Get completed submission for viewing (read-only).
     */
    public function getCompleted(Request $request, Induction $induction)
    {
        $completedSubmission = Submission::where('user_id', $request->user()->id)
            ->where('induction_id', $induction->id)
            ->where('status', 'completed')
            ->with(['induction', 'user', 'answers'])
            ->first();

        if (!$completedSubmission) {
            return response()->json(['message' => 'No completed submission found'], 404);
        }

        return response()->json($completedSubmission);
    }

    /**
     * Check for new chapters and update submission if needed.
     */
    private function updateSubmissionWithNewChapters(Submission $submission, Induction $induction)
    {
        $snapshot = $submission->induction_snapshot;
        $existingChapterIds = collect($snapshot['chapters'] ?? [])->pluck('id')->toArray();
        
        // Get all current chapters from induction
        $currentChapters = $induction->chapters()->orderBy('display_order')->get();
        $currentChapterIds = $currentChapters->pluck('id')->toArray();
        
        // Find new chapters (chapters that exist in induction but not in snapshot)
        $newChapterIds = array_diff($currentChapterIds, $existingChapterIds);
        
        if (empty($newChapterIds)) {
            return false; // No new chapters
        }
        
        // Build snapshot for new chapters
        $newChapters = $currentChapters->whereIn('id', $newChapterIds)->map(function ($chapter) {
            $videoUrl = $chapter->video_path 
                ? \Storage::disk('public')->url($chapter->video_path)
                : ($chapter->video_url ?? null);

            return [
                'id' => $chapter->id,
                'title' => $chapter->title,
                'description' => $chapter->description,
                'video_url' => $videoUrl,
                'video_path' => $chapter->video_path,
                'video_filename' => $chapter->video_filename,
                'video_duration' => $chapter->video_duration,
                'display_order' => $chapter->display_order,
                'questions' => $chapter->questions()->orderBy('display_order')->get()->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'question_text' => $question->question_text,
                        'type' => $question->type,
                        'options' => $question->options,
                        'correct_answer' => $question->correct_answer,
                        'display_order' => $question->display_order,
                    ];
                })->toArray(),
            ];
        })->toArray();
        
        // Merge new chapters with existing ones, maintaining order
        $allChapters = collect($snapshot['chapters'] ?? [])
            ->merge($newChapters)
            ->sortBy('display_order')
            ->values()
            ->toArray();
        
        // Update snapshot
        $snapshot['chapters'] = $allChapters;
        
        // Update submission status to pending if it was completed
        $newStatus = $submission->status === 'completed' ? 'pending' : $submission->status;
        
        $submission->update([
            'induction_snapshot' => $snapshot,
            'status' => $newStatus,
        ]);
        
        return true; // New chapters added
    }

    /**
     * Start an induction (create submission).
     */
    public function start(Request $request, Induction $induction)
    {
        if (!$induction->is_active) {
            return response()->json(['message' => 'Induction is not active'], 400);
        }

        // Check if user already has a completed submission
        $completedSubmission = Submission::where('user_id', $request->user()->id)
            ->where('induction_id', $induction->id)
            ->where('status', 'completed')
            ->first();

        if ($completedSubmission) {
            // Check for new chapters and update submission if needed
            $hasNewChapters = $this->updateSubmissionWithNewChapters($completedSubmission, $induction);
            
            // Reload submission to get updated data
            $completedSubmission->refresh();
            
            if ($hasNewChapters) {
                return response()->json([
                    'message' => 'New chapters have been added to this induction. Please complete them.',
                    'submission' => $completedSubmission->load(['induction', 'user']),
                    'has_new_chapters' => true,
                ], 200);
            }
            
            return response()->json([
                'message' => 'You have already completed this induction.',
                'submission' => $completedSubmission->load(['induction', 'user']),
                'completed' => true,
            ], 200);
        }

        // Check if user already has an in-progress or pending submission
        $existingSubmission = Submission::where('user_id', $request->user()->id)
            ->where('induction_id', $induction->id)
            ->whereIn('status', ['in_progress', 'pending'])
            ->first();

        if ($existingSubmission) {
            return response()->json($existingSubmission->load(['induction', 'user']));
        }

        // Create snapshot of induction structure
        $snapshot = [
            'induction' => [
                'id' => $induction->id,
                'title' => $induction->title,
                'description' => $induction->description,
            ],
            'chapters' => $induction->chapters()->orderBy('display_order')->get()->map(function ($chapter) {
                // Get video URL (from path or URL)
                $videoUrl = $chapter->video_path 
                    ? \Storage::disk('public')->url($chapter->video_path)
                    : ($chapter->video_url ?? null);

                return [
                    'id' => $chapter->id,
                    'title' => $chapter->title,
                    'description' => $chapter->description,
                    'video_url' => $videoUrl,
                    'video_path' => $chapter->video_path,
                    'video_filename' => $chapter->video_filename,
                    'video_duration' => $chapter->video_duration,
                    'display_order' => $chapter->display_order,
                    'pass_percentage' => $chapter->pass_percentage ?? 70,
                    'questions' => $chapter->questions()->orderBy('display_order')->get()->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'question_text' => $question->question_text,
                            'type' => $question->type,
                            'options' => $question->options,
                            'correct_answer' => $question->correct_answer, // Include for admin comparison
                            'display_order' => $question->display_order,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];

        $submission = Submission::create([
            'user_id' => $request->user()->id,
            'induction_id' => $induction->id,
            'status' => 'in_progress',
            'induction_snapshot' => $snapshot,
        ]);

        return response()->json($submission->load(['induction', 'user']));
    }
}
