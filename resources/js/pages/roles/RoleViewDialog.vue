<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import {
    Calendar,
    CheckCircle2,
    Edit,
    KeyRound,
    Loader2,
    Shield,
    Users,
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
import { show as rolesShow } from '@/actions/App/Http/Controllers/RoleController';
import type { RoleItem } from './RoleTableRowActions.vue';

const props = defineProps<{
    open: boolean;
    role: RoleItem | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'edit', role: RoleItem): void;
}>();

interface DetailedUser {
    id: number;
    name: string;
    email: string;
}

interface DetailedRole {
    id: number;
    name: string;
    created_at: string | null;
    users_count: number;
    users: DetailedUser[];
    permissions: Array<{ id: number; name: string }>;
    can?: {
        update?: boolean;
        delete?: boolean;
    };
}

const isLoading = ref(false);
const roleDetails = ref<DetailedRole | null>(null);

watch(
    () => [props.open, props.role?.id],
    async ([isOpen]) => {
        if (isOpen && props.role) {
            isLoading.value = true;
            try {
                const response = await fetch(rolesShow.url(props.role.id), {
                    headers: {
                        Accept: 'application/json',
                    },
                });
                if (response.ok) {
                    const data = await response.json();
                    roleDetails.value = data;
                }
            } catch (e) {
                console.error('Failed to load role details', e);
            } finally {
                isLoading.value = false;
            }
        } else {
            roleDetails.value = null;
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
            return 'bg-primary/10 text-primary border-primary/20';
    }
}

// Group permissions by prefix/module
const groupedPermissions = computed(() => {
    const permissions =
        roleDetails.value?.permissions || props.role?.permissions || [];
    const groups: Record<string, Array<{ id: number; name: string }>> = {};

    for (const perm of permissions) {
        const parts = perm.name.split('.');
        const moduleKey = parts[0] || 'other';
        if (!groups[moduleKey]) {
            groups[moduleKey] = [];
        }
        groups[moduleKey].push(perm);
    }

    return groups;
});

function getModuleTitle(key: string): string {
    switch (key) {
        case 'users':
            return 'User Management';
        case 'roles':
            return 'Role Security';
        case 'permissions':
            return 'Permission Catalog';
        case 'records':
            return 'Digital Land Records Vault';
        case 'assessment':
            return 'Appraisal & Valuation';
        case 'tdms':
            return 'Tax Declaration (TDMS)';
        case 'notes':
            return 'Developer Open Notes';
        default:
            return key.toUpperCase();
    }
}

function formatDate(dateStr?: string | null) {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="flex max-h-[85vh] flex-col overflow-hidden p-0 sm:max-w-2xl"
        >
            <DialogHeader class="border-b p-6 pb-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="bg-primary/10 text-primary flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                        >
                            <Shield class="h-5 w-5" />
                        </div>
                        <div>
                            <DialogTitle
                                class="text-foreground flex items-center gap-2 text-xl font-bold"
                            >
                                {{ role?.name }}
                                <Badge
                                    v-if="role"
                                    variant="outline"
                                    :class="`text-xs font-semibold ${getRoleBadgeClass(role.name)}`"
                                >
                                    {{
                                        role.name === 'System Admin'
                                            ? 'Core System Role'
                                            : 'Custom LGU Role'
                                    }}
                                </Badge>
                            </DialogTitle>
                            <DialogDescription
                                class="text-muted-foreground mt-0.5 text-xs"
                            >
                                Detailed access capabilities and assigned staff
                                members.
                            </DialogDescription>
                        </div>
                    </div>
                </div>
            </DialogHeader>

            <!-- Dialog Body -->
            <div class="flex-1 space-y-6 overflow-y-auto p-6">
                <!-- Meta Info Cards -->
                <div class="grid grid-cols-2 gap-3">
                    <div
                        class="bg-muted/40 flex items-center gap-3 rounded-lg border p-3"
                    >
                        <Users class="text-muted-foreground h-5 w-5" />
                        <div>
                            <span class="text-muted-foreground block text-xs"
                                >Assigned Staff</span
                            >
                            <span class="text-foreground text-sm font-semibold">
                                {{
                                    roleDetails?.users_count ??
                                    role?.users_count ??
                                    0
                                }}
                                {{
                                    (roleDetails?.users_count ??
                                        role?.users_count ??
                                        0) === 1
                                        ? 'User'
                                        : 'Users'
                                }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-muted/40 flex items-center gap-3 rounded-lg border p-3"
                    >
                        <Calendar class="text-muted-foreground h-5 w-5" />
                        <div>
                            <span class="text-muted-foreground block text-xs"
                                >Created Date</span
                            >
                            <span class="text-foreground text-sm font-semibold">
                                {{ formatDate(role?.created_at) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Assigned Users preview -->
                <div
                    v-if="roleDetails?.users && roleDetails.users.length > 0"
                    class="space-y-2"
                >
                    <div
                        class="text-muted-foreground text-xs font-semibold tracking-wider uppercase"
                    >
                        Assigned Users ({{ roleDetails.users.length }})
                    </div>
                    <div
                        class="bg-muted/20 flex max-h-28 flex-wrap gap-1.5 overflow-y-auto rounded-lg border p-2"
                    >
                        <div
                            v-for="u in roleDetails.users"
                            :key="u.id"
                            class="bg-background text-foreground inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-xs font-medium"
                        >
                            <div
                                class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                            ></div>
                            {{ u.name }}
                            <span class="text-muted-foreground text-[10.5px]"
                                >({{ u.email }})</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Loading State for permissions -->
                <div
                    v-if="isLoading"
                    class="text-muted-foreground flex flex-col items-center justify-center py-8"
                >
                    <Loader2 class="mb-2 h-6 w-6 animate-spin" />
                    <span class="text-xs">Loading role permissions...</span>
                </div>

                <!-- Permissions Matrix by Module -->
                <div v-else class="space-y-4">
                    <div
                        class="flex items-center justify-between border-b pb-2"
                    >
                        <span
                            class="text-muted-foreground text-xs font-semibold tracking-wider uppercase"
                        >
                            Granted Permissions ({{
                                (
                                    roleDetails?.permissions ||
                                    role?.permissions ||
                                    []
                                ).length
                            }})
                        </span>
                    </div>

                    <div
                        v-if="Object.keys(groupedPermissions).length === 0"
                        class="text-muted-foreground bg-muted/10 rounded-lg border py-6 text-center text-sm"
                    >
                        No permissions assigned to this role yet.
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="(perms, mod) in groupedPermissions"
                            :key="mod"
                            class="bg-card space-y-2 rounded-lg border p-3 shadow-xs"
                        >
                            <div
                                class="text-foreground flex items-center justify-between text-xs font-semibold"
                            >
                                <span class="flex items-center gap-1.5">
                                    <KeyRound
                                        class="text-primary h-3.5 w-3.5"
                                    />
                                    {{ getModuleTitle(String(mod)) }}
                                </span>
                                <Badge
                                    variant="secondary"
                                    class="px-1.5 py-0 text-[10.5px] font-normal"
                                >
                                    {{ perms.length }}
                                    {{ perms.length === 1 ? 'perm' : 'perms' }}
                                </Badge>
                            </div>

                            <div
                                class="grid grid-cols-1 gap-1.5 pt-1 sm:grid-cols-2"
                            >
                                <div
                                    v-for="p in perms"
                                    :key="p.id"
                                    class="text-foreground bg-muted/30 border-muted/50 flex items-center gap-1.5 rounded border px-2 py-1 text-xs"
                                >
                                    <CheckCircle2
                                        class="h-3.5 w-3.5 shrink-0 text-emerald-500"
                                    />
                                    <span
                                        class="truncate font-mono text-[11.5px]"
                                        :title="p.name"
                                        >{{ p.name }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <DialogFooter
                class="bg-muted/10 flex items-center border-t p-4 sm:justify-between"
            >
                <Button
                    v-if="role?.can?.update"
                    variant="outline"
                    size="sm"
                    class="text-xs"
                    @click="role && emit('edit', role)"
                >
                    <Edit class="mr-1.5 h-3.5 w-3.5" />
                    Edit Role & Permissions
                </Button>
                <div v-else></div>

                <Button
                    variant="secondary"
                    size="sm"
                    class="text-xs"
                    @click="emit('update:open', false)"
                >
                    Close
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
