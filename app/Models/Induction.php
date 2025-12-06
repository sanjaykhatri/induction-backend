<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Induction extends Model
{
    protected $fillable = [
        'title',
        'description',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get all chapters for this induction.
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('display_order');
    }

    /**
     * Get all submissions for this induction.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Scope to get only active inductions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
