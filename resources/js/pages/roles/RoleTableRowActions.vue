<script setup lang="ts">
import { Edit, Eye, MoreHorizontal, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export interface RoleItem {
    id: number;
    name: string;
    created_at: string | null;
    users_count: number;
    permissions: Array<{ id: number; name: string }>;
    can?: {
        update?: boolean;
        delete?: boolean;
    };
}

const props = defineProps<{
    role: RoleItem;
}>();

const emit = defineEmits<{
    (e: 'view', role: RoleItem): void;
    (e: 'edit', role: RoleItem): void;
    (e: 'delete', role: RoleItem): void;
}>();
</script>

<template>
    <div class="flex items-center justify-end space-x-1">
        <!-- Quick View Action Button -->
        <Button
            variant="ghost"
            size="icon"
            class="text-muted-foreground hover:text-foreground h-8 w-8"
            title="View Role Details"
            @click="emit('view', role)"
        >
            <Eye class="h-4 w-4" />
            <span class="sr-only">View</span>
        </Button>

        <!-- Dropdown for more actions -->
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
                <DropdownMenuItem @click="emit('view', role)">
                    <Eye class="mr-2 h-4 w-4" />
                    View Details
                </DropdownMenuItem>

                <DropdownMenuItem
                    v-if="role.can?.update"
                    @click="emit('edit', role)"
                >
                    <Edit class="mr-2 h-4 w-4" />
                    Edit Role
                </DropdownMenuItem>

                <DropdownMenuSeparator
                    v-if="role.can?.delete && role.name !== 'System Admin'"
                />

                <DropdownMenuItem
                    v-if="role.can?.delete && role.name !== 'System Admin'"
                    class="text-destructive focus:bg-destructive/10 focus:text-destructive"
                    @click="emit('delete', role)"
                >
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete Role
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
