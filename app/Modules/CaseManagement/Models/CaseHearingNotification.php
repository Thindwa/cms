<?php

namespace App\Modules\CaseManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseHearingNotification extends Model
{
    protected $table = 'case_hearing_notifications';

    protected $fillable = [
        'case_id',
        'user_id',
        'hearing_date',
        'days_before',
        'channel',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'hearing_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
