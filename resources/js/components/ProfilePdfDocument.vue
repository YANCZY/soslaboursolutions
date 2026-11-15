<script setup lang="ts">
import type {
    PDFDocumentLoadingTask,
    PDFDocumentProxy,
} from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';
import { onMounted, onUnmounted, ref, shallowRef } from 'vue';
import ProfilePdfPage from '@/components/ProfilePdfPage.vue';
import type { ProfilePreviewInfo } from '@/types/profile-document';

const props = defineProps<{
    url: string;
    scale: number;
    rotation: number;
}>();

const emit = defineEmits<{
    ready: [info: ProfilePreviewInfo];
}>();

const pdf = shallowRef<PDFDocumentProxy | null>(null);
const firstPage = ref({ width: 612, height: 792 });
const error = ref('');

let task: PDFDocumentLoadingTask | undefined;
let disposed = false;

onMounted(async () => {
    try {
        const pdfjs = await import('pdfjs-dist');

        if (disposed) {
            return;
        }

        pdfjs.GlobalWorkerOptions.workerSrc = workerUrl;

        task = pdfjs.getDocument({
            url: props.url,
            withCredentials: true,
        });

        const document = await task.promise;

        if (disposed) {
            return;
        }

        const page = await document.getPage(1);
        const viewport = page.getViewport({ scale: 1 });

        if (disposed) {
            return;
        }

        firstPage.value = {
            width: viewport.width,
            height: viewport.height,
        };

        pdf.value = document;

        emit('ready', {
            ...firstPage.value,
            pages: document.numPages,
        });
    } catch {
        if (!disposed) {
            error.value =
                'This PDF could not be previewed. It may be password-protected, damaged, or unavailable.';
        }
    }
});

onUnmounted(() => {
    disposed = true;

    if (task) {
        void task.destroy().catch(() => {});
    }
});
</script>

<template>
    <div class="flex w-max min-w-full flex-col items-center gap-6">
        <p
            v-if="error"
            role="alert"
            class="max-w-md whitespace-normal p-6 text-center text-sm text-destructive"
        >
            {{ error }}
        </p>

        <template v-else-if="pdf">
            <ProfilePdfPage
                v-for="number in pdf.numPages"
                :key="number"
                :pdf="pdf"
                :page-number="number"
                :scale="scale"
                :rotation="rotation"
                :initial-width="firstPage.width"
                :initial-height="firstPage.height"
            />
        </template>

        <p v-else class="p-6 text-sm text-muted-foreground">
            Loading PDF...
        </p>
    </div>
</template>
