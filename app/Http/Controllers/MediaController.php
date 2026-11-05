<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Order;
use App\Models\Registration;
use App\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Sajikan file media privat (mis. photo_kia) dengan otorisasi.
     */
    public function show(Request $request, Media $media): StreamedResponse
    {
        abort_unless($media->isPrivate(), 404);
        abort_unless($this->viewerCanAccess($media), 403);

        $size = $request->filled('size') ? (int) $request->query('size') : null;
        if ($size !== null && $size <= 0) {
            $size = null;
        }

        $path = $media->getPath($size);
        $disk = $media->resolveDiskForPath($path);
        abort_unless($disk !== null, 404);

        return Storage::disk($disk)->response($path);
    }

    private function viewerCanAccess(Media $media): bool
    {
        $model = $media->model;
        $user = auth()->user();

        if ($model instanceof Rider) {
            if ($user && (int) $model->user_id === (int) $user->id) {
                return true;
            }

            if ($user && ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('committee'))) {
                return true;
            }

            if ($user && $user->canAs('rider.read')) {
                return true;
            }

            // Organizer: hanya jika rider terdaftar di event yang dikelolanya.
            if ($user && $user->canAs('event.read')) {
                $managed = Registration::query()
                    ->where('rider_id', $model->id)
                    ->whereHas('event.organizer', fn ($q) => $q->managedBy($user))
                    ->exists();

                if ($managed) {
                    return true;
                }
            }

            // Pemilik order (session/login) untuk registrasi rider ini.
            return Order::query()
                ->whereHas('registration', fn ($q) => $q->where('rider_id', $model->id))
                ->get()
                ->contains(fn (Order $order) => $order->isOwnedByCurrentVisitor());
        }

        // Fallback media privat non-rider: hanya staff dengan rider.read.
        return $user !== null && $user->canAs('rider.read');
    }
}
