<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import type { Map as LeafletMap, Marker } from 'leaflet';
import { onBeforeUnmount, ref, watch } from 'vue';
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

const props = defineProps<{
    name: string;
    address: string;
    latitude: number | null;
    longitude: number | null;
    radiusMeters: number | null;
    saveUrl: string;
}>();
const emit = defineEmits<{ close: [] }>();

const mapElement = ref<HTMLDivElement | null>(null);
const ready = ref(false);
const mapError = ref('');
const locating = ref(false);

const page = usePage<{
    default_max_distance_meters: number;
}>();

const form = useForm<{
    latitude: number | null;
    longitude: number | null;
    max_distance: number;
    max_distance_unit: 'm' | 'km';
}>({
    latitude: props.latitude,
    longitude: props.longitude,
    max_distance:
        props.radiusMeters ?? page.props.default_max_distance_meters,
    max_distance_unit: 'm',
});

watch(
    () => form.max_distance_unit,
    (unit, previousUnit) => {
        if (unit === previousUnit) {
            return;
        }

        const value = Number(form.max_distance);

        if (!Number.isFinite(value) || value <= 0) {
            return;
        }

        const converted = unit === 'km'
            ? value / 1000
            : value * 1000;

        form.max_distance = Number(converted.toFixed(3));

        form.clearErrors('max_distance', 'max_distance_unit');
    },
);

let map: LeafletMap | undefined;
let marker: Marker | undefined;
let observer: ResizeObserver | undefined;
let disposed = false;
let placePin: ((latitude: number, longitude: number) => void) | undefined;

const close = () => {
    if (!form.processing) {
        emit('close');
    }
};

watch(mapElement, async (element) => {
    if (!element || map) {
        return;
    }

    try {
        const L = await import('leaflet');

        if (disposed || mapElement.value !== element) {
            return;
        }

        map = L.map(element).setView([0, 0], 2);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map).on('tileerror', () => {
            mapError.value = 'Some map tiles could not load. Check your connection.';
        });

        const icon = L.divIcon({
            className: '',
            html: '<span style="display:block;width:24px;height:24px;border:3px solid white;border-radius:50%;background:#dc2626;box-shadow:0 1px 5px #0008"></span>',
            iconSize: [24, 24],
            iconAnchor: [12, 12],
        });

        placePin = (latitude, longitude) => {
            if (!map || form.processing) {
                return;
            }

            const point = L.latLng(latitude, longitude).wrap();

            form.latitude = Number(point.lat.toFixed(7));
            form.longitude = Number(point.lng.toFixed(7));
            form.clearErrors();

            if (marker) {
                marker.setLatLng(point);
            } else {
                marker = L.marker(point, {
                    icon,
                    draggable: true,
                    title: 'Workplace location',
                }).addTo(map);

                marker.on('dragend', () => {
                    const position = marker!.getLatLng();
                    placePin?.(position.lat, position.lng);
                });
            }
        };

        map.on('click', (event) => {
            placePin?.(event.latlng.lat, event.latlng.lng);
        });

        if (form.latitude !== null && form.longitude !== null) {
            placePin(form.latitude, form.longitude);
            map.setView([form.latitude, form.longitude], 18);
        }

        observer = new ResizeObserver(() => map?.invalidateSize());
        observer.observe(element);
        ready.value = true;
    } catch {
        mapError.value = 'Unable to open the map. Reload and try again.';
    }
}, { flush: 'post' });

const locate = () => {
    if (!navigator.geolocation) {
        toast.error('Your browser does not support location access.');

        return;
    }

    locating.value = true;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            if (disposed) {
                return;
            }

            // Navigate only. The administrator still chooses the pin.
            map?.setView([
                position.coords.latitude,
                position.coords.longitude,
            ], 18);
            locating.value = false;
        },
        () => {
            if (disposed) {
                return;
            }

            locating.value = false;
            toast.error('Unable to access your location. Navigate the map manually.');
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
    );
};

const pinCentre = () => {
    const centre = map?.getCenter();

    if (centre) {
        placePin?.(centre.lat, centre.lng);
    }
};

