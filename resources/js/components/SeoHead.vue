<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Renders the `seo` prop built server-side (App\Support\Seo). The root Blade
// view prints the same tags on first load; this keeps them current on visits.
const page = usePage();
const seo = computed(() => page.props.seo);
const fullTitle = computed(() =>
    seo.value.title
        ? `${seo.value.title} - ${page.props.name}`
        : page.props.name,
);
</script>

<template>
    <Head :title="seo.title ?? ''">
        <link head-key="canonical" rel="canonical" :href="seo.url" />
        <meta
            head-key="description"
            name="description"
            :content="seo.description"
        />
        <meta head-key="og:title" property="og:title" :content="fullTitle" />
        <meta
            head-key="og:description"
            property="og:description"
            :content="seo.description"
        />
        <meta head-key="og:url" property="og:url" :content="seo.url" />
        <slot />
    </Head>
</template>
