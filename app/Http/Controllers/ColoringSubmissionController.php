<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\ColoringPage;
use App\Models\ColoringSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ColoringSubmissionController extends Controller
{
    use RespondsToAjax;

    public function store(Request $request, string $category, string $page): JsonResponse|RedirectResponse
    {
        $coloring = ColoringPage::query()->where('slug', $page)->where('is_published', true)
            ->whereHas('category', fn ($query) => $query->where('slug', $category)->where('is_published', true))
            ->firstOrFail();
        $data = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:120'],
            'artwork' => ['required', 'image', 'mimes:png', 'max:8192', 'dimensions:max_width=3000,max_height=3000'],
            'guardian_consent' => ['accepted'],
        ]);
        $fileName = Str::uuid().'.png';
        $path = Storage::disk('local')->putFileAs('coloring-submissions', $data['artwork'], $fileName);
        abort_unless($path, 500, 'Не удалось сохранить рисунок. Повторите попытку.');

        $submission = $request->user()->coloringSubmissions()->create([
            'coloring_page_id' => $coloring->id,
            'title' => trim($data['title']),
            'file_path' => $path,
            'status' => 'pending',
            'guardian_consent_at' => now(),
        ]);
        $request->user()->consentEvents()->create([
            'consent_type' => 'child_art_publication',
            'policy_version' => 'art-gallery-v1',
            'action' => 'accepted',
            'source' => 'coloring-submission',
            'recorded_at' => now(),
        ]);

        return response()->json(['message' => 'Работа отправлена родителем на проверку. После одобрения она появится в галерее.', 'submission_id' => $submission->id], 201);
    }
}
