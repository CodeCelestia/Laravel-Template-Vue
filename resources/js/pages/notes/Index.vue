<script setup lang="ts">
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    Check,
    Copy,
    Download,
    FileText,
    GripVertical,
    Loader2,
    Maximize2,
    Minimize2,
    NotebookPen,
    Pin,
    PinOff,
    Plus,
    Search,
    Trash2,
    X,
} from '@lucide/vue';
import { toast } from 'vue-sonner';
import {
    destroy as notesDestroy,
    reorder as notesReorder,
    store as notesStore,
    update as notesUpdate,
} from '@/actions/App/Http/Controllers/NoteController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Open Notes',
                href: '/notes',
            },
        ],
    },
});

export type NoteItem = {
    id: number;
    title: string;
    content: string;
    color: string;
    is_pinned: boolean;
    order_column: number;
    created_at: string;
    updated_at: string;
};

const props = defineProps<{
    notes: NoteItem[];
}>();

// Color palette definitions matching the reference design
const noteColors = [
    {
        key: 'slate',
        label: 'Dark Gray',
        bgClass: 'bg-slate-700',
        ringClass: 'ring-slate-400',
        dotClass: 'bg-slate-500',
    },
    {
        key: 'orange',
        label: 'Orange',
        bgClass: 'bg-orange-500',
        ringClass: 'ring-orange-400',
        dotClass: 'bg-orange-500',
    },
    {
        key: 'yellow',
        label: 'Yellow',
        bgClass: 'bg-amber-400',
        ringClass: 'ring-amber-300',
        dotClass: 'bg-amber-400',
    },
    {
        key: 'cyan',
        label: 'Cyan',
        bgClass: 'bg-cyan-500',
        ringClass: 'ring-cyan-400',
        dotClass: 'bg-cyan-500',
    },
    {
        key: 'rose',
        label: 'Rose',
        bgClass: 'bg-rose-500',
        ringClass: 'ring-rose-400',
        dotClass: 'bg-rose-500',
    },
    {
        key: 'purple',
        label: 'Purple',
        bgClass: 'bg-purple-500',
        ringClass: 'ring-purple-400',
        dotClass: 'bg-purple-500',
    },
] as const;

function getColorDotClass(colorName?: string): string {
    const found = noteColors.find((c) => c.key === colorName);
    return found ? found.dotClass : 'bg-slate-500';
}

function getCsrfToken(): string {
    const match = document.cookie.match(
        new RegExp('(^|;\\s*)XSRF-TOKEN=([^;]+)'),
    );
    return match ? decodeURIComponent(match[2]) : '';
}

// Local state
const localNotes = ref<NoteItem[]>([...props.notes]);
const activeNoteId = ref<number | null>(
    props.notes.length > 0 ? props.notes[0].id : null,
);
const searchQuery = ref('');
const isFullscreen = ref(false);
const saveStatus = ref<'saved' | 'saving' | 'unsaved' | 'error'>('saved');
const isCreatingNote = ref(false);

// Active note draft fields for editing
const draftTitle = ref('');
const draftContent = ref('');
const draftColor = ref('slate');
const draftIsPinned = ref(false);
const isTitleUserModified = ref(false);

const titleInputRef = ref<HTMLInputElement | null>(null);
const contentTextareaRef = ref<HTMLTextAreaElement | null>(null);

// Maximum characters for auto-populated title (like Notepad)
const MAX_AUTO_TITLE_LENGTH = 50;

