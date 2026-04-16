<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'acc_name',
        'acc_bank',
        'acc_number',
    ];

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_account');
    }
}
