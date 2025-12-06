<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    /**
     * Display a listing of submissions.
     */
    public function index(Request $request)
    {
        $query = Submission::with(['user', 'induction']);

        // Filter by induction if provided
        if ($request->has('induction_id')) {
            $query->where('induction_id', $request->induction_id);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range if provided
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $submissions = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($submissions);
    }

    /**
     * Display the specified submission with answer comparison.
     */
    public function show(Submission $submission)
    {
        $submission->load(['user', 'induction', 'answers.question']);
        
        // Get snapshot to access all questions
        $snapshot = $submission->induction_snapshot;
        $questionsData = [];
        $totalQuestions = 0;
        $correctAnswers = 0;
        $wrongAnswers = 0;
        $unanswered = 0;
        
        if ($snapshot && isset($snapshot['chapters'])) {
            foreach ($snapshot['chapters'] as $chapterData) {
                $chapterQuestions = $chapterData['questions'] ?? [];
                foreach ($chapterQuestions as $questionData) {
                    $totalQuestions++;
                    $userAnswer = $submission->answers()->where('question_id', $questionData['id'])->first();
                    $isCorrect = false;
                    $isAnswered = false;
                    
                    // Get correct_answer from snapshot, or fetch from database if not in snapshot
                    $correctAnswer = $questionData['correct_answer'] ?? null;
                    if ($correctAnswer === null || $correctAnswer === []) {
                        // Fetch from actual question in database
                        $actualQuestion = \App\Models\Question::find($questionData['id']);
                        if ($actualQuestion && $actualQuestion->correct_answer) {
                            $correctAnswer = $actualQuestion->correct_answer;
                        }
                    }
                    
                    // Ensure correct_answer is in array format (it should be from DB cast, but handle snapshot data)
                    if ($correctAnswer !== null && !is_array($correctAnswer)) {
                        $correctAnswer = [$correctAnswer];
                    }
                    
                    if ($userAnswer) {
                        $isAnswered = true;
                        $userAnswerPayload = $userAnswer->answer_payload;
                        
                        // Compare answers based on question type
                        // Note: correct_answer is stored as array in database (JSON)
                        $correctAnswerArray = is_array($correctAnswer) ? $correctAnswer : ($correctAnswer ? [$correctAnswer] : []);
                        
                        if (empty($correctAnswerArray)) {
                            // No correct answer set - cannot determine if correct
                            $isCorrect = false;
                        } elseif ($questionData['type'] === 'single_choice') {
                            // For single choice, correct_answer is array with one element
                            $correctValue = $correctAnswerArray[0];
                            // User answer might be string or already in array format (from JSON cast)
                            $userValue = is_array($userAnswerPayload) && !empty($userAnswerPayload) 
                                ? $userAnswerPayload[0] 
                                : $userAnswerPayload;
                            // Compare as strings to handle type differences
                            $isCorrect = (string)$userValue === (string)$correctValue;
                        } elseif ($questionData['type'] === 'multi_choice') {
                            // For multi-choice, compare arrays (order doesn't matter)
                            $userArray = is_array($userAnswerPayload) ? $userAnswerPayload : [$userAnswerPayload];
                            // Convert all values to strings for comparison (handle int vs string)
                            $userArray = array_map('strval', array_filter($userArray, function($v) { return $v !== null && $v !== ''; }));
                            $correctArray = array_map('strval', array_filter($correctAnswerArray, function($v) { return $v !== null && $v !== ''; }));
                            sort($userArray);
                            sort($correctArray);
                            // Compare arrays - both must have same length and same values
                            $isCorrect = count($userArray) === count($correctArray) && $userArray === $correctArray;
                        } else {
                            // Text questions - case-insensitive comparison
                            // correct_answer is array with one element (the expected text)
                            $correctText = is_string($correctAnswerArray[0]) 
                                ? strtolower(trim($correctAnswerArray[0])) 
                                : '';
                            $userText = is_string($userAnswerPayload) ? strtolower(trim($userAnswerPayload)) : '';
                            $isCorrect = $userText === $correctText && $userText !== '' && $correctText !== '';
                        }
                        
                        if ($isCorrect) {
                            $correctAnswers++;
                        } else {
                            $wrongAnswers++;
                        }
                    } else {
                        $unanswered++;
                    }
                    
                    $questionsData[] = [
                        'question_id' => $questionData['id'],
                        'chapter_id' => $chapterData['id'],
                        'chapter_title' => $chapterData['title'],
                        'question_text' => $questionData['question_text'],
                        'question_type' => $questionData['type'],
                        'question_options' => $questionData['options'] ?? [],
                        'correct_answer' => $correctAnswer, // Use the fetched correct_answer
                        'user_answer' => $userAnswer ? $userAnswer->answer_payload : null,
                        'is_correct' => $isCorrect,
                        'is_answered' => $isAnswered,
                    ];
                }
            }
        }
        
        return response()->json([
            'submission' => $submission,
            'statistics' => [
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctAnswers,
                'wrong_answers' => $wrongAnswers,
                'unanswered' => $unanswered,
                'score_percentage' => $totalQuestions > 0 
                    ? round(($correctAnswers / $totalQuestions) * 100, 2) 
                    : 0,
            ],
            'questions' => $questionsData,
        ]);
    }
}