function extractAutoTitle(content: string): string {
    if (!content) {
        return 'Untitled Note';
    }

    // Find the first non-empty line
    const lines = content.split('\n');
    let firstLine = '';
    for (const rawLine of lines) {
        const line = rawLine.trim();
        if (line.length > 0) {
            firstLine = line;
            break;
        }
    }

    if (!firstLine) {
        return 'Untitled Note';
    }

    // Strip leading markdown symbols (# header, - list, * list, 1. numbered list)
    firstLine = firstLine.replace(/^(#{1,6}|\*|-|\d+\.)\s+/, '').trim();

    if (!firstLine) {
        return 'Untitled Note';
    }

    // Truncate at word boundary within limit if longer than max limit
    if (firstLine.length <= MAX_AUTO_TITLE_LENGTH) {
        return firstLine;
    }

    const truncated = firstLine.slice(0, MAX_AUTO_TITLE_LENGTH);
    const lastSpace = truncated.lastIndexOf(' ');
    if (lastSpace > 20) {
        return truncated.slice(0, lastSpace).trim();
    }
    return truncated.trim();
}

// Delete dialog
const isDeleteDialogOpen = ref(false);
const noteToDelete = ref<NoteItem | null>(null);
const isDeleting = ref(false);

// Drag & drop state
const draggedNoteIndex = ref<number | null>(null);
const dragOverNoteIndex = ref<number | null>(null);
const isReordering = ref(false);

// Timer for auto-save debounce
let autoSaveTimer: ReturnType<typeof setTimeout> | null = null;
let pendingSaveAbortController: AbortController | null = null;

// The currently active note object in localNotes
const activeNote = computed(() => {
    if (!activeNoteId.value) return null;
    return localNotes.value.find((n) => n.id === activeNoteId.value) || null;
});

// Sync draft with active note when active note changes
function syncDraftFromActiveNote(note: NoteItem | null) {
    if (note) {
        draftTitle.value = note.title;
        draftContent.value = note.content;
        draftColor.value = note.color;
        draftIsPinned.value = note.is_pinned;
        saveStatus.value = 'saved';

        // Check if title was manually set or matches auto-generated title / Untitled Note
        const autoTitle = extractAutoTitle(note.content);
        isTitleUserModified.value =
            Boolean(note.title) &&
            note.title !== 'Untitled Note' &&
            note.title !== autoTitle;
    } else {
        draftTitle.value = '';
        draftContent.value = '';
        draftColor.value = 'slate';
        draftIsPinned.value = false;
        isTitleUserModified.value = false;
        saveStatus.value = 'saved';
    }
}

// Watch activeNoteId to sync draft
watch(
    activeNoteId,
    (newId, oldId) => {
        if (oldId && saveStatus.value === 'unsaved') {
            flushSaveImmediate(oldId);
        }
        const note = localNotes.value.find((n) => n.id === newId) || null;
        syncDraftFromActiveNote(note);
    },
    { immediate: true },
);

// Filtered notes by search query
const filteredNotes = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) {
        return localNotes.value;
    }
    return localNotes.value.filter(
        (n) =>
            (n.title && n.title.toLowerCase().includes(q)) ||
            (n.content && n.content.toLowerCase().includes(q)),
    );
});

// Format date like "Sep 21, 2026 04:33 PM"
function formatNoteDate(isoString?: string): string {
    if (!isoString) return '';
    const d = new Date(isoString);
    if (isNaN(d.getTime())) return '';
    return d.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
}

// Called when user types in the content area
function onContentInput() {
    if (!activeNote.value) return;

    // Automatically populate title from first words if title wasn't manually customized
    if (
        !isTitleUserModified.value ||
        !draftTitle.value.trim() ||
        draftTitle.value === 'Untitled Note'
    ) {
        const autoTitle = extractAutoTitle(draftContent.value);
        draftTitle.value = autoTitle;
        activeNote.value.title = autoTitle;
    }

    onDraftChange();
}

// Called when user explicitly edits the title input
function onTitleManualInput() {
    isTitleUserModified.value = true;
    onDraftChange();
}

// Called when title input loses focus
function onTitleBlur() {
    if (!draftTitle.value.trim()) {
        isTitleUserModified.value = false;
        const autoTitle = extractAutoTitle(draftContent.value);
        draftTitle.value = autoTitle;
        if (activeNote.value) {
            activeNote.value.title = autoTitle;
        }
    }
    flushSave();
}

