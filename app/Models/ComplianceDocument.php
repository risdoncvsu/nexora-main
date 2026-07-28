<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplianceDocument extends Model
{
    protected $fillable = ['company_id', 'details', 'linked_id', 'classification', 'status', 'file_path'];
}
