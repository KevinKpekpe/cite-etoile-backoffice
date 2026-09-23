<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuditLogRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;

class AuditLogController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(AuditLogRequest $request): View
    {
        $filters = $request->validated();
        $logs = AuditLog::query()->with('user:id,first_name,last_name')
            ->when($filters['user_id'] ?? null, fn ($query, $user) => $query->where('user_id', $user))
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', 'like', "%{$action}%"))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()->paginate(10)->withQueryString();

        return view('audit-logs.index', [
            'logs' => $logs, 'filters' => $filters,
            'users' => User::query()->whereHas('roles')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }
}
