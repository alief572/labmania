<script setup lang="ts">
import { appUrl } from '../composables/appUrl';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../layouts/AppLayout.vue';
import type { SharedProps } from '../types';
const page = usePage<SharedProps>();
</script>

<template>
    <Head title="Beranda" />
    <AppLayout title="Beranda">
        <h1>Beranda</h1><p class="muted">{{ page.props.auth?.nm_lengkap || page.props.auth?.username }}</p>
        <section class="panel" aria-labelledby="menu-title"><h2 id="menu-title">Menu Anda</h2>
            <ul class="home-links"><li v-for="item in page.props.navigation.filter((item) => item.url !== '/')" :key="item.url"><Link :href="appUrl(item.url)">{{ item.label }}</Link></li></ul>
            <p v-if="page.props.navigation.length === 1">Belum ada akses menu. Hubungi pengelola akses.</p>
        </section>
    </AppLayout>
</template>
