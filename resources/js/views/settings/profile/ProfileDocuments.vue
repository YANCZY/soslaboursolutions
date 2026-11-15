<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import ProfileDocumentCard from '@/components/ProfileDocumentCard.vue';
import ProfileDocumentPreview from '@/components/ProfileDocumentPreview.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type { ProfileDocumentItem } from '@/types/profile-document';


const props = defineProps<{ documents: ProfileDocumentItem[] }>();

const MAX_FILES = 15;
const INLINE_LIMIT = 10;

type DialogMode = 'all' | 'upload' | 'remove' | 'preview' | null;

const mode = ref<DialogMode>(null);
const returnMode = ref<'all' | null>(null);
const selected = ref<ProfileDocumentItem | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const deleteError = ref('');

const previewId = ref<number | null>(null);

const previewDocument = computed(() =>
    props.documents.find((document) => document.id === previewId.value),
);

function openPreview(document: ProfileDocumentItem) {
    previewId.value = document.id;
    mode.value = 'preview';
}


const count = computed(() => props.documents.length);
const remaining = computed(() => Math.max(0, MAX_FILES - count.value));
const visibleDocuments = computed(
    () => props.documents.slice(0, INLINE_LIMIT),
);

const form = useForm({
    category: 'resume' as ProfileDocumentItem['category'],
    documents: [] as File[],
});

const removeForm = useForm({});
const busy = computed(() => form.processing || removeForm.processing);

const uploadErrors = computed(() =>
    Object.entries(form.errors)
        .filter(([key]) => key.startsWith('documents'))
        .map(([, message]) => message),
);

const dialogTitle = computed(() => {

    if (mode.value === 'all') {
        return 'Resume, License & Certification';
    }

    if (mode.value === 'upload') {
        return 'Add documents';
    }

    if (mode.value === 'preview') {
        return 'File preview';
    }

    return 'Remove this document?';
});

function changeOpen(open: boolean) {
    if (!open && !busy.value) {
        mode.value = null;
        selected.value = null;
    }
}

function openAdd() {
    if (remaining.value === 0) {
        return;
    }

    returnMode.value = mode.value === 'all' ? 'all' : null;
    form.reset();
    form.clearErrors();
    mode.value = 'upload';
}

function chooseFiles(event: Event) {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);

    form.documents = [];
    form.clearErrors();

    if (files.length > remaining.value) {
        form.setError(
            'documents',
            `You have ${remaining.value} available slots. Please select fewer files.`,
        );
        input.value = '';

        return;
    }

    for (const file of files) {
        if (!/\.(pdf|png|jpe?g|webp|svg)$/i.test(file.name)) {
            form.setError(
                'documents',
                `${file.name}: only PDF, PNG, JPG, JPEG, WebP and SVG files are accepted.`,
            );
            input.value = '';

            return;
        }

        const isSvg = /\.svg$/i.test(file.name);
        const limit = (isSvg ? 1 : 10) * 1024 * 1024;

        if (file.size > limit) {
            form.setError(
                'documents',
                `${file.name}: maximum ${isSvg ? 1 : 10} MB per file.`,
            );
            input.value = '';

            return;
        }
    }

    form.documents = files;
}

function upload() {
    if (
        form.documents.length === 0
        || form.documents.length > remaining.value
    ) {
        form.setError(
            'documents',
            `Please select between 1 and ${remaining.value} files.`,
        );

        return;
    }

    form.post('/settings/profile/documents', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            if (fileInput.value) {
                fileInput.value.value = '';
            }

            form.reset();
            mode.value = returnMode.value;
        },
    });
}

function requestRemoval(document: ProfileDocumentItem) {
    returnMode.value = mode.value === 'all' ? 'all' : null;
    selected.value = document;
    deleteError.value = '';
    removeForm.clearErrors();
    mode.value = 'remove';
}

