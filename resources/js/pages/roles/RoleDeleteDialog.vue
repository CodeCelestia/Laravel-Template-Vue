<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { AlertCircle, AlertTriangle, Loader2, ShieldAlert } from '@lucide/vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { destroy as rolesDestroy } from '@/actions/App/Http/Controllers/RoleController';
import type { RoleItem } from './RoleTableRowActions.vue';

const props = defineProps<{
    open: boolean;
    role: RoleItem | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const isProcessing = ref(false);

const isSystemAdmin = computed(() => props.role?.name === 'System Admin');
const hasUsers = computed(() => (props.role?.users_count ?? 0) > 0);
const isBlocked = computed(() => isSystemAdmin.value || hasUsers.value);

function closeModal() {
    emit('update:open', false);
}

function confirmDelete() {
    if (!props.role || isBlocked.value) {
        return;
    }

    isProcessing.value = true;
    router.delete(rolesDestroy.url(props.role.id), {
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
                    <div
                        :class="[
                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                            isBlocked ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400' : 'bg-destructive/10 text-destructive',
                        ]"
                    >
                        <ShieldAlert v-if="isBlocked" class="h-5 w-5" />
                        <AlertTriangle v-else class="h-5 w-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-base font-semibold text-foreground">
                            {{ isBlocked ? 'Cannot Delete Role' : 'Delete Role' }}
                        </DialogTitle>
                        <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                            Role: <span class="font-medium text-foreground">{{ role?.name }}</span>
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="py-2">
                <!-- System Admin Guard -->
                <div
                    v-if="isSystemAdmin"
                    class="rounded-lg bg-amber-500/10 border border-amber-500/20 p-3 text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2.5"
                >
                    <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" />
                    <div>
                        <strong>Core Role Protection:</strong> The <strong>System Admin</strong> role is required for platform maintenance and root governance. It cannot be deleted.
                    </div>
                </div>

                <!-- Assigned Users Guard -->
                <div
                    v-else-if="hasUsers"
                    class="rounded-lg bg-amber-500/10 border border-amber-500/20 p-3 text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2.5"
                >
                    <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" />
                    <div>
                        <strong>Active Users Assigned:</strong> There {{ role?.users_count === 1 ? 'is' : 'are' }} currently
                        <strong>{{ role?.users_count }} user{{ role?.users_count === 1 ? '' : 's' }}</strong> assigned to this role. You must reassign those users to a different role before deleting.
                    </div>
                </div>

                <!-- Confirmation Message -->
                <p v-else class="text-xs text-muted-foreground leading-relaxed">
                    Are you sure you want to permanently delete the role
                    <strong class="text-foreground">{{ role?.name }}</strong>? This will revoke all its permission assignments. This action cannot be undone.
                </p>
            </div>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    variant="outline"
                    size="sm"
                    class="text-xs"
                    @click="closeModal"
                >
                    {{ isBlocked ? 'Understood' : 'Cancel' }}
                </Button>

                <Button
                    v-if="!isBlocked"
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
                    Delete Role
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
