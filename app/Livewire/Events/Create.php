<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Person;
use Livewire\Component;

class Create extends Component
{
    public Person $person;

    public ?int $event_type_id = null;

    public bool $is_annual = false;

    public bool $show_milestone = false;

    public string $date = '';

    public string $budget = '';

    /**
     * Mount the component
     */
    public function mount(Person $person): void
    {
        $this->person = $person;
        $this->date = now()->format('Y-m-d');
    }

    /**
     * Save the event
     */
    public function save(): void
    {
        $validated = $this->validate([
            'event_type_id' => ['required', 'exists:event_types,id'],
            'is_annual' => ['boolean'],
            'show_milestone' => ['boolean'],
            'date' => ['required', 'date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['person_id'] = $this->person->id;
        $validated['budget'] = $validated['budget'] ?: null;

        Event::create($validated);

        session()->flash('status', 'Event created successfully.');

        $this->redirect(route('people.show', $this->person), navigate: true);
    }

    public function render()
    {
        $eventTypes = EventType::orderBy('is_custom')->orderBy('name')->get();

        return view('livewire.events.create', [
            'eventTypes' => $eventTypes,
        ]);
    }
}
