<script setup lang="ts">
import L from 'leaflet';
import { onBeforeUnmount, onMounted, ref, shallowRef, watch } from 'vue';
import { iconSvg } from '@/lib/markerIcons';
import type {
    MapData,
    MapMarker,
    MapNote,
    MarkerTypeData,
    RoutePoint,
} from '@/types/game';

/**
 * Leaflet map using a flat CRS. All app coordinates are percentages of the
 * map image (x from the left, y from the top, 0-100), so they survive base
 * image replacements with a different resolution.
 */
const props = withDefaults(
    defineProps<{
        map: MapData;
        markers: MapMarker[];
        types: Record<number, MarkerTypeData>;
        visibleTypeIds: number[];
        selectedId?: number | null;
        highlightIds?: number[];
        showLabels?: boolean;
        favorites?: number[];
        discovered?: number[];
        notes?: MapNote[];
        routePoints?: RoutePoint[];
        draggable?: boolean;
        crosshair?: boolean;
    }>(),
    {
        selectedId: null,
        highlightIds: () => [],
        showLabels: false,
        favorites: () => [],
        discovered: () => [],
        notes: () => [],
        routePoints: () => [],
        draggable: false,
        crosshair: false,
    },
);

const emit = defineEmits<{
    select: [id: number];
    mapClick: [point: { x: number; y: number }];
    markerMoved: [payload: { id: number; x: number; y: number }];
    noteSelect: [id: number];
    cursor: [point: { x: number; y: number } | null];
}>();

const container = ref<HTMLDivElement | null>(null);
const leaflet = shallowRef<L.Map | null>(null);
const markerLayer = L.layerGroup();
const shapeLayer = L.layerGroup();
const noteLayer = L.layerGroup();
const routeLayer = L.layerGroup();
const markerIndex = new Map<number, L.Marker>();
let baseLayer: L.Layer | null = null;
let fitZoom = 0;

const width = () => props.map.width || 1000;
const height = () => props.map.height || 1000;

function toLatLng(x: number, y: number): L.LatLng {
    return L.latLng((-y / 100) * height(), (x / 100) * width());
}

function fromLatLng(latlng: L.LatLng): { x: number; y: number } {
    const clamp = (v: number) => Math.min(100, Math.max(0, v));

    return {
        x: Math.round(clamp((latlng.lng / width()) * 100) * 10000) / 10000,
        y: Math.round(clamp((-latlng.lat / height()) * 100) * 10000) / 10000,
    };
}

function bounds(): L.LatLngBounds {
    return L.latLngBounds([-height(), 0], [0, width()]);
}

/**
 * Original schematic grid used until a legally sourced base image exists.
 * The lettered grid gives players a shared vocabulary ("C4") for positions.
 */
function schematicLayer(): L.SVGOverlay {
    const w = width();
    const h = height();
    const cells = 10;
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', `0 0 ${w} ${h}`);
    const stroke = 'hsl(220 16% 16%)';
    let content = `<rect width="${w}" height="${h}" fill="hsl(222 24% 7%)"/>`;

    for (let i = 0; i <= cells; i++) {
        const x = (w / cells) * i;
        const y = (h / cells) * i;
        content += `<line x1="${x}" y1="0" x2="${x}" y2="${h}" stroke="${stroke}" stroke-width="${i % 5 === 0 ? 2 : 1}"/>`;
        content += `<line x1="0" y1="${y}" x2="${w}" y2="${y}" stroke="${stroke}" stroke-width="${i % 5 === 0 ? 2 : 1}"/>`;
    }

    for (let i = 0; i < cells; i++) {
        const letter = String.fromCharCode(65 + i);
        content += `<text x="${(w / cells) * (i + 0.5)}" y="${h * 0.02 + 10}" fill="hsl(216 12% 36%)" font-size="${w / 60}" text-anchor="middle" font-family="sans-serif">${letter}</text>`;
        content += `<text x="${w * 0.012}" y="${(h / cells) * (i + 0.5)}" fill="hsl(216 12% 36%)" font-size="${w / 60}" font-family="sans-serif">${i + 1}</text>`;
    }

    svg.innerHTML = content;

    return L.svgOverlay(svg, bounds(), { interactive: false });
}

