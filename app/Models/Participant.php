<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Participant extends Model
{
    protected $fillable = [
        'package_id',
        'category_id',
        'registration_number',
        'unique_code',
        'name',
        'nickname',
        'number_plate',
        'komunitas',
        'email',
        'phone',
        'city',
        'date_of_birth',
        'address',
        'gender',
        'emergency_contact_name',
        'emergency_contact_phone',
        'form_data',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'form_data' => 'array',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($participant) {
            if (empty($participant->registration_number)) {
                $participant->registration_number = self::generateRegistrationNumber();
            }
            
            if (empty($participant->unique_code)) {
                $participant->unique_code = self::generateUniqueCode();
            }
            
            // Category harus dipilih secara manual karena package tidak terikat ke category spesifik
            // Package tersedia untuk semua category di event yang sama
        });
    }

    protected static function generateRegistrationNumber(): string
    {
        do {
            $number = 'REG-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while (self::where('registration_number', $number)->exists());

        return $number;
    }

    protected static function generateUniqueCode(): string
    {
        // Generate 3 digit unique code (001-500)
        $maxAttempts = 100;
        $attempt = 0;
        
        do {
            $code = str_pad(rand(1, 500), 3, '0', STR_PAD_LEFT);
            $attempt++;
            
            if ($attempt >= $maxAttempts) {
                // Fallback: use random 3 digits if can't generate unique
                $code = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                break;
            }
        } while (self::where('unique_code', $code)->exists());

        return $code;
    }
}
