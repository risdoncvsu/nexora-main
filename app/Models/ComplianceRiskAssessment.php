<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplianceRiskAssessment extends Model
{
    protected $fillable = [
        'company_id',
        'risk_id',
        'title',
        'inherent_score',
        'inherent_text',
        'likelihood',
        'impact',
        'residual_score',
        'residual_text',
        'status',
    ];
}
