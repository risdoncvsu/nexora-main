<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    /**
     * Leave requests belong to the dedicated HR database, not the ITSM
     * database. Keeping that explicit prevents an accidental fallback to the
     * application's default connection.
     */
    protected $connection = 'hr';

    protected $fillable = [
        'client_id',
        'employee_id',
        'type',
        'from_date',
        'to_date',
        'total_days',
        'reason',
        'attachments',
        'status',
        'status_note',
        'reference_id',
        'reviewed_by_name',
        'reviewed_by_position',
        'reviewed_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'from_date' => 'date',
        'to_date' => 'date',
        'total_days' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('client', function (Builder $query): void {
            if ($clientId = (int) session('employee_client_id')) {
                $query->where($query->getModel()->qualifyColumn('client_id'), $clientId);
            }
        });

        static::creating(function (self $leaveRequest): void {
            if (! $leaveRequest->client_id && ($clientId = (int) session('employee_client_id'))) {
                $leaveRequest->client_id = $clientId;
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
