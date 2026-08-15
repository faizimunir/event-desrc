<?php

namespace App\Livewire\Rundowns;

use App\Models\Event;
use App\Models\LiveResultCategory;
use App\Models\Rundown;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Livewire\Component;

class RundownForm extends Component
{
    public Event $event;

    public ?Rundown $rundown = null;

    public string $start_time = '';

    public string $end_time = '';

    public string $title = '';

    public string $actual_started_at = '';

    public string $actual_ended_at = '';

    /** @var array<int|string> */
    public array $bracketsSelected = [];

    /** @var array<string, int|string> bracket_id => sort_order */
    public array $bracketOrders = [];

    public function mount(Event $event, ?Rundown $rundown = null): void
    {
        $this->event = $event;
        $this->rundown = $rundown;

        if ($this->rundown?->exists) {
            abort_unless(auth()->user()->canAs('rundown.update'), 403);
            $this->authorize('update', $this->rundown);
            abort_if($this->rundown->event_id !== $this->event->id, 404);

            $this->start_time = $this->rundown->timeInputValue($this->rundown->start_time);
            $this->end_time = $this->rundown->timeInputValue($this->rundown->end_time);
            $this->title = (string) ($this->rundown->title ?? '');
            $this->actual_started_at = $this->rundown->actual_started_at
                ? $this->rundown->actual_started_at->format('H:i')
                : '';
            $this->actual_ended_at = $this->rundown->actual_ended_at
                ? $this->rundown->actual_ended_at->format('H:i')
                : '';

            $orderedBrackets = $this->rundown->brackets()
                ->orderByPivot('sort_order')
                ->orderBy('event_brackets.id')
                ->get();

            $this->bracketsSelected = $orderedBrackets->pluck('id')->map(fn ($id) => (string) $id)->all();
            $this->bracketOrders = $orderedBrackets
                ->mapWithKeys(fn ($bracket, $index) => [
                    (string) $bracket->id => (int) ($bracket->pivot->sort_order ?? $index),
                ])
                ->all();
        } else {
            abort_unless(auth()->user()->canAs('rundown.create'), 403);
        }
    }

    public function updatedBracketsSelected(): void
    {
        $selected = collect($this->bracketsSelected)->map(fn ($id) => (string) $id)->unique()->values();
        $this->bracketsSelected = $selected->all();

        $next = collect($this->bracketOrders)
            ->only($selected->all())
            ->map(fn ($order) => (int) $order)
            ->max();
        $next = is_int($next) ? $next + 1 : 0;

        $orders = [];
        foreach ($selected as $id) {
            if (array_key_exists($id, $this->bracketOrders) && $this->bracketOrders[$id] !== '' && $this->bracketOrders[$id] !== null) {
                $orders[$id] = (int) $this->bracketOrders[$id];
            } else {
                $orders[$id] = $next++;
            }
        }

        $this->bracketOrders = $orders;
    }

    public function eventMinTime(): ?string
    {
        return $this->event->start_at?->format('H:i');
    }

    public function eventMaxTime(): ?string
    {
        return $this->event->end_at?->format('H:i');
    }

    public function eventTimeWindowLabel(): ?string
    {
        $min = $this->eventMinTime();
        $max = $this->eventMaxTime();

        if (! $min && ! $max) {
            return null;
        }

        $format = fn (?string $time) => $time
            ? str_replace(':', '.', $time)
            : '—';

        return $format($min).' – '.$format($max);
    }

