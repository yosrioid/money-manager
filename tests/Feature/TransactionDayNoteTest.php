<?php

use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function setUpDayNoteWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    return [$user, $workspace];
}

test('a day note can be created and appears on the day and calendar views', function () {
    [$user, $workspace] = setUpDayNoteWorkspace();

    $this->actingAs($user)
        ->put(route('transactions.day-notes.update', ['date' => '2026-06-05']), [
            'note' => 'Paid rent today.',
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('transactions.day', ['date' => '2026-06-05']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Day')
            ->where('note', 'Paid rent today.')
        );

    $this->actingAs($user)
        ->get(route('transactions.calendar', ['month' => '2026-06']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Calendar')
            ->where('notes.2026-06-05', 'Paid rent today.')
        );
});

test('a day note can be updated', function () {
    [$user, $workspace] = setUpDayNoteWorkspace();

    $this->actingAs($user)
        ->put(route('transactions.day-notes.update', ['date' => '2026-06-05']), [
            'note' => 'First version.',
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->put(route('transactions.day-notes.update', ['date' => '2026-06-05']), [
            'note' => 'Updated version.',
        ])
        ->assertRedirect();

    expect($workspace->dayNotes()->where('date', '2026-06-05')->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('transactions.day', ['date' => '2026-06-05']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('note', 'Updated version.')
        );
});

test('a day note can be removed', function () {
    [$user, $workspace] = setUpDayNoteWorkspace();

    $this->actingAs($user)
        ->put(route('transactions.day-notes.update', ['date' => '2026-06-05']), [
            'note' => 'Temporary note.',
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->delete(route('transactions.day-notes.destroy', ['date' => '2026-06-05']))
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('transactions.day', ['date' => '2026-06-05']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('note', null)
        );
});

test('a day note requires non-empty text', function () {
    [$user, $workspace] = setUpDayNoteWorkspace();

    $this->actingAs($user)
        ->put(route('transactions.day-notes.update', ['date' => '2026-06-05']), [
            'note' => '',
        ])
        ->assertInvalid(['note']);
});

test('day notes are isolated per workspace', function () {
    [$user, $workspace] = setUpDayNoteWorkspace();
    [$otherUser, $otherWorkspace] = setUpDayNoteWorkspace();

    $this->actingAs($otherUser)
        ->put(route('transactions.day-notes.update', ['date' => '2026-06-05']), [
            'note' => 'Other workspace note.',
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('transactions.day', ['date' => '2026-06-05']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('note', null)
        );

    $this->actingAs($user)
        ->get(route('transactions.calendar', ['month' => '2026-06']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notes', []),
        );
});
