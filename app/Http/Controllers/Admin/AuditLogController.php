<?php

namespace App\Http\Controllers\Admin;

use App\Core\Audit\AuditLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:admin.audit.view')->only(['index', 'show']);
        $this->middleware('can:admin.audit.export')->only(['export']);
    }

    public function index(Request $request): View
    {
        $logs = $this->filteredQuery($request)
            ->with('user:id,name,username,email')
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        $modules = AuditLog::query()->whereNotNull('module')->select('module')->distinct()->orderBy('module')->pluck('module');
        $actions = AuditLog::query()->select('action')->distinct()->orderBy('action')->limit(400)->pluck('action');

        return view('admin.audit.index', compact('logs', 'modules', 'actions'));
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user:id,name,username,email');

        $oldValues = $auditLog->old_values ?? [];
        $newValues = $auditLog->new_values ?? [];
        $changedFields = $this->buildChangedFields($oldValues, $newValues);
        $context = is_array($auditLog->context) ? $auditLog->context : [];
        $submittedFields = [];
        if ($changedFields === [] && isset($context['payload']) && is_array($context['payload'])) {
            $submittedFields = collect($context['payload'])
                ->map(fn ($value, $key) => [
                    'field' => Str::of((string) $key)->replace('_', ' ')->title()->toString(),
                    'raw_field' => (string) $key,
                    'before' => '—',
                    'after' => $this->displayValue($value),
                ])
                ->values()
                ->all();
        }

        $eventSummary = $this->humanSummary($auditLog);

        return view('admin.audit.show', compact('auditLog', 'oldValues', 'newValues', 'changedFields', 'submittedFields', 'eventSummary'));
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->filteredQuery($request)
            ->with('user:id,name,username,email')
            ->latest('created_at')
            ->limit(10000)
            ->get();

        $filename = 'audit-logs-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Module', 'Action', 'Level', 'Outcome', 'Actor', 'IP', 'Route', 'Method', 'Request ID']);
            foreach ($rows as $log) {
                fputcsv($handle, [
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->module,
                    $log->action,
                    $log->level,
                    $log->outcome,
                    $log->user?->name ?? $log->actor_name ?? 'System',
                    $log->ip_address,
                    $log->route_name,
                    $log->method,
                    $log->request_id,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function filteredQuery(Request $request)
    {
        $query = AuditLog::query();

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($inner) use ($q): void {
                $inner->where('action', 'like', "%{$q}%")
                    ->orWhere('module', 'like', "%{$q}%")
                    ->orWhere('route_name', 'like', "%{$q}%")
                    ->orWhere('request_id', 'like', "%{$q}%")
                    ->orWhere('ip_address', 'like', "%{$q}%")
                    ->orWhere('actor_name', 'like', "%{$q}%")
                    ->orWhere('actor_email', 'like', "%{$q}%");
            });
        }

        if ($request->filled('module')) {
            $query->where('module', (string) $request->input('module'));
        }
        if ($request->filled('action')) {
            $query->where('action', (string) $request->input('action'));
        }
        if ($request->filled('level')) {
            $query->where('level', (string) $request->input('level'));
        }
        if ($request->filled('outcome')) {
            $query->where('outcome', (string) $request->input('outcome'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', (string) $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', (string) $request->input('date_to'));
        }

        return $query;
    }

    protected function buildChangedFields(array $oldValues, array $newValues): array
    {
        $keys = collect(array_keys($oldValues))
            ->merge(array_keys($newValues))
            ->unique()
            ->values();

        return $keys
            ->map(function (string|int $key) use ($oldValues, $newValues): ?array {
                if (! is_string($key) || $this->shouldIgnoreField($key)) {
                    return null;
                }

                $before = Arr::exists($oldValues, $key) ? $oldValues[$key] : null;
                $after = Arr::exists($newValues, $key) ? $newValues[$key] : null;

                if ($this->normalizeForComparison($key, $before) === $this->normalizeForComparison($key, $after)) {
                    return null;
                }

                return [
                    'field' => Str::of((string) $key)->replace('_', ' ')->title()->toString(),
                    'raw_field' => (string) $key,
                    'before' => $this->displayValue($before),
                    'after' => $this->displayValue($after),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function shouldIgnoreField(string $field): bool
    {
        return in_array($field, [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'remember_token',
        ], true);
    }

    protected function normalizeForComparison(string $field, mixed $value): mixed
    {
        if (is_string($value)) {
            $normalized = trim($value);

            if (in_array($field, ['description', 'body', 'details'], true)) {
                $normalized = $this->normalizeRichText($normalized);
            } else {
                $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
            }

            return $normalized;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    protected function normalizeRichText(string $value): string
    {
        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return trim($text);
    }

    protected function displayValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
        }

        return (string) $value;
    }

    protected function humanSummary(AuditLog $auditLog): string
    {
        $actor = $auditLog->user?->name ?? $auditLog->actor_name ?? 'System';
        $context = is_array($auditLog->context) ? $auditLog->context : [];
        $targetDisplay = data_get($context, 'target.display');
        $targetType = $auditLog->auditable_type ? class_basename($auditLog->auditable_type) : 'record';
        $target = $targetDisplay ?: ($auditLog->auditable_id ? "{$targetType} {$auditLog->auditable_id}" : $targetType);

        if (str_contains($auditLog->action, '_store') || str_contains($auditLog->action, '.created')) {
            return "{$actor} created {$target}.";
        }
        if (str_contains($auditLog->action, '_update') || str_contains($auditLog->action, '.updated')) {
            return "{$actor} updated {$target}.";
        }
        if (str_contains($auditLog->action, '_destroy') || str_contains($auditLog->action, '.deleted')) {
            return "{$actor} deleted {$target}.";
        }
        if (str_contains($auditLog->action, '.viewed') || str_contains($auditLog->action, '_show')) {
            return "{$actor} viewed {$target}.";
        }

        return "{$actor} performed action {$auditLog->action} on {$target}.";
    }
}
