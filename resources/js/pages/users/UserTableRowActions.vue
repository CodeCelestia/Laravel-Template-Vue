<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Edit, Eye, MoreHorizontal, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { UserItem } from './UserFormDialog.vue';

const props = defineProps<{
    user: UserItem;
}>();

const emit = defineEmits<{
    (e: 'view', user: UserItem): void;
    (e: 'edit', user: UserItem): void;
    (e: 'delete', user: UserItem): void;
}>();

const page = usePage();
const currentUserId = computed(() => (page.props.auth as any)?.user?.id);
const isSelf = computed(() => props.user.id === currentUserId.value);
</script>

<template>
    <div class="flex items-center justify-end space-x-1">
        <!-- Quick View Action Button -->
        <Button
            variant="ghost"
            size="icon"
            class="text-muted-foreground hover:text-foreground h-8 w-8"
            title="View User Details"
            @click="emit('view', user)"
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
                <DropdownMenuItem @click="emit('view', user)">
                    <Eye class="mr-2 h-4 w-4" />
                    View Details
                </DropdownMenuItem>

                <DropdownMenuItem
                    v-if="user.can?.update"
                    @click="emit('edit', user)"
                >
                    <Edit class="mr-2 h-4 w-4" />
                    Edit User
                </DropdownMenuItem>

                <DropdownMenuSeparator v-if="user.can?.delete && !isSelf" />

                <DropdownMenuItem
                    v-if="user.can?.delete && !isSelf"
                    class="text-destructive focus:bg-destructive/10 focus:text-destructive"
                    @click="emit('delete', user)"
                >
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete User
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
