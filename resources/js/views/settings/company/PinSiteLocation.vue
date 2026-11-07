<script setup lang="ts">
import type { Site } from '@/types/site';
import PinLocation from '@/views/settings/company/PinLocation.vue';

defineProps<{ site: Site }>();
const emit = defineEmits<{ close: [] }>();
</script>

<template>
    <PinLocation
        :name="site.site_name || site.company.company_name"
        :address="[site.street, site.city, site.state, site.postcode, site.country].filter(Boolean).join(', ')"
        :latitude="site.geofence_latitude"
        :longitude="site.geofence_longitude"
        :radius-meters="site.geofence_radius_meters"
        :save-url="`/settings/company/sites/${site.id}/location`"
        @close="emit('close')"
    />
</template>
