<?php

namespace App\Http\Controllers\Admin;

use App\Core\Audit\AuditLog;
use App\Http\Controllers\Controller;
use App\Modules\CaseManagement\Models\CaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:admin.audit.view')->only(['index', 'show', 'stats']);
        $this->middleware('can:admin.audit.export')->only(['export', 'exportXlsx']);
        $this->middleware('can:admin.audit.delete')->only(['batchDelete']);
    }

    public function index(Request $request): View
    {
        $logs = $this->filteredQuery($request)
            ->with('user:id,name,username,email')
            ->select([
                'id', 'created_at', 'module', 'action', 'method', 'route_name',
                'auditable_type', 'auditable_id', 'level', 'outcome',
                'user_id', 'actor_name', 'ip_address', 'request_id',
            ])
            ->latest('created_at')
            ->paginate((int) config('app.items_per_page', 50))
            ->withQueryString();

        $modules = AuditLog::query()->whereNotNull('module')->select('module')->distinct()->orderBy('module')->pluck('module');
        $actions = AuditLog::query()->select('action')->distinct()->orderBy('action')->limit(400)->pluck('action');

        $auditableTypes = AuditLog::query()
            ->whereNotNull('auditable_type')
            ->select('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type');

        return view('admin.audit.index', compact('logs', 'modules', 'actions', 'auditableTypes'));
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
        $recordUrl = $this->resolveRecordUrl($auditLog);

        return view('admin.audit.show', compact('auditLog', 'oldValues', 'newValues', 'changedFields', 'submittedFields', 'eventSummary', 'recordUrl'));
    }

    public function stats(Request $request): JsonResponse
    {
        $query = $this->filteredQuery($request);

        $total = (clone $query)->count();
        $byLevel = (clone $query)
            ->selectRaw('level, count(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level');
        $byOutcome = (clone $query)
            ->selectRaw('outcome, count(*) as count')
            ->groupBy('outcome')
            ->pluck('count', 'outcome');
        $byAction = (clone $query)
            ->selectRaw("case when action like 'http.%' then 'http' when action like 'auth.%' then 'auth' else substring_index(action, '.', 1) end as category, count(*) as count")
            ->groupBy('category')
            ->orderByDesc('count')
            ->pluck('count', 'category');

        $recentDays = (clone $query)
            ->selectRaw('date(created_at) as day, count(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day');

        return response()->json([
            'total' => $total,
            'by_level' => $byLevel,
            'by_outcome' => $byOutcome,
            'by_action' => $byAction,
            'recent_days' => $recentDays,
        ]);
    }

    public function batchDelete(Request $request): RedirectResponse
    {
        $count = $this->filteredQuery($request)->delete();

        return redirect()->route('admin.audit.index')
            ->with('success', "Deleted {$count} audit log entries.");
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->filteredQuery($request)
            ->with('user:id,name,username,email')
            ->select([
                'id', 'created_at', 'module', 'action', 'level', 'outcome',
                'user_id', 'actor_name', 'actor_email', 'ip_address', 'route_name', 'method', 'request_id',
                'auditable_type', 'auditable_id', 'old_values', 'new_values', 'context',
            ])
            ->latest('created_at')
            ->limit(10000)
            ->get();

        $filename = 'audit-logs-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Date', 'Module', 'Action', 'Friendly Action', 'Level', 'Outcome',
                'Actor', 'Actor Email', 'IP', 'Route', 'Method', 'Request ID',
                'Record Type', 'Record ID', 'Old Values', 'New Values', 'Context',
            ]);
            foreach ($rows as $log) {
                fputcsv($handle, [
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->module,
                    $log->action,
                    self::friendlyAction($log->action),
                    $log->level,
                    $log->outcome,
                    $log->user?->name ?? $log->actor_name ?? 'System',
                    $log->actor_email ?? $log->user?->email ?? '',
                    $log->ip_address,
                    $log->route_name,
                    $log->method,
                    $log->request_id,
                    $log->auditable_type ? class_basename($log->auditable_type) : '',
                    $log->auditable_id,
                    $log->old_values ? json_encode($log->old_values) : '',
                    $log->new_values ? json_encode($log->new_values) : '',
                    $log->context ? json_encode($log->context) : '',
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportXlsx(Request $request): BinaryFileResponse
    {
        $rows = $this->filteredQuery($request)
            ->with('user:id,name,username,email')
            ->select([
                'id', 'created_at', 'module', 'action', 'level', 'outcome',
                'user_id', 'actor_name', 'actor_email', 'ip_address', 'route_name', 'method', 'request_id',
                'auditable_type', 'auditable_id', 'old_values', 'new_values', 'context',
            ])
            ->latest('created_at')
            ->limit(10000)
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Audit Logs');

        $headers = [
            'Date', 'Module', 'Action', 'Friendly Action', 'Level', 'Outcome',
            'Actor', 'Actor Email', 'IP', 'Route', 'Method', 'Request ID',
            'Record Type', 'Record ID', 'Old Values', 'New Values', 'Context',
        ];
        foreach (array_values($headers) as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
        }
        $sheet->getStyle('A1:Q1')->getFont()->setBold(true);

        $rowIndex = 2;
        foreach ($rows as $log) {
            $sheet->setCellValueByColumnAndRow(1, $rowIndex, $log->created_at?->format('Y-m-d H:i:s'));
            $sheet->setCellValueByColumnAndRow(2, $rowIndex, $log->module);
            $sheet->setCellValueByColumnAndRow(3, $rowIndex, $log->action);
            $sheet->setCellValueByColumnAndRow(4, $rowIndex, self::friendlyAction($log->action));
            $sheet->setCellValueByColumnAndRow(5, $rowIndex, $log->level);
            $sheet->setCellValueByColumnAndRow(6, $rowIndex, $log->outcome);
            $sheet->setCellValueByColumnAndRow(7, $rowIndex, $log->user?->name ?? $log->actor_name ?? 'System');
            $sheet->setCellValueByColumnAndRow(8, $rowIndex, $log->actor_email ?? $log->user?->email ?? '');
            $sheet->setCellValueByColumnAndRow(9, $rowIndex, $log->ip_address);
            $sheet->setCellValueByColumnAndRow(10, $rowIndex, $log->route_name);
            $sheet->setCellValueByColumnAndRow(11, $rowIndex, $log->method);
            $sheet->setCellValueByColumnAndRow(12, $rowIndex, $log->request_id);
            $sheet->setCellValueByColumnAndRow(13, $rowIndex, $log->auditable_type ? class_basename($log->auditable_type) : '');
            $sheet->setCellValueByColumnAndRow(14, $rowIndex, $log->auditable_id);
            $sheet->setCellValueByColumnAndRow(15, $rowIndex, $log->old_values ? json_encode($log->old_values) : '');
            $sheet->setCellValueByColumnAndRow(16, $rowIndex, $log->new_values ? json_encode($log->new_values) : '');
            $sheet->setCellValueByColumnAndRow(17, $rowIndex, $log->context ? json_encode($log->context) : '');
            $rowIndex++;
        }

        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'audit-') . '.xlsx';
        $writer->save($tempFile);

        return response()->download($tempFile, 'audit-logs-' . now()->format('Ymd-His') . '.xlsx')->deleteFileAfterSend();
    }

    public static function friendlyAction(string $action): string
    {
        return match (true) {
            str_contains($action, '.created') || str_contains($action, '_store') => 'Created',
            str_contains($action, '.updated') || str_contains($action, '_update') => 'Updated',
            str_contains($action, '.deleted') || str_contains($action, '_destroy') => 'Deleted',
            str_contains($action, '.restored') => 'Restored',
            str_contains($action, '.viewed') || str_contains($action, '_show') => 'Viewed',
            str_contains($action, '.categorized') => 'Categorized',
            str_contains($action, 'auth.login') => 'Login',
            str_contains($action, 'auth.logout') => 'Logout',
            str_starts_with($action, 'http.') => 'HTTP Request',
            default => $action,
        };
    }

    public static function actionCategory(string $action): string
    {
        if (str_starts_with($action, 'http.')) return 'HTTP';
        if (str_starts_with($action, 'auth.')) return 'Authentication';
        if (str_starts_with($action, 'case.')) return 'Case Management';
        if (str_starts_with($action, 'category.')) return 'Categories';
        if (str_starts_with($action, 'document.')) return 'Documents';
        if (str_starts_with($action, 'note.')) return 'Notes';
        if (str_starts_with($action, 'import.')) return 'Imports';
        if (str_starts_with($action, 'user.')) return 'Users';
        if (str_starts_with($action, 'role.')) return 'Roles';
        if (str_starts_with($action, 'setting.')) return 'Settings';
        return 'Other';
    }

    protected function filteredQuery(Request $request): Builder
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
                    ->orWhere('actor_email', 'like', "%{$q}%")
                    ->orWhere('auditable_id', 'like', "%{$q}%")
                    ->orWhere('old_values', 'like', "%{$q}%")
                    ->orWhere('new_values', 'like', "%{$q}%")
                    ->orWhere('context', 'like', "%{$q}%");
            });
        }

        if ($request->filled('module')) {
            $query->where('module', (string) $request->input('module'));
        }

        if ($request->filled('action')) {
            $query->where('action', (string) $request->input('action'));
        }

        if ($request->filled('action_category')) {
            $category = (string) $request->input('action_category');
            $query->where('action', 'like', $category . '.%');
        }

        if ($request->filled('level')) {
            $query->where('level', (string) $request->input('level'));
        }

        if ($request->filled('outcome')) {
            $query->where('outcome', (string) $request->input('outcome'));
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', (string) $request->input('auditable_type'));
        }

        if ($request->filled('auditable_id')) {
            $query->where('auditable_id', (string) $request->input('auditable_id'));
        }

        if ($request->filled('period')) {
            $period = (string) $request->input('period');
            $now = now();
            $from = match ($period) {
                'today' => $now->copy()->startOfDay(),
                'yesterday' => $now->copy()->subDay()->startOfDay(),
                'this_week' => $now->copy()->startOfWeek(),
                'this_month' => $now->copy()->startOfMonth(),
                'this_year' => $now->copy()->startOfYear(),
                'last_7_days' => $now->copy()->subDays(7)->startOfDay(),
                'last_30_days' => $now->copy()->subDays(30)->startOfDay(),
                'last_90_days' => $now->copy()->subDays(90)->startOfDay(),
                default => null,
            };
            if ($from !== null) {
                $query->where('created_at', '>=', $from);
                if ($period === 'yesterday') {
                    $query->where('created_at', '<', $now->copy()->startOfDay());
                }
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', (string) $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', (string) $request->input('date_to'));
        }

        return $query;
    }

    protected function resolveRecordUrl(AuditLog $log): ?string
    {
        if (!$log->auditable_type || !$log->auditable_id) {
            return null;
        }

        return match ($log->auditable_type) {
            CaseModel::class => route('cases.show', $log->auditable_id, false),
            default => null,
        };
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
