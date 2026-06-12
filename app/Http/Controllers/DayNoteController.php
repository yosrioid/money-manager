<?php

namespace App\Http\Controllers;

use App\Domain\Workspaces\WorkspaceContext;
use App\Http\Requests\SaveDayNoteRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class DayNoteController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
    ) {}

    public function update(SaveDayNoteRequest $request, string $date): RedirectResponse
    {
        $workspace = $this->workspaceContext->get();

        $workspace->dayNotes()->updateOrCreate(
            ['date' => $date],
            ['note' => $request->validated('note')],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note saved.')]);

        return back();
    }

    public function destroy(string $date): RedirectResponse
    {
        $workspace = $this->workspaceContext->get();

        $dayNote = $workspace->dayNotes()->where('date', $date)->first();

        if ($dayNote !== null) {
            $this->authorize('delete', $dayNote);
            $dayNote->delete();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note removed.')]);

        return back();
    }
}
