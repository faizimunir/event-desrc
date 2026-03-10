# MEDIA.md

## STRICT BUILD INSTRUCTION — LARAVEL MEDIA ENGINE (PRODUCTION READY)

---

## 🚨 GLOBAL RULES (NON-NEGOTIABLE)

* ❌ No TODO
* ❌ No placeholder
* ❌ No pseudo code
* ❌ No controller image logic
* ❌ No synchronous image processing
* ❌ No skipping queue
* ❌ No hardcoded sizes in service
* ❌ No incremental ID
* ❌ No package except `intervention/image`
* ❌ No storing non-WebP image
* ❌ No saving image path in other tables

All code must be complete and executable.

---

# 1 DEPENDENCIES

Use:

* `intervention/image`
* Laravel built-in queue system

Do NOT use Spatie Media Library.

---

# 2 DATABASE

Create migration:

### Table: `media`

Columns:

* `id` → uuid primary
* `model_type` → string (indexed)
* `model_id` → unsignedBigInteger (indexed)
* `collection` → string (indexed)
* `disk` → string default `'public'`
* `mime_type` → string
* `size` → unsignedBigInteger
* `meta` → json nullable
* `timestamps`

Required:

```php
$table->uuid('id')->primary();
$table->morphs('model');
```

Indexes must be optimized.

---

# 3 MODEL: Media

Requirements:

* `$keyType = 'string';`
* `$incrementing = false;`
* Auto-generate UUID in `booted()`
* Cast `meta` to array
* `morphTo()` relation

### Required Methods

```php
getPath(int $size = null): string
getUrl(int $size = null): string
```

Logic:

* If `$size` null → use `original.webp`
* If requested size not exists → fallback to original
* Use `Storage::url()`

---

# 4 CONFIG FILE (MANDATORY)

Create:

`config/media.php`

Structure MUST match:

```php
return [

    'max_upload_size_kb' => 2048,

    'max_original_width' => 2000,

    'quality_original' => 85,

    'quality_variant' => 80,

    'collections' => [

        'avatar' => [
            'force_ratio' => [1,1],
            'variants' => [150, 400, 800],
        ],

        'user_avatar' => [
            'force_ratio' => [1,1],
            'variants' => [150, 400],
        ],

        'rider_background' => [
            'keep_ratio' => true,
            'variants' => [400, 800, 1600],
        ],

        'post_cover' => [
            'force_ratio' => [16,9],
            'variants' => [400, 800, 1600],
        ],

        'gallery' => [
            'keep_ratio' => true,
            'variants' => [400, 800],
        ],

    ],
];
```

No hardcoded sizes in service.
Everything must read from config.

---

# 5 SERVICE: MediaService

Location:

`app/Services/MediaService.php`

### Method:

```php
public function upload(
    UploadedFile $file,
    Model $model,
    string $collection
): Media
```

### Mandatory Validation

* mime: jpg, jpeg, png, webp
* size ≤ config `max_upload_size_kb`
* collection must exist in config

### Flow (Strict Order)

1. Generate UUID
2. Create media DB record
3. Resize original to max width (config)
4. If `force_ratio` → use `fit()`
5. If `keep_ratio` → use `resize()` + `aspectRatio()`
6. Encode WebP (quality_original)
7. Save `original.webp`
8. Dispatch `ProcessMediaVariants`
9. Return Media instance

No image logic allowed in controller.

---

# 6 JOB: ProcessMediaVariants

Location:

`app/Jobs/ProcessMediaVariants.php`

Must implement:

```php
ShouldQueue
```

### Handle Logic

1. Read collection config
2. Loop through `variants`
3. Resize from `original.webp`
4. Respect ratio rule (force_ratio or keep_ratio)
5. Encode WebP (quality_variant)
6. Save `{width}.webp`
7. Update `meta` column:

```php
[
    'original_width' => int,
    'original_height' => int,
    'variants' => [150,400,800]
]
```

### Must Be Idempotent

If variant file exists → skip.

---

# 7 TRAIT: HasMedia

Location:

`app/Traits/HasMedia.php`

### Methods:

```php
media()
getMedia(string $collection)
getFirstMediaUrl(string $collection, int $size = null)
deleteMediaCollection(string $collection)
```

Rules:

* Use morphMany
* No direct path query
* Always use Media model

---

# 8 AUTO CLEANUP

Create:

`MediaObserver`

### Rules

* When media deleted → delete folder `storage/media/{uuid}`
* When parent model deleted → delete related media

Register observer in `AppServiceProvider`.

No orphan folders allowed.

---

# 9 BLADE COMPONENT

Create:

`resources/views/components/media.blade.php`

Props:

* `media` (nullable)
* `size` (default 400)
* `alt`
* `class`

Must render:

* `<img>`
* `src`
* `srcset`
* `sizes`
* `loading="lazy"`
* Fallback image if null

Must use responsive image best practice.

---

# 10 CONTROLLER EXAMPLE

Create example method in `RiderController`:

```php
public function updateAvatar(Request $request, Rider $rider)
```

Rules:

* Inject MediaService
* Call upload()
* No image processing inside controller

---

# 11 STORAGE STRUCTURE (MANDATORY)

```
storage/app/public/media/{uuid}/
    original.webp
    150.webp
    400.webp
    800.webp
    1600.webp
```

Use `Storage` facade only.
No direct filesystem path.

---

# 12️⃣ PERFORMANCE RULES

* All images must use `loading="lazy"`
* Never use `original.webp` in frontend unless explicitly required
* Queue driver configurable
* Local = sync allowed
* Production = async required

---

# OUTPUT REQUIREMENT FOR AI

Must generate FULL implementation:

* migration
* model
* config
* service
* job
* trait
* observer
* blade component
* controller example
* AppServiceProvider registration

No explanation.
No summary.
Only complete file-by-file code with filename headers.

---

If AI simplifies or skips logic:

Repeat:

> Follow instruction strictly. Do not simplify. Generate full production code.
