<script setup lang="ts">
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useBranding } from '@/composables/useBranding';

type Props = {
    orientation?: 'horizontal' | 'vertical';
};

const props = withDefaults(defineProps<Props>(), {
    orientation: 'horizontal',
});

const { appName } = useBranding();
const appTitle = computed(() => appName.value);
const shortName = computed(() => appName.value);
</script>

<template>
    <div
        v-if="orientation === 'vertical'"
        class="flex w-full flex-col items-center justify-center text-center"
    >
        <!-- Logo at Top -->
        <div
            class="flex aspect-square size-16 sm:size-20 items-center justify-center shrink-0 transition-transform duration-300 group-hover:scale-105 group-data-[collapsible=icon]:size-8"
        >
            <AppLogoIcon
                class="size-16 sm:size-20 object-contain drop-shadow-xs group-data-[collapsible=icon]:size-8"
            />
        </div>

        <!-- App Name Below -->
        <div
            class="mt-3 flex flex-col items-center justify-center px-1 text-center group-data-[collapsible=icon]:hidden"
        >
            <span
                class="text-lg sm:text-xl font-bold tracking-wider text-sidebar-foreground uppercase leading-tight break-words max-w-[220px]"
            >
                {{ appTitle }}
            </span>
        </div>
    </div>

    <template v-else>
        <div
            class="flex aspect-square size-8 items-center justify-center shrink-0"
        >
            <AppLogoIcon class="size-8" />
        </div>
        <div class="ml-1 grid flex-1 text-left text-sm">
            <span class="mb-0.5 truncate leading-tight font-semibold">{{
                shortName
            }}</span>
        </div>
    </template>
</template>
