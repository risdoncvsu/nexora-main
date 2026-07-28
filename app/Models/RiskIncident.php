<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskIncident extends Model
{
    protected $fillable = ['company_id', 'incident_no', 'title', 'severity', 'description', 'reporter', 'status', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }
}
