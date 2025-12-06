<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SubmissionCompleted;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SubmissionController extends Controller
{
    /**
     * Get submission details.
     */
    public function show(Request $request, Submission $submission)
    {
        // Ensure user owns this submission
        if ($submission->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $submission->load(['user', 'induction', 'answers.question']);

        // Enrich snapshot with correct_answer from database if missing
        $snapshot = $submission->induction_snapshot;
        if ($snapshot && isset($snapshot['chapters'])) {
            foreach ($snapshot['chapters'] as &$chapterData) {
                if (isset($chapterData['questions'])) {
                    foreach ($chapterData['questions'] as &$questionData) {
                        // If correct_answer is missing or null, fetch from database
                        if (!isset($questionData['correct_answer']) || $questionData['correct_answer'] === null || $questionData['correct_answer'] === []) {
                            $actualQuestion = \App\Models\Question::find($questionData['id']);
                            if ($actualQuestion && $actualQuestion->correct_answer) {
                                $questionData['correct_answer'] = $actualQuestion->correct_answer;
                            }
                        }
                    }
                }
            }
            // Update the submission's snapshot with enriched data
            $submission->induction_snapshot = $snapshot;
        }

        return response()->json($submission);
    }

    /**
     * Submit answers for a chapter.
     */
    public function submitAnswers(Request $request, Submission $submission)
    {
        // Ensure user owns this submission
        if ($submission->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($submission->status === 'completed') {
            return response()->json(['message' => 'Submission already completed'], 400);
        }

        $request->validate([
            'chapter_id' => 'required|exists:chapters,id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer_payload' => 'required',
        ]);

        // Check if video is completed for this chapter
        $videoCompletion = \App\Models\VideoCompletion::where('user_id', $request->user()->id)
            ->where('chapter_id', $request->chapter_id)
            ->where('submission_id', $submission->id)
            ->first();

        if (!$videoCompletion || !$videoCompletion->is_completed) {
            return response()->json([
                'message' => 'You must complete watching the video before answering questions.',
                'video_completed' => false,
            ], 403);
        }

        foreach ($request->answers as $answerData) {
            $submission->answers()->updateOrCreate(
                ['question_id' => $answerData['question_id']],
                ['answer_payload' => $answerData['answer_payload']]
            );
        }

        // Check if all questions for all chapters are answered
        $snapshot = $submission->induction_snapshot;
        $allQuestionsAnswered = true;
        $lastUnansweredChapter = null;
        $lastUnansweredQuestion = null;

        if ($snapshot && isset($snapshot['chapters'])) {
            foreach ($snapshot['chapters'] as $chapterData) {
                $chapterQuestions = $chapterData['questions'] ?? [];
                foreach ($chapterQuestions as $questionData) {
                    $answer = $submission->answers()->where('question_id', $questionData['id'])->first();
                    if (!$answer) {
                        $allQuestionsAnswered = false;
                        if (!$lastUnansweredChapter) {
                            $lastUnansweredChapter = $chapterData;
                            $lastUnansweredQuestion = $questionData;
                        }
                    }
                }
            }
        }

        // Update status based on completion
        if ($allQuestionsAnswered) {
            // All questions answered, but check if all videos are completed
            $allVideosCompleted = true;
            if ($snapshot && isset($snapshot['chapters'])) {
                foreach ($snapshot['chapters'] as $chapterData) {
                    $videoCompletion = \App\Models\VideoCompletion::where('user_id', $request->user()->id)
                        ->where('chapter_id', $chapterData['id'])
                        ->where('submission_id', $submission->id)
                        ->where('is_completed', true)
                        ->first();
                    if (!$videoCompletion) {
                        $allVideosCompleted = false;
                        break;
                    }
                }
            }

            if ($allVideosCompleted) {
                // Check for new chapters before marking as completed
                $induction = $submission->induction;
                $existingChapterIds = collect($snapshot['chapters'] ?? [])->pluck('id')->toArray();
                $currentChapterIds = $induction->chapters()->pluck('id')->toArray();
                $newChapterIds = array_diff($currentChapterIds, $existingChapterIds);
                
                if (empty($newChapterIds)) {
                    // All videos and questions completed, and no new chapters - mark as completed
                    $submission->update(['status' => 'completed', 'completed_at' => now()]);
                    // Send email notification
                    $notificationEmail = env('SUBMISSION_NOTIFICATION_EMAIL', 'admin@example.com');
                    Mail::to($notificationEmail)->send(new SubmissionCompleted($submission));
                } else {
                    // All current chapters completed but new chapters exist - keep as pending
                    $submission->update(['status' => 'pending']);
                }
            } else {
                // All questions answered but videos not all completed - keep as pending
                $submission->update(['status' => 'pending']);
            }
        } else {
            // Not all questions answered - set to pending
            $submission->update(['status' => 'pending']);
        }

        return response()->json([
            'message' => 'Answers submitted successfully',
            'all_questions_answered' => $allQuestionsAnswered,
            'status' => $submission->status,
            'last_unanswered_chapter' => $lastUnansweredChapter ? [
                'id' => $lastUnansweredChapter['id'],
                'title' => $lastUnansweredChapter['title'],
                'display_order' => $lastUnansweredChapter['display_order'],
            ] : null,
        ]);
    }

    /**
     * Mark submission as completed and send email notification.
     * Only completes if all videos and questions are answered.
     */
    public function complete(Request $request, Submission $submission)
    {
        // Ensure user owns this submission
        if ($submission->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check for new chapters before completing and update submission if needed
        $induction = $submission->induction;
        $snapshot = $submission->induction_snapshot;
        $existingChapterIds = collect($snapshot['chapters'] ?? [])->pluck('id')->toArray();
        $currentChapterIds = $induction->chapters()->pluck('id')->toArray();
        $newChapterIds = array_diff($currentChapterIds, $existingChapterIds);
        
        // If there are new chapters, update the submission and don't allow completion
        if (!empty($newChapterIds)) {
            // Update submission with new chapters (similar to InductionController)
            $currentChapters = $induction->chapters()->orderBy('display_order')->get();
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
                    'pass_percentage' => $chapter->pass_percentage,
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
            
            return response()->json([
                'message' => 'New chapters have been added. Please complete them before finalizing.',
                'submission' => $submission->load(['user', 'induction']),
                'has_new_chapters' => true,
            ], 200); // Return 200 instead of 400 so frontend can handle it gracefully
        }

        if ($submission->status === 'completed') {
            return response()->json(['message' => 'Submission already completed'], 400);
        }

        // Check if all questions are answered
        $allQuestionsAnswered = true;
        $missingAnswers = [];

        if ($snapshot && isset($snapshot['chapters'])) {
            foreach ($snapshot['chapters'] as $chapterData) {
                $chapterQuestions = $chapterData['questions'] ?? [];
                foreach ($chapterQuestions as $questionData) {
                    $answer = $submission->answers()->where('question_id', $questionData['id'])->first();
                    if (!$answer) {
                        $allQuestionsAnswered = false;
                        $missingAnswers[] = [
                            'chapter' => $chapterData['title'],
                            'question' => $questionData['question_text'],
                        ];
                    }
                }
            }
        }

        // Check if all videos are completed
        $allVideosCompleted = true;
        if ($snapshot && isset($snapshot['chapters'])) {
            foreach ($snapshot['chapters'] as $chapterData) {
                $videoCompletion = \App\Models\VideoCompletion::where('user_id', $request->user()->id)
                    ->where('chapter_id', $chapterData['id'])
                    ->where('submission_id', $submission->id)
                    ->where('is_completed', true)
                    ->first();
                if (!$videoCompletion) {
                    $allVideosCompleted = false;
                    break;
                }
            }
        }

        if (!$allQuestionsAnswered || !$allVideosCompleted) {
            return response()->json([
                'message' => 'Cannot complete submission. Please ensure all videos are watched and all questions are answered.',
                'all_questions_answered' => $allQuestionsAnswered,
                'all_videos_completed' => $allVideosCompleted,
                'missing_answers' => $missingAnswers,
            ], 400);
        }

        $submission->markAsCompleted();

        // Send email notification
        $notificationEmail = env('SUBMISSION_NOTIFICATION_EMAIL', 'admin@example.com');
        Mail::to($notificationEmail)->send(new SubmissionCompleted($submission));

        return response()->json([
            'message' => 'Submission completed successfully',
            'submission' => $submission->load(['user', 'induction']),
        ]);
    }

    /**
     * Get the last unanswered question/chapter for resuming.
     */
    public function getLastUnanswered(Request $request, Submission $submission)
    {
        // Ensure user owns this submission
        if ($submission->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $snapshot = $submission->induction_snapshot;
        $lastUnansweredChapter = null;
        $lastUnansweredQuestion = null;

        if ($snapshot && isset($snapshot['chapters'])) {
            foreach ($snapshot['chapters'] as $chapterData) {
                // Check if video is completed
                $videoCompletion = \App\Models\VideoCompletion::where('user_id', $request->user()->id)
                    ->where('chapter_id', $chapterData['id'])
                    ->where('submission_id', $submission->id)
                    ->where('is_completed', true)
                    ->first();

                if (!$videoCompletion) {
                    // Video not completed - this is where user should resume
                    $lastUnansweredChapter = $chapterData;
                    break;
                }

                // Check questions
                $chapterQuestions = $chapterData['questions'] ?? [];
                foreach ($chapterQuestions as $questionData) {
                    $answer = $submission->answers()->where('question_id', $questionData['id'])->first();
                    if (!$answer) {
                        $lastUnansweredChapter = $chapterData;
                        $lastUnansweredQuestion = $questionData;
                        break;
                    }
                }

                if ($lastUnansweredQuestion) {
                    break;
                }
            }
        }

        return response()->json([
            'last_unanswered_chapter' => $lastUnansweredChapter ? [
                'id' => $lastUnansweredChapter['id'],
                'title' => $lastUnansweredChapter['title'],
                'display_order' => $lastUnansweredChapter['display_order'],
            ] : null,
            'last_unanswered_question' => $lastUnansweredQuestion ? [
                'id' => $lastUnansweredQuestion['id'],
                'question_text' => $lastUnansweredQuestion['question_text'],
            ] : null,
        ]);
    }
}
