<script setup lang="ts">
import { SwitchRoot, SwitchThumb, useForwardPropsEmits } from 'reka-ui';
import type { SwitchRootEmits, SwitchRootProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<SwitchRootProps & { class?: string }>();
const emits = defineEmits<SwitchRootEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <SwitchRoot
        v-bind="forwarded"
        :class="
            cn(
                'peer inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                props.class,
            )
        "
    >
        <SwitchThumb
            class="pointer-events-none flex size-5 items-center justify-center rounded-full bg-white shadow-sm transition-transform data-[state=checked]:translate-x-5 data-[state=unchecked]:translate-x-0.5"
        >
            <slot />
        </SwitchThumb>
    </SwitchRoot>
</template>
