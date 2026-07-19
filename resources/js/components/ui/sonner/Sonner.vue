<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { Toaster as SonnerPrimitive } from 'vue-sonner';
import 'vue-sonner/style.css';
import { Info } from 'lucide-vue-next';

const { appearance } = useAppearance();
</script>

<template>
    <SonnerPrimitive
        :theme="appearance"
        class="toaster group"
        position="bottom-right"
        :style="{
            '--normal-bg': 'var(--popover)',
            '--normal-text': 'var(--popover-foreground)',
            '--normal-border': 'var(--border)',
            '--error-bg': 'var(--popover)',
            '--error-text': 'var(--popover-foreground)',
            '--error-border': 'var(--border)',
        }"
    >
        <template #error-icon>
            <Info class="size-5 text-[#0EACD8]" />
        </template>
    </SonnerPrimitive>
</template>