function setupBase(): void {
    const instance = leaflet.value;

    if (!instance) {
        return;
    }

    if (baseLayer) {
        instance.removeLayer(baseLayer);
    }

    baseLayer = props.map.image_url
        ? L.imageOverlay(props.map.image_url, bounds(), {
              attribution: props.map.image_attribution ?? undefined,
          })
        : schematicLayer();
    baseLayer.addTo(instance);

    const size = instance.getSize();
    fitZoom = instance.getBoundsZoom(bounds(), false, L.point(24, 24));
    // Before the container has a size Leaflet reports Infinity; fall back safely.
    if (!Number.isFinite(fitZoom) || size.x === 0) {
        fitZoom = -1;
    }

    instance.setMinZoom(fitZoom - 1);
    instance.setMaxZoom(fitZoom + 5);
    instance.setMaxBounds(bounds().pad(0.25));
    instance.fitBounds(bounds(), { padding: [24, 24] });
}

function markerClasses(marker: MapMarker): string {
    const classes = ['map-marker'];

    if (marker.id === props.selectedId) {
        classes.push('is-selected');
    }

    if (props.highlightIds.length && !props.highlightIds.includes(marker.id)) {
        classes.push('is-dimmed');
    }

    if (marker.status !== 'published') {
        classes.push('is-draft');
    }

    if (props.favorites.includes(marker.id)) {
        classes.push('is-favorite');
    }

    if (props.discovered.includes(marker.id)) {
        classes.push('is-discovered');
    }

    return classes.join(' ');
}

function markerIcon(marker: MapMarker): L.DivIcon {
    const type = props.types[marker.type_id];
    const size = marker.id === props.selectedId ? 30 : 24;

    return L.divIcon({
        className: '',
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
        html: `<div class="${markerClasses(marker)}" style="position:relative;width:${size}px;height:${size}px;background:${type?.color ?? '#94a3b8'};color:${type?.color ?? '#94a3b8'}">${iconSvg(type?.icon ?? 'map-pin')}</div>`,
    });
}

function renderMarkers(): void {
    markerLayer.clearLayers();
    shapeLayer.clearLayers();
    markerIndex.clear();

    const visible = new Set(props.visibleTypeIds);

    for (const marker of props.markers) {
        if (
            marker.x === null ||
            marker.y === null ||
            !visible.has(marker.type_id)
        ) {
            continue;
        }

        const type = props.types[marker.type_id];

        if (marker.geometry && marker.geometry.length > 1) {
            const latlngs = marker.geometry.map(([x, y]) => toLatLng(x, y));
            const style = {
                color: type?.color ?? '#94a3b8',
                weight: 2,
                opacity: 0.9,
                fillOpacity: 0.12,
                dashArray: type?.geometry === 'polyline' ? undefined : '6 4',
            };
            const shape =
                type?.geometry === 'polyline'
                    ? L.polyline(latlngs, style)
                    : L.polygon(latlngs, style);
            shape.on('click', () => emit('select', marker.id));
            shapeLayer.addLayer(shape);
        }

        const leafletMarker = L.marker(toLatLng(marker.x, marker.y), {
            icon: markerIcon(marker),
            title: marker.name,
            alt: marker.name,
            keyboard: true,
            riseOnHover: true,
            draggable: props.draggable,
            zIndexOffset: marker.id === props.selectedId ? 1000 : 0,
        });

        leafletMarker.on('click', () => emit('select', marker.id));
        leafletMarker.on('dragend', () => {
            const point = fromLatLng(leafletMarker.getLatLng());
            emit('markerMoved', { id: marker.id, ...point });
        });

        if (props.showLabels) {
            leafletMarker.bindTooltip(marker.name, {
                permanent: true,
                direction: 'right',
                offset: [12, 0],
                className: 'map-label',
            });
        }

        markerIndex.set(marker.id, leafletMarker);
        markerLayer.addLayer(leafletMarker);
    }
}

function renderNotes(): void {
    noteLayer.clearLayers();

    for (const note of props.notes) {
        const color = note.color ?? '#facc15';
        const icon = L.divIcon({
            className: '',
            iconSize: [22, 22],
            iconAnchor: [0, 22],
            html: `<div class="map-note-marker" style="width:22px;height:22px;background:${color}">${iconSvg('sticky-note')}</div>`,
        });
        const noteMarker = L.marker(toLatLng(note.x, note.y), {
            icon,
            title: `Note: ${note.title}`,
            keyboard: true,
        });
        noteMarker.on('click', () => emit('noteSelect', note.id));
        noteLayer.addLayer(noteMarker);
    }
}

