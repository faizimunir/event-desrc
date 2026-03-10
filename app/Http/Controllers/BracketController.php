<?php

namespace App\Http\Controllers;

use App\Models\Bracket;
use App\Models\Event;
use Illuminate\Http\Request;

class BracketController extends Controller
{
    public function index(Event $event)
    {
        abort_unless(auth()->user()->canAs('bracket.read'), 403);

        return view('events.brackets.index', compact('event'));
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
        ]);

        $validated = $this->normalizeBracketRules($validated);
        $event->brackets()->create($validated);

        return redirect()->route('events.brackets.index', $event)->with('status', __('Bracket created.'));
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
        ]);

        $validated = $this->normalizeBracketRules($validated);
        $bracket->update($validated);

        return redirect()->route('events.brackets.index', $event)->with('status', __('Bracket updated.'));
    }

    public function destroy(Event $event, Bracket $bracket)
    {
        abort_unless(auth()->user()->canAs('bracket.delete'), 403);
        $this->authorize('delete', $bracket);
        abort_if($bracket->event_id !== $event->id, 404);

        $bracket->delete();

        return redirect()->route('events.brackets.index', $event)->with('status', __('Bracket deleted.'));
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