    public function save(): void
    {
        if ($this->rundown?->exists) {
            abort_unless(auth()->user()->canAs('rundown.update'), 403);
            $this->authorize('update', $this->rundown);
        } else {
            abort_unless(auth()->user()->canAs('rundown.create'), 403);
        }

        $minTime = $this->eventMinTime();
        $maxTime = $this->eventMaxTime();

        $this->withValidator(function (Validator $validator) use ($minTime, $maxTime) {
            $validator->after(function (Validator $validator) use ($minTime, $maxTime) {
                $title = trim($this->title);
                $brackets = array_filter($this->bracketsSelected);

                if ($title === '' && empty($brackets)) {
                    $validator->errors()->add('title', __('Provide a title or select at least one bracket.'));
                    $validator->errors()->add('bracketsSelected', __('Provide a title or select at least one bracket.'));
                }

                if ($validator->errors()->has('start_time') || $validator->errors()->has('end_time')) {
                    return;
                }

                if (! $this->start_time || ! $this->end_time) {
                    return;
                }

                $window = $this->eventTimeWindowLabel();

                if ($minTime && $this->start_time < $minTime) {
                    $validator->errors()->add(
                        'start_time',
                        __('Start time must be within the event window (:window).', ['window' => $window])
                    );
                }

                if ($maxTime && $this->start_time > $maxTime) {
                    $validator->errors()->add(
                        'start_time',
                        __('Start time must be within the event window (:window).', ['window' => $window])
                    );
                }

                if ($minTime && $this->end_time < $minTime) {
                    $validator->errors()->add(
                        'end_time',
                        __('End time must be within the event window (:window).', ['window' => $window])
                    );
                }

                if ($maxTime && $this->end_time > $maxTime) {
                    $validator->errors()->add(
                        'end_time',
                        __('End time must be within the event window (:window).', ['window' => $window])
                    );
                }

                if ($this->rundown?->exists) {
                    $actualStart = trim($this->actual_started_at);
                    $actualEnd = trim($this->actual_ended_at);

                    if ($actualEnd !== '' && $actualStart === '') {
                        $validator->errors()->add(
                            'actual_started_at',
                            __('Actual start time is required when actual end time is set.')
                        );
                    }

                    if ($actualStart !== '' && $actualEnd !== '' && $actualEnd <= $actualStart) {
                        $validator->errors()->add(
                            'actual_ended_at',
                            __('Actual end time must be after actual start time.')
                        );
                    }
                }
            });
        })->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'title' => ['nullable', 'string', 'max:255'],
            'actual_started_at' => ['nullable', 'date_format:H:i'],
            'actual_ended_at' => ['nullable', 'date_format:H:i'],
            'bracketsSelected' => ['nullable', 'array'],
            'bracketsSelected.*' => [
                'integer',
                Rule::exists('event_brackets', 'id')->where('event_id', $this->event->id),
            ],
            'bracketOrders' => ['nullable', 'array'],
            'bracketOrders.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $title = trim($this->title) !== '' ? trim($this->title) : null;
        $sync = collect($this->bracketsSelected)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->mapWithKeys(fn ($id) => [
                $id => ['sort_order' => (int) ($this->bracketOrders[(string) $id] ?? 0)],
            ])
            ->all();

        $payload = [
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'title' => $title,
        ];

        if ($this->rundown?->exists) {
            $payload['actual_started_at'] = $this->resolveActualDateTime(trim($this->actual_started_at));
            $payload['actual_ended_at'] = $this->resolveActualDateTime(trim($this->actual_ended_at));

            $this->rundown->update($payload);
            $this->rundown->brackets()->sync($sync);
            session()->flash('status', __('Rundown updated.'));
        } else {
            $rundown = $this->event->rundowns()->create($payload);
            if (! empty($sync)) {
                $rundown->brackets()->sync($sync);
            }
            session()->flash('status', __('Rundown created.'));
        }

        LiveResultCategory::syncOrderForEvent($this->event);

        $this->redirect(route('events.show', [$this->event, 'tab' => 'rundown']), navigate: true);
    }

    private function resolveActualDateTime(string $time): ?\Carbon\CarbonInterface
    {
        if ($time === '') {
            return null;
        }

        $clock = \Carbon\Carbon::parse($time);
        $base = $this->event->start_at?->copy() ?? now();

        return $base->copy()->setTime($clock->hour, $clock->minute, 0);
    }

    public function render()
    {
        $selectedIds = collect($this->bracketsSelected)->map(fn ($id) => (string) $id)->all();

        $selectedBrackets = $this->event->brackets_sorted_for_display
            ->filter(fn ($bracket) => in_array((string) $bracket->id, $selectedIds, true))
            ->sortBy(fn ($bracket) => (int) ($this->bracketOrders[(string) $bracket->id] ?? 0))
            ->values();

        $previewRundown = null;
        if ($this->rundown?->exists) {
            $previewRundown = $this->rundown->replicate();
            $previewRundown->setRelation('event', $this->event);
            $previewRundown->setRelation('brackets', $this->rundown->brackets);
            $previewRundown->start_time = $this->start_time !== '' ? $this->start_time : $this->rundown->start_time;
            $previewRundown->end_time = $this->end_time !== '' ? $this->end_time : $this->rundown->end_time;
            $previewRundown->actual_started_at = $this->resolveActualDateTime(trim($this->actual_started_at));
            $previewRundown->actual_ended_at = $this->resolveActualDateTime(trim($this->actual_ended_at));
        }

        return view('livewire.rundowns.rundown-form', [
            'brackets' => $this->event->brackets_sorted_for_display,
            'selectedBrackets' => $selectedBrackets,
            'minTime' => $this->eventMinTime(),
            'maxTime' => $this->eventMaxTime(),
            'timeWindowLabel' => $this->eventTimeWindowLabel(),
            'previewRundown' => $previewRundown,
        ]);
    }
}
