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

    /** @var array<int|string> */
    public array $bracketsSelected = [];

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
            $this->bracketsSelected = $this->rundown->brackets->pluck('id')->map(fn ($id) => (string) $id)->all();
        } else {
            abort_unless(auth()->user()->canAs('rundown.create'), 403);
        }
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
            });
        })->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'title' => ['nullable', 'string', 'max:255'],
            'bracketsSelected' => ['nullable', 'array'],
            'bracketsSelected.*' => [
                'integer',
                Rule::exists('event_brackets', 'id')->where('event_id', $this->event->id),
            ],
        ]);

        $title = trim($this->title) !== '' ? trim($this->title) : null;
        $brackets = collect($this->bracketsSelected)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $payload = [
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'title' => $title,
        ];

        if ($this->rundown?->exists) {
            $this->rundown->update($payload);
            $this->rundown->brackets()->sync($brackets);
            session()->flash('status', __('Rundown updated.'));
        } else {
            $rundown = $this->event->rundowns()->create($payload);
            if (! empty($brackets)) {
                $rundown->brackets()->sync($brackets);
            }
            session()->flash('status', __('Rundown created.'));
        }

        LiveResultCategory::syncOrderForEvent($this->event);

        $this->redirect(route('events.show', [$this->event, 'tab' => 'rundown']), navigate: true);
    }

    public function render()
    {
        return view('livewire.rundowns.rundown-form', [
            'brackets' => $this->event->brackets_sorted_for_display,
            'minTime' => $this->eventMinTime(),
            'maxTime' => $this->eventMaxTime(),
            'timeWindowLabel' => $this->eventTimeWindowLabel(),
        ]);
    }
}
