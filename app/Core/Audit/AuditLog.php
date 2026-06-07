<?php

namespace App\Core\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'action',
        'module',
        'level',
        'outcome',
        'auditable_type',
        'auditable_id',
        'request_id',
        'session_id',
        'route_name',
        'method',
        'url',
        'old_values',
        'new_values',
        'tags',
        'context',
        'ip_address',
        'user_agent',
        'actor_name',
        'actor_email',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'tags' => 'array',
            'context' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
