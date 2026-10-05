<script setup lang="ts">
import { Edit, MoreHorizontal, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export interface PermissionItem {
    id: number;
    name: string;
    roles_count: number;
    roles: Array<{ id: number; name: string }>;
    can?: {
        update?: boolean;
        delete?: boolean;
    };
}

const props = defineProps<{
    permission: PermissionItem;
}>();

const emit = defineEmits<{
    (e: 'edit', permission: PermissionItem): void;
    (e: 'delete', permission: PermissionItem): void;
}>();
</script>

<template>
    <div class="flex items-center justify-end space-x-1">
        <!-- Dropdown for actions -->
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="ghost"
                    size="icon"
                    class="text-muted-foreground hover:text-foreground h-8 w-8"
                >
                    <MoreHorizontal class="h-4 w-4" />
                    <span class="sr-only">Open menu</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-[160px]">
                <DropdownMenuItem
                    v-if="permission.can?.update"
                    @click="emit('edit', permission)"
                >
                    <Edit class="mr-2 h-4 w-4" />
                    Edit Permission
                </DropdownMenuItem>

                <DropdownMenuSeparator v-if="permission.can?.delete" />

                <DropdownMenuItem
                    v-if="permission.can?.delete"
                    class="text-destructive focus:bg-destructive/10 focus:text-destructive"
                    @click="emit('delete', permission)"
                >
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete Permission
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
