<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Loader2 } from '@lucide/vue';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import {
    store as usersStore,
    update as usersUpdate,
} from '@/actions/App/Http/Controllers/UserController';

export interface UserRole {
    id: number;
    name: string;
}

export interface UserItem {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string | null;
    roles: UserRole[];
    can?: {
        update?: boolean;
        delete?: boolean;
    };
}

const props = defineProps<{
    open: boolean;
    user: UserItem | null;
    roles: UserRole[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const isEditing = computed(() => !!props.user);

const form = useForm({
    name: '',
    email: '',
    role: '',
    password: '',
    password_confirmation: '',
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            form.clearErrors();
            if (props.user) {
                form.name = props.user.name;
                form.email = props.user.email;
                form.role =
                    props.user.roles[0]?.name ?? (props.roles[0]?.name || '');
                form.password = '';
                form.password_confirmation = '';
            } else {
                form.reset();
                form.role =
                    props.roles.find((r) => r.name === 'User')
                        ?.name ??
                    (props.roles[0]?.name || '');
            }
        }
    },
    { immediate: true },
);

function closeModal() {
    emit('update:open', false);
}

function submit() {
    if (isEditing.value && props.user) {
        form.put(usersUpdate.url(props.user.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                emit('success');
            },
        });
    } else {
        form.post(usersStore.url(), {
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
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>
                    {{ isEditing ? 'Edit User Account' : 'Create New User' }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        isEditing
                            ? 'Update user credentials and assign their role.'
                            : 'Fill in the information below to register a new user in the system.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4 py-2" @submit.prevent="submit">
                <!-- Name -->
                <div class="space-y-1.5">
                    <Label for="user-name">Full Name</Label>
                    <Input
                        id="user-name"
                        v-model="form.name"
                        type="text"
                        placeholder="e.g. Juan Dela Cruz"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <!-- Email -->
                <div class="space-y-1.5">
                    <Label for="user-email">Email Address</Label>
                    <Input
                        id="user-email"
                        v-model="form.email"
                        type="email"
                        placeholder="e.g. name@example.com"
                        required
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <!-- Role Selection -->
                <div class="space-y-1.5">
                    <Label for="user-role">Assigned Role</Label>
                    <Select v-model="form.role">
                        <SelectTrigger id="user-role" class="w-full">
                            <SelectValue placeholder="Select a role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="roleItem in roles"
                                :key="roleItem.id"
                                :value="roleItem.name"
                            >
                                {{ roleItem.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.role" />
                </div>

                <!-- Password -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <Label for="user-password">Password</Label>
                        <span
                            v-if="isEditing"
                            class="text-muted-foreground text-xs"
                        >
                            (Leave blank to keep current password)
                        </span>
                    </div>
                    <Input
                        id="user-password"
                        v-model="form.password"
                        type="password"
                        placeholder="••••••••"
                        :required="!isEditing"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <!-- Password Confirmation -->
                <div class="space-y-1.5">
                    <Label for="user-password-confirmation"
                        >Confirm Password</Label
                    >
                    <Input
                        id="user-password-confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        placeholder="••••••••"
                        :required="!isEditing && !!form.password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <DialogFooter class="pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="closeModal"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2
                            v-if="form.processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        {{ isEditing ? 'Save Changes' : 'Create User' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