// Trigger auto-save debounce on title or content change
function onDraftChange() {
    if (!activeNote.value) return;

    saveStatus.value = 'unsaved';

    // Update active note in local array for immediate responsive UI / snippet preview
    activeNote.value.title = draftTitle.value;
    activeNote.value.content = draftContent.value;
    activeNote.value.color = draftColor.value;
    activeNote.value.is_pinned = draftIsPinned.value;

    if (autoSaveTimer) {
        clearTimeout(autoSaveTimer);
    }
    autoSaveTimer = setTimeout(() => {
        flushSave();
    }, 600);
}

// Immediate flush save for the current or specific note
async function flushSaveImmediate(targetNoteId?: number) {
    const id = targetNoteId ?? activeNoteId.value;
    if (!id) return;

    if (autoSaveTimer) {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = null;
    }

    const note = localNotes.value.find((n) => n.id === id);
    if (!note) return;

    const payload = {
        title: note.id === activeNoteId.value ? draftTitle.value : note.title,
        content:
            note.id === activeNoteId.value ? draftContent.value : note.content,
        color: note.id === activeNoteId.value ? draftColor.value : note.color,
        is_pinned:
            note.id === activeNoteId.value
                ? draftIsPinned.value
                : note.is_pinned,
    };

    saveStatus.value = 'saving';

    try {
        if (pendingSaveAbortController) {
            pendingSaveAbortController.abort();
        }
        pendingSaveAbortController = new AbortController();

        const res = await fetch(notesUpdate.url(id), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(payload),
            signal: pendingSaveAbortController.signal,
        });

        if (res.ok) {
            const data = await res.json();
            if (data?.note) {
                note.updated_at = data.note.updated_at;
            }
            saveStatus.value = 'saved';
        } else {
            saveStatus.value = 'error';
        }
    } catch (err: any) {
        if (err.name !== 'AbortError') {
            saveStatus.value = 'error';
        }
    }
}

function flushSave() {
    flushSaveImmediate();
}

// Beacon save for browser unload
function flushSaveBeacon() {
    if (!activeNoteId.value || saveStatus.value === 'saved') return;
    const note = activeNote.value;
    if (!note) return;

    const payload = JSON.stringify({
        title: draftTitle.value,
        content: draftContent.value,
        color: draftColor.value,
        is_pinned: draftIsPinned.value,
    });

    const headers = { type: 'application/json' };
    const blob = new Blob([payload], headers);
    navigator.sendBeacon?.(notesUpdate.url(note.id), blob);
}

// Change color immediately and save
function selectColor(colorKey: string) {
    if (!activeNote.value) return;
    draftColor.value = colorKey;
    activeNote.value.color = colorKey;
    flushSave();
}

// Toggle pinned status and save
function togglePin() {
    if (!activeNote.value) return;
    draftIsPinned.value = !draftIsPinned.value;
    activeNote.value.is_pinned = draftIsPinned.value;
    flushSave();
}

// Create new note
async function createNewNote() {
    if (isCreatingNote.value) return;
    isCreatingNote.value = true;

    // Flush any pending unsaved work first
    if (saveStatus.value === 'unsaved' && activeNoteId.value) {
        await flushSaveImmediate();
    }

    try {
        const res = await fetch(notesStore.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                title: 'Untitled Note',
                content: '',
                color: 'slate',
                is_pinned: false,
            }),
        });

        if (res.ok) {
            const data = await res.json();
            const newNote: NoteItem = data.note;
            localNotes.value.unshift(newNote);
            activeNoteId.value = newNote.id;
            isTitleUserModified.value = false;
            syncDraftFromActiveNote(newNote);

            nextTick(() => {
                contentTextareaRef.value?.focus();
            });

            toast.success('New note created.');
        } else {
            toast.error('Failed to create note.');
        }
    } catch (e) {
        toast.error('An error occurred while creating the note.');
    } finally {
        isCreatingNote.value = false;
    }
}

