<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { defineAsyncComponent } from 'vue';
import { Button } from '@/components/ui/button';
import type { ProfileDocumentItem } from '@/types/profile-document';

defineProps<{ document: ProfileDocumentItem }>();

const emit = defineEmits<{
    remove: [document: ProfileDocumentItem];
    preview: [document: ProfileDocumentItem];
}>();

const PdfPreview = defineAsyncComponent(
    () => import('@/components/ProfilePdfPreview.vue'),
);

const labels = {
    resume: 'Resume',
    license: 'License',
    certification: 'Certification',
};
</script>

<template>
    <article
        class="document-card relative w-40 shrink-0 snap-start rounded-lg border bg-card"
    >
        <button
            type="button"
            class="block w-full overflow-hidden rounded-lg text-left focus-visible:outline-2 focus-visible:outline-primary"
            :aria-label="`Preview ${document.original_name}`"
            @click="emit('preview', document)"
        >
            <div class="h-28 overflow-hidden rounded-t-lg bg-muted">
                <PdfPreview
                    v-if="document.mime_type === 'application/pdf'"
                    :url="document.url"
                />

                <img
                    v-else
                    :src="document.url"
                    :alt="document.original_name"
                    loading="lazy"
                    class="h-full w-full object-contain p-2"
                />
            </div>

            <div class="space-y-1 border-t p-3">
                <p class="text-xs text-muted-foreground">
                    {{ labels[document.category] }}
                </p>

                <p
                    class="truncate text-xs font-medium"
                    :title="document.original_name"
                >
                    {{ document.original_name }}
                </p>

                <p class="text-xs text-muted-foreground">
                    {{ (document.size / 1024).toFixed(0) }} KB
                </p>
            </div>
        </button>

        <Button
            type="button"
            variant="secondary"
            size="icon"
            class="remove-button absolute top-1 right-1 size-7 rounded-full border shadow-sm"
            :aria-label="`Remove ${document.original_name}`"
            @click="emit('remove', document)"
        >
            <X class="size-3.5" />
        </Button>
    </article>
</template>

<style scoped>
.remove-button {
    opacity: 1;
}

@media (hover: hover) and (pointer: fine) {
    .remove-button {
        opacity: 0;
    }

    .document-card:hover .remove-button,
    .document-card:focus-within .remove-button {
        opacity: 1;
    }
}
</style>
