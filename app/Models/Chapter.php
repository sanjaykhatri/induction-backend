<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\VideoCompletion;

class Chapter extends Model
{
    protected $fillable = [
        'induction_id',
        'title',
        'description',
        'video_url',
        'video_path',
        'video_filename',
        'video_duration',
        'display_order',
        'pass_percentage',
    ];

    /**
     * Get all video completions for this chapter.
     */
    public function videoCompletions(): HasMany
    {
        return $this->hasMany(\App\Models\VideoCompletion::class);
    }

    /**
     * Get the video URL (either from video_path or video_url).
     */
    public function getVideoUrlAttribute($value)
    {
        if ($this->video_path) {
            return \Storage::disk('public')->url($this->video_path);
        }
        return $value;
    }

    /**
     * Get the induction that owns this chapter.
     */
    public function induction(): BelongsTo
    {
        return $this->belongsTo(Induction::class);
    }

    /**
     * Get all questions for this chapter.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('display_order');
    }
}
