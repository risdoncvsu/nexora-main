<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskMitigationPlan extends Model
{
    protected $fillable = ['company_id', 'risk_assessment_id', 'title', 'owner', 'budget', 'status'];

    protected function casts(): array
    {
        return ['budget' => 'decimal:2'];
    }

    public function risk(): BelongsTo
    {
        return $this->belongsTo(RiskAssessment::class, 'risk_assessment_id');
    }
}
