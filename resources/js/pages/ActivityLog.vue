<script setup lang="ts">
import { appUrl } from '../composables/appUrl';
import { brief, changes, printable } from '../composables/activityLog';
import PageHead from '../components/PageHead.vue';
import ServerTable from "../components/ServerTable.vue";
import ConfirmDialog from "../components/ConfirmDialog.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { onBeforeUnmount, reactive, ref, watch } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
type Log = { id: number; actor_label: string; module: string; sub_module: string | null; action: string; description: string; created_date: string; nm_lengkap: string | null; username: string | null; ref_no: string | null; data: Record<string, unknown> | null };
const props = defineProps<{ logs: { data: Log[]; current_page: number; last_page: number; total: number; prev_page_url: string | null; next_page_url: string | null }; filters: Record<string, string>; modules: string[]; actions: string[]; users: { created_by: number; nm_lengkap: string | null }[]; menus: Record<string, string> }>();
const filter = reactive({ module: '', action: '', user: '', from: '', to: '', keyword: '', ref_table: '', ref_id: '', ref_no: '', ...props.filters });
const submit = () => router.get(appUrl('/activity-log'), filter, { preserveState: true, preserveScroll: true, replace: true });
let timer: ReturnType<typeof setTimeout> | undefined;
watch(() => [filter.module, filter.action, filter.user, filter.from, filter.to], () => { clearTimeout(timer); submit(); });
watch(() => filter.keyword, () => { clearTimeout(timer); timer = setTimeout(submit, 300); });
onBeforeUnmount(() => clearTimeout(timer));
const selected = ref<Log | null>(null);
const dateTime = (value: string) => value.replace(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}:\d{2}).*$/, '$3-$2-$1 $4');
</script>

<template>
    <Head title="Activity Log" />
    <AppLayout title="Activity Log" full-width>
        <PageHead title="Activity Log" />
        <div class="batch-card">
        <form class="filters" @submit.prevent="submit">
            <label class="grow">Cari<input v-model="filter.keyword" type="search" maxlength="100" placeholder="Cari data, keterangan, atau pengguna…" /></label>
            <label>Modul<select v-model="filter.module"><option value="">Semua modul</option><option v-for="module in modules" :key="module">{{ module }}</option></select></label>
            <label>Aksi<select v-model="filter.action"><option value="">Semua aksi</option><option v-for="action in actions" :key="action">{{ action }}</option></select></label>
            <label>Pengguna<select v-model="filter.user"><option value="">Semua pengguna</option><option v-for="user in users" :key="user.created_by" :value="user.created_by">{{ user.nm_lengkap || `Akun #${user.created_by}` }}</option></select></label>
            <label>Dari<input v-model="filter.from" type="date" /></label><label>Sampai<input v-model="filter.to" type="date" /></label>
            
            <Link :href="appUrl('/activity-log')">Reset filter</Link>
        </form>
        <p v-if="$page.props.errors && Object.keys($page.props.errors).length" class="error" role="alert">Periksa filter tanggal dan pencarian.</p>
        <ServerTable :keyword="filters.keyword || ''" :url="appUrl('/activity-log/data')" label="Activity Log" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "Waktu", "orderable": true, "className": ""}, {"title": "Pengguna", "orderable": true, "className": ""}, {"title": "Modul", "orderable": true, "className": ""}, {"title": "Action", "orderable": true, "className": ""}, {"title": "Data", "orderable": true, "className": ""}, {"title": "Perubahan", "orderable": false, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters, q: filters.keyword || '' }" refresh-on-visit ><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }">{{ dateTime(p.created_date) }}</template><template #cell-2="{ row: p, number }">{{ p.actor_label }}</template><template #cell-3="{ row: p, number }">{{ p.module }}<small>{{ p.sub_module }}</small></template><template #cell-4="{ row: p, number }"><span class="badge">{{ p.action }}</span></template><template #cell-5="{ row: p, number }">{{ p.ref_no || "—" }}<small>{{ p.description }}</small></template><template #cell-6="{ row: p, number }"><template v-if="p.data"><small v-for="change in changes(p.data, menus).slice(0, 2)" :key="change.field">{{ change.label }}: {{ brief(change.old) }} → {{ brief(change.new) }}</small><small v-if="changes(p.data).length > 2">+{{ changes(p.data).length - 2 }} field lain</small><span v-if="!changes(p.data).length">—</span></template><span v-else>—</span></template><template #cell-7="{ row: p, number }"><button v-if="p.data" type="button" @click="selected=p">Detail</button></template></ServerTable>
        </div>
        <ConfirmDialog v-if="selected" :title="`Detail aktivitas ${selected.ref_no || selected.id}`" confirm-label="Tutup" @confirm="selected=null" @cancel="selected=null">
            <p>{{ dateTime(selected.created_date) }} · {{ selected.actor_label }}</p><p>{{ selected.module }} · {{ selected.action }}</p><p>{{ selected.description }}</p>
            <div class="table-wrap"><table v-if="selected.data && changes(selected.data).length" class="diff"><thead><tr><th>Field</th><th>Sebelum</th><th>Sesudah</th></tr></thead><tbody><tr v-for="change in changes(selected.data, menus)" :key="change.field"><th>{{ change.label }}</th><td class="before">{{ printable(change.old) }}</td><td class="after">{{ printable(change.new) }}</td></tr></tbody></table><pre v-else>{{ JSON.stringify(selected.data,null,2) }}</pre></div>
        </ConfirmDialog>

    </AppLayout>
</template>

