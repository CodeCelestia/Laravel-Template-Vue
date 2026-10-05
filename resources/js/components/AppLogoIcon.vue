<script setup lang="ts">
import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';
import { useBranding } from '@/composables/useBranding';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
    src?: string;
};

const props = defineProps<Props>();

const { logoUrl } = useBranding();
const logoSrc = computed(() => props.src || logoUrl.value);
</script>

<template>
    <img
        :src="logoSrc"
        alt="App Logo"
        :class="[className, ($attrs as Record<string, any>).class]"
        v-bind="{ ...$attrs, class: undefined }"
        class="aspect-square object-contain shrink-0"
    />
</template>
