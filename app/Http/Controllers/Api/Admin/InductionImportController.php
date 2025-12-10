<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Induction;
use App\Models\Chapter;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InductionImportController extends Controller
{
    /**
     * Import induction, chapters, and questions from a CSV file.
     *
     * Expected CSV columns (header row required):
     * induction_title, induction_description, induction_is_active (0/1), induction_display_order,
     * chapter_title, chapter_description, chapter_video_url, chapter_display_order, pass_percentage,
     * question_text, question_type (text|single_choice|multi_choice), question_options (pipe separated),
     * question_correct_answer (pipe separated for multi), question_display_order
     *
     * A single CSV describes one induction; chapters are grouped by chapter_title + display_order.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');

        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return response()->json(['message' => 'Unable to read uploaded file'], 422);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            return response()->json(['message' => 'CSV header missing'], 422);
        }

        $normalizedHeader = array_map(function ($h) {
            return strtolower(trim($h));
        }, $header);

        $requiredColumns = [
            'induction_title',
            'chapter_title',
            'question_text',
            'question_type',
        ];

        foreach ($requiredColumns as $col) {
            if (!in_array($col, $normalizedHeader, true)) {
                return response()->json(['message' => "Missing required column: {$col}"], 422);
            }
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === 1 && trim($row[0]) === '') {
                continue; // skip empty lines
            }
            $rows[] = array_combine($normalizedHeader, $row);
        }

        if (empty($rows)) {
            return response()->json(['message' => 'No data rows found in CSV'], 422);
        }

        $result = DB::transaction(function () use ($rows) {
            // Use the first row for induction metadata
            $first = $rows[0];
            $induction = Induction::create([
                'title' => $first['induction_title'],
                'description' => $first['induction_description'] ?? null,
                'is_active' => isset($first['induction_is_active']) ? (bool)$first['induction_is_active'] : true,
                'display_order' => isset($first['induction_display_order']) ? (int)$first['induction_display_order'] : 0,
            ]);

            $chapterMap = [];

            foreach ($rows as $row) {
                $chapterKey = ($row['chapter_title'] ?? '') . '|' . ($row['chapter_display_order'] ?? '');

                if (!isset($chapterMap[$chapterKey])) {
                    $chapter = Chapter::create([
                        'induction_id' => $induction->id,
                        'title' => $row['chapter_title'],
                        'description' => $row['chapter_description'] ?? null,
                        'video_url' => $row['chapter_video_url'] ?? null,
                        'display_order' => isset($row['chapter_display_order']) ? (int)$row['chapter_display_order'] : 0,
                        'pass_percentage' => isset($row['pass_percentage']) ? (int)$row['pass_percentage'] : 70,
                    ]);
                    $chapterMap[$chapterKey] = $chapter;
                } else {
                    $chapter = $chapterMap[$chapterKey];
                }

                // Create question for this row
                $optionsRaw = $row['question_options'] ?? '';
                $options = [];
                if (!empty($optionsRaw)) {
                    // Support JSON array or pipe-separated values
                    $maybeJson = json_decode($optionsRaw, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($maybeJson)) {
                        $options = $maybeJson;
                    } else {
                        $options = array_values(array_filter(array_map('trim', explode('|', $optionsRaw)), fn($v) => $v !== ''));
                    }
                }
                // Normalize options to objects { id, label } to match frontend expectations
                $normalizedOptions = [];
                foreach ($options as $idx => $opt) {
                    if (is_array($opt) && isset($opt['id'], $opt['label'])) {
                        $normalizedOptions[] = [
                            'id' => (string)$opt['id'],
                            'label' => (string)$opt['label'],
                        ];
                    } else {
                        $label = is_string($opt) ? $opt : (string)$opt;
                        $normalizedOptions[] = [
                            'id' => (string)($idx + 1),
                            'label' => $label,
                        ];
                    }
                }

                $questionData = [
                    'chapter_id' => $chapter->id,
                    'question_text' => $row['question_text'],
                    'type' => $row['question_type'] ?? 'text',
                    'options' => $normalizedOptions,
                    'display_order' => isset($row['question_display_order']) ? (int)$row['question_display_order'] : 0,
                ];

                // Correct answer handling
                $correctRaw = $row['question_correct_answer'] ?? null;
                if ($correctRaw !== null && $correctRaw !== '') {
                    $correctAnswers = array_values(array_filter(array_map('trim', explode('|', $correctRaw)), fn($v) => $v !== ''));
                    $optionLabelsToId = collect($normalizedOptions)->mapWithKeys(function ($opt) {
                        return [strtolower($opt['label']) => $opt['id']];
                    });

                    $mappedAnswers = array_map(function ($ans) use ($optionLabelsToId) {
                        $key = strtolower($ans);
                        return $optionLabelsToId[$key] ?? $ans;
                    }, $correctAnswers);

                    if ($questionData['type'] === 'text' && count($mappedAnswers) === 1) {
                        $questionData['correct_answer'] = $mappedAnswers[0];
                    } else {
                        $questionData['correct_answer'] = $mappedAnswers;
                    }
                }

                Question::create($questionData);
            }

            return $induction->load(['chapters.questions']);
        });

        return response()->json([
            'message' => 'Import successful',
            'induction' => $result,
        ], 201);
    }
}

