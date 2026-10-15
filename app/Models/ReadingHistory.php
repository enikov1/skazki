<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingHistory extends Model
{
    protected $fillable = ['last_read_at', 'reading_sessions'];
    protected function casts(): array { return ['last_read_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function story(): BelongsTo { return $this->belongsTo(Story::class); }
}