function renderRoute(): void {
    routeLayer.clearLayers();

    if (!props.routePoints.length) {
        return;
    }

    const latlngs = props.routePoints.map((p) => toLatLng(p.x, p.y));
    routeLayer.addLayer(
        L.polyline(latlngs, {
            color: '#f59e0b',
            weight: 4,
            opacity: 0.9,
            dashArray: '10 8',
            interactive: false,
        }),
    );

    props.routePoints.forEach((point, index) => {
        routeLayer.addLayer(
            L.marker(toLatLng(point.x, point.y), {
                interactive: false,
                keyboard: false,
                icon: L.divIcon({
                    className: '',
                    iconSize: [22, 22],
                    iconAnchor: [11, 11],
                    html: `<div class="route-stop" style="width:22px;height:22px">${index + 1}</div>`,
                }),
            }),
        );
    });
}

// Until the player moves the map, keep it fitted when the container resizes
// (e.g. the sidebar collapsing on mobile right after mount).
let interacted = false;

function flyTo(x: number, y: number, zoomDelta = 2): void {
    interacted = true;
    leaflet.value?.flyTo(toLatLng(x, y), fitZoom + zoomDelta, {
        duration: 0.6,
    });
}

function resetView(): void {
    leaflet.value?.flyToBounds(bounds(), { padding: [24, 24], duration: 0.5 });
}

function zoomBy(delta: number): void {
    interacted = true;
    const instance = leaflet.value;
    instance?.setZoom(instance.getZoom() + delta);
}

function invalidateSize(): void {
    leaflet.value?.invalidateSize();
}

defineExpose({ flyTo, resetView, zoomBy, invalidateSize });

let resizeObserver: ResizeObserver | null = null;

onMounted(() => {
    if (!container.value) {
        return;
    }

    const instance = L.map(container.value, {
        crs: L.CRS.Simple,
        zoomSnap: 0.25,
        zoomDelta: 0.5,
        wheelPxPerZoomLevel: 90,
        zoomControl: false,
        attributionControl: true,
        maxBoundsViscosity: 0.8,
        keyboard: true,
    });
    instance.attributionControl.setPrefix(false);
    leaflet.value = instance;

    shapeLayer.addTo(instance);
    markerLayer.addTo(instance);
    noteLayer.addTo(instance);
    routeLayer.addTo(instance);

    setupBase();
    renderMarkers();
    renderNotes();
    renderRoute();

    instance.on('click', (event: L.LeafletMouseEvent) => {
        emit('mapClick', fromLatLng(event.latlng));
    });
    instance.on('mousemove', (event: L.LeafletMouseEvent) => {
        emit('cursor', fromLatLng(event.latlng));
    });
    instance.on('mouseout', () => emit('cursor', null));

    instance.on('dragstart', () => (interacted = true));
    container.value.addEventListener('wheel', () => (interacted = true), {
        passive: true,
    });
    container.value.addEventListener('touchstart', () => (interacted = true), {
        passive: true,
    });

    resizeObserver = new ResizeObserver(() => {
        instance.invalidateSize();

        if (!interacted) {
            setupBase();
        }
    });
    resizeObserver.observe(container.value);
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    leaflet.value?.remove();
    leaflet.value = null;
});

watch(
    () => [
        props.map.slug,
        props.map.image_url,
        props.map.width,
        props.map.height,
    ],
    () => setupBase(),
);

watch(
    () => [
        props.markers,
        props.visibleTypeIds,
        props.selectedId,
        props.highlightIds,
        props.showLabels,
        props.favorites,
        props.discovered,
        props.draggable,
    ],
    () => renderMarkers(),
    { deep: true },
);

watch(() => props.notes, renderNotes, { deep: true });
watch(() => props.routePoints, renderRoute, { deep: true });

watch(
    () => props.crosshair,
    (on) => {
        container.value?.classList.toggle('cursor-crosshair', on);
    },
    { immediate: true },
);
</script>

<template>
    <div
        ref="container"
        class="h-full w-full"
        role="application"
        :aria-label="`Interactive map of ${map.name}`"
    />
</template>

<style scoped>
.cursor-crosshair :deep(.leaflet-grab),
.cursor-crosshair {
    cursor: crosshair;
}
</style>
