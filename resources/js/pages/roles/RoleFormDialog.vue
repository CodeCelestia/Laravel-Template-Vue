<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    CheckCheck,
    KeyRound,
    Loader2,
    Search,
    Shield,
    XCircle,
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/InputError.vue';
import {
    store as rolesStore,
    update as rolesUpdate,
} from '@/actions/App/Http/Controllers/RoleController';
import type { RoleItem } from './RoleTableRowActions.vue';

export interface AvailablePermission {
    id: number;
    name: string;
}

const props = defineProps<{
    open: boolean;
    role: RoleItem | null;
    allPermissions: AvailablePermission[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const isEditing = computed(() => !!props.role);
const isSystemAdmin = computed(() => props.role?.name === 'System Admin');

const permissionSearch = ref('');

const form = useForm({
    name: '',
    permissions: [] as string[],
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            form.clearErrors();
            permissionSearch.value = '';
            if (props.role) {
                form.name = props.role.name;
                form.permissions = props.role.permissions.map((p) => p.name);
            } else {
                form.reset();
                form.permissions = [];
            }
        }
    },
    { immediate: true },
);

// Group all permissions by module
const groupedPermissions = computed(() => {
    const search = permissionSearch.value.toLowerCase().trim();
    const groups: Record<
        string,
        {
            title: string;
            description: string;
            items: AvailablePermission[];
        }
    > = {};

    for (const perm of props.allPermissions) {
        if (search && !perm.name.toLowerCase().includes(search)) {
            continue;
        }

        const parts = perm.name.split('.');
        const mod = parts[0] || 'other';

        if (!groups[mod]) {
            groups[mod] = {
                title: getModuleTitle(mod),
                description: getModuleDescription(mod),
                items: [],
            };
        }
        groups[mod].items.push(perm);
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
        case 'municipalities':
            return 'Municipalities & Cities';
        case 'notes':
            return 'Developer Open Notes';
        default:
            return key.toUpperCase();
    }
}

function getModuleDescription(key: string): string {
    switch (key) {
        case 'users':
            return 'Manage staff login accounts, passwords, and user details';
        case 'roles':
            return 'Control security role definitions and access tiers';
        case 'permissions':
            return 'Maintain granular application capabilities and guards';
        case 'municipalities':
            return 'Manage local government units, geographic coordinates, and districts';
        case 'notes':
            return 'Developer-only note system, scratchpad code snippets, and task tracking';
        default:
            return 'System permissions';
    }
}

function togglePermission(permissionName: string) {
    const idx = form.permissions.indexOf(permissionName);
    if (idx > -1) {
        form.permissions.splice(idx, 1);
    } else {
        form.permissions.push(permissionName);
    }
}

function isPermissionSelected(permissionName: string): boolean {
    return form.permissions.includes(permissionName);
}

function selectAllInModule(items: AvailablePermission[]) {
    for (const item of items) {
        if (!form.permissions.includes(item.name)) {
            form.permissions.push(item.name);
        }
    }
}

function deselectAllInModule(items: AvailablePermission[]) {
    const names = items.map((i) => i.name);
    form.permissions = form.permissions.filter((p) => !names.includes(p));
}

function isModuleAllSelected(items: AvailablePermission[]): boolean {
    if (items.length === 0) return false;
    return items.every((i) => form.permissions.includes(i.name));
}

function selectAllGlobal() {
    form.permissions = props.allPermissions.map((p) => p.name);
}

function clearAllGlobal() {
    form.permissions = [];
}

function closeModal() {
    emit('update:open', false);
}

function submit() {
    if (isEditing.value && props.role) {
        form.put(rolesUpdate.url(props.role.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                emit('success');
            },
        });
    } else {
        form.post(rolesStore.url(), {
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
        <DialogContent
            class="flex max-h-[90vh] flex-col overflow-hidden p-0 sm:max-w-3xl"
        >
            <DialogHeader class="border-b p-6 pb-4">
                <div class="flex items-center gap-3">
                    <div
                        class="bg-primary/10 text-primary flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                    >
                        <Shield class="h-5 w-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-foreground text-xl font-bold">
                            {{
                                isEditing
                                    ? 'Edit Role & Permissions'
                                    : 'Create New Role'
                            }}
                        </DialogTitle>
                        <DialogDescription
                            class="text-muted-foreground mt-0.5 text-xs"
                        >
                            Define the role name and configure its granular
                            system permissions across all modules.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <form
                @submit.prevent="submit"
                class="flex flex-1 flex-col overflow-hidden"
            >
                <div class="flex-1 space-y-6 overflow-y-auto p-6">
                    <!-- Role Name -->
                    <div class="space-y-2">
                        <Label
                            for="role-name"
                            class="text-foreground text-xs font-semibold"
                        >
                            Role Name <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="role-name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. User, Manager, Editor"
                            class="h-10"
                            :disabled="isSystemAdmin"
                            required
                        />
                        <p
                            v-if="isSystemAdmin"
                            class="text-[11.5px] text-amber-600 dark:text-amber-400"
                        >
                            The System Admin role name is protected and cannot
                            be changed.
                        </p>
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Permissions Assignment Section -->
                    <div class="space-y-4">
                        <div
                            class="flex flex-col gap-2 border-b pb-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <KeyRound class="text-primary h-4 w-4" />
                                    <h3
                                        class="text-foreground text-sm font-semibold"
                                    >
                                        Assigned Permissions
                                    </h3>
                                    <Badge
                                        variant="secondary"
                                        class="font-mono text-xs"
                                    >
                                        {{ form.permissions.length }} of
                                        {{ allPermissions.length }}
                                    </Badge>
                                </div>
                                <p class="text-muted-foreground mt-0.5 text-xs">
                                    Select the capabilities granted to accounts
                                    with this role.
                                </p>
                            </div>

                            <!-- Bulk actions & search -->
                            <div class="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="h-8 text-xs"
                                    @click="selectAllGlobal"
                                >
                                    <CheckCheck class="mr-1 h-3.5 w-3.5" />
                                    Select All
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="text-muted-foreground hover:text-foreground h-8 text-xs"
                                    @click="clearAllGlobal"
                                >
                                    <XCircle class="mr-1 h-3.5 w-3.5" />
                                    Clear
                                </Button>
                            </div>
                        </div>

                        <!-- Search permissions input -->
                        <div class="relative">
                            <Search
                                class="text-muted-foreground absolute top-2.5 left-2.5 h-4 w-4"
                            />
                            <Input
                                v-model="permissionSearch"
                                type="search"
                                placeholder="Search permissions in list..."
                                class="h-9 pl-9 text-xs"
                            />
                        </div>

                        <!-- Categorized modules -->
                        <div class="space-y-4 pt-1">
                            <div
                                v-for="(group, mod) in groupedPermissions"
                                :key="mod"
                                class="bg-card space-y-3 rounded-xl border p-4 shadow-xs"
                            >
                                <div
                                    class="flex items-center justify-between border-b pb-2.5"
                                >
                                    <div>
                                        <div
                                            class="text-foreground flex items-center gap-2 text-sm font-bold"
                                        >
                                            {{ group.title }}
                                            <span
                                                class="text-muted-foreground text-[11.5px] font-normal"
                                                >({{
                                                    group.items.length
                                                }})</span
                                            >
                                        </div>
                                        <div
                                            class="text-muted-foreground text-[11.5px]"
                                        >
                                            {{ group.description }}
                                        </div>
                                    </div>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="h-7 px-2 text-[11.5px]"
                                        @click="
                                            isModuleAllSelected(group.items)
                                                ? deselectAllInModule(
                                                      group.items,
                                                  )
                                                : selectAllInModule(group.items)
                                        "
                                    >
                                        {{
                                            isModuleAllSelected(group.items)
                                                ? 'Deselect Module'
                                                : 'Select All'
                                        }}
                                    </Button>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-2.5 pt-1 sm:grid-cols-2"
                                >
                                    <label
                                        v-for="perm in group.items"
                                        :key="perm.id"
                                        :class="[
                                            'flex cursor-pointer items-start gap-2.5 rounded-lg border p-2 text-xs transition-colors select-none',
                                            isPermissionSelected(perm.name)
                                                ? 'bg-primary/5 border-primary/30 text-foreground'
                                                : 'hover:bg-muted/40 text-muted-foreground border-transparent',
                                        ]"
                                    >
                                        <Checkbox
                                            :checked="
                                                isPermissionSelected(perm.name)
                                            "
                                            @update:checked="
                                                togglePermission(perm.name)
                                            "
                                            class="mt-0.5"
                                        />
                                        <div class="flex flex-col">
                                            <span
                                                class="text-foreground font-mono text-[11.5px] font-semibold"
                                            >
                                                {{ perm.name }}
                                            </span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <InputError :message="form.errors.permissions" />
                    </div>
                </div>

                <DialogFooter
                    class="bg-muted/10 flex items-center border-t p-4 sm:justify-between"
                >
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
                        class="min-w-[110px] text-xs"
                        :disabled="form.processing"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="mr-1.5 h-3.5 w-3.5 animate-spin"
                        />
                        {{ isEditing ? 'Save Changes' : 'Create Role' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
