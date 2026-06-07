<?php

namespace App\Http\Middleware;

use App\Core\Audit\AuditService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditRequestMiddleware
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $targetModel = $this->resolveAuditableModel($request);
        $targetClass = $targetModel ? $targetModel::class : null;
        $targetId = $targetModel?->getKey() !== null ? (string) $targetModel->getKey() : null;
        $submittedPayload = $request->except([
            'password',
            'password_confirmation',
            'current_password',
            'token',
            '_token',
        ]);
        $trackedFields = $this->trackedFieldsForModelChange($submittedPayload);
        $beforeValues = $this->extractModelValues($targetModel, $trackedFields);

        /** @var Response $response */
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        try {
            $routeName = $request->route()?->getName() ?? 'unknown';
            $action = 'http.' . Str::replace('.', '_', $routeName);
            $statusCode = (int) $response->getStatusCode();
            $level = $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warning' : 'info');
            $outcome = $statusCode >= 400 ? 'failed' : 'success';
            [$auditableType, $auditableId] = [$targetClass, $targetId];
            $afterModel = $this->resolveAfterModelState($targetModel);
            $afterValues = $this->extractModelValues($afterModel, $trackedFields);
            $oldValues = $beforeValues;
            $newValues = $afterValues;
            if ($request->isMethod('delete')) {
                $newValues['deleted'] = true;
            }

            $this->auditService->record(
                action: $action,
                module: 'http',
                level: $level,
                outcome: $outcome,
                auditableType: $auditableType,
                auditableId: $auditableId,
                oldValues: $oldValues !== [] ? $oldValues : null,
                newValues: $newValues !== [] ? $newValues : null,
                context: [
                    'status_code' => $statusCode,
                    'method' => $request->method(),
                    'route_name' => $routeName,
                    'path' => $request->path(),
                    'route_parameters' => $this->sanitizeForAudit($request->route()?->parameters() ?? []),
                    'payload' => $this->sanitizeForAudit($submittedPayload),
                    'files' => $this->fileAuditSummary($request),
                    'target' => [
                        'type' => $auditableType,
                        'id' => $auditableId,
                        'display' => $this->targetDisplayLabel($afterModel ?? $targetModel),
                    ],
                ],
                request: $request
            );
        } catch (Throwable) {
            // Never break user flow due to auditing failures.
        }

        return $response;
    }

    protected function resolveAuditableModel(Request $request): ?Model
    {
        $parameters = $request->route()?->parameters() ?? [];

        foreach ($parameters as $value) {
            if ($value instanceof Model) {
                return $value;
            }
        }

        return null;
    }

    protected function fileAuditSummary(Request $request): array
    {
        $files = $request->allFiles();
        if ($files === []) {
            return [];
        }

        $summary = [];
        foreach ($files as $key => $entry) {
            $entryFiles = is_array($entry) ? $entry : [$entry];
            $summary[$key] = collect($entryFiles)
                ->filter()
                ->map(fn ($file) => [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => $file->getClientMimeType(),
                ])
                ->values()
                ->all();
        }

        return $summary;
    }

    protected function trackedFieldsForModelChange(array $submittedPayload): array
    {
        return collect(array_keys($submittedPayload))
            ->filter(fn ($key) => is_string($key))
            ->reject(fn (string $key) => in_array($key, ['_method'], true))
            ->values()
            ->all();
    }

    protected function extractModelValues(?Model $model, array $fields): array
    {
        if (! $model || $fields === []) {
            return [];
        }

        $values = [];
        foreach ($fields as $field) {
            if (! is_string($field) || $field === '') {
                continue;
            }
            $values[$field] = $this->sanitizeForAudit($model->getAttribute($field));
        }

        return $values;
    }

    protected function resolveAfterModelState(?Model $targetModel): ?Model
    {
        if (! $targetModel) {
            return null;
        }

        try {
            if (method_exists($targetModel, 'trashed') && method_exists($targetModel, 'fresh')) {
                return $targetModel->fresh(['*']) ?? $targetModel;
            }

            return $targetModel->fresh() ?? $targetModel;
        } catch (Throwable) {
            return $targetModel;
        }
    }

    protected function targetDisplayLabel(?Model $model): ?string
    {
        if (! $model) {
            return null;
        }

        foreach (['case_number', 'name', 'title', 'original_name'] as $field) {
            $value = $model->getAttribute($field);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    protected function sanitizeForAudit(mixed $value): mixed
    {
        if (is_null($value) || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            return mb_strlen($value) > 2000 ? mb_substr($value, 0, 2000) . '…' : $value;
        }

        if (is_array($value)) {
            $clean = [];
            foreach ($value as $key => $item) {
                $clean[$key] = $this->sanitizeForAudit($item);
            }

            return $clean;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return is_object($value) ? get_class($value) : (string) $value;
    }
}
