<?php

namespace App\Modules\CaseManagement\Imports;

use App\Core\Audit\AuditLog;
use App\Modules\CaseManagement\Models\CaseModel;
use App\Modules\CaseManagement\Models\CaseNote;
use App\Modules\CaseManagement\Services\CaseManagementService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelCaseImportService
{
    public const TARGET_FIELDS = [
        'date_filed',
        'claimant',
        'reference_number',
        'cause_number',
        'civil_case_number',
        'description',
        'officer_dealing',
        'entered_by_legacy',
        'defendant',
        'hearing_date',
    ];

    public function __construct(
        protected CaseManagementService $caseService,
    ) {}

    public function profile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheetNames = $spreadsheet->getSheetNames();
        $activeSheet = $sheetNames[0] ?? 'Sheet1';
        $worksheet = $spreadsheet->getSheetByName($activeSheet) ?: $spreadsheet->getActiveSheet();

        $headers = $this->extractHeaders($worksheet);
        $samples = $this->sampleRowsByHeaders($worksheet, $headers, 6, 2);

        return [
            'sheet_names' => $sheetNames,
            'default_sheet' => $worksheet->getTitle(),
            'headers' => array_values($headers),
            'sample_rows' => $samples,
            'auto_mapping' => $this->guessMapping(array_values($headers)),
            'default_options' => $this->defaultOptions(),
        ];
    }

    public function analyze(string $filePath, string $sheetName, array $mapping, array $options): array
    {
        $options = $this->normalizeOptions($options);
        $worksheet = $this->loadSheet($filePath, $sheetName);
        $headers = $this->extractHeaders($worksheet);

        $stats = [
            'rows_total' => 0,
            'rows_processed' => 0,
            'rows_skipped' => 0,
            'duplicates_in_file' => 0,
            'rows_without_reference' => 0,
            'rows_missing_officer' => 0,
            'invalid_dates' => 0,
            'blocking_issues' => 0,
            'warning_issues' => 0,
        ];

        $issues = [];
        $mappedPreview = [];
        $seenKeys = [];
        $highestRow = (int) $worksheet->getHighestDataRow();

        $requiredMappings = ['reference_number', 'description'];
        foreach ($requiredMappings as $required) {
            if (empty($mapping[$required])) {
                $issues[] = [
                    'severity' => 'blocking',
                    'row' => null,
                    'type' => 'missing_mapping',
                    'message' => "Mapping for {$required} is required before import.",
                ];
                $stats['blocking_issues']++;
            }
        }

        for ($row = 2; $row <= $highestRow; $row++) {
            $stats['rows_total']++;
            $normalized = $this->rowToNormalizedPayload($worksheet, $headers, $mapping, $row, $options['text_policy']);

            if ($this->isEmptyRow($normalized)) {
                $stats['rows_skipped']++;
                continue;
            }

            $stats['rows_processed']++;
            if (count($mappedPreview) < 6) {
                $mappedPreview[] = [
                    'row' => $row,
                    'values' => $normalized,
                ];
            }

            if (empty($normalized['reference_number'])) {
                $stats['rows_without_reference']++;
                $issues[] = [
                    'severity' => 'warning',
                    'row' => $row,
                    'type' => 'missing_reference',
                    'message' => 'Reference number is empty; matching quality will be low.',
                ];
                $stats['warning_issues']++;
            }

            $dateFiled = $this->parseDate($normalized['date_filed'], $options['date_mode']);
            if (! empty($normalized['date_filed']) && $dateFiled === null) {
                $stats['invalid_dates']++;
                $issues[] = [
                    'severity' => 'warning',
                    'row' => $row,
                    'type' => 'invalid_date',
                    'message' => 'Date could not be parsed.',
                ];
                $stats['warning_issues']++;
            }

            $officer = $this->resolveOfficerDealing($normalized['officer_dealing'], $normalized['entered_by_legacy'], $options);
            if ($officer === null) {
                $stats['rows_missing_officer']++;
                $severity = $options['missing_officer_policy'] === 'skip' ? 'warning' : 'blocking';
                $issues[] = [
                    'severity' => $severity,
                    'row' => $row,
                    'type' => 'missing_officer',
                    'message' => 'Officer dealing is missing after mapping and policy.',
                ];
                if ($severity === 'blocking') {
                    $stats['blocking_issues']++;
                } else {
                    $stats['warning_issues']++;
                }
            }

            $key = $this->buildDryRunKey($normalized);
            if ($key !== null && isset($seenKeys[$key])) {
                $firstSeen = $seenKeys[$key];
                $stats['duplicates_in_file']++;
                $issues[] = [
                    'severity' => 'warning',
                    'row' => $row,
                    'type' => 'duplicate_in_file',
                    'message' => sprintf(
                        'Duplicate matching key detected. First seen at row %d, duplicated at row %d. Key: %s.',
                        (int) ($firstSeen['row'] ?? 0),
                        $row,
                        $this->describeMatchingKey($normalized)
                    ),
                ];
                $stats['warning_issues']++;
            }
            if ($key !== null) {
                $seenKeys[$key] = ['row' => $row];
            }
        }

        return [
            'sheet_name' => $sheetName,
            'headers' => array_values($headers),
            'mapping' => $mapping,
            'options' => $options,
            'stats' => $stats,
            'issues' => array_slice($issues, 0, 200),
            'mapped_preview' => $mappedPreview,
        ];
    }

    public function importConfigured(
        string $filePath,
        string $sheetName,
        array $mapping,
        array $options,
        ?int $userId = null,
        bool $dryRun = true
    ): array {
        $options = $this->normalizeOptions($options);
        $worksheet = $this->loadSheet($filePath, $sheetName);
        $headers = $this->extractHeaders($worksheet);

        $stats = [
            'file' => $filePath,
            'sheet' => $sheetName,
            'dry_run' => $dryRun,
            'rows_total' => 0,
            'rows_processed' => 0,
            'rows_skipped' => 0,
            'cases_created' => 0,
            'cases_updated' => 0,
            'cases_matched' => 0,
            'notes_created' => 0,
            'errors' => [],
            'rollback' => [
                'created_case_ids' => [],
                'updated_cases' => [],
                'created_note_ids' => [],
            ],
        ];

        $highestRow = (int) $worksheet->getHighestDataRow();
        if ($highestRow < 2) {
            return $stats;
        }

        $seenDryRunKeys = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $stats['rows_total']++;

            $raw = $this->rowToNormalizedPayload($worksheet, $headers, $mapping, $row, $options['text_policy']);
            if ($this->isEmptyRow($raw)) {
                $stats['rows_skipped']++;
                continue;
            }

            $stats['rows_processed']++;

            try {
                $dateFiled = $this->parseDate($raw['date_filed'], $options['date_mode']);
                $hearingDate = $this->parseDate($raw['hearing_date'], $options['date_mode']);
                $officerDealing = $this->resolveOfficerDealing($raw['officer_dealing'], $raw['entered_by_legacy'], $options);

                if ($officerDealing === null && $options['missing_officer_policy'] === 'skip') {
                    $stats['rows_skipped']++;
                    continue;
                }

                if ($officerDealing === null) {
                    throw new \RuntimeException('Officer dealing could not be resolved for this row.');
                }

                if (! $dryRun) {
                    DB::transaction(function () use ($raw, $dateFiled, $hearingDate, $officerDealing, $userId, $options, &$stats, $row): void {
                        $case = $options['duplicate_policy'] === 'create_new'
                            ? null
                            : $this->findExistingCase($raw);

                        if ($case) {
                            $stats['cases_matched']++;
                            $wasUpdated = false;
                            $oldAuditValues = null;
                            $newAuditValues = null;
                            $updatedFields = [];

                            if ($options['duplicate_policy'] === 'update_existing') {
                                $updated = ['updated_by' => $userId];
                                if (empty($case->claimant) && ! empty($raw['claimant'])) {
                                    $updated['claimant'] = $raw['claimant'];
                                }
                                if (empty($case->cause_number) && ! empty($raw['cause_number'])) {
                                    $updated['cause_number'] = $raw['cause_number'];
                                }
                                if (empty($case->civil_case_number) && ! empty($raw['civil_case_number'])) {
                                    $updated['civil_case_number'] = $raw['civil_case_number'];
                                }
                                if (empty($case->defendant) && ! empty($raw['defendant'])) {
                                    $updated['defendant'] = $raw['defendant'];
                                }
                                if (empty($case->title) && ! empty($officerDealing)) {
                                    $updated['title'] = $officerDealing;
                                }
                                if (empty($case->date_filed) && $dateFiled) {
                                    $updated['date_filed'] = $dateFiled;
                                }
                                if (empty($case->hearing_date) && $hearingDate) {
                                    $updated['hearing_date'] = $hearingDate;
                                }
                                if (count($updated) > 1) {
                                    $fieldsToRollback = array_keys(array_filter($updated, static fn ($v, $k) => $k !== 'updated_by', ARRAY_FILTER_USE_BOTH));
                                    if ($fieldsToRollback !== []) {
                                        $oldValues = [];
                                        foreach ($fieldsToRollback as $field) {
                                            $oldValues[$field] = $this->serializeRollbackValue($case->getAttribute($field));
                                        }
                                        $stats['rollback']['updated_cases'][] = [
                                            'case_id' => $case->id,
                                            'old_values' => $oldValues,
                                        ];
                                    }

                                    $oldAuditValues = $case->toArray();
                                    $case->update($updated);
                                    $stats['cases_updated']++;
                                    $wasUpdated = true;
                                    $updatedFields = $fieldsToRollback;
                                    $newAuditValues = $case->fresh()->toArray();
                                }
                            }

                            if ($wasUpdated) {
                                $this->logImportAudit(
                                    action: 'case.import.updated',
                                    case: $case,
                                    userId: $userId,
                                    oldValues: $oldAuditValues,
                                    newValues: $newAuditValues
                                );
                            }

                            // Only add import note when the import actually changed the case.
                            if ($wasUpdated) {
                                $noteText = $this->buildNoteBody($raw, $dateFiled, $row, true, $updatedFields);
                                if ($noteText !== null && $userId !== null) {
                                    $note = $this->createNoteIfUnique($case->id, $userId, $noteText);
                                    if ($note) {
                                        $stats['notes_created']++;
                                        $stats['rollback']['created_note_ids'][] = $note->id;
                                    }
                                }
                            }

                            return;
                        }

                        $case = CaseModel::create([
                            'case_number' => $this->caseService->generateCaseNumber(),
                            'date_filed' => $dateFiled,
                            'hearing_date' => $hearingDate,
                            'reference_number' => $raw['reference_number'] ?: null,
                            'civil_case_number' => $raw['civil_case_number'] ?: null,
                            'defendant' => $raw['defendant'] ?: null,
                            'claimant' => $raw['claimant'] ?: null,
                            'cause_number' => $raw['cause_number'] ?: null,
                            'title' => $officerDealing,
                            'description' => $raw['description'] ?: null,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);

                        $stats['cases_created']++;
                        $stats['rollback']['created_case_ids'][] = $case->id;
                        $this->logImportAudit(
                            action: 'case.import.created',
                            case: $case,
                            userId: $userId,
                            oldValues: null,
                            newValues: $case->toArray()
                        );

                        $noteText = $this->buildNoteBody($raw, $dateFiled, $row, false, ['created']);
                        if ($noteText !== null && $userId !== null) {
                            $note = $this->createNoteIfUnique($case->id, $userId, $noteText);
                            if ($note) {
                                $stats['notes_created']++;
                                $stats['rollback']['created_note_ids'][] = $note->id;
                            }
                        }
                    });
                } else {
                    $key = $this->buildDryRunKey($raw);
                    if ($key !== null && isset($seenDryRunKeys[$key])) {
                        $stats['cases_matched']++;
                    } else {
                        $stats['cases_created']++;
                        if ($key !== null) {
                            $seenDryRunKeys[$key] = true;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $stats['errors'][] = [
                    'row' => $row,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $stats;
    }

    // Backward-compatible entry point for existing CLI usage.
    public function import(string $filePath, string $sheetName = 'Sheet1', ?int $userId = null, bool $dryRun = true): array
    {
        $mapping = [
            'date_filed' => 'DATE',
            'claimant' => 'PLAINTIFF',
            'reference_number' => 'REFERNCE NO',
            'cause_number' => 'CAUSE NO',
            'description' => 'LATEST ISSUE',
            'officer_dealing' => 'FILE MOVED TO',
            'entered_by_legacy' => 'ENTERED BY',
            'defendant' => 'LEGAL OPINION RESPONDANT',
            'civil_case_number' => 'CIVIL CASE NO',
            'hearing_date' => '',
        ];

        return $this->importConfigured($filePath, $sheetName, $mapping, $this->defaultOptions(), $userId, $dryRun);
    }

    public function defaultOptions(): array
    {
        return [
            'duplicate_policy' => 'update_existing',
            'missing_officer_policy' => 'default',
            'default_officer_value' => 'Unassigned Officer',
            'date_mode' => 'auto',
            'text_policy' => 'clean',
        ];
    }

    public function guessMapping(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $header) {
            $normalized[$this->normalizeHeader($header)] = $header;
        }

        $map = [];
        $map['date_filed'] = $normalized['date'] ?? $normalized['datefiled'] ?? null;
        $map['claimant'] = $normalized['plaintiff'] ?? $normalized['claimant'] ?? null;
        $map['reference_number'] = $normalized['refernceno'] ?? $normalized['referenceno'] ?? $normalized['referencenumber'] ?? null;
        $map['cause_number'] = $normalized['causeno'] ?? $normalized['caseno'] ?? null;
        $map['civil_case_number'] = $normalized['civilcaseno'] ?? null;
        $map['description'] = $normalized['latestissue'] ?? $normalized['issue'] ?? $normalized['description'] ?? null;
        $map['officer_dealing'] = $normalized['filemovedto'] ?? $normalized['officerdealing'] ?? null;
        $map['entered_by_legacy'] = $normalized['enteredby'] ?? null;
        $map['defendant'] = $normalized['legalopinionrespondant'] ?? $normalized['respondant'] ?? $normalized['defendant'] ?? null;
        $map['hearing_date'] = $normalized['hearingdate'] ?? null;

        return $map;
    }

    protected function loadSheet(string $filePath, string $sheetName): Worksheet
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getSheetByName($sheetName);
        if (! $worksheet) {
            throw new \RuntimeException("Sheet '{$sheetName}' not found in {$filePath}");
        }

        return $worksheet;
    }

    protected function extractHeaders(Worksheet $worksheet): array
    {
        $highestColumn = $worksheet->getHighestDataColumn(1);
        $maxColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        $headers = [];

        for ($col = 1; $col <= $maxColumnIndex; $col++) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);
            $value = (string) $worksheet->getCell($columnLetter . '1')->getFormattedValue();
            $clean = trim(preg_replace('/\s+/', ' ', $value) ?? '');
            if ($clean === '') {
                continue;
            }
            $headers[$columnLetter] = $clean;
        }

        return $headers;
    }

    protected function sampleRowsByHeaders(Worksheet $worksheet, array $headers, int $limit = 5, int $startRow = 2): array
    {
        $rows = [];
        $highestRow = (int) $worksheet->getHighestDataRow();

        for ($row = $startRow; $row <= $highestRow && count($rows) < $limit; $row++) {
            $item = [];
            $hasAny = false;
            foreach ($headers as $column => $header) {
                $value = $worksheet->getCell($column . $row)->getFormattedValue();
                $clean = $this->clean((string) $value);
                $item[$header] = $clean;
                if ($clean !== null) {
                    $hasAny = true;
                }
            }
            if ($hasAny) {
                $rows[] = $item;
            }
        }

        return $rows;
    }

    protected function rowToNormalizedPayload(Worksheet $worksheet, array $headers, array $mapping, int $row, string $textPolicy): array
    {
        $headerToColumn = array_flip($headers);
        $result = array_fill_keys(self::TARGET_FIELDS, null);

        foreach (self::TARGET_FIELDS as $targetField) {
            $sourceHeader = $mapping[$targetField] ?? null;
            if (! $sourceHeader || ! isset($headerToColumn[$sourceHeader])) {
                continue;
            }

            $column = $headerToColumn[$sourceHeader];
            $rawValue = in_array($targetField, ['date_filed', 'hearing_date'], true)
                ? $worksheet->getCell($column . $row)->getValue()
                : $worksheet->getCell($column . $row)->getFormattedValue();

            if (is_string($rawValue)) {
                $value = $textPolicy === 'clean' ? $this->clean($rawValue) : trim($rawValue);
            } else {
                $value = $rawValue;
            }

            if (is_string($value) && trim($value) === '') {
                $value = null;
            }

            $result[$targetField] = $value;
        }

        return $result;
    }

    protected function findExistingCase(array $raw): ?CaseModel
    {
        if (! empty($raw['reference_number']) && ! empty($raw['cause_number'])) {
            return CaseModel::query()
                ->where('reference_number', $raw['reference_number'])
                ->where('cause_number', $raw['cause_number'])
                ->first();
        }

        if (! empty($raw['reference_number']) && ! empty($raw['claimant'])) {
            return CaseModel::query()
                ->where('reference_number', $raw['reference_number'])
                ->where('claimant', $raw['claimant'])
                ->first();
        }

        if (! empty($raw['reference_number'])) {
            return CaseModel::query()->where('reference_number', $raw['reference_number'])->first();
        }

        return null;
    }

    protected function resolveOfficerDealing(mixed $officerValue, mixed $enteredBy, array $options): ?string
    {
        $value = is_string($officerValue) ? trim($officerValue) : (is_scalar($officerValue) ? trim((string) $officerValue) : '');
        if ($value !== '') {
            return $value;
        }

        $entered = is_string($enteredBy) ? trim($enteredBy) : (is_scalar($enteredBy) ? trim((string) $enteredBy) : '');
        if ($entered !== '') {
            return $entered;
        }

        if ($options['missing_officer_policy'] === 'default') {
            $fallback = trim((string) ($options['default_officer_value'] ?? ''));
            return $fallback !== '' ? $fallback : null;
        }

        return null;
    }

    protected function buildNoteBody(array $raw, ?Carbon $dateFiled, int $row, bool $isUpdate, array $changedFields = []): ?string
    {
        $parts = [];
        $parts[] = $isUpdate ? 'Imported update row from Excel.' : 'Imported initial row from Excel.';
        $parts[] = "Row: {$row}";
        if ($changedFields !== []) {
            $parts[] = 'Changed fields: ' . implode(', ', $changedFields);
        }

        if (! empty($raw['description'])) {
            $parts[] = 'Latest issue: ' . $raw['description'];
        }
        if (! empty($raw['entered_by_legacy'])) {
            $parts[] = 'Entered by (legacy): ' . $raw['entered_by_legacy'];
        }
        if (! empty($raw['officer_dealing'])) {
            $parts[] = 'File moved to / officer (legacy): ' . $raw['officer_dealing'];
        }
        if ($dateFiled) {
            $parts[] = 'Date filed: ' . $dateFiled->format('Y-m-d');
        }

        $body = implode("\n", $parts);

        return trim($body) !== '' ? $body : null;
    }

    protected function createNoteIfUnique(string $caseId, int $userId, string $body): ?CaseNote
    {
        $normalized = trim($body);
        if ($normalized === '') {
            return null;
        }

        $exists = CaseNote::query()
            ->where('case_id', $caseId)
            ->where('body', $normalized)
            ->exists();

        if ($exists) {
            return null;
        }

        return CaseNote::create([
            'case_id' => $caseId,
            'user_id' => $userId,
            'body' => $normalized,
        ]);
    }

    protected function logImportAudit(
        string $action,
        CaseModel $case,
        ?int $userId,
        ?array $oldValues,
        ?array $newValues
    ): void {
        AuditLog::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $userId,
            'action' => $action,
            'auditable_type' => CaseModel::class,
            'auditable_id' => $case->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    protected function parseDate(mixed $value, string $mode = 'auto'): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (($mode === 'excel_serial' || $mode === 'auto') && is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $value);
                return Carbon::instance($dt)->startOfDay();
            } catch (\Throwable) {
                // continue
            }
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        $formats = match ($mode) {
            'ddmmyyyy' => ['d/m/Y', 'd-m-Y', 'd.m.Y', 'd/m/y', 'd-m-y'],
            default => ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd-m-y', 'm/d/Y', 'm-d-Y'],
        };

        foreach ($formats as $format) {
            try {
                $dt = Carbon::createFromFormat($format, $str);
                if ($dt !== false) {
                    return $dt->startOfDay();
                }
            } catch (\Throwable) {
                // continue
            }
        }

        if ($mode === 'ddmmyyyy' || $mode === 'excel_serial') {
            return null;
        }

        try {
            return Carbon::parse($str)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return $trimmed === '' ? null : $trimmed;
    }

    protected function isEmptyRow(array $raw): bool
    {
        foreach ($raw as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    protected function buildDryRunKey(array $raw): ?string
    {
        if (! empty($raw['reference_number']) && ! empty($raw['cause_number'])) {
            return 'ref-cause:' . $raw['reference_number'] . '|' . $raw['cause_number'];
        }

        if (! empty($raw['reference_number']) && ! empty($raw['claimant'])) {
            return 'ref-claimant:' . $raw['reference_number'] . '|' . $raw['claimant'];
        }

        if (! empty($raw['reference_number'])) {
            return 'ref:' . $raw['reference_number'];
        }

        return null;
    }

    protected function normalizeHeader(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($value)) ?? '';
    }

    protected function normalizeOptions(array $options): array
    {
        return array_merge($this->defaultOptions(), array_filter($options, static fn ($v) => $v !== null));
    }

    protected function describeMatchingKey(array $raw): string
    {
        $reference = (string) ($raw['reference_number'] ?? '');
        $cause = (string) ($raw['cause_number'] ?? '');
        $claimant = (string) ($raw['claimant'] ?? '');

        if ($reference !== '' && $cause !== '') {
            return "reference_number='{$reference}', cause_number='{$cause}'";
        }

        if ($reference !== '' && $claimant !== '') {
            return "reference_number='{$reference}', claimant='{$claimant}'";
        }

        if ($reference !== '') {
            return "reference_number='{$reference}'";
        }

        return 'no matching key values';
    }

    protected function serializeRollbackValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return $value;
    }
}
