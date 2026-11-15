<script setup lang="ts">
import {
    ChevronLeft,
    ChevronRight,
    Maximize,
    RotateCw,
    ZoomIn,
    ZoomOut,
} from 'lucide-vue-next';
import {
    computed,
    defineAsyncComponent,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import { Button } from '@/components/ui/button';
import type {
    ProfileDocumentItem,
    ProfilePreviewInfo,
} from '@/types/profile-document';

const props = defineProps<{
    documents: ProfileDocumentItem[];
    selectedId: number;
}>();

const emit = defineEmits<{
    select: [id: number];
}>();

const PdfThumbnail = defineAsyncComponent(
    () => import('@/components/ProfilePdfPreview.vue'),
);

const PdfDocument = defineAsyncComponent(
    () => import('@/components/ProfilePdfDocument.vue'),
);

const current = computed(() =>
    props.documents.find((document) => document.id === props.selectedId),
);

const index = computed(() =>
    props.documents.findIndex((document) => document.id === props.selectedId),
);

const isPdf = computed(
    () => current.value?.mime_type === 'application/pdf',
);

const viewport = ref<HTMLElement | null>(null);
const info = ref<ProfilePreviewInfo | null>(null);
const imageError = ref(false);
const scale = ref(1);
const rotation = ref(0);
const viewMode = ref<'fit' | 'custom'>('fit');

let resizeObserver: ResizeObserver | undefined;

const labels = {
    resume: 'Resume',
    license: 'License',
    certification: 'Certification',
};

const dimensions = computed(() => {
    if (!info.value) {
        return { width: 0, height: 0 };
    }

    const swapped = rotation.value % 180 !== 0;

    return {
        width: swapped ? info.value.height : info.value.width,
        height: swapped ? info.value.width : info.value.height,
    };
});

const imageFrameStyle = computed(() => ({
    width: `${dimensions.value.width * scale.value}px`,
    height: `${dimensions.value.height * scale.value}px`,
}));

const imageStyle = computed(() => ({
    width: `${(info.value?.width ?? 0) * scale.value}px`,
    height: `${(info.value?.height ?? 0) * scale.value}px`,
    maxWidth: 'none',
    transform:
        `translate(-50%, -50%) rotate(${rotation.value}deg)`,
}));

function formatSize(bytes: number) {
    return bytes >= 1024 * 1024
        ? `${(bytes / (1024 * 1024)).toFixed(2)} MB`
        : `${(bytes / 1024).toFixed(0)} KB`;
}

function fit() {
    viewMode.value = 'fit';

    if (!viewport.value || !info.value) {
        return;
    }

    const width = Math.max(1, viewport.value.clientWidth - 32);
    const height = Math.max(1, viewport.value.clientHeight - 32);

    scale.value = Math.max(
        0.001,
        Math.min(
            3,
            width / dimensions.value.width,
            height / dimensions.value.height,
        ),
    );
}

function setZoom(value: number) {
    viewMode.value = 'custom';
    scale.value = Math.max(0.1, Math.min(3, value));
}

function rotate() {
    rotation.value = (rotation.value + 90) % 360;

    if (viewMode.value === 'fit') {
        void nextTick(fit);
    }
}

function receiveInfo(value: ProfilePreviewInfo, id: number) {
    if (id !== props.selectedId) {
        return;
    }

    info.value = value;
    void nextTick(fit);
}

function imageReady(event: Event, id: number) {
    const image = event.target as HTMLImageElement;

    receiveInfo({
        width: image.naturalWidth,
        height: image.naturalHeight,
    }, id);
}

function imageFailed(id: number) {
    if (id === props.selectedId) {
        imageError.value = true;
    }
}

function move(offset: number) {
    const document = props.documents[index.value + offset];

    if (document) {
        emit('select', document.id);
    }
}

watch(() => props.selectedId, () => {
    info.value = null;
    imageError.value = false;
    rotation.value = 0;
    scale.value = 1;
    viewMode.value = 'fit';

    void nextTick(() => {
        viewport.value?.scrollTo({ top: 0, left: 0 });
    });
});

onMounted(() => {
    resizeObserver = new ResizeObserver(() => {
        if (viewMode.value === 'fit') {
            fit();
        }
    });

    if (viewport.value) {
        resizeObserver.observe(viewport.value);
    }
});

onBeforeUnmount(() => resizeObserver?.disconnect());
</script>

<template>
    <div
        v-if="current"
        class="grid min-h-0 flex-1 grid-rows-[auto_minmax(0,1fr)_auto] lg:grid-cols-[8rem_minmax(0,1fr)_14rem] lg:grid-rows-1"
    >
        <nav
            aria-label="Uploaded files"
            class="flex h-28 min-h-0 gap-2 overflow-x-auto border-b p-3 lg:block lg:h-auto lg:space-y-3 lg:overflow-x-hidden lg:overflow-y-auto lg:border-r lg:border-b-0"
        >
            <button
                v-for="(document, fileIndex) in documents"
                :key="document.id"
                type="button"
                :aria-pressed="document.id === selectedId"
                :aria-label="`Preview ${document.original_name}`"
                :title="document.original_name"
                class="w-24 shrink-0 rounded-lg border p-2 text-left focus-visible:outline-2 focus-visible:outline-primary lg:w-full"
                :class="document.id === selectedId
                    ? 'border-primary bg-accent ring-1 ring-primary'
                    : 'hover:bg-muted'"
                @click="emit('select', document.id)"
            >
                <span class="block h-12 overflow-hidden rounded bg-muted">
                    <PdfThumbnail
                        v-if="document.mime_type === 'application/pdf'"
                        :url="document.url"
                    />

                    <img
                        v-else
                        :src="document.url"
                        alt=""
                        loading="lazy"
                        class="h-full w-full object-contain"
                    />
                </span>

                <span class="mt-1 block truncate text-xs">
                    {{ document.original_name }}
                </span>

                <span class="block text-xs text-muted-foreground">
                    {{ fileIndex + 1 }} of {{ documents.length }}
                </span>
            </button>
        </nav>

        <div class="flex min-h-0 min-w-0 flex-col">
            <div
                class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b px-3 py-2"
            >
                <div class="flex items-center gap-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Previous file"
                        :disabled="index <= 0"
                        @click="move(-1)"
                    >
                        <ChevronLeft class="size-4" />
                    </Button>

                    <span class="text-xs">
                        {{ index + 1 }}/{{ documents.length }}
                    </span>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Next file"
                        :disabled="index >= documents.length - 1"
                        @click="move(1)"
                    >
                        <ChevronRight class="size-4" />
                    </Button>
                </div>

                <div class="flex items-center gap-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Zoom out"
                        :disabled="!info || scale <= 0.1"
                        @click="setZoom(scale - 0.25)"
                    >
                        <ZoomOut class="size-4" />
                    </Button>

                    <span
                        class="min-w-12 text-center text-xs"
                        aria-live="polite"
                    >
                        {{ Math.round(scale * 100) }}%
                    </span>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Zoom in"
                        :disabled="!info || scale >= 3"
                        @click="setZoom(scale + 0.25)"
                    >
                        <ZoomIn class="size-4" />
                    </Button>
                </div>
            </div>

            <div
                ref="viewport"
                tabindex="0"
                role="region"
                :aria-label="`Preview of ${current.original_name}`"
                class="min-h-0 flex-1 overflow-auto bg-muted/50 p-4"
            >
                <PdfDocument
                    v-if="isPdf"
                    :key="current.id"
                    :url="current.url"
                    :scale="scale"
                    :rotation="rotation"
                    @ready="receiveInfo($event, current.id)"
                />

                <template v-else>
                    <p
                        v-if="imageError"
                        role="alert"
                        class="p-6 text-center text-sm text-destructive"
                    >
                        This image could not be loaded.
                    </p>

                    <div
                        v-else
                        class="grid min-h-full w-max min-w-full place-items-center"
                    >
                        <p
                            v-if="!info"
                            class="p-6 text-sm text-muted-foreground"
                        >
                            Loading image...
                        </p>

                        <div
                            v-show="info"
                            class="relative shrink-0"
                            :style="imageFrameStyle"
                        >
                            <img
                                :key="current.id"
                                :src="current.url"
                                :alt="current.original_name"
                                :draggable="false"
                                class="absolute top-1/2 left-1/2"
                                :style="imageStyle"
                                @load="imageReady($event, current.id)"
                                @error="imageFailed(current.id)"
                            />
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <aside
            class="max-h-36 overflow-y-auto border-t p-4 lg:max-h-none lg:border-t-0 lg:border-l"
        >
            <h3 class="font-semibold">File details</h3>

            <dl class="mt-4 space-y-4 text-sm">
                <div>
                    <dt class="text-xs text-muted-foreground">File name</dt>
                    <dd class="mt-1 break-words">
                        {{ current.original_name }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-muted-foreground">Category</dt>
                    <dd class="mt-1">{{ labels[current.category] }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-muted-foreground">File type</dt>
                    <dd class="mt-1">
                        {{ current.mime_type.split('/')[1].toUpperCase() }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-muted-foreground">File size</dt>
                    <dd class="mt-1">{{ formatSize(current.size) }}</dd>
                </div>

                <div v-if="info?.pages">
                    <dt class="text-xs text-muted-foreground">Pages</dt>
                    <dd class="mt-1">{{ info.pages }}</dd>
                </div>

                <div v-if="info">
                    <dt class="text-xs text-muted-foreground">
                        {{ isPdf ? 'First page at 100%' : 'Dimensions' }}
                    </dt>
                    <dd class="mt-1">
                        {{ Math.round(info.width) }}
                        × {{ Math.round(info.height) }} px
                    </dd>
                </div>
            </dl>

            <div class="mt-6 space-y-2 border-t pt-4">
                <h3 class="mb-3 font-semibold">View options</h3>

                <Button
                    type="button"
                    variant="outline"
                    class="w-full justify-start"
                    :disabled="!info"
                    @click="fit"
                >
                    <Maximize class="size-4" />
                    Fit to screen
                </Button>

                <Button
                    type="button"
                    variant="outline"
                    class="w-full justify-start"
                    :disabled="!info"
                    @click="setZoom(1)"
                >
                    <ZoomIn class="size-4" />
                    Actual size (100%)
                </Button>

                <Button
                    type="button"
                    variant="outline"
                    class="w-full justify-start"
                    :disabled="!info"
                    @click="rotate"
                >
                    <RotateCw class="size-4" />
                    Rotate
                </Button>
            </div>
        </aside>
    </div>
</template>
