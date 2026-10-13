<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize(Permission::ViewAuditLog->value);

        $action = is_string($request->query('action')) && preg_match('/^[a-z_.]+$/', $request->query('action'))
            ? $request->query('action') : null;

        return Inertia::render('admin/audit-log', [
            'entries' => AuditLog::query()
                ->with('user:id,name')
                ->when($action, fn ($q, string $a) => $q->where('action', 'like', $a.'%'))
                ->latest('created_at')->latest('id')
                ->paginate(50)->withQueryString()
                ->through(fn (AuditLog $l) => [
                    'id' => $l->id,
                    'action' => $l->action,
                    'user' => $l->user?->name,
                    'subject' => $l->subject_type ? "{$l->subject_type} #{$l->subject_id}" : null,
                    'properties' => $l->properties,
                    'ip_address' => $l->ip_address,
                    'created_at' => $l->created_at?->toIso8601String(),
                ]),
            'filters' => ['action' => $action],
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
