<script setup lang="ts">
import type { PDFDocumentLoadingTask } from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{ url: string }>();

const container = ref<HTMLElement | null>(null);
const canvas = ref<HTMLCanvasElement | null>(null);
const state = ref<'loading' | 'ready' | 'failed'>('loading');

let observer: IntersectionObserver | undefined;
let task: PDFDocumentLoadingTask | undefined;
let disposed = false;

async function renderPreview() {
    try {
        const pdfjs = await import('pdfjs-dist');

        if (disposed || !canvas.value) {
            return;
        }

        pdfjs.GlobalWorkerOptions.workerSrc = workerUrl;

        task = pdfjs.getDocument({
            url: props.url,
            withCredentials: true,
        });

        const pdf = await task.promise;

        if (disposed) {
            return;
        }

        const page = await pdf.getPage(1);
        const original = page.getViewport({ scale: 1 });
        const viewport = page.getViewport({
            scale: 480 / original.width,
        });

        const element = canvas.value;

        if (disposed || !element) {
            return;
        }

        const context = element.getContext('2d');

        if (!context) {
            throw new Error('Canvas unavailable.');
        }

        element.width = Math.ceil(viewport.width);
        element.height = Math.ceil(viewport.height);

        await page.render({
            canvas: element,
            canvasContext: context,
            viewport,
        }).promise;

        if (!disposed) {
            state.value = 'ready';
        }
    } catch {
        if (!disposed) {
            state.value = 'failed';
        }
    } finally {
        if (task) {
            await task.destroy();
            task = undefined;
        }
    }
}

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
            observer?.disconnect();
            void renderPreview();
        }
    }, { rootMargin: '150px' });

    if (container.value){
        observer.observe(container.value);
    }
});

onBeforeUnmount(() => {
    disposed = true;
    observer?.disconnect();
    void task?.destroy();
});
</script>

<template>
    <div
        ref="container"
        class="flex h-full w-full items-center justify-center overflow-hidden bg-white p-2"
    >
        <canvas
            ref="canvas"
            v-show="state === 'ready'"
            aria-label="PDF first-page preview"
            class="max-h-full max-w-full object-contain"
        />

        <span
            v-if="state !== 'ready'"
            class="px-4 text-center text-xs text-gray-600"
        >
            {{ state === 'loading'
                ? 'Loading PDF preview...'
                : 'Preview unavailable. Click to open the PDF.' }}
        </span>
    </div>
</template>
