<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    protected $fillable = [
        'user_id',
        'induction_id',
        'status',
        'induction_snapshot',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'induction_snapshot' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns this submission.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the induction for this submission.
     */
    public function induction(): BelongsTo
    {
        return $this->belongsTo(Induction::class);
    }

    /**
     * Get all answers for this submission.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * Get all video completions for this submission.
     */
    public function videoCompletions(): HasMany
    {
        return $this->hasMany(VideoCompletion::class);
    }

    /**
     * Mark submission as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
