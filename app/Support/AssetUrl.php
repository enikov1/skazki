<?php

namespace App\Support;

final class AssetUrl
{
    public static function versioned(string $path): string
    {
        static $versions = [];
        if (! array_key_exists($path, $versions)) {
            $file = public_path($path);
            $versions[$path] = is_file($file) ? substr(hash_file('sha256', $file), 0, 12) : null;
        }
        return asset($path).($versions[$path] ? '?v='.$versions[$path] : '');
    }
}
