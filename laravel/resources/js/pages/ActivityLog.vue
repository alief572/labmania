<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
type Log = { id: number; actor_label: string; module: string; sub_module: string | null; action: string; description: string; created_date: string; nm_lengkap: string | null; username: string | null; ref_no: string | null; data: Record<string, unknown> | null };
const props = defineProps<{ logs: { data: Log[]; current_page: number; last_page: number; total: number; prev_page_url: string | null; next_page_url: string | null }; filters: Record<string, string>; modules: string[]; actions: string[]; users: { created_by: number; nm_lengkap: string | null }[] }>();
const filter = reactive({ module: '', action: '', user: '', from: '', to: '', keyword: '', ref_table: '', ref_id: '', ref_no: '', ...props.filters });
const submit = () => router.get('/activity-log', filter, { preserveState: false, preserveScroll: true });
const printable = (value: unknown) => value === null || value === undefined ? '—' : typeof value === 'object' ? JSON.stringify(value) : String(value);
const dateTime = (value: string) => value.replace(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}:\d{2}).*$/, '$3-$2-$1 $4');
function changes(data: Record<string, unknown>) {
    const old = (data.old || {}) as Record<string, unknown>;
    const current = (data.new || {}) as Record<string, unknown>;
    const rows = Object.keys({ ...old, ...current }).map((field) => ({ field, old: old[field], new: current[field] }));
    const details = (data.detail_changes || {}) as Record<string, { old: unknown; new: unknown }>;
    return [...rows, ...Object.entries(details).map(([field, change]) => ({ field, old: change.old, new: change.new }))];
}
</script>

<template>
    <Head title="Activity Log" />
    <AppLayout title="Activity Log">
        <h1>Activity Log</h1><p class="muted">{{ logs.total }} aktivitas</p>
        <form class="filters" @submit.prevent="submit">
            <label>Modul<select v-model="filter.module"><option value="">Semua modul</option><option v-for="module in modules" :key="module">{{ module }}</option></select></label>
            <label>Aksi<select v-model="filter.action"><option value="">Semua aksi</option><option v-for="action in actions" :key="action">{{ action }}</option></select></label>
            <label>Pengguna<select v-model="filter.user"><option value="">Semua pengguna</option><option v-for="user in users" :key="user.created_by" :value="user.created_by">{{ user.nm_lengkap || `Akun #${user.created_by}` }}</option></select></label>
            <label>Dari<input v-model="filter.from" type="date" /></label><label>Sampai<input v-model="filter.to" type="date" /></label>
            <label>Cari<input v-model="filter.keyword" type="search" placeholder="Nomor, aktivitas, atau user" maxlength="100" /></label>
            <button class="primary">Terapkan</button><Link href="/activity-log">Reset filter</Link>
        </form>
        <p v-if="$page.props.errors && Object.keys($page.props.errors).length" class="error" role="alert">Periksa filter tanggal dan pencarian.</p>
        <div class="table-wrap"><table><caption class="sr-only">Daftar aktivitas dan perubahan data</caption><thead><tr><th scope="col">Waktu / User</th><th scope="col">Modul / Aksi</th><th scope="col">Aktivitas</th><th scope="col">Perubahan</th></tr></thead><tbody>
            <tr v-for="log in logs.data" :key="log.id"><td>{{ dateTime(log.created_date) }}<small>{{ log.actor_label }}</small></td><td>{{ log.module }}<small>{{ log.sub_module }} · {{ log.action }}</small></td><td>{{ log.description }}<small v-if="log.ref_no">{{ log.ref_no }}</small></td><td><details v-if="log.data"><summary>Lihat perubahan</summary><table v-if="log.data.old || log.data.new || log.data.detail_changes" class="diff"><thead><tr><th>Field</th><th>Sebelum</th><th>Sesudah</th></tr></thead><tbody><tr v-for="change in changes(log.data)" :key="change.field"><th>{{ change.field }}</th><td class="before">{{ printable(change.old) }}</td><td class="after">{{ printable(change.new) }}</td></tr></tbody></table><pre v-else>{{ JSON.stringify(log.data, null, 2) }}</pre></details><span v-else>—</span></td></tr>
            <tr v-if="!logs.data.length"><td colspan="4" class="empty">Tidak ada aktivitas sesuai filter.</td></tr>
        </tbody></table></div>
        <nav class="pagination" aria-label="Halaman Activity Log"><Link v-if="logs.prev_page_url" :href="logs.prev_page_url">Sebelumnya</Link><span>Halaman {{ logs.current_page }} / {{ logs.last_page }}</span><Link v-if="logs.next_page_url" :href="logs.next_page_url">Berikutnya</Link></nav>
    </AppLayout>
</template>

