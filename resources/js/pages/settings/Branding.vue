<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Loader2 } from '@lucide/vue';
import BrandingController from '@/actions/App/Http/Controllers/Settings/BrandingController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useBranding } from '@/composables/useBranding';
import { edit } from '@/routes/branding';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Branding settings',
                href: edit(),
            },
        ],
    },
});

const { branding } = useBranding();

const nameForm = useForm({
    name: branding.value.name,
});

const saveName = () => {
    nameForm.patch(BrandingController.update.url(), {
        preserveScroll: true,
    });
};

// Logo Upload & Reset
const logoInputRef = ref<HTMLInputElement | null>(null);
const logoForm = useForm<{ logo: File | null }>({
    logo: null,
});

const triggerLogoUpload = () => {
    logoInputRef.value?.click();
};

const onLogoSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) {
        return;
    }

    logoForm.logo = file;
    logoForm.post(BrandingController.updateLogo.url(), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            logoForm.reset();
            if (logoInputRef.value) {
                logoInputRef.value.value = '';
            }
        },
        onError: () => {
            if (logoInputRef.value) {
                logoInputRef.value.value = '';
            }
        },
    });
};

const resetLogo = () => {
    if (!branding.value.has_custom_logo) {
        return;
    }

    router.delete(BrandingController.resetLogo.url(), {
        preserveScroll: true,
    });
};

// Favicon Upload & Reset
const faviconInputRef = ref<HTMLInputElement | null>(null);
const faviconForm = useForm<{ favicon: File | null }>({
    favicon: null,
});

const triggerFaviconUpload = () => {
    faviconInputRef.value?.click();
};

const onFaviconSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) {
        return;
    }

    faviconForm.favicon = file;
    faviconForm.post(BrandingController.updateFavicon.url(), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            faviconForm.reset();
            if (faviconInputRef.value) {
                faviconInputRef.value.value = '';
            }
        },
        onError: () => {
            if (faviconInputRef.value) {
                faviconInputRef.value.value = '';
            }
        },
    });
};

const resetFavicon = () => {
    if (!branding.value.has_custom_favicon) {
        return;
    }

    router.delete(BrandingController.resetFavicon.url(), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Branding settings" />

    <h1 class="sr-only">Branding settings</h1>

    <div class="space-y-10">
        <!-- Header -->
        <Heading
            variant="small"
            title="Branding"
            description="Customize the app name, logo, and favicon shown across the application"
        />

        <!-- App Name Section -->
        <div class="space-y-4">
            <form @submit.prevent="saveName" class="space-y-2">
                <Label for="app-name" class="text-sm font-semibold text-foreground">
                    App name
                </Label>
                <div class="max-w-xl">
                    <Input
                        id="app-name"
                        v-model="nameForm.name"
                        class="bg-card w-full"
                        placeholder="PRPIMS-SDN"
                        required
                    />
                    <InputError :message="nameForm.errors.name" class="mt-2" />
                    <p class="text-xs text-muted-foreground mt-2">
                        Shown in the sidebar, browser tab title, and sign-in pages.
                    </p>
                </div>

                <div class="pt-2">
                    <Button
                        type="submit"
                        :disabled="nameForm.processing"
                        class="rounded-xl px-5 py-2 font-medium"
                    >
                        <Loader2
                            v-if="nameForm.processing"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        Save Name
                    </Button>
                </div>
            </form>
        </div>

        <!-- Cards Section: App Logo & Favicon -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- App Logo Card -->
            <div
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-xs flex flex-col justify-between"
            >
                <div>
                    <!-- Preview Box -->
                    <div
                        class="relative flex h-52 sm:h-56 w-full items-center justify-center rounded-xl bg-muted/30 dark:bg-muted/20 border border-border/40 p-6 overflow-hidden"
                    >
                        <div
                            v-if="logoForm.processing"
                            class="absolute inset-0 flex items-center justify-center bg-card/70 backdrop-blur-xs"
                        >
                            <Loader2 class="h-8 w-8 animate-spin text-primary" />
                        </div>
                        <img
                            :src="branding.logo"
                            alt="App logo preview"
                            class="max-h-24 max-w-24 sm:max-h-28 sm:max-w-28 object-contain drop-shadow-xs"
                        />
                    </div>

                    <!-- Details -->
                    <div class="mt-5">
                        <h3 class="text-base font-semibold text-foreground">
                            App logo
                        </h3>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            Shown in the sidebar, header, and sign-in pages.
                        </p>
                        <p class="text-xs text-muted-foreground/80 mt-1">
                            Recommended: 512 × 512 px square PNG, transparent background
                        </p>
                        <InputError :message="logoForm.errors.logo" class="mt-2" />
                    </div>
                </div>

                <!-- Hidden file input -->
                <input
                    ref="logoInputRef"
                    type="file"
                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                    class="hidden"
                    @change="onLogoSelected"
                />

                <!-- Action Buttons -->
                <div class="mt-6 flex items-center gap-3">
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        class="rounded-lg px-4 font-medium cursor-pointer"
                        :disabled="logoForm.processing"
                        @click="triggerLogoUpload"
                    >
                        Replace
                    </Button>

                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="text-sm font-medium text-muted-foreground hover:text-foreground cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                        :disabled="!branding.has_custom_logo || logoForm.processing"
                        @click="resetLogo"
                    >
                        Reset
                    </Button>
                </div>
            </div>

            <!-- Favicon Card -->
            <div
                class="rounded-2xl border border-border/80 bg-card p-6 shadow-xs flex flex-col justify-between"
            >
                <div>
                    <!-- Preview Box -->
                    <div
                        class="relative flex h-52 sm:h-56 w-full items-center justify-center rounded-xl bg-muted/30 dark:bg-muted/20 border border-border/40 p-6 overflow-hidden"
                    >
                        <div
                            v-if="faviconForm.processing"
                            class="absolute inset-0 flex items-center justify-center bg-card/70 backdrop-blur-xs"
                        >
                            <Loader2 class="h-8 w-8 animate-spin text-primary" />
                        </div>
                        <img
                            :src="branding.favicon"
                            alt="Favicon preview"
                            class="size-10 sm:size-12 object-contain drop-shadow-2xs"
                        />
                    </div>

                    <!-- Details -->
                    <div class="mt-5">
                        <h3 class="text-base font-semibold text-foreground">
                            Favicon
                        </h3>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            The browser tab icon (.ico, .png, or .svg).
                        </p>
                        <p class="text-xs text-muted-foreground/80 mt-1">
                            Recommended: 48 × 48 px PNG or .ico (SVG scales to any size)
                        </p>
                        <InputError :message="faviconForm.errors.favicon" class="mt-2" />
                    </div>
                </div>

                <!-- Hidden file input -->
                <input
                    ref="faviconInputRef"
                    type="file"
                    accept=".ico,image/png,image/svg+xml,image/webp,image/x-icon"
                    class="hidden"
                    @change="onFaviconSelected"
                />

                <!-- Action Buttons -->
                <div class="mt-6 flex items-center gap-3">
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        class="rounded-lg px-4 font-medium cursor-pointer"
                        :disabled="faviconForm.processing"
                        @click="triggerFaviconUpload"
                    >
                        Replace
                    </Button>

                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="text-sm font-medium text-muted-foreground hover:text-foreground cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                        :disabled="!branding.has_custom_favicon || faviconForm.processing"
                        @click="resetFavicon"
                    >
                        Reset
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
