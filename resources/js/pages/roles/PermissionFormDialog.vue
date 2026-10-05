<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { KeyRound, Loader2, Shield } from '@lucide/vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import {
    store as permissionsStore,
    update as permissionsUpdate,
} from '@/actions/App/Http/Controllers/PermissionController';
import type { PermissionItem } from './PermissionTableRowActions.vue';

interface RoleOption {
    id: number;
    name: string;
}

const props = defineProps<{
    open: boolean;
    permission: PermissionItem | null;
    allRoles: RoleOption[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const isEditing = computed(() => !!props.permission);

const form = useForm({
    name: '',
    roles: [] as string[],
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            form.clearErrors();
            if (props.permission) {
                form.name = props.permission.name;
                form.roles = props.permission.roles.map((r) => r.name);
            } else {
                form.reset();
                form.roles = ['System Admin'];
            }
        }
    },
    { immediate: true },
);

function toggleRole(roleName: string) {
    if (roleName === 'System Admin') return; // Always assigned
    const idx = form.roles.indexOf(roleName);
    if (idx > -1) {
        form.roles.splice(idx, 1);
    } else {
        form.roles.push(roleName);
    }
}

function closeModal() {
    emit('update:open', false);
}

function submit() {
    if (isEditing.value && props.permission) {
        form.put(permissionsUpdate.url(props.permission.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                emit('success');
            },
        });
    } else {
        form.post(permissionsStore.url(), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                emit('success');
            },
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg flex flex-col p-0 overflow-hidden">
            <DialogHeader class="p-6 pb-4 border-b">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <KeyRound class="h-5 w-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-xl font-bold text-foreground">
                            {{ isEditing ? 'Edit Permission' : 'Create New Permission' }}
                        </DialogTitle>
                        <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                            Grant system capabilities using the standard module.action pattern.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <form @submit.prevent="submit" class="flex flex-col">
                <div class="p-6 space-y-5">
                    <!-- Permission Name -->
                    <div class="space-y-1.5">
                        <Label for="permission-name" class="text-xs font-semibold text-foreground">
                            Permission Name <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="permission-name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. users.export-csv, notes.archive"
                            class="h-10 font-mono text-xs"
                            required
                        />
                        <p class="text-[11.5px] text-muted-foreground">
                            Use lowercase with dots (e.g. <code>module.action</code> or <code>module.sub-action</code>).
                        </p>
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Assign To Roles Section -->
                    <div class="space-y-2.5">
                        <Label class="text-xs font-semibold text-foreground flex items-center gap-1.5">
                            <Shield class="h-3.5 w-3.5 text-primary" />
                            Assign Immediately to Roles (Optional)
                        </Label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto p-1">
                            <label
                                v-for="role in allRoles"
                                :key="role.id"
                                :class="[
                                    'flex items-center gap-2 p-2 rounded-lg border text-xs cursor-pointer select-none transition-colors',
                                    form.roles.includes(role.name)
                                        ? 'bg-primary/5 border-primary/30 text-foreground'
                                        : 'hover:bg-muted/40 text-muted-foreground border-border',
                                ]"
                            >
                                <Checkbox
                                    :checked="form.roles.includes(role.name)"
                                    :disabled="role.name === 'System Admin'"
                                    @update:checked="toggleRole(role.name)"
                                />
                                <span class="truncate font-medium text-foreground">
                                    {{ role.name }}
                                </span>
                                <span v-if="role.name === 'System Admin'" class="text-[10.5px] text-muted-foreground ml-auto">
                                    (Default)
                                </span>
                            </label>
                        </div>
                        <InputError :message="form.errors.roles" />
                    </div>
                </div>

                <DialogFooter class="p-4 border-t bg-muted/10 flex sm:justify-between items-center">
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        class="text-xs"
                        @click="closeModal"
                    >
                        Cancel
                    </Button>

                    <Button
                        type="submit"
                        size="sm"
                        class="text-xs min-w-[110px]"
                        :disabled="form.processing"
                    >
                        <Loader2 v-if="form.processing" class="mr-1.5 h-3.5 w-3.5 animate-spin" />
                        {{ isEditing ? 'Save Changes' : 'Create Permission' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
