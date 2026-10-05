<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import {
    CheckCircle2,
    KeyRound,
    Loader2,
    ShieldCheck,
    User as UserIcon,
} from '@lucide/vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { show as usersShow } from '@/actions/App/Http/Controllers/UserController';
import type { UserItem } from './UserFormDialog.vue';

interface PermissionItem {
    id: number;
    name: string;
}

const props = defineProps<{
    open: boolean;
    user: UserItem | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'edit', user: UserItem): void;
}>();

const isLoadingPermissions = ref(false);
const userPermissions = ref<PermissionItem[]>([]);

watch(
    () => [props.open, props.user?.id],
    async ([isOpen, userId]) => {
        if (isOpen && userId) {
            isLoadingPermissions.value = true;
            try {
                const response = await fetch(usersShow.url(Number(userId)), {
                    headers: {
                        Accept: 'application/json',
                    },
                });
                if (response.ok) {
                    const data = await response.json();
                    userPermissions.value = data.permissions || [];
                }
            } catch (e) {
                console.error('Failed to load user permissions', e);
            } finally {
                isLoadingPermissions.value = false;
            }
        } else {
            userPermissions.value = [];
        }
    },
    { immediate: true },
);

function getRoleBadgeClass(roleName: string) {
    switch (roleName) {
        case 'System Admin':
            return 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20';
        case 'User':
            return 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20';
        default:
            return 'bg-muted text-muted-foreground border-border';
    }
}

const groupedPermissions = computed(() => {
    const groups: Record<
        string,
        { title: string; items: Array<{ id: number; name: string }> }
    > = {};

    for (const perm of userPermissions.value) {
        const prefix = perm.name.split('.')[0] || 'other';
        if (!groups[prefix]) {
            let title = prefix.toUpperCase();
            if (prefix === 'users') title = 'User Management';
            else if (prefix === 'roles') title = 'Role Security';
            else if (prefix === 'permissions') title = 'Permission Catalog';
            else if (prefix === 'municipalities') title = 'Municipalities & Cities';
            else if (prefix === 'notes') title = 'Developer Open Notes';
            groups[prefix] = { title, items: [] };
        }
        groups[prefix].items.push(perm);
    }

    return groups;
});

function formatDate(dateStr?: string | null) {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-[580px]">
            <DialogHeader>
                <DialogTitle class="flex items-center space-x-2">
                    <UserIcon class="text-primary h-5 w-5" />
                    <span>User Details</span>
                </DialogTitle>
                <DialogDescription>
                    Account overview, role designation, and granular permissions
                    granted.
                </DialogDescription>
            </DialogHeader>

            <div v-if="user" class="space-y-6 py-2">
                <!-- User Profile Card -->
                <div
                    class="bg-muted/30 flex items-start space-x-4 rounded-xl border p-4"
                >
                    <div
                        class="bg-primary/10 text-primary flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-lg font-semibold"
                    >
                        {{ user.name.slice(0, 2).toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <h3
                                class="text-foreground truncate text-base font-semibold"
                            >
                                {{ user.name }}
                            </h3>
                            <CheckCircle2
                                v-if="user.email_verified_at"
                                class="h-4 w-4 shrink-0 text-emerald-500"
                                title="Verified Email"
                            />
                        </div>
                        <p class="text-muted-foreground truncate text-sm">
                            {{ user.email }}
                        </p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="text-muted-foreground text-xs"
                                >Role:</span
                            >
                            <Badge
                                v-for="role in user.roles"
                                :key="role.id"
                                variant="outline"
                                :class="getRoleBadgeClass(role.name)"
                            >
                                <ShieldCheck class="mr-1 h-3 w-3" />
                                {{ role.name }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <!-- Timestamps -->
                <div
                    class="text-muted-foreground grid grid-cols-2 gap-3 text-xs"
                >
                    <div class="bg-card rounded-lg border p-2.5">
                        <span class="text-foreground block font-medium"
                            >Registered:</span
                        >
                        {{ formatDate(user.created_at) }}
                    </div>
                    <div class="bg-card rounded-lg border p-2.5">
                        <span class="text-foreground block font-medium"
                            >Email Status:</span
                        >
                        {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                    </div>
                </div>

                <!-- Granted Permissions Section -->
                <div class="space-y-3">
                    <div
                        class="flex items-center justify-between border-b pb-2"
                    >
                        <div
                            class="text-foreground flex items-center space-x-2 text-sm font-semibold"
                        >
                            <KeyRound class="text-primary h-4 w-4" />
                            <span
                                >Granted Permissions ({{
                                    userPermissions.length
                                }})</span
                            >
                        </div>
                        <span
                            v-if="
                                user.roles.some(
                                    (r) => r.name === 'System Admin',
                                )
                            "
                            class="text-xs font-medium text-rose-600 dark:text-rose-400"
                        >
                            ★ Full Access Bypass
                        </span>
                    </div>

                    <div
                        v-if="isLoadingPermissions"
                        class="text-muted-foreground flex items-center justify-center py-6 text-sm"
                    >
                        <Loader2
                            class="text-primary mr-2 h-4 w-4 animate-spin"
                        />
                        Loading permissions...
                    </div>

                    <div
                        v-else-if="userPermissions.length === 0"
                        class="text-muted-foreground py-4 text-center text-xs"
                    >
                        No explicit permissions assigned.
                    </div>

                    <div
                        v-else
                        class="max-h-[220px] space-y-3 overflow-y-auto pr-1 text-xs"
                    >
                        <div
                            v-for="(group, key) in groupedPermissions"
                            :key="key"
                            class="space-y-1.5"
                        >
                            <p class="text-muted-foreground font-medium">
                                {{ group.title }}
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="p in group.items"
                                    :key="p.id"
                                    class="rounded-md bg-primary/10 px-2 py-0.5 font-mono text-[11.5px] text-primary"
                                >
                                    {{ p.name }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter class="pt-2">
                <Button
                    v-if="user?.can?.update"
                    type="button"
                    variant="default"
                    @click="emit('edit', user!)"
                >
                    Edit User
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    @click="emit('update:open', false)"
                >
                    Close
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
