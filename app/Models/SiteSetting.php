<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function printLogoUrl(): ?string
    {
        $path = static::query()->where('key', 'print_watermark_logo')->value('value');

        return filled($path) ? Storage::disk('public')->url($path) : null;
    }
}
