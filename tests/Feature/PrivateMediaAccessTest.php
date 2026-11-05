<?php

use App\Models\Media;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');

    foreach (['rider.read', 'event.read'] as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
});

test('photo_kia media url uses private route not public storage', function () {
    $owner = User::factory()->create();
    $rider = Rider::query()->create([
        'user_id' => $owner->id,
        'name' => 'KIA Rider',
    ]);

    $media = Media::query()->create([
        'model_type' => $rider->getMorphClass(),
        'model_id' => $rider->id,
        'collection' => 'photo_kia',
        'disk' => 'local',
        'mime_type' => 'image/webp',
        'size' => 10,
        'meta' => ['variants' => []],
    ]);

    Storage::disk('local')->put($media->getPath(), 'fake-webp');

    expect($media->isPrivate())->toBeTrue()
        ->and($media->getUrl())->toStartWith('/media/'.$media->id);
});

test('owner can view private photo_kia and stranger cannot', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $rider = Rider::query()->create([
        'user_id' => $owner->id,
        'name' => 'KIA Rider 2',
    ]);

    $media = Media::query()->create([
        'model_type' => $rider->getMorphClass(),
        'model_id' => $rider->id,
        'collection' => 'photo_kia',
        'disk' => 'local',
        'mime_type' => 'image/webp',
        'size' => 10,
        'meta' => ['variants' => []],
    ]);

    Storage::disk('local')->put($media->getPath(), 'fake-webp-content');

    $this->actingAs($owner)
        ->get(route('media.show', $media))
        ->assertOk();

    $this->actingAs($stranger)
        ->get(route('media.show', $media))
        ->assertForbidden();
});
