<?php

namespace App\Core\Audit;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Request;

class AuditService
{
    public function record(
        string $action,
        ?string $module = null,
        string $level = 'info',
        string $outcome = 'success',
        ?string $auditableType = null,
        ?string $auditableId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $tags = null,
        ?array $context = null,
        ?Request $request = null,
        ?User $actor = null,
    ): AuditLog {
        $request = $request ?? request();
        $actor = $actor ?? Auth::user();
        $module ??= $this->inferModule($action);

        return AuditLog::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $actor?->id,
            'action' => $action,
            'module' => $module,
            'level' => $level,
            'outcome' => $outcome,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'request_id' => $request?->attributes?->get('request_id'),
            'session_id' => $request?->hasSession() ? $request->session()->getId() : null,
            'route_name' => $request?->route()?->getName(),
            'method' => $request?->method(),
            'url' => $request?->fullUrl(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'tags' => $tags,
            'context' => $context,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'actor_name' => $actor?->name,
            'actor_email' => $actor?->email,
        ]);
    }

    public function log(
        string $action,
        ?string $auditableType = null,
        ?string $auditableId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null
    ): AuditLog {
        return $this->record(
            action: $action,
            auditableType: $auditableType,
            auditableId: $auditableId,
            oldValues: $oldValues,
            newValues: $newValues,
            request: $request
        );
    }

    protected function inferModule(string $action): string
    {
        if (str_contains($action, '.')) {
            return (string) Str::before($action, '.');
        }

        return 'system';
    }
}
