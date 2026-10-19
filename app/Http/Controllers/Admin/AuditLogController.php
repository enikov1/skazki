<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('viewAny', AuditLog::class);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:100', Rule::in(['user.blocked', 'user.unblocked', 'user.role_changed'])],
        ]);

        $logs = AuditLog::with('actor:id,name,email')
            ->when($filters['action'] ?? null, fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['q'] ?? null, fn (Builder $query, string $term) => $query->where('summary', 'like', '%'.$term.'%'))
            ->latest('created_at')->latest('id')->paginate(30)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json(['html' => view('admin.audit-logs.results', compact('logs'))->render(), 'count' => $logs->total()]);
        }

        return view('admin.audit-logs.index', compact('logs', 'filters'));
    }
}
