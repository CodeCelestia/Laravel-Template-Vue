<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    Building2,
    ExternalLink,
    Loader2,
    MapPin,
    Navigation,
    Shield,
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import {
    store as municipalitiesStore,
    update as municipalitiesUpdate,
} from '@/actions/App/Http/Controllers/MunicipalityController';
import type { MunicipalityItem } from './MunicipalityTableRowActions.vue';

const props = defineProps<{
    open: boolean;
    municipality: MunicipalityItem | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const isEditing = computed(() => !!props.municipality);

const form = useForm({
    name: '',
    code: '',
    type: 'municipality' as 'city' | 'municipality',
    district: 'District 2',
    zip_code: '',
    location_description: '',
    latitude: '' as string | number,
    longitude: '' as string | number,
    is_active: true,
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            form.clearErrors();
            if (props.municipality) {
                form.name = props.municipality.name;
                form.code = props.municipality.code || '';
                form.type = props.municipality.type;
                form.district = props.municipality.district;
                form.zip_code = props.municipality.zip_code || '';
                form.location_description = props.municipality.location_description || '';
                form.latitude = props.municipality.latitude ?? '';
                form.longitude = props.municipality.longitude ?? '';
                form.is_active = props.municipality.is_active;
            } else {
                form.reset();
                form.type = 'municipality';
                form.district = 'District 2';
                form.is_active = true;
            }
        }
    },
    { immediate: true },
);

const mapPreviewUrl = computed(() => {
    if (form.latitude && form.longitude) {
        return `https://www.google.com/maps?q=${form.latitude},${form.longitude}`;
    }
    if (form.location_description) {
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(form.location_description + ', Surigao del Norte')}`;
    }
    return null;
});

const submit = () => {
    if (isEditing.value && props.municipality) {
        form.put(municipalitiesUpdate.url(props.municipality.id), {
            preserveScroll: true,
            onSuccess: () => {
                emit('update:open', false);
                emit('success');
            },
        });
    } else {
        form.post(municipalitiesStore.url(), {
            preserveScroll: true,
            onSuccess: () => {
                emit('update:open', false);
                emit('success');
            },
        });
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-[620px] max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <div class="flex items-center space-x-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <Building2 class="h-5 w-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-xl font-bold tracking-tight">
                            {{ isEditing ? 'Edit Municipality / City' : 'Add New Municipality / City' }}
                        </DialogTitle>
                        <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                            {{ isEditing ? 'Update geographical and administrative profile for this LGU.' : 'Register a new local government unit and territorial coordinates.' }}
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-5 py-2">
                <!-- Basic Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5 md:col-span-2">
                        <Label for="muni_name" class="text-xs font-semibold text-foreground">
                            LGU Name <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="muni_name"
                            v-model="form.name"
                            placeholder="e.g., Claver, Surigao City, Dapa"
                            class="h-9"
                            :disabled="form.processing"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="muni_code" class="text-xs font-semibold text-foreground">
                            LGU Code / Acronym
                        </Label>
                        <Input
                            id="muni_code"
                            v-model="form.code"
                            placeholder="e.g., CLA, SRG, DAP"
                            class="h-9 uppercase"
                            :disabled="form.processing"
                        />
                        <InputError :message="form.errors.code" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="muni_type" class="text-xs font-semibold text-foreground">
                            LGU Classification <span class="text-destructive">*</span>
                        </Label>
                        <Select v-model="form.type">
                            <SelectTrigger id="muni_type" class="h-9 w-full">
                                <SelectValue placeholder="Select classification" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="municipality">Municipality</SelectItem>
                                <SelectItem value="city">City (Chartered / Capital)</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.type" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="muni_district" class="text-xs font-semibold text-foreground">
                            Legislative District <span class="text-destructive">*</span>
                        </Label>
                        <Select v-model="form.district">
                            <SelectTrigger id="muni_district" class="h-9 w-full">
                                <SelectValue placeholder="Select district" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="District 1">District 1 (Siargao & Bucas Grande)</SelectItem>
                                <SelectItem value="District 2">District 2 (Mainland Surigao)</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.district" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="muni_zip" class="text-xs font-semibold text-foreground">
                            Postal / Zip Code
                        </Label>
                        <Input
                            id="muni_zip"
                            v-model="form.zip_code"
                            placeholder="e.g., 8410"
                            class="h-9"
                            :disabled="form.processing"
                        />
                        <InputError :message="form.errors.zip_code" />
                    </div>
                </div>

                <!-- Location & Coordinates Section -->
                <div class="rounded-xl border border-border/80 bg-muted/30 p-4 space-y-3.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <MapPin class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                            <h4 class="text-xs font-bold uppercase tracking-wider text-foreground">
                                Geographic Location & Coordinates
                            </h4>
                        </div>
                        <a
                            v-if="mapPreviewUrl"
                            :href="mapPreviewUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center space-x-1 text-[11.5px] font-medium text-primary hover:underline"
                        >
                            <span>Preview Pin in Maps</span>
                            <ExternalLink class="h-3 w-3" />
                        </a>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="location_desc" class="text-xs font-semibold text-foreground">
                            Office / Municipal Hall Address
                        </Label>
                        <Input
                            id="location_desc"
                            v-model="form.location_description"
                            placeholder="e.g., Municipal Hall, National Highway, Tayaga, Claver"
                            class="h-9 bg-background"
                            :disabled="form.processing"
                        />
                        <InputError :message="form.errors.location_description" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="latitude" class="text-xs font-semibold text-foreground flex items-center justify-between">
                                <span>Latitude (Decimal °N)</span>
                                <span class="text-[10.5px] text-muted-foreground font-normal">e.g. 9.5167</span>
                            </Label>
                            <Input
                                id="latitude"
                                v-model="form.latitude"
                                type="number"
                                step="any"
                                placeholder="9.5167"
                                class="h-9 bg-background font-mono text-xs"
                                :disabled="form.processing"
                            />
                            <InputError :message="form.errors.latitude" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="longitude" class="text-xs font-semibold text-foreground flex items-center justify-between">
                                <span>Longitude (Decimal °E)</span>
                                <span class="text-[10.5px] text-muted-foreground font-normal">e.g. 125.7333</span>
                            </Label>
                            <Input
                                id="longitude"
                                v-model="form.longitude"
                                type="number"
                                step="any"
                                placeholder="125.7333"
                                class="h-9 bg-background font-mono text-xs"
                                :disabled="form.processing"
                            />
                            <InputError :message="form.errors.longitude" />
                        </div>
                    </div>
                    <p class="text-[11.5px] text-muted-foreground">
                        Tip: Surigao del Norte coordinates typically fall between 9.4° – 10.1° N latitude and 125.4° – 126.2° E longitude.
                    </p>
                </div>

                <!-- Active Status Toggle -->
                <div class="flex items-center space-x-3 rounded-lg border border-border/70 p-3 bg-background">
                    <Checkbox
                        id="is_active"
                        :checked="form.is_active"
                        @update:checked="(val: boolean) => form.is_active = val"
                    />
                    <div class="space-y-0.5">
                        <Label for="is_active" class="text-xs font-semibold text-foreground cursor-pointer">
                            Active Status
                        </Label>
                        <p class="text-[11.5px] text-muted-foreground">
                            When enabled, this LGU appears in active geographic and location filters.
                        </p>
                    </div>
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
                        type="submit"
                        :disabled="form.processing"
                        class="min-w-[100px]"
                    >
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isEditing ? 'Save Changes' : 'Create LGU' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