function remove() {
    if (!selected.value || removeForm.processing) {
        return;
    }

    deleteError.value = '';

    removeForm.delete(
        `/settings/profile/documents/${selected.value.id}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                selected.value = null;
                mode.value = returnMode.value;
            },
            onError: (errors) => {
                deleteError.value = Object.values(errors).join(' ')
                    || 'The file could not be removed. Please retry.';
            },
        },
    );
}

function cancelAction() {
    selected.value = null;
    mode.value = returnMode.value;
}
</script>

<template>
    <section
        class="min-w-0 rounded-2xl border bg-card p-5 text-card-foreground shadow-sm"
        aria-labelledby="profile-documents-heading"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2
                    id="profile-documents-heading"
                    class="text-lg font-semibold"
                >
                    Resume, License &amp; Certification
                </h2>

                <p class="mt-1 text-sm text-muted-foreground">
                    {{ count }}/{{ MAX_FILES }} files attached
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Button
                    v-if="count >= INLINE_LIMIT"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="mode = 'all'"
                >
                    Show All
                </Button>

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="remaining === 0"
                    @click="openAdd"
                >
                    <Plus class="size-4" />
                    Add
                </Button>
            </div>
        </div>

        <div
            v-if="count === 0"
            class="mt-4 rounded-lg border border-dashed p-5 text-center text-sm text-muted-foreground"
        >
            Add your resume, licenses or certifications.
        </div>

        <div
            v-else
            tabindex="0"
            role="region"
            aria-label="Attached documents. Scroll horizontally to see more."
            class="mt-4 flex min-w-0 max-w-full snap-x gap-3 overflow-x-auto pb-3"
        >
            <ProfileDocumentCard
                v-for="document in visibleDocuments"
                :key="document.id"
                :document="document"
                @preview="openPreview"
                @remove="requestRemoval"
            />
        </div>

        <p
            v-if="count > INLINE_LIMIT"
            class="mt-1 text-xs text-muted-foreground"
        >
            Showing {{ INLINE_LIMIT }} of {{ count }} files.
            Select Show All to view every attachment.
        </p>

        <p
            v-if="remaining === 0"
            class="mt-2 text-xs text-muted-foreground"
        >
            You have reached the 15-file limit.
            Remove an attachment to add another.
        </p>

        <Dialog :open="mode !== null" @update:open="changeOpen">
            <DialogContent
                :show-close-button="!busy"
                :class="mode === 'preview'
                    ? 'flex h-[92dvh] w-[calc(100%-1rem)] min-w-0 flex-col gap-0 overflow-hidden p-0 sm:max-w-7xl'
                    : mode === 'all'
                        ? 'flex max-h-[85dvh] min-w-0 flex-col overflow-hidden sm:max-w-5xl'
                        : 'max-h-[85dvh] overflow-y-auto'"
            >
                <DialogHeader
                    :class="mode === 'preview'
                        ? 'shrink-0 border-b p-4 pr-12'
                        : ''"
                >
                    <DialogTitle>{{ dialogTitle }}</DialogTitle>

                    <DialogDescription v-if="mode === 'preview'">
                        {{ previewDocument?.original_name }}
                    </DialogDescription>

                    <DialogDescription v-else-if="mode === 'all'">
                        {{ count }}/{{ MAX_FILES }} files attached.
                        Scroll horizontally to view your documents.
                    </DialogDescription>

                    <DialogDescription v-else-if="mode === 'upload'">
                        Select multiple files.
                        {{ remaining }} slots available.
                        PDF, PNG, JPG, JPEG, WebP or SVG.
                        Maximum 10 MB each; SVG maximum 1 MB.
                    </DialogDescription>

                    <DialogDescription v-else>
                        {{ selected?.original_name }} will be removed from
                        your profile and deleted from storage.
                        This cannot be undone.
                    </DialogDescription>
                </DialogHeader>

                <!-- Preview Content Of File -->
                <ProfileDocumentPreview
                    v-if="mode === 'preview' && previewDocument"
                    :documents="documents"
                    :selected-id="previewDocument.id"
                    @select="previewId = $event"
                />

                <template v-if="mode === 'all'">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-muted-foreground">
                            {{ count }} attachments
                        </span>

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="remaining === 0"
                            @click="openAdd"
                        >
                            <Plus class="size-4" />
                            Add
                        </Button>
                    </div>

                    <div
                        tabindex="0"
                        role="region"
                        aria-label="All attached documents"
                        class="flex min-w-0 max-w-full snap-x gap-3 overflow-x-auto pb-3"
                    >
                        <ProfileDocumentCard
                            v-for="document in documents"
                            :key="document.id"
                            :document="document"
                            @preview="openPreview"
                            @remove="requestRemoval"
                        />
                    </div>

                    <p
                        v-if="count === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No files attached.
                    </p>
                </template>

                <form
                    v-else-if="mode === 'upload'"
                    class="space-y-5"
                    @submit.prevent="upload"
                >
                    <div class="space-y-2">
                        <Label for="document-category">
                            Document type
                        </Label>

                        <select
                            id="document-category"
                            v-model="form.category"
                            :disabled="form.processing"
                            class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                        >
                            <option value="resume">Resume</option>
                            <option value="license">License</option>
                            <option value="certification">
                                Certification
                            </option>
                        </select>

                        <p class="text-xs text-muted-foreground">
                            This type applies to all selected files.
                        </p>

                        <InputError :message="form.errors.category" />
                    </div>

                    <div class="space-y-2">
                        <Label for="profile-document-files">Files</Label>

                        <input
                            id="profile-document-files"
                            ref="fileInput"
                            type="file"
                            multiple
                            accept=".pdf,.png,.jpg,.jpeg,.webp,.svg"
                            :disabled="form.processing"
                            class="block w-full rounded-md border p-2 text-sm"
                            @change="chooseFiles"
                        />

                        <InputError
                            v-for="(message, index) in uploadErrors"
                            :key="index"
                            :message="message"
                        />
                    </div>

                    <div
                        v-if="form.documents.length"
                        class="space-y-2 rounded-lg border p-3"
                    >
                        <p class="text-sm font-medium">
                            {{ form.documents.length }} files selected
                        </p>

                        <ul
                            class="max-h-32 space-y-1 overflow-y-auto text-xs text-muted-foreground"
                        >
                            <li
                                v-for="(file, index) in form.documents"
                                :key="`${file.name}-${index}`"
                                class="break-words"
                            >
                                {{ file.name }}
                            </li>
                        </ul>
                    </div>

                    <p
                        v-if="form.processing"
                        class="text-sm text-muted-foreground"
                        aria-live="polite"
                    >
                        Uploading {{ form.progress?.percentage ?? 0 }}%
                    </p>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="form.processing"
                            @click="cancelAction"
                        >
                            Cancel
                        </Button>

                        <Button
                            type="submit"
                            :disabled="form.processing || !form.documents.length"
                        >
                            Upload
                            {{ form.documents.length || '' }}
                        </Button>
                    </DialogFooter>
                </form>

                <template v-else-if="mode === 'remove'">
                    <InputError :message="deleteError" />

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="removeForm.processing"
                            @click="cancelAction"
                        >
                            Cancel
                        </Button>

                        <Button
                            type="button"
                            variant="destructive"
                            :disabled="removeForm.processing"
                            @click="remove"
                        >
                            {{ removeForm.processing
                                ? 'Removing...'
                                : 'Remove file' }}
                        </Button>
                    </DialogFooter>
                </template>
            </DialogContent>
        </Dialog>
    </section>
</template>
