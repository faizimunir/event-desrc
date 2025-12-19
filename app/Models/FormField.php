<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    protected $fillable = [
        'package_id',
        'name',
        'label',
        'type',
        'options',
        'help_text',
        'required',
        'order',
        'status',
    ];

    protected $casts = [
        'options' => 'array',
        'required' => 'boolean',
        'order' => 'integer',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}

