<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoCompletion extends Model
{
    protected $fillable = [
        'user_id',
        'chapter_id',
        'submission_id',
        'is_completed',
        'progress_percentage',
        'watched_seconds',
        'total_seconds',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'progress_percentage' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns this completion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the chapter for this completion.
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Get the submission for this completion.
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * Mark video as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'progress_percentage' => 100.00,
            'completed_at' => now(),
        ]);
    }
}
