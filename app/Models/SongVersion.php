<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SongVersion extends Model
{
    public $timestamps = false;

    protected $fillable = ['song_id', 'content', 'title', 'label'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}
