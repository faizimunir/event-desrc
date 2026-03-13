<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'acc_name',
        'acc_bank',
        'acc_number',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
