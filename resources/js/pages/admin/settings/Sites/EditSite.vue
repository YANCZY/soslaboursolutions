<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Site } from '@/types/site';

const props = defineProps<{
    open: boolean;
    site: Site | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'pin-location': [siteId: number];
}>();

const form = useForm({
    site_name: '',
    street: '',
    city: '',
    state: '',
    postcode: '',
    country: '',
});

const fields = [
    { key: 'site_name', label: 'Site name', maxLength: 255, required: false },
    { key: 'street', label: 'Street', maxLength: 255, required: true },
    { key: 'city', label: 'Suburb/City', maxLength: 100, required: true },
    { key: 'state', label: 'State', maxLength: 100, required: true },
    { key: 'postcode', label: 'Postcode', maxLength: 20, required: true },
    { key: 'country', label: 'Country', maxLength: 100, required: true },
] as const;

watch(
    [() => props.open, () => props.site?.id],
    ([open]) => {
        if (!open || !props.site) {
            return;
        }

        form.clearErrors();

        form.site_name = props.site.site_name ?? '';
        form.street = props.site.street ?? '';
        form.city = props.site.city ?? '';
        form.state = props.site.state ?? '';
        form.postcode = props.site.postcode ?? '';
        form.country = props.site.country ?? '';
    },
    { immediate: true },
);

const updateOpen = (open: boolean) => {
    if (form.processing) {
        return;
    }

    emit('update:open', open);
};

const save = (pinAfterSave = false) => {
    if (!props.site || form.processing) {
        return;
    }

    const siteId = props.site.id;

    form.patch(`/settings/company/sites/${siteId}`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            emit('update:open', false);
            toast.success('Site updated.');

            if (pinAfterSave) {
                emit('pin-location', siteId);
            }
        },
        onError: () => {
            toast.error(
                'Unable to save the site. Check the highlighted fields.',
            );
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="updateOpen">
        <DialogContent
            class="max-h-[85vh] overflow-y-auto sm:max-w-2xl"
            :show-close-button="!form.processing"
            @escape-key-down="(event) => {
                if (form.processing) event.preventDefault();
            }"
            @interact-outside="(event) => {
                if (form.processing) event.preventDefault();
            }"
        >
            <DialogHeader>
                <DialogTitle>Edit site</DialogTitle>
                <DialogDescription>
                    Update the site's name and address.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="save()">
                <div class="space-y-2">
                    <Label for="edit-site-company">Company</Label>
                    <Input
                        id="edit-site-company"
                        :model-value="site?.company.company_name ?? ''"
                        readonly
                    />
                </div>

                <fieldset
                    :disabled="form.processing"
                    class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2"
                >
                    <div
                        v-for="field in fields"
                        :key="field.key"
                        class="min-w-0 space-y-2"
                        :class="field.key === 'street' ? 'sm:col-span-2' : ''"
                    >
                        <Label :for="`edit-site-${field.key}`">
                            {{ field.label }}{{ field.required ? ' *' : '' }}
                        </Label>

                        <Input
                            :id="`edit-site-${field.key}`"
                            v-model="form[field.key]"
                            :maxlength="field.maxLength"
                            :required="field.required"
                            :aria-invalid="Boolean(form.errors[field.key])"
                            :aria-describedby="
                                form.errors[field.key]
                                    ? `edit-site-${field.key}-error`
                                    : undefined
                            "
                        />

                        <p
                            v-if="form.errors[field.key]"
                            :id="`edit-site-${field.key}-error`"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors[field.key] }}
                        </p>
                    </div>
                </fieldset>

                <div v-if="site" class="space-y-3 border-t pt-4">
                    <p class="text-sm">
                        Location:
                        {{ site.geofence_pinned_at ? 'Confirmed' : 'Not pinned' }}
                    </p>

                    <p class="text-sm text-muted-foreground">
                        Save your changes, then select the workplace location on the map.
                        Changing the address requires confirming the location again.
                    </p>

                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing || !site.is_active"
                        @click="save(true)"
                    >
                        {{ form.processing ? 'Saving...' : 'Save & Pin Location' }}
                    </Button>

                    <p v-if="!site.is_active" class="text-sm text-muted-foreground">
                        Activate this site before pinning its location.
                    </p>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="updateOpen(false)"
                    >
                        Cancel
                    </Button>

                    <Button
                        type="submit"
                        :disabled="form.processing || !site"
                    >
                        {{ form.processing ? 'Saving...' : 'Save changes' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
