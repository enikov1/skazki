<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait RespondsToAjax
{
    protected function respond(Request $request, string $message, ?string $redirectTo = null, bool $redirectAjax = true): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(array_filter([
                'message' => $message,
                'redirect' => $redirectAjax ? $redirectTo : null,
            ], static fn ($value): bool => $value !== null));
        }

        return $redirectTo
            ? redirect()->to($redirectTo)->with('status', $message)
            : back()->with('status', $message);
    }
}