// Select note
function selectNote(note: NoteItem) {
    if (activeNoteId.value === note.id) return;
    activeNoteId.value = note.id;
}

// Copy note text to clipboard
async function copyNoteToClipboard() {
    if (!activeNote.value) return;
    const textToCopy = draftContent.value
        ? `${draftTitle.value}\n\n${draftContent.value}`
        : draftTitle.value;

    try {
        await navigator.clipboard.writeText(textToCopy);
        toast.success('Note copied to clipboard.');
    } catch (e) {
        toast.error('Unable to copy note to clipboard.');
    }
}

// Download note as Markdown / Text file
function downloadNote() {
    if (!activeNote.value) return;
    const filename = `${(draftTitle.value.trim() || 'note').replace(/[^a-z0-9-_]/gi, '_')}.md`;
    const content = `# ${draftTitle.value}\n\n${draftContent.value}`;
    const blob = new Blob([content], { type: 'text/markdown;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    toast.success(`Downloaded as ${filename}`);
}

// Open Delete Dialog
function openDeleteDialog(note?: NoteItem) {
    const target = note || activeNote.value;
    if (!target) return;
    noteToDelete.value = target;
    isDeleteDialogOpen.value = true;
}

// Confirm note deletion
async function confirmDeleteNote() {
    if (!noteToDelete.value) return;
    isDeleting.value = true;

    try {
        const res = await fetch(notesDestroy.url(noteToDelete.value.id), {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
        });

        if (res.ok) {
            const deletedId = noteToDelete.value.id;
            localNotes.value = localNotes.value.filter(
                (n) => n.id !== deletedId,
            );

            if (activeNoteId.value === deletedId) {
                activeNoteId.value =
                    localNotes.value.length > 0 ? localNotes.value[0].id : null;
            }

            toast.success('Note deleted successfully.');
            isDeleteDialogOpen.value = false;
            noteToDelete.value = null;
        } else {
            toast.error('Failed to delete note.');
        }
    } catch (e) {
        toast.error('An error occurred while deleting note.');
    } finally {
        isDeleting.value = false;
    }
}

// Tab key handling in content textarea (2 spaces indent)
function handleTabKey(e: KeyboardEvent) {
    const textarea = contentTextareaRef.value;
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const val = textarea.value;

    draftContent.value = val.substring(0, start) + '  ' + val.substring(end);
    onContentInput();

    nextTick(() => {
        textarea.selectionStart = textarea.selectionEnd = start + 2;
    });
}

// Drag and drop reordering
function onDragStart(e: DragEvent, index: number) {
    draggedNoteIndex.value = index;
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', index.toString());
    }
}

function onDragOver(e: DragEvent, index: number) {
    e.preventDefault();
    if (e.dataTransfer) {
        e.dataTransfer.dropEffect = 'move';
    }
    dragOverNoteIndex.value = index;
}

function onDragLeave(index: number) {
    if (dragOverNoteIndex.value === index) {
        dragOverNoteIndex.value = null;
    }
}

async function onDrop(e: DragEvent, dropIndex: number) {
    e.preventDefault();
    const sourceIndex = draggedNoteIndex.value;
    if (sourceIndex === null || sourceIndex === dropIndex) {
        draggedNoteIndex.value = null;
        dragOverNoteIndex.value = null;
        return;
    }

    // Reorder localNotes in place
    const itemToMove = localNotes.value.splice(sourceIndex, 1)[0];
    localNotes.value.splice(dropIndex, 0, itemToMove);

    draggedNoteIndex.value = null;
    dragOverNoteIndex.value = null;

    // Send reorder payload to backend
    await persistNotesOrder();
}

function onDragEnd() {
    draggedNoteIndex.value = null;
    dragOverNoteIndex.value = null;
}

async function persistNotesOrder() {
    isReordering.value = true;
    try {
        const orderedIds = localNotes.value.map((n) => n.id);
        await fetch(notesReorder.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ ordered_ids: orderedIds }),
        });
    } catch (e) {
        console.error('Failed to persist note order', e);
    } finally {
        isReordering.value = false;
    }
}

