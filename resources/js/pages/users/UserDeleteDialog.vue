<script setup lang="ts">
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
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
import { destroy as usersDestroy } from '@/actions/App/Http/Controllers/UserController';
import type { UserItem } from './UserFormDialog.vue';

const props = defineProps<{
    open: boolean;
    user: UserItem | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const page = usePage();
const currentUserId = (page.props.auth as any)?.user?.id;
const isProcessing = ref(false);

const isSelf = () => {
    return props.user && currentUserId && props.user.id === currentUserId;
};

function closeModal() {
    emit('update:open', false);
}

function confirmDelete() {
    if (!props.user || isSelf()) {
        return;
    }

    isProcessing.value = true;
    router.delete(usersDestroy.url(props.user.id), {
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
                <div
                    class="bg-destructive/10 text-destructive mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full"
                >
                    <AlertTriangle class="h-6 w-6" />
                </div>
                <DialogTitle class="text-center">
                    {{
                        isSelf()
                            ? 'Cannot Delete Your Own Account'
                            : 'Confirm User Deletion'
                    }}
                </DialogTitle>
                <DialogDescription class="text-center">
                    <template v-if="isSelf()">
                        For security reasons, you cannot delete the account you
                        are currently logged in with.
                    </template>
                    <template v-else-if="user">
                        Are you sure you want to delete
                        <span class="text-foreground font-semibold">{{
                            user.name
                        }}</span>
                        ({{ user.email }})?
                        <p class="text-muted-foreground mt-2 text-xs">
                            This action will permanently delete their account
                            and revoke active sessions.
                        </p>
                    </template>
                </DialogDescription>
            </DialogHeader>

            <DialogFooter
                class="flex flex-col-reverse gap-2 pt-3 sm:flex-row sm:gap-0"
            >
                <Button
                    type="button"
                    variant="outline"
                    class="w-full sm:w-auto"
                    :disabled="isProcessing"
                    @click="closeModal"
                >
                    Cancel
                </Button>
                <Button
                    v-if="!isSelf()"
                    type="button"
                    variant="destructive"
                    class="w-full sm:w-auto"
                    :disabled="isProcessing"
                    @click="confirmDelete"
                >
                    <Loader2
                        v-if="isProcessing"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    Delete User
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
