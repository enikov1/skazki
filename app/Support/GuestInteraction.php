<?php

namespace App\Support;

use Illuminate\Http\Request;

final class GuestInteraction
{
    public static function token(Request $request): string
    {
        return hash('sha256', $request->session()->getId());
    }

    public static function identity(Request $request): array
    {
        return $request->user()
            ? ['user_id' => $request->user()->id, 'guest_token' => null]
            : ['user_id' => null, 'guest_token' => self::token($request)];
    }

    public static function lookup(Request $request): array
    {
        return $request->user()
            ? ['user_id' => $request->user()->id]
            : ['guest_token' => self::token($request)];
    }

    public static function guestName(Request $request): ?string
    {
        if (! $request->user() && is_string($request->input('guest_name'))) {
            $request->merge(['guest_name' => trim($request->input('guest_name'))]);
        }

        $data = $request->validate([
            'guest_name' => [$request->user() ? 'nullable' : 'required', 'string', 'min:2', 'max:40'],
        ]);

        return $request->user() ? null : trim($data['guest_name']);
    }
}
