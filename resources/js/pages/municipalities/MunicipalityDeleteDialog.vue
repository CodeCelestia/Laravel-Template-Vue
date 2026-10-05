<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
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
import { destroy as municipalitiesDestroy } from '@/actions/App/Http/Controllers/MunicipalityController';
import type { MunicipalityItem } from './MunicipalityTableRowActions.vue';

const props = defineProps<{
    open: boolean;
    municipality: MunicipalityItem | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const form = useForm({});

const submit = () => {
    if (!props.municipality) {
        return;
    }

    form.delete(municipalitiesDestroy.url(props.municipality.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false);
            emit('success');
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-[460px]">
            <DialogHeader>
                <div class="flex items-center space-x-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-destructive/10 text-destructive">
                        <AlertTriangle class="h-6 w-6" />
                    </div>
                    <div>
                        <DialogTitle class="text-lg font-bold tracking-tight text-foreground">
                            Delete Municipality / City
                        </DialogTitle>
                        <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                            This action will remove the LGU from the system master registry.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="space-y-3 py-2">
                <p class="text-sm text-foreground">
                    Are you sure you want to delete
                    <strong class="font-semibold">{{ municipality?.name }}</strong>?
                </p>

                <p class="text-xs text-muted-foreground">
                    This LGU can be safely soft-deleted and restored by an administrator if needed.
                </p>
            </div>

            <DialogFooter class="pt-2">
                <Button
                    type="button"
                    variant="outline"
                    @click="emit('update:open', false)"
                    :disabled="form.processing"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    :disabled="form.processing"
                    @click="submit"
                >
                    <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Delete Municipality
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
