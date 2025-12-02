<?php

declare(strict_types=1);

use App\Livewire\Dashboard;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Gift;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can view dashboard', function () {
    Livewire::test(Dashboard::class)
        ->assertStatus(200);
});

test('can open gift modal', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    Livewire::test(Dashboard::class)
        ->call('openGiftModal', $event->id)
        ->assertSet('showGiftModal', true)
        ->assertSet('selectedEventId', $event->id);
});

test('can close gift modal', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    Livewire::test(Dashboard::class)
        ->call('openGiftModal', $event->id)
        ->assertSet('showGiftModal', true)
        ->call('closeGiftModal')
        ->assertSet('showGiftModal', false)
        ->assertSet('selectedEventId', null)
        ->assertSet('giftTitle', '')
        ->assertSet('giftValue', '');
});

test('can add gift via modal', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    Livewire::test(Dashboard::class)
        ->call('openGiftModal', $event->id)
        ->set('giftTitle', 'Test Gift')
        ->set('giftValue', '50.00')
        ->call('saveGift')
        ->assertHasNoErrors()
        ->assertSet('showGiftModal', false);

    $gift = Gift::where('event_id', $event->id)->first();
    expect($gift)
        ->not->toBeNull()
        ->and($gift->title)->toBe('Test Gift')
        ->and((float) $gift->value)->toBe(50.00)
        ->and($gift->year)->toBe($event->next_occurrence_year);
});

test('can add gift without value', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    Livewire::test(Dashboard::class)
        ->call('openGiftModal', $event->id)
        ->set('giftTitle', 'Gift without value')
        ->call('saveGift')
        ->assertHasNoErrors();

    $gift = Gift::where('event_id', $event->id)->first();
    expect($gift)
        ->not->toBeNull()
        ->and($gift->title)->toBe('Gift without value')
        ->and($gift->value)->toBeNull();
});

test('validates gift title is required', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    Livewire::test(Dashboard::class)
        ->call('openGiftModal', $event->id)
        ->set('giftTitle', '')
        ->call('saveGift')
        ->assertHasErrors(['giftTitle' => 'required']);
});

test('validates gift value is numeric', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    Livewire::test(Dashboard::class)
        ->call('openGiftModal', $event->id)
        ->set('giftTitle', 'Test Gift')
        ->set('giftValue', 'invalid')
        ->call('saveGift')
        ->assertHasErrors(['giftValue' => 'numeric']);
});

test('can toggle event completion', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    expect($event->isCompletedForYear($event->next_occurrence_year))->toBeFalse();

    Livewire::test(Dashboard::class)
        ->call('toggleCompletion', $event->id);

    $event->refresh();
    expect($event->isCompletedForYear($event->next_occurrence_year))->toBeTrue();
});

test('can untoggle event completion', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    // Mark as complete first
    $event->markCompleteForYear($event->next_occurrence_year);
    expect($event->isCompletedForYear($event->next_occurrence_year))->toBeTrue();

    // Then toggle to incomplete
    Livewire::test(Dashboard::class)
        ->call('toggleCompletion', $event->id);

    $event->refresh();
    expect($event->isCompletedForYear($event->next_occurrence_year))->toBeFalse();
});

test('shows only upcoming uncompleted events', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);

    // Create upcoming event
    $upcomingEvent = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    // Create past event
    $pastEvent = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->subDays(10),
        'recurrence' => 'yearly',
    ]);

    // Create completed event
    $completedEvent = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(20),
        'recurrence' => 'yearly',
    ]);
    $completedEvent->markCompleteForYear($completedEvent->next_occurrence_year);

    $component = Livewire::test(Dashboard::class);
    $upcomingEvents = $component->get('upcomingEvents');

    expect($upcomingEvents)
        ->toHaveCount(1)
        ->first()->id->toBe($upcomingEvent->id);
});
