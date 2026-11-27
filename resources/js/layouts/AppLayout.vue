<script setup lang="ts">
import { appUrl } from '../composables/appUrl';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import type { SharedProps } from '../types';
import { navigationBreadcrumb, navigationSections } from '../composables/navigation';
import Toast from '../components/Toast.vue';
import Highlight from '../components/Highlight.vue';

defineProps<{ title: string; fullWidth?: boolean }>();
const page = usePage<SharedProps>();
const open = ref(false);
const collapsed = ref(false);
const mobile = ref(false);
const sidebar = ref<HTMLElement | null>(null);
const toggleButton = ref<HTMLButtonElement | null>(null);
const closeButton = ref<HTMLButtonElement | null>(null);
const search = ref('');
const searchQuery = ref('');
let searchTimer: ReturnType<typeof setTimeout> | undefined;
function applySearch() {
    clearTimeout(searchTimer);
    searchQuery.value = search.value;
}
watch(search, value => {
    clearTimeout(searchTimer);
    if (!value.trim()) applySearch();
    else searchTimer = setTimeout(applySearch, 250);
}, { flush: 'sync' });
onBeforeUnmount(() => clearTimeout(searchTimer));
const menus = computed(() => navigationSections(page.props.navigation, page.url, searchQuery.value));
const currentSection = computed(() => navigationSections(page.props.navigation, page.url).find(section => section.groups.some(group => group.items.some(item => item.active))));
const breadcrumb = computed(() => navigationBreadcrumb(page.props.navigation, page.url));
const name = computed(() => page.props.auth?.nm_lengkap || page.props.auth?.username || '');
const icons: Record<string, string> = {
    'Penjualan Program': 'M3 3v18h18M7 15l4-4 3 3 5-6',
    'Quotation': 'M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9zM14 3v6h6M8 13h8M8 17h5',
    'Invoice': 'M4 3h16v18l-4-2-4 2-4-2-4 2zM8 7h8M8 11h8M8 15h4',
    'Manage Peserta': 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M23 21v-2a4 4 0 0 0-3-3.87M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0M16 3.13a4 4 0 0 1 0 7.75',
    'Pengajuan Biaya': 'M4 4h16v12H8l-4 4zM8 9h8M8 12h5',
    'Pengaturan': 'M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M5.6 18.4l2.8-2.8M15.6 8.4l2.8-2.8M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0',
};
async function showMenu() {
    if (!mobile.value) {
        collapsed.value = false;
        await nextTick();
        sidebar.value?.querySelector<HTMLButtonElement>('.nav-hide')?.focus();
        return;
    }
    open.value = true;
    await nextTick();
    closeButton.value?.focus();
}
async function closeMenu() {
    open.value = false;
    await nextTick();
    toggleButton.value?.focus();
}
function trapFocus(event: KeyboardEvent) {
    if (event.key === 'Escape' && search.value) { event.preventDefault(); search.value = ''; return; }
    if (!open.value) return;
    if (event.key === 'Escape') { event.preventDefault(); closeMenu(); }
    if (event.key !== 'Tab') return;
    const items = Array.from(sidebar.value?.querySelectorAll<HTMLElement>('a[href], button, input, summary') || []).filter(item => item.getClientRects().length);
    if (!items?.length) return;
    const first = items[0], last = items[items.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
}
function searchKey(event: KeyboardEvent) {
    if (event.key === 'Enter') {
        applySearch();
        const first = menus.value[0]?.groups[0]?.items[0];
        if (first) { event.preventDefault(); open.value = false; router.visit(appUrl(first.url)); }
    }
}
function resized() { mobile.value = window.innerWidth <= 900; if (!mobile.value) open.value = false; }
onMounted(() => { resized(); window.addEventListener('resize', resized); });
onBeforeUnmount(() => window.removeEventListener('resize', resized));
</script>

<template>
    <a class="skip" href="#main">Lewati ke konten</a>
    <div class="app-shell" :class="{ 'nav-hidden': collapsed }">
        <aside id="sidebar" ref="sidebar" class="sidebar" :class="{ 'is-open': open }" :inert="collapsed && !open" @keydown="trapFocus">
            <Link class="brand" :href="appUrl('/')"><span class="brand-mark">L</span><span><strong>Labmania</strong><small>ERP · Modul Program</small></span></Link>
            <button ref="closeButton" class="mobile-close" type="button" @click="closeMenu">Tutup menu</button>
            <button class="nav-hide" type="button" aria-controls="sidebar" aria-label="Sembunyikan menu" title="Sembunyikan menu" @click="collapsed = true; nextTick(() => toggleButton?.focus())"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3" /><path d="M9 3v18" /><path d="m16 9-3 3 3 3" /></svg></button>
            <label class="nav-search"><span class="sr-only">Cari menu</span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg><input v-model="search" type="search" placeholder="Cari menu…" @keydown="searchKey" /></label>
            <nav aria-label="Menu utama">
                <template v-for="section in menus" :key="section.key">
                    <template v-if="!section.label">
                        <Link v-for="item in section.groups[0].items" :key="item.url" class="nav-home" :href="appUrl(item.url)" :aria-current="item.active ? 'page' : undefined" @click="open = false">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10H3zM9 20v-7h6v7" /></svg><span><Highlight :text="item.label" :query="searchQuery" /></span>
                        </Link>
                    </template>
                    <details v-else :key="`${section.key}-${page.url.split('?')[0]}-${searchQuery.trim()}`" class="nav-module" :open="!!searchQuery.trim() || section.key === currentSection?.key">
                        <summary class="nav-module-head"><svg viewBox="0 0 24 24" aria-hidden="true"><template v-if="section.label === 'Master Data'"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/></template><path v-else :d="icons[section.label] || icons.Quotation"/></svg><span><Highlight :text="section.label" :query="searchQuery" /></span><i class="caret" aria-hidden="true"></i></summary>
                        <div class="nav-module-body">
                            <template v-for="group in section.groups" :key="group.key">
                                <details v-if="group.label" :key="`${group.key}-${page.url.split('?')[0]}-${searchQuery.trim()}`" class="nav-subgroup" :open="!!searchQuery.trim() || group.items.some(item => item.active)">
                                    <summary><Highlight :text="group.label" :query="searchQuery" /><i class="caret" aria-hidden="true"></i></summary>
                                    <Link v-for="item in group.items" :key="item.url" :href="appUrl(item.url)" :aria-current="item.active ? 'page' : undefined" @click="open = false"><Highlight :text="item.label" :query="searchQuery" /><span v-if="item.count !== undefined" class="nav-count">{{ item.count }}</span></Link>
                                </details>
                                <template v-else><Link v-for="item in group.items" :key="item.url" :href="appUrl(item.url)" :aria-current="item.active ? 'page' : undefined" @click="open = false"><Highlight :text="item.label" :query="searchQuery" /><span v-if="item.count !== undefined" class="nav-count">{{ item.count }}</span></Link></template>
                            </template>
                        </div>
                    </details>
                </template>
                <p v-if="!menus.length" class="nav-empty">Menu tidak ditemukan.</p>
            </nav>
            <div class="sidebar-foot"><div class="me-box"><span class="avatar" aria-hidden="true">{{ name.slice(0, 1).toUpperCase() }}</span><div class="info"><strong>{{ name }}</strong><small>{{ page.props.auth?.username }}</small></div><Link :href="appUrl('/logout')" method="post" as="button" aria-label="Keluar" title="Keluar"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H4V3h5M9 12h12m-5-5 5 5-5 5" /></svg></Link></div><p class="sidebar-meta">Labmania ERP</p></div>
        </aside>
        <button v-if="open" type="button" tabindex="-1" class="scrim" aria-label="Tutup menu" @click="closeMenu"></button>
        <div class="main-panel" :inert="open">
            <header class="topbar"><button ref="toggleButton" class="menu-toggle" type="button" :aria-expanded="mobile ? open : !collapsed" aria-controls="sidebar" @click="showMenu" aria-label="Menu" title="Menu"><template v-if="mobile"><span></span><span></span><span></span></template><svg v-else viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3" /><path d="M9 3v18" /><path d="m14 9 3 3-3 3" /></svg></button><span class="crumb"><span v-for="(label, index) in breadcrumb" :key="index">{{ label }} / </span><b>{{ title }}</b></span><span class="pill">Labmania ERP</span></header>
            <main id="main" tabindex="-1" class="content" :class="{ 'content--fluid': fullWidth }">
                <slot />
            </main>
        </div>
        <Toast :message="page.props.flash.success" />
    </div>
</template>
