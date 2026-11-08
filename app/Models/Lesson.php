<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    protected $fillable = ['title', 'content', 'video_url', 'position'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
