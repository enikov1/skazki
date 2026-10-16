<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Models\ColoringSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ColoringSubmissionModerationController extends Controller
{
    use RespondsToAjax;

    public function index(): View
    {
        $works = ColoringSubmission::query()->with(['creator:id,name', 'page:id,title'])->where('status', 'pending')->oldest()->paginate(25);
        return view('admin.colorings.moderation', compact('works'));
    }

    public function image(ColoringSubmission $submission)
    {
        abort_unless(preg_match('~^coloring-submissions/[A-Za-z0-9._/-]+\.png$~i', $submission->file_path) === 1, 404);
        $path = Storage::disk('local')->path($submission->file_path);
        abort_unless(is_file($path), 404);
        return response()->file($path, ['Content-Type' => 'image/png', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function update(Request $request, ColoringSubmission $submission): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);
        $submission->update(['status' => $data['status'], 'moderated_by' => $request->user()->id, 'moderated_at' => now()]);
        return $this->respond($request, $data['status'] === 'approved' ? 'Работа опубликована в галерее.' : 'Работа отклонена.');
    }
}