const save = () => {
    if (
        !ready.value
        || form.processing
        || form.latitude === null
        || form.longitude === null
    ) {
        return;
    }

    form.clearErrors('max_distance', 'max_distance_unit');

    const distanceValue = Number(form.max_distance);

    if (!Number.isFinite(distanceValue) || distanceValue <= 0) {
        form.setError(
            'max_distance',
            'Enter a Max Distance greater than zero.',
        );
        
        return;
    }

    marker?.dragging?.disable();

    form.patch(props.saveUrl, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Workplace location saved.');
            emit('close');
        },
        onError: () => toast.error('Unable to save the workplace location.'),
        onFinish: () => marker?.dragging?.enable(),
    });
};

onBeforeUnmount(() => {
    disposed = true;
    observer?.disconnect();
    map?.remove();
    map = undefined;
    marker = undefined;
});
</script>

<template>
    <Dialog :open="true" @update:open="(open) => { if (!open) close(); }">
        <DialogContent
            class="max-h-[90vh] overflow-y-auto sm:max-w-3xl"
            :show-close-button="!form.processing"
            @escape-key-down="(event) => {
                if (form.processing) event.preventDefault();
            }"
            @interact-outside="(event) => {
                if (form.processing) event.preventDefault();
            }"
        >
            <DialogHeader>
                <DialogTitle>Pin Location</DialogTitle>
                <DialogDescription>
                    {{ name }}:
                    select the actual workplace, then save its location.
                </DialogDescription>
            </DialogHeader>

            <p class="text-sm text-muted-foreground">
                {{ address }}
            </p>

            <div class="flex flex-wrap gap-2">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="!ready || locating || form.processing"
                    @click="locate"
                >
                    {{ locating ? 'Finding your location...' : 'Go to my location' }}
                </Button>

                <Button
                    type="button"
                    variant="outline"
                    :disabled="!ready || form.processing"
                    @click="pinCentre"
                >
                    Pin map centre
                </Button>
            </div>

            <p class="text-sm text-muted-foreground">
                Click the map or drag the marker. You can also pan using
                the arrow keys and select “Pin map centre”.
            </p>

            <fieldset :disabled="form.processing" class="space-y-2">
                <label
                    for="workplace-max-distance"
                    class="text-sm font-medium"
                >
                    Max Distance
                </label>

                <div class="flex items-center gap-2">
                    <input
                        id="workplace-max-distance"
                        v-model.number="form.max_distance"
                        type="number"
                        required
                        :min="form.max_distance_unit === 'm' ? 1 : 0.001"
                        :step="form.max_distance_unit === 'm' ? 1 : 0.001"
                        :aria-invalid="Boolean(form.errors.max_distance)"
                        class="h-10 min-w-0 flex-1 rounded-md border bg-background px-3 text-sm"
                    >

                    <select
                        v-model="form.max_distance_unit"
                        aria-label="Max Distance unit"
                        :aria-invalid="Boolean(form.errors.max_distance_unit)"
                        class="h-10 rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="m">m</option>
                        <option value="km">km</option>
                    </select>
                </div>

                <p class="text-sm text-muted-foreground">
                    The reported position must be within this distance
                    of the saved workplace pin.
                </p>
            </fieldset>

            <div
                ref="mapElement"
                class="relative z-0 h-[45vh] min-h-64 min-w-0 w-full overflow-hidden rounded-md border"
                aria-label="Select workplace location on the map"
            />

            <p v-if="mapError" role="alert" class="text-sm text-destructive">
                {{ mapError }}
            </p>

            <p class="text-sm" aria-live="polite">
                Latitude: {{ form.latitude ?? 'Select a location' }}<br>
                Longitude: {{ form.longitude ?? 'Select a location' }}
            </p>

            <p
                v-for="(error, field) in form.errors"
                :key="field"
                role="alert"
                class="text-sm text-destructive"
            >
                {{ error }}
            </p>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="form.processing"
                    @click="close"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    :disabled="
                        !ready
                        || form.processing
                        || form.latitude === null
                        || form.longitude === null
                    "
                    @click="save"
                >
                    {{ form.processing ? 'Saving...' : 'Save Location' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
