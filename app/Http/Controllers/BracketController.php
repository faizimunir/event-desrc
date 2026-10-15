<?php

namespace App\Http\Controllers;

use App\Exceptions\ComponentHasRegistrationsException;
use App\Http\Controllers\Concerns\RespondsToRegistrationTransfer;
use App\Models\Bracket;
use App\Models\Event;
use App\Services\RegistrationTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BracketController extends Controller
{
    use RespondsToRegistrationTransfer;

    public function index(Event $event)
    {
        abort_unless(auth()->user()->canAs('bracket.read'), 403);

        return redirect()->route('events.show', [$event, 'tab' => 'brackets']);
    }

    public function create(Event $event)
    {
        abort_unless(auth()->user()->canAs('bracket.create'), 403);

        return view('events.brackets.create', compact('event'));
    }

    public function store(Request $request, Event $event)
    {
        abort_unless(auth()->user()->canAs('bracket.create'), 403);

        $request->merge([
            'gender_rule' => $request->input('gender_rule') ?: null,
            'rule_type' => $request->input('rule_type') ?: null,
            'birth_year_start' => $request->input('birth_year_start') !== '' && $request->input('birth_year_start') !== null ? (int) $request->input('birth_year_start') : null,
            'birth_year_end' => $request->input('birth_year_end') !== '' && $request->input('birth_year_end') !== null ? (int) $request->input('birth_year_end') : null,
            'age_min' => $request->input('age_min') !== '' && $request->input('age_min') !== null ? (int) $request->input('age_min') : null,
            'age_max' => $request->input('age_max') !== '' && $request->input('age_max') !== null ? (int) $request->input('age_max') : null,
            'age_ref_date' => $request->input('age_ref_date') ?: null,
            'quota' => $request->input('quota') !== '' && $request->input('quota') !== null ? (int) $request->input('quota') : null,
            'hide_quota' => $request->boolean('hide_quota'),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender_rule' => ['nullable', 'string', 'in:boys,girls'],
            'rule_type' => ['nullable', 'string', 'in:age,birth_year'],
            'birth_year_start' => ['nullable', 'integer', 'min:1900', 'max:2100', 'required_if:rule_type,birth_year'],
            'birth_year_end' => ['nullable', 'integer', 'min:1900', 'max:2100', 'gte:birth_year_start'],
            'age_min' => ['nullable', 'integer', 'min:0', 'max:120', 'required_if:rule_type,age'],
            'age_max' => ['nullable', 'integer', 'min:0', 'max:120', 'gte:age_min', 'required_if:rule_type,age'],
            'age_ref_date' => ['nullable', 'date', 'required_if:rule_type,age'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'hide_quota' => ['boolean'],
        ]);

        $validated = $this->normalizeBracketRules($validated);
        $validated['hide_quota'] = $request->boolean('hide_quota');
        $event->brackets()->create($validated);

        return redirect()->route('events.show', [$event, 'tab' => 'brackets'])->with('status', __('Bracket created.'));
    }

    public function show(Event $event, Bracket $bracket)
    {
        abort_unless(auth()->user()->canAs('bracket.read'), 403);
        abort_if($bracket->event_id !== $event->id, 404);

        if (auth()->user()->canAs('bracket.update') && auth()->user()->can('update', $bracket)) {
            return redirect()->route('events.brackets.edit', [$event, $bracket]);
        }

        return redirect()->route('events.show', [$event, 'tab' => 'brackets']);
    }

    public function edit(Event $event, Bracket $bracket)
    {
        abort_unless(auth()->user()->canAs('bracket.update'), 403);
        $this->authorize('update', $bracket);
        abort_if($bracket->event_id !== $event->id, 404);

        return view('events.brackets.edit', compact('event', 'bracket'));
    }

    public function update(Request $request, Event $event, Bracket $bracket)
    {
        abort_unless(auth()->user()->canAs('bracket.update'), 403);
        $this->authorize('update', $bracket);
        abort_if($bracket->event_id !== $event->id, 404);

        $request->merge([
            'gender_rule' => $request->input('gender_rule') ?: null,
            'rule_type' => $request->input('rule_type') ?: null,
            'birth_year_start' => $request->input('birth_year_start') !== '' && $request->input('birth_year_start') !== null ? (int) $request->input('birth_year_start') : null,
            'birth_year_end' => $request->input('birth_year_end') !== '' && $request->input('birth_year_end') !== null ? (int) $request->input('birth_year_end') : null,
            'age_min' => $request->input('age_min') !== '' && $request->input('age_min') !== null ? (int) $request->input('age_min') : null,
            'age_max' => $request->input('age_max') !== '' && $request->input('age_max') !== null ? (int) $request->input('age_max') : null,
            'age_ref_date' => $request->input('age_ref_date') ?: null,
            'quota' => $request->input('quota') !== '' && $request->input('quota') !== null ? (int) $request->input('quota') : null,
            'hide_quota' => $request->boolean('hide_quota'),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender_rule' => ['nullable', 'string', 'in:boys,girls'],
            'rule_type' => ['nullable', 'string', 'in:age,birth_year'],
            'birth_year_start' => ['nullable', 'integer', 'min:1900', 'max:2100', 'required_if:rule_type,birth_year'],
            'birth_year_end' => ['nullable', 'integer', 'min:1900', 'max:2100', 'gte:birth_year_start'],
            'age_min' => ['nullable', 'integer', 'min:0', 'max:120', 'required_if:rule_type,age'],
            'age_max' => ['nullable', 'integer', 'min:0', 'max:120', 'gte:age_min', 'required_if:rule_type,age'],
            'age_ref_date' => ['nullable', 'date', 'required_if:rule_type,age'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'hide_quota' => ['boolean'],
        ]);

        $validated = $this->normalizeBracketRules($validated);
        $validated['hide_quota'] = $request->boolean('hide_quota');
        $bracket->update($validated);

        return redirect()->route('events.show', [$event, 'tab' => 'brackets'])->with('status', __('Bracket updated.'));
    }

    /**
     * Hapus bracket. Bracket yang masih punya pendaftar tidak boleh dihapus (cascade akan menghapus
     * registrasi, order, pembayaran, tiket, check-in). Hanya super_admin yang boleh force delete,
     * dengan mengetik ulang nama bracket sebagai konfirmasi.
     */
    public function destroy(Request $request, Event $event, Bracket $bracket)
    {
        abort_unless(auth()->user()->canAs('bracket.delete'), 403);
        $this->authorize('delete', $bracket);
        abort_if($bracket->event_id !== $event->id, 404);

        $user = auth()->user();
        $force = $user->hasRole('super_admin') && $request->boolean('force_delete');
        $editUrl = route('events.brackets.edit', [$event, $bracket]);

        if ($force && trim((string) $request->input('confirm_name')) !== $bracket->name) {
            return redirect($editUrl)->with('error', __('Confirmation name does not match. Bracket was not deleted.'));
        }

        try {
            $deletedRegistrations = DB::transaction(function () use ($bracket, $force, $user, $event) {
                // Lock baris bracket agar pendaftaran baru (yang juga me-lock bracket) tidak menyelip.
                $locked = Bracket::query()->whereKey($bracket->id)->lockForUpdate()->firstOrFail();
                $count = $locked->registrationsCount();

                if ($count > 0 && $force) {
                    $locked->allowDeleteWithRegistrations = true;
                    Log::warning('Bracket force-deleted together with its registrations.', [
                        'user_id' => $user->id,
                        'event_id' => $event->id,
                        'bracket_id' => $locked->id,
                        'bracket_name' => $locked->name,
                        'registrations_deleted' => $count,
                    ]);
                }

                $locked->delete();

                return $count;
            });
        } catch (ComponentHasRegistrationsException $e) {
            return redirect($editUrl)->with('error', __('This bracket still has :count registrants and cannot be deleted. Transfer them to another bracket first.', ['count' => $e->registrationsCount]));
        }

        return redirect()->route('events.show', [$event, 'tab' => 'brackets'])->with(
            'status',
            $deletedRegistrations > 0
                ? __('Bracket deleted together with :count registrations.', ['count' => $deletedRegistrations])
                : __('Bracket deleted.')
        );
    }

    /**
     * Alihkan seluruh pendaftar bracket ini ke bracket lain di event yang sama.
     * Setiap pendaftar divalidasi (umur/tahun lahir, gender, kuota, duplikat) secara terpisah.
     */
    public function transfer(Request $request, Event $event, Bracket $bracket, RegistrationTransferService $transfers)
    {
        abort_unless(auth()->user()->canAs('bracket.update') && auth()->user()->canAs('event.update'), 403);
        $this->authorize('update', $bracket);
        $this->authorize('update', $event);
        abort_if($bracket->event_id !== $event->id, 404);

        $validated = $request->validate([
            'target_bracket_id' => ['required', 'integer', Rule::in($event->brackets()->whereKeyNot($bracket->id)->pluck('id')->all())],
        ], [
            'target_bracket_id.in' => __('Please choose a different bracket in this event.'),
        ]);

        $target = $event->brackets()->findOrFail($validated['target_bracket_id']);
        $result = $transfers->transferBracket($bracket, $target);

        return $this->transferResponse(
            redirect()->route('events.brackets.edit', [$event, $bracket]),
            $result,
            $target->name,
        );
    }

    private function normalizeBracketRules(array $validated): array
    {
        $ruleType = $validated['rule_type'] ?? null;
        if ($ruleType !== 'birth_year') {
            $validated['birth_year_start'] = null;
            $validated['birth_year_end'] = null;
        }
        if ($ruleType !== 'age') {
            $validated['age_min'] = null;
            $validated['age_max'] = null;
            $validated['age_ref_date'] = null;
        }

        return $validated;
    }
}
