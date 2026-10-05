<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notes\ReorderNotesRequest;
use App\Http\Requests\Notes\StoreNoteRequest;
use App\Http\Requests\Notes\UpdateNoteRequest;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    /**
     * Display a listing of user notes.
     */
    public function index(Request $request): Response|JsonResponse
    {
        abort_unless($request->user()->can('viewAny', Note::class), 403);

        $notes = $request->user()
            ->notes()
            ->orderBy('is_pinned', 'desc')
            ->orderBy('order_column', 'asc')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn (Note $note) => [
                'id' => $note->id,
                'title' => $note->title,
                'content' => $note->content ?? '',
                'color' => $note->color,
                'is_pinned' => (bool) $note->is_pinned,
                'order_column' => (int) $note->order_column,
                'created_at' => $note->created_at?->toISOString(),
                'updated_at' => $note->updated_at?->toISOString(),
            ]);

        if ($request->wantsJson()) {
            return response()->json($notes);
        }

        return Inertia::render('notes/Index', [
            'notes' => $notes,
        ]);
    }

    /**
     * Store a newly created note in storage.
     */
    public function store(StoreNoteRequest $request): JsonResponse|RedirectResponse
    {
        $minOrder = (int) ($request->user()->notes()->min('order_column') ?? 0);
        $newOrder = $minOrder > 0 ? 0 : $minOrder - 1;

        $note = $request->user()->notes()->create([
            'title' => $request->input('title') ?: 'Untitled Note',
            'content' => $request->input('content', ''),
            'color' => $request->input('color', 'slate'),
            'is_pinned' => (bool) $request->input('is_pinned', false),
            'order_column' => $newOrder,
        ]);

        $formatted = [
            'id' => $note->id,
            'title' => $note->title,
            'content' => $note->content ?? '',
            'color' => $note->color,
            'is_pinned' => (bool) $note->is_pinned,
            'order_column' => (int) $note->order_column,
            'created_at' => $note->created_at?->toISOString(),
            'updated_at' => $note->updated_at?->toISOString(),
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'note' => $formatted,
            ], 201);
        }

        return to_route('notes.index');
    }

    /**
     * Update the specified note in storage.
     */
    public function update(UpdateNoteRequest $request, Note $note): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $note->update($validated);

        $formatted = [
            'id' => $note->id,
            'title' => $note->title,
            'content' => $note->content ?? '',
            'color' => $note->color,
            'is_pinned' => (bool) $note->is_pinned,
            'order_column' => (int) $note->order_column,
            'created_at' => $note->created_at?->toISOString(),
            'updated_at' => $note->updated_at?->toISOString(),
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'note' => $formatted,
            ]);
        }

        return back();
    }

    /**
     * Remove the specified note from storage.
     */
    public function destroy(Request $request, Note $note): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()->can('delete', $note), 403);

        $note->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Note deleted successfully.'),
        ]);

        return to_route('notes.index');
    }

    /**
     * Reorder notes for the current user.
     */
    public function reorder(ReorderNotesRequest $request): JsonResponse
    {
        $orderedIds = $request->validated('ordered_ids');

        DB::transaction(function () use ($request, $orderedIds) {
            foreach ($orderedIds as $index => $id) {
                $request->user()->notes()->where('id', $id)->update(['order_column' => $index]);
            }
        });

        return response()->json(['success' => true]);
    }
}
