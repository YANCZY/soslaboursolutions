<script setup lang="ts">
import type {
    PDFDocumentProxy,
    PDFPageProxy,
    RenderTask,
} from 'pdfjs-dist';
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

const props = defineProps<{
    pdf: PDFDocumentProxy;
    pageNumber: number;
    scale: number;
    rotation: number;
    initialWidth: number;
    initialHeight: number;
}>();

const container = ref<HTMLElement | null>(null);
const canvas = ref<HTMLCanvasElement | null>(null);
const visible = ref(false);
const ready = ref(false);
const failed = ref(false);

const base = ref({
    width: props.initialWidth,
    height: props.initialHeight,
});

const swapped = computed(() => props.rotation % 180 !== 0);

const width = computed(() =>
    (swapped.value ? base.value.height : base.value.width) * props.scale,
);

const height = computed(() =>
    (swapped.value ? base.value.width : base.value.height) * props.scale,
);

let observer: IntersectionObserver | undefined;
let page: PDFPageProxy | undefined;
let renderTask: RenderTask | undefined;
let revision = 0;
let disposed = false;
let work: Promise<void> = Promise.resolve();

function scheduleRender() {
    const token = ++revision;

    renderTask?.cancel();

    work = work.then(async () => {
        if (disposed || token !== revision) {
            return;
        }

        const element = canvas.value;

        if (!element) {
            return;
        }

        ready.value = false;
        failed.value = false;

        if (!visible.value) {
            element.width = 1;
            element.height = 1;
            page?.cleanup();

            return;
        }

        page ??= await props.pdf.getPage(props.pageNumber);

        if (disposed || token !== revision) {
            return;
        }

        const original = page.getViewport({ scale: 1 });

        base.value = {
            width: original.width,
            height: original.height,
        };

        const cssViewport = page.getViewport({
            scale: props.scale,
            rotation: (page.rotate + props.rotation) % 360,
        });

        // Limit backing-canvas memory while keeping the CSS zoom level.
        const density = Math.min(
            window.devicePixelRatio || 1,
            2,
            Math.sqrt(
                4_000_000 / (cssViewport.width * cssViewport.height),
            ),
            4096 / cssViewport.width,
            4096 / cssViewport.height,
        );

        const viewport = page.getViewport({
            scale: props.scale * density,
            rotation: (page.rotate + props.rotation) % 360,
        });

        const context = element.getContext('2d');

        if (!context) {
            throw new Error('Canvas unavailable.');
        }

        element.width = Math.max(1, Math.ceil(viewport.width));
        element.height = Math.max(1, Math.ceil(viewport.height));

        renderTask = page.render({
            canvas: element,
            canvasContext: context,
            viewport,
        });

        await renderTask.promise;
        renderTask = undefined;

        if (!disposed && token === revision) {
            ready.value = true;
        }
    }).catch(() => {
        if (!disposed && token === revision && visible.value) {
            failed.value = true;
        }
    });
}

watch(
    [visible, () => props.scale, () => props.rotation],
    scheduleRender,
    { flush: 'post' },
);

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        visible.value = entries.some((entry) => entry.isIntersecting);
    }, { rootMargin: '300px' });

    if (container.value) {
        observer.observe(container.value);
    }
});

onBeforeUnmount(() => {
    disposed = true;
    revision++;
    observer?.disconnect();
    renderTask?.cancel();

    void work.then(() => page?.cleanup()).catch(() => {});
});
</script>

<template>
    <div class="shrink-0">
        <p class="mb-2 text-center text-xs text-muted-foreground">
            Page {{ pageNumber }}
        </p>

        <div
            ref="container"
            class="relative shrink-0 bg-white shadow-sm"
            :style="{
                width: `${width}px`,
                height: `${height}px`,
            }"
        >
            <canvas
                ref="canvas"
                v-show="ready"
                :aria-label="`PDF page ${pageNumber}`"
                class="block h-full w-full"
            />

            <div
                v-if="!ready"
                class="absolute inset-0 flex items-center justify-center p-4 text-center text-sm text-gray-600"
            >
                {{ failed
                    ? 'This page could not be rendered.'
                    : `Page ${pageNumber}` }}
            </div>
        </div>
    </div>
</template>