// Auto-save on Inertia navigation & beforeunload
let unregisterRouterHook: (() => void) | null = null;

onMounted(() => {
    // Intercept Inertia page visits to flush any pending save before leaving
    unregisterRouterHook = router.on('before', () => {
        if (saveStatus.value === 'unsaved' && activeNoteId.value) {
            flushSaveImmediate();
        }
    });

    // Intercept browser window/tab close or refresh
    window.addEventListener('beforeunload', flushSaveBeacon);
});

onBeforeUnmount(() => {
    if (unregisterRouterHook) {
        unregisterRouterHook();
    }
    window.removeEventListener('beforeunload', flushSaveBeacon);
    if (autoSaveTimer) {
        clearTimeout(autoSaveTimer);
    }
    if (saveStatus.value === 'unsaved' && activeNoteId.value) {
        flushSaveImmediate();
    }
});
</script>

<template>
    <div
        :class="[
            'bg-background text-foreground flex flex-col transition-all duration-200',
            isFullscreen
                ? 'fixed inset-0 z-50 m-0 h-screen w-screen overflow-hidden p-0'
                : 'h-[calc(100vh-4rem)] min-h-[600px] w-full overflow-hidden',
        ]"
    >
        <Head title="Open Notes - Developer Scratchpad" />
            <div
                class="border-border/60 flex min-h-0 w-full flex-1 overflow-hidden border-t"
            >
                <!-- ================= LEFT PANEL: Notes List ================= -->
                <aside
                    class="border-border/70 bg-card/40 flex w-full flex-shrink-0 flex-col border-r backdrop-blur-sm md:w-80 lg:w-96"
                >
                    <!-- Left Panel Header -->
                    <div
                        class="border-border/50 flex flex-col gap-3 border-b p-4"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 shadow-xs ring-1 ring-emerald-500/20 dark:text-emerald-400"
                                >
                                    <NotebookPen class="h-5 w-5" />
                                </div>
                                <div>
                                    <h2
                                        class="text-foreground text-base leading-tight font-semibold tracking-tight"
                                    >
                                        Open Notes
                                    </h2>
                                    <p
                                        class="text-muted-foreground mt-0.5 text-xs"
                                    >
                                        {{ localNotes.length }} personal
                                        {{
                                            localNotes.length === 1
                                                ? 'note'
                                                : 'notes'
                                        }}
                                        • Drag to reorder
                                    </p>
                                </div>
                            </div>

                            <!-- + New Note Button -->
                            <button
                                type="button"
                                :disabled="isCreatingNote"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-emerald-700 active:bg-emerald-800 disabled:opacity-50"
                                @click="createNewNote"
                            >
                                <Loader2
                                    v-if="isCreatingNote"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                <Plus v-else class="h-3.5 w-3.5 stroke-[2.5]" />
                                <span>New Note</span>
                            </button>
                        </div>

                        <!-- Search Box -->
                        <div class="relative">
                            <Search
                                class="text-muted-foreground/70 absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2"
                            />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search in notes..."
                                class="border-input/80 bg-background/80 placeholder:text-muted-foreground/60 w-full rounded-lg border py-1.5 pr-8 pl-9 text-xs transition-shadow focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none"
                            />
                            <button
                                v-if="searchQuery"
                                type="button"
                                class="text-muted-foreground/60 hover:text-foreground absolute top-1/2 right-2.5 -translate-y-1/2 p-0.5"
                                @click="searchQuery = ''"
                            >
                                <X class="h-3 w-3" />
                            </button>
                        </div>
                    </div>

                    <!-- Notes Scroll List -->
                    <div class="flex-1 space-y-2.5 overflow-y-auto p-3">
                        <div
                            v-if="filteredNotes.length === 0"
                            class="text-muted-foreground flex h-48 flex-col items-center justify-center px-4 text-center"
                        >
                            <FileText
                                class="text-muted-foreground/40 mb-2 h-8 w-8 stroke-1"
                            />
                            <p class="text-xs font-medium">No notes found</p>
                            <p
                                class="text-muted-foreground/70 mt-0.5 text-[11.5px]"
                            >
                                {{
                                    searchQuery
                                        ? 'Try adjusting your search query'
                                        : 'Click "+ New Note" to start writing'
                                }}
                            </p>
                        </div>

                        <!-- Draggable Note Card -->
                        <div
                            v-for="(note, index) in filteredNotes"
                            :key="note.id"
                            :draggable="!searchQuery"
                            :class="[
                                'group relative cursor-pointer rounded-xl border p-3.5 transition-all duration-150 select-none',
                                activeNoteId === note.id
                                    ? 'border-emerald-500/80 bg-emerald-50/40 shadow-xs ring-1 ring-emerald-500/30 dark:border-emerald-500/60 dark:bg-emerald-950/20'
                                    : 'border-border/60 bg-card hover:bg-muted/40 hover:border-border',
                                draggedNoteIndex === index
                                    ? 'scale-[0.98] opacity-40'
                                    : '',
                                dragOverNoteIndex === index &&
                                draggedNoteIndex !== index
                                    ? 'border-t-2 border-t-emerald-500 dark:border-t-emerald-400'
                                    : '',
                            ]"
                            @click="selectNote(note)"
                            @dragstart="onDragStart($event, index)"
                            @dragover="onDragOver($event, index)"
                            @dragleave="onDragLeave(index)"
                            @drop="onDrop($event, index)"
                            @dragend="onDragEnd"
                        >
                            <!-- Card Header: Title, Pin, Color Dot, Drag Handle -->
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div
                                    class="flex min-w-0 flex-1 items-center gap-1.5"
                                >
                                    <div
                                        v-if="!searchQuery"
                                        class="text-muted-foreground -ml-1 flex-shrink-0 cursor-grab opacity-0 transition-opacity group-hover:opacity-40 hover:opacity-100! active:cursor-grabbing"
                                        title="Drag to reorder"
                                    >
                                        <GripVertical class="h-3.5 w-3.5" />
                                    </div>
                                    <h3
                                        :class="[
                                            'truncate text-sm font-semibold',
                                            activeNoteId === note.id
                                                ? 'text-foreground font-bold'
                                                : 'text-foreground/90',
                                        ]"
                                    >
                                        {{ note.title || 'Untitled Note' }}
                                    </h3>
                                </div>

                                <div
                                    class="flex flex-shrink-0 items-center gap-1.5"
                                >
                                    <Pin
                                        v-if="note.is_pinned"
                                        class="h-3 w-3 fill-amber-500/30 text-amber-500"
                                    />
                                    <span
                                        :class="[
                                            'h-2 w-2 rounded-full',
                                            getColorDotClass(note.color),
                                        ]"
                                    />
                                </div>
                            </div>

                            <!-- Snippet preview -->
                            <p
                                class="text-muted-foreground mt-1.5 line-clamp-2 text-xs leading-relaxed break-words"
                            >
                                {{ note.content || 'No additional text' }}
                            </p>

                            <!-- Card Footer: Formatted Date -->
                            <div
                                class="text-muted-foreground/70 mt-2.5 flex items-center justify-between text-[11.5px]"
                            >
                                <span>{{
                                    formatNoteDate(
                                        note.updated_at || note.created_at,
                                    )
                                }}</span>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- ================= RIGHT PANEL: Note Editor ================= -->
                <main
                    class="bg-background flex min-w-0 flex-1 flex-col overflow-hidden"
                >
                    <template v-if="activeNote">
                        <!-- Editor Top Toolbar -->
                        <div
                            class="border-border/60 bg-card/20 flex h-14 flex-shrink-0 items-center justify-between gap-4 border-b px-6 backdrop-blur-xs"
                        >
                            <!-- Note Title Input -->
                            <div class="min-w-0 flex-1">
                                <input
                                    ref="titleInputRef"
                                    v-model="draftTitle"
                                    type="text"
                                    placeholder="Note title..."
                                    class="text-foreground placeholder:text-muted-foreground/50 w-full truncate border-0 bg-transparent p-0 text-base font-semibold focus:ring-0 focus:outline-none sm:text-lg"
                                    @input="onTitleManualInput"
                                    @blur="onTitleBlur"
                                />
                            </div>

                            <!-- Toolbar Right: Palette, Actions, Save status, Fullscreen -->
                            <div
                                class="flex flex-shrink-0 items-center gap-3 sm:gap-4"
                            >
                                <!-- Color Palette Picker -->
                                <div class="flex items-center gap-1.5 sm:gap-2">
                                    <button
                                        v-for="c in noteColors"
                                        :key="c.key"
                                        type="button"
                                        :title="c.label"
                                        :class="[
                                            'h-5 w-5 cursor-pointer rounded-full transition-all duration-150',
                                            c.bgClass,
                                            draftColor === c.key
                                                ? 'scale-110 ring-2 ring-slate-400 ring-offset-2 dark:ring-offset-neutral-950'
                                                : 'opacity-80 hover:scale-105 hover:opacity-100',
                                        ]"
                                        @click="selectColor(c.key)"
                                    />
                                </div>

                                <div
                                    class="bg-border/80 hidden h-4 w-[1px] sm:block"
                                />

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-1">
                                    <!-- Pin Toggle -->
                                    <button
                                        type="button"
                                        :title="
                                            draftIsPinned
                                                ? 'Unpin note'
                                                : 'Pin note to top'
                                        "
                                        :class="[
                                            'cursor-pointer rounded-md p-1.5 transition-colors',
                                            draftIsPinned
                                                ? 'bg-amber-500/10 text-amber-500'
                                                : 'text-muted-foreground hover:text-foreground hover:bg-muted',
                                        ]"
                                        @click="togglePin"
                                    >
                                        <PinOff
                                            v-if="draftIsPinned"
                                            class="h-4 w-4"
                                        />
                                        <Pin v-else class="h-4 w-4" />
                                    </button>

                                    <!-- Copy to Clipboard -->
                                    <button
                                        type="button"
                                        title="Copy note content"
                                        class="text-muted-foreground hover:text-foreground hover:bg-muted cursor-pointer rounded-md p-1.5 transition-colors"
                                        @click="copyNoteToClipboard"
                                    >
                                        <Copy class="h-4 w-4" />
                                    </button>

                                    <!-- Download Markdown -->
                                    <button
                                        type="button"
                                        title="Download as Markdown (.md)"
                                        class="text-muted-foreground hover:text-foreground hover:bg-muted cursor-pointer rounded-md p-1.5 transition-colors"
                                        @click="downloadNote"
                                    >
                                        <Download class="h-4 w-4" />
                                    </button>

                                    <!-- Delete Note -->
                                    <button
                                        type="button"
                                        title="Delete note"
                                        class="text-muted-foreground hover:text-destructive hover:bg-destructive/10 cursor-pointer rounded-md p-1.5 transition-colors"
                                        @click="openDeleteDialog()"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>

                                <!-- Save Status Badge -->
                                <div class="flex items-center">
                                    <span
                                        v-if="saveStatus === 'saved'"
                                        class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 shadow-2xs dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300"
                                    >
                                        <Check class="h-3 w-3 stroke-[2.5]" />
                                        <span>Saved</span>
                                    </span>
                                    <span
                                        v-else-if="saveStatus === 'saving'"
                                        class="bg-muted text-muted-foreground border-border/60 inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium"
                                    >
                                        <Loader2
                                            class="h-3 w-3 animate-spin text-emerald-500"
                                        />
                                        <span>Saving...</span>
                                    </span>
                                    <span
                                        v-else-if="saveStatus === 'unsaved'"
                                        class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-300"
                                    >
                                        <span>Unsaved</span>
                                    </span>
                                    <span
                                        v-else-if="saveStatus === 'error'"
                                        class="inline-flex cursor-pointer items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700 dark:border-rose-800/60 dark:bg-rose-950/40 dark:text-rose-300"
                                        @click="flushSave"
                                    >
                                        <span>Retry Save</span>
                                    </span>
                                </div>

                                <!-- Fullscreen Toggle -->
                                <button
                                    type="button"
                                    :title="
                                        isFullscreen
                                            ? 'Exit full screen'
                                            : 'Expand full screen'
                                    "
                                    class="text-muted-foreground hover:text-foreground hover:bg-muted cursor-pointer rounded-md p-1.5 transition-colors"
                                    @click="isFullscreen = !isFullscreen"
                                >
                                    <Minimize2
                                        v-if="isFullscreen"
                                        class="h-4 w-4"
                                    />
                                    <Maximize2 v-else class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Main Note Writing Canvas -->
                        <div
                            class="flex flex-1 flex-col overflow-y-auto p-6 sm:p-8"
                        >
                            <textarea
                                ref="contentTextareaRef"
                                v-model="draftContent"
                                placeholder="Write your thoughts, code snippets, checklists, or scratchpad notes here..."
                                class="placeholder:text-muted-foreground/40 text-foreground w-full flex-1 resize-none border-0 bg-transparent p-0 font-sans text-sm leading-relaxed focus:ring-0 focus:outline-none sm:text-base"
                                @input="onContentInput"
                                @blur="flushSave"
                                @keydown.tab.prevent="handleTabKey"
                            />
                        </div>
                    </template>

                    <!-- Empty State: When no note is selected -->
                    <div
                        v-else
                        class="flex flex-1 flex-col items-center justify-center p-8 text-center"
                    >
                        <div
                            class="bg-muted/60 text-muted-foreground mb-4 flex h-16 w-16 items-center justify-center rounded-2xl"
                        >
                            <NotebookPen class="h-8 w-8 stroke-1" />
                        </div>
                        <h3 class="text-foreground text-base font-semibold">
                            No note selected
                        </h3>
                        <p class="text-muted-foreground mt-1 max-w-sm text-xs">
                            Select a note from the left sidebar or create a new
                            developer note to start jotting down thoughts,
                            plans, and code snippets.
                        </p>
                        <Button
                            class="mt-4 gap-1.5 bg-emerald-600 text-white hover:bg-emerald-700"
                            @click="createNewNote"
                        >
                            <Plus class="h-4 w-4" />
                            <span>Create Note</span>
                        </Button>
                    </div>
                </main>
            </div>

        <!-- Confirm Delete Dialog -->
        <Dialog
            :open="isDeleteDialogOpen"
            @update:open="isDeleteDialogOpen = $event"
        >
            <DialogContent class="sm:max-w-[420px]">
                <DialogHeader>
                    <div
                        class="bg-destructive/10 text-destructive mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full"
                    >
                        <Trash2 class="h-6 w-6" />
                    </div>
                    <DialogTitle class="text-center">Delete Note</DialogTitle>
                    <DialogDescription class="text-center">
                        Are you sure you want to delete
                        <span class="text-foreground font-semibold"
                            >"{{ noteToDelete?.title || 'this note' }}"</span
                        >? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter
                    class="flex flex-col-reverse gap-2 pt-3 sm:flex-row sm:gap-0"
                >
                    <Button
                        type="button"
                        variant="outline"
                        class="w-full sm:w-auto"
                        :disabled="isDeleting"
                        @click="isDeleteDialogOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        class="w-full sm:w-auto"
                        :disabled="isDeleting"
                        @click="confirmDeleteNote"
                    >
                        <Loader2
                            v-if="isDeleting"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        Delete Note
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
