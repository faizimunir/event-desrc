<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Event extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'start_date',
        'end_date',
        'registration_start',
        'registration_end',
        'registration_open',
        'location',
        'image',
        'logo_url',
        'status',
        'payment_method',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_start' => 'datetime',
        'registration_end' => 'datetime',
        'registration_open' => 'boolean',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function activeCategories(): HasMany
    {
        return $this->hasMany(Category::class)->where('status', 'active');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function activePackages(): HasMany
    {
        return $this->hasMany(Package::class)->where('status', 'active');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function paymentSettings()
    {
        return $this->hasMany(PaymentSetting::class);
    }

    public function notificationTemplates()
    {
        return $this->hasMany(NotificationTemplate::class);
    }

    public function adminFee()
    {
        return $this->hasOne(EventAdminFee::class);
    }

    public function liveResultCategories(): HasMany
    {
        return $this->hasMany(LiveResultCategory::class);
    }

    /**
     * Generate slug from event name
     */
    public function generateSlug()
    {
        if (empty($this->slug) && !empty($this->name)) {
            $baseSlug = Str::slug($this->name);
            $slug = $baseSlug;
            $counter = 1;
            
            while (static::where('slug', $slug)->where('id', '!=', $this->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            $this->slug = $slug;
        }
        
        return $this->slug;
    }

    protected static function booted()
    {
        // Auto-generate slug before saving
        static::saving(function ($event) {
            if (empty($event->slug) && !empty($event->name)) {
                $event->generateSlug();
            }
        });

        // Clear cache when event is updated or created
        static::saved(function ($event) {
            Cache::forget('published_events');
            Cache::forget("event_detail_{$event->id}");
        });

        static::deleted(function ($event) {
            Cache::forget('published_events');
            Cache::forget("event_detail_{$event->id}");
        });
    }
}
