<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Gift;
use Illuminate\Support\Collection;
use Livewire\Component;

class Dashboard extends Component
{
    public bool $showGiftModal = false;

    public ?int $selectedEventId = null;

    public string $giftTitle = '';

    public string $giftValue = '';

    public int $timeframeDays = 30;

    /**
     * Set the timeframe for upcoming events
     */
    public function setTimeframe(int $days): void
    {
        $this->timeframeDays = $days;
    }

    /**
     * Get upcoming events based on selected timeframe
     */
    public function getUpcomingEventsProperty(): Collection
    {
        $today = now()->startOfDay();
        $endDate = now()->addDays($this->timeframeDays);

        return Event::with(['person', 'eventType', 'gifts', 'completions'])
            ->get()
            ->filter(function ($event) use ($today, $endDate) {
                $nextOccurrence = $event->next_occurrence;

                return $nextOccurrence->between($today, $endDate);
            })
            ->sortBy('next_occurrence')
            ->values();
    }

    /**
     * Open the gift modal for a specific event
     */
    public function openGiftModal(int $eventId): void
    {
        $this->selectedEventId = $eventId;
        $this->giftTitle = '';
        $this->giftValue = '';
        $this->showGiftModal = true;
    }

    /**
     * Close the gift modal
     */
    public function closeGiftModal(): void
    {
        $this->showGiftModal = false;
        $this->selectedEventId = null;
        $this->giftTitle = '';
        $this->giftValue = '';
        $this->resetValidation();
    }

    /**
     * Save the gift
     */
    public function saveGift(): void
    {
        $validated = $this->validate([
            'giftTitle' => ['required', 'string', 'max:255'],
            'giftValue' => ['nullable', 'numeric', 'min:0'],
        ]);

        $event = Event::findOrFail($this->selectedEventId);

        Gift::create([
            'event_id' => $event->id,
            'year' => $event->next_occurrence_year,
            'title' => $validated['giftTitle'],
            'value' => $validated['giftValue'] ?: null,
        ]);

        session()->flash('status', 'Gift added successfully.');

        $this->closeGiftModal();
    }

    /**
     * Toggle completion for an event
     */
    public function toggleCompletion(int $eventId): void
    {
        $event = Event::findOrFail($eventId);
        $year = $event->next_occurrence_year;

        if ($event->isCompletedForYear($year)) {
            $event->unmarkCompleteForYear($year);
            session()->flash('status', 'Event marked as incomplete.');
        } else {
            $event->markCompleteForYear($year);
            session()->flash('status', 'Event marked as complete!');
        }
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
