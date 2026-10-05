<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { AlertTriangle, Loader2 } from '@lucide/vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { destroy as permissionsDestroy } from '@/actions/App/Http/Controllers/PermissionController';
import type { PermissionItem } from './PermissionTableRowActions.vue';

const props = defineProps<{
    open: boolean;
    permission: PermissionItem | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const isProcessing = ref(false);

const assignedRolesCount = computed(() => props.permission?.roles_count ?? 0);

function closeModal() {
    emit('update:open', false);
}

function confirmDelete() {
    if (!props.permission) {
        return;
    }

    isProcessing.value = true;
    router.delete(permissionsDestroy.url(props.permission.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            emit('success');
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-[460px]">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-destructive/10 text-destructive">
                        <AlertTriangle class="h-5 w-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-base font-semibold text-foreground">
                            Delete Permission
                        </DialogTitle>
                        <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                            Permission: <code class="font-mono text-foreground font-semibold">{{ permission?.name }}</code>
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="py-2 space-y-3">
                <div
                    v-if="assignedRolesCount > 0"
                    class="rounded-lg bg-amber-500/10 border border-amber-500/20 p-3 text-xs text-amber-800 dark:text-amber-300"
                >
                    <strong>Assigned to {{ assignedRolesCount }} {{ assignedRolesCount === 1 ? 'role' : 'roles' }}:</strong>
                    Deleting this permission will immediately revoke this ability from all associated roles.
                </div>

                <p class="text-xs text-muted-foreground leading-relaxed">
                    Are you sure you want to permanently delete the permission
                    <strong class="text-foreground font-mono">{{ permission?.name }}</strong>? This action cannot be undone.
                </p>
            </div>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    variant="outline"
                    size="sm"
                    class="text-xs"
                    @click="closeModal"
                >
                    Cancel
                </Button>

                <Button
                    variant="destructive"
                    size="sm"
                    class="text-xs min-w-[90px]"
                    :disabled="isProcessing"
                    @click="confirmDelete"
                >
                    <Loader2
                        v-if="isProcessing"
                        class="mr-1.5 h-3.5 w-3.5 animate-spin"
                    />
                    Delete Permission
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
