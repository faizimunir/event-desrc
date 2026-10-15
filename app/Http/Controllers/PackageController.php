<?php

namespace App\Http\Controllers;

use App\Exceptions\ComponentHasRegistrationsException;
use App\Http\Controllers\Concerns\RespondsToRegistrationTransfer;
use App\Models\Event;
use App\Models\Package;
use App\Services\RegistrationTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    use RespondsToRegistrationTransfer;

    public function index(Event $event)
    {
        abort_unless(auth()->user()->canAs('package.read'), 403);

        return redirect()->route('events.show', [$event, 'tab' => 'packages']);
    }

    public function create(Event $event)
    {
        abort_unless(auth()->user()->canAs('package.create'), 403);

        return view('events.packages.create', compact('event'));
    }

    public function show(Event $event, Package $package)
    {
        abort_unless(auth()->user()->canAs('package.read'), 403);
        abort_if($package->event_id !== $event->id, 404);

        if (auth()->user()->canAs('package.update') && auth()->user()->can('update', $package)) {
            return redirect()->route('events.packages.edit', [$event, $package]);
        }

        return redirect()->route('events.show', [$event, 'tab' => 'packages']);
    }

    public function edit(Event $event, Package $package)
    {
        abort_unless(auth()->user()->canAs('package.update'), 403);
        $this->authorize('update', $package);
        abort_if($package->event_id !== $event->id, 404);

        return view('events.packages.edit', compact('event', 'package'));
    }

    /**
     * Hapus package. Package yang masih punya pendaftar tidak boleh dihapus (cascade akan menghapus
     * registrasi, order, pembayaran, tiket, check-in). Hanya super_admin yang boleh force delete,
     * dengan mengetik ulang nama package sebagai konfirmasi.
     */
    public function destroy(Request $request, Event $event, Package $package)
    {
        abort_unless(auth()->user()->canAs('package.delete'), 403);
        $this->authorize('delete', $package);
        abort_if($package->event_id !== $event->id, 404);

        $user = auth()->user();
        $force = $user->hasRole('super_admin') && $request->boolean('force_delete');
        $editUrl = route('events.packages.edit', [$event, $package]);

        if ($force && trim((string) $request->input('confirm_name')) !== $package->name) {
            return redirect($editUrl)->with('error', __('Confirmation name does not match. Package was not deleted.'));
        }

        try {
            $deletedRegistrations = DB::transaction(function () use ($package, $force, $user, $event) {
                // Lock baris package agar pendaftaran baru (yang juga me-lock package) tidak menyelip.
                $locked = Package::query()->whereKey($package->id)->lockForUpdate()->firstOrFail();
                $count = $locked->registrationsCount();

                if ($count > 0 && $force) {
                    $locked->allowDeleteWithRegistrations = true;
                    Log::warning('Package force-deleted together with its registrations.', [
                        'user_id' => $user->id,
                        'event_id' => $event->id,
                        'package_id' => $locked->id,
                        'package_name' => $locked->name,
                        'registrations_deleted' => $count,
                    ]);
                }

                $locked->delete();

                return $count;
            });
        } catch (ComponentHasRegistrationsException $e) {
            return redirect($editUrl)->with('error', __('This package still has :count registrants and cannot be deleted. Transfer them to another active package first.', ['count' => $e->registrationsCount]));
        }

        return redirect()->route('events.show', [$event, 'tab' => 'packages'])->with(
            'status',
            $deletedRegistrations > 0
                ? __('Package deleted together with :count registrations.', ['count' => $deletedRegistrations])
                : __('Package deleted.')
        );
    }

    /**
     * Alihkan seluruh pendaftar package ini ke package aktif lain di event yang sama.
     */
    public function transfer(Request $request, Event $event, Package $package, RegistrationTransferService $transfers)
    {
        abort_unless(auth()->user()->canAs('package.update') && auth()->user()->canAs('event.update'), 403);
        $this->authorize('update', $package);
        $this->authorize('update', $event);
        abort_if($package->event_id !== $event->id, 404);

        $validTargets = $event->packages()
            ->whereKeyNot($package->id)
            ->where('status', Package::STATUS_ACTIVE)
            ->pluck('id')
            ->all();

        $validated = $request->validate([
            'target_package_id' => ['required', 'integer', Rule::in($validTargets)],
        ], [
            'target_package_id.in' => __('Please choose a different active package in this event.'),
        ]);

        $target = $event->packages()->findOrFail($validated['target_package_id']);
        $result = $transfers->transferPackage($package, $target);

        return $this->transferResponse(
            redirect()->route('events.packages.edit', [$event, $package]),
            $result,
            $target->name,
        );
    }
}
