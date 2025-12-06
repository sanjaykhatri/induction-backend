<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    /**
     * Display a listing of questions for a chapter.
     */
    public function index(Chapter $chapter)
    {
        $questions = $chapter->questions()->get();
        return response()->json($questions);
    }

    /**
     * Store a newly created question.
     */
    public function store(Request $request, Chapter $chapter)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'type' => 'required|in:single_choice,multi_choice,text',
            'options' => 'nullable|array',
            'options.*.id' => 'required|string',
            'options.*.label' => 'required|string',
            'correct_answer' => 'nullable', // Accept any type, will normalize below
            'display_order' => 'integer',
        ]);

        // Normalize correct_answer based on question type
        $validated['correct_answer'] = $this->normalizeCorrectAnswer(
            $validated['type'] ?? $request->type,
            $validated['correct_answer'] ?? $request->correct_answer
        );

        $validated['chapter_id'] = $chapter->id;
        $question = Question::create($validated);

        return response()->json($question, 201);
    }

    /**
     * Update the specified question.
     */
    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'question_text' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:single_choice,multi_choice,text',
            'options' => 'nullable|array',
            'options.*.id' => 'required|string',
            'options.*.label' => 'required|string',
            'correct_answer' => 'nullable', // Accept any type, will normalize below
            'display_order' => 'integer',
        ]);

        // Normalize correct_answer based on question type
        $questionType = $validated['type'] ?? $question->type;
        if (isset($validated['correct_answer'])) {
            $validated['correct_answer'] = $this->normalizeCorrectAnswer(
                $questionType,
                $validated['correct_answer']
            );
        }

        $question->update($validated);

        return response()->json($question);
    }

    /**
     * Normalize correct_answer based on question type.
     * The model expects an array (JSON), so we convert accordingly.
     */
    private function normalizeCorrectAnswer(string $type, $correctAnswer)
    {
        if ($correctAnswer === null || $correctAnswer === '') {
            return null;
        }

        switch ($type) {
            case 'single_choice':
                // Convert string to array with single element
                return is_array($correctAnswer) ? $correctAnswer : [$correctAnswer];
            
            case 'multi_choice':
                // Ensure it's an array
                return is_array($correctAnswer) ? $correctAnswer : [$correctAnswer];
            
            case 'text':
                // For text questions, store as array with single string element
                // This allows for flexibility while maintaining JSON array format
                return is_array($correctAnswer) ? $correctAnswer : [$correctAnswer];
            
            default:
                return is_array($correctAnswer) ? $correctAnswer : [$correctAnswer];
        }
    }

    /**
     * Remove the specified question.
     */
    public function destroy(Question $question)
    {
        $question->delete();
        return response()->json(['message' => 'Question deleted successfully']);
    }

    /**
     * Reorder questions.
     */
    public function reorder(Request $request, Question $question)
    {
        $request->validate([
            'display_order' => 'required|integer',
        ]);

        $question->update(['display_order' => $request->display_order]);

        return response()->json($question);
    }
}
