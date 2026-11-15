<script setup lang="ts">
import PageHead from '../components/PageHead.vue';
import ServerTable from "../components/ServerTable.vue";
import ConfirmDialog from "../components/ConfirmDialog.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
type Log = { id: number; actor_label: string; module: string; sub_module: string | null; action: string; description: string; created_date: string; nm_lengkap: string | null; username: string | null; ref_no: string | null; data: Record<string, unknown> | null };
const props = defineProps<{ logs: { data: Log[]; current_page: number; last_page: number; total: number; prev_page_url: string | null; next_page_url: string | null }; filters: Record<string, string>; modules: string[]; actions: string[]; users: { created_by: number; nm_lengkap: string | null }[] }>();
const filter = reactive({ module: '', action: '', user: '', from: '', to: '', keyword: '', ref_table: '', ref_id: '', ref_no: '', ...props.filters });
const submit = () => router.get('/activity-log', filter, { preserveState: false, preserveScroll: true });
const selected = ref<Log | null>(null);
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
    <AppLayout title="Activity Log" full-width>
        <PageHead title="Activity Log" description="Semua pergerakan data di sistem: siapa, kapan (tanggal & jam), modul, aksi, data, dan field yang berubah (sebelum → sesudah). Catatan tidak dapat diubah atau dihapus pengguna." />
        <form class="filters" @submit.prevent="submit">
            <label>Modul<select v-model="filter.module"><option value="">Semua modul</option><option v-for="module in modules" :key="module">{{ module }}</option></select></label>
            <label>Aksi<select v-model="filter.action"><option value="">Semua aksi</option><option v-for="action in actions" :key="action">{{ action }}</option></select></label>
            <label>Pengguna<select v-model="filter.user"><option value="">Semua pengguna</option><option v-for="user in users" :key="user.created_by" :value="user.created_by">{{ user.nm_lengkap || `Akun #${user.created_by}` }}</option></select></label>
            <label>Dari<input v-model="filter.from" type="date" /></label><label>Sampai<input v-model="filter.to" type="date" /></label>
            
            <button class="primary">Terapkan</button><Link href="/activity-log">Reset filter</Link>
        </form>
        <p v-if="$page.props.errors && Object.keys($page.props.errors).length" class="error" role="alert">Periksa filter tanggal dan pencarian.</p>
        <ServerTable url="/activity-log/data" label="Activity Log" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "Waktu", "orderable": true, "className": ""}, {"title": "Pengguna", "orderable": true, "className": ""}, {"title": "Modul", "orderable": true, "className": ""}, {"title": "Action", "orderable": true, "className": ""}, {"title": "Data", "orderable": true, "className": ""}, {"title": "Perubahan", "orderable": false, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters, q: filters.keyword || '' }" refresh-on-visit ><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }">{{ dateTime(p.created_date) }}</template><template #cell-2="{ row: p, number }">{{ p.actor_label }}</template><template #cell-3="{ row: p, number }">{{ p.module }}<small>{{ p.sub_module }}</small></template><template #cell-4="{ row: p, number }"><span class="badge">{{ p.action }}</span></template><template #cell-5="{ row: p, number }">{{ p.ref_no || "—" }}<small>{{ p.description }}</small></template><template #cell-6="{ row: p, number }">{{ p.data ? changes(p.data).length + " field" : "?" }}</template><template #cell-7="{ row: p, number }"><button v-if="p.data" type="button" @click="selected=p">Lihat detail</button></template></ServerTable>
        <ConfirmDialog v-if="selected" :title="`Detail aktivitas ${selected.ref_no || selected.id}`" confirm-label="Tutup" @confirm="selected=null" @cancel="selected=null">
            <p>{{ dateTime(selected.created_date) }} · {{ selected.actor_label }}</p><p>{{ selected.module }} · {{ selected.action }}</p><p>{{ selected.description }}</p>
            <div class="table-wrap"><table v-if="selected.data && changes(selected.data).length" class="diff"><thead><tr><th>Field</th><th>Sebelum</th><th>Sesudah</th></tr></thead><tbody><tr v-for="change in changes(selected.data)" :key="change.field"><th>{{ change.field }}</th><td class="before">{{ printable(change.old) }}</td><td class="after">{{ printable(change.new) }}</td></tr></tbody></table><pre v-else>{{ JSON.stringify(selected.data,null,2) }}</pre></div>
        </ConfirmDialog>

    </AppLayout>
</template>

