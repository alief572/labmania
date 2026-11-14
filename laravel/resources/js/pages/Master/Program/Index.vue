<script setup lang="ts">
import ActionMenu from "../../../components/ActionMenu.vue";
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';
import ConfirmDialog from '../../../components/ConfirmDialog.vue';
import Pagination from '../../../components/Pagination.vue';
import { rupiah } from '../../../composables/costing';

type Row = { id: number; nomor: string; judul: string; nomor_product: string; durasi_hari: number; target_peserta: number; harga_jual_peserta: number; hpp: number; status: 'draft' | 'final' | 'batal' };
type Summary = { id: number; nomor: string; status: string; judul: string; durasi: number; target: number; harga: number; jp: number | null; hpp: number; profit: number; bep_peserta: number | null; warnings: string[]; jumlah_biaya: number; n_penjualan: number };
const props = defineProps<{
    filters: { q: string; status: string };
    rows: { data: Row[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null; from: number | null };
    can: { add: boolean; manage: boolean; approve: boolean };
}>();
const filter = ref({ ...props.filters });
const apply = () => router.get('/program', Object.fromEntries(Object.entries(filter.value).filter(([, v]) => v !== '')), { preserveScroll: true });
const label = { draft: 'Draft', final: 'Final', batal: 'Batal' } as const;
const pending = ref<{ action: 'finalkan' | 'batalkan'; summary: Summary } | null>(null);
const busy = ref(false);
async function ask(row: Row, action: 'finalkan' | 'batalkan') {
    const res = await fetch(`/program/${row.id}/ringkasan`, { headers: { Accept: 'application/json' } });
    pending.value = { action, summary: await res.json() };
}
function confirm() {
    busy.value = true;
    router.post(`/program/${pending.value!.summary.id}/${pending.value!.action}`, {}, { preserveScroll: true, onFinish: () => { busy.value = false; pending.value = null; } });
}
</script>

<template>
    <Head title="Daftar Master Program" />
    <AppLayout title="Daftar Master Program">
        <div class="page-head"><h1>Daftar Master Program</h1><Link v-if="can.add" class="primary" href="/program/create">+ Buat Master Program</Link></div>
        <p v-if="($page.props.errors as Record<string, string>).program" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).program }}</p>
        <form class="filters" @submit.prevent="apply">
            <label>Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Nomor program atau Product" /></label>
            <label>Status<select v-model="filter.status"><option value="all">Semua</option><option value="draft">Draft</option><option value="final">Final</option><option value="batal">Batal</option></select></label>
            <button class="primary">Terapkan</button>
        </form>
        <div class="table-wrap"><table>
            <caption class="sr-only">Daftar Master Program</caption>
            <thead><tr><th scope="col">No</th><th scope="col">Nomor</th><th scope="col">Judul</th><th scope="col">Durasi</th><th scope="col">Target peserta</th><th scope="col">Harga jual</th><th scope="col">HPP</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
            <tbody>
                <tr v-for="(row, i) in rows.data" :key="row.id">
                    <td>{{ (rows.from ?? 1) + i }}</td><td>{{ row.nomor }}</td><td>{{ row.judul || '—' }}<small>{{ row.nomor_product }}</small></td><td>{{ row.durasi_hari }} hari</td><td>{{ row.target_peserta }}</td>
                    <td>{{ rupiah(row.harga_jual_peserta) }}</td><td>{{ rupiah(row.hpp) }}</td><td><span class="badge" :class="{ on: row.status === 'final', off: row.status === 'batal', warn: row.status === 'draft' }">{{ label[row.status] }}</span></td>
                    <td><ActionMenu >
                        <Link :href="`/program/${row.id}`">{{ row.status === 'draft' && can.manage ? 'Edit' : 'Lihat' }}</Link>
                        <template v-if="row.status === 'draft'">
                            <button v-if="can.approve" type="button" @click="ask(row, 'finalkan')">Finalkan</button>
                            <button v-if="can.manage" type="button" class="danger" @click="ask(row, 'batalkan')">Batalkan</button>
                        </template>
                    </ActionMenu></td>
                </tr>
                <tr v-if="!rows.data.length"><td colspan="9" class="empty">Belum ada Master Program.</td></tr>
            </tbody>
        </table></div>
        <Pagination :page="rows" label="Halaman Master Program" />

        <ConfirmDialog v-if="pending" :title="`${pending.action === 'finalkan' ? 'Finalkan' : 'Batalkan'} ${pending.summary.nomor}?`" :confirm-label="pending.action === 'finalkan' ? 'Finalkan program' : 'Batalkan program'" :danger="pending.action === 'batalkan'" :busy="busy" @confirm="confirm" @cancel="pending = null">
            <dl>
                <dt>Product</dt><dd>{{ pending.summary.judul || '—' }}</dd>
                <dt>Durasi / Jam Pembelajaran</dt><dd>{{ pending.summary.durasi }} hari · {{ pending.summary.jp ?? 'belum diisi' }} JP</dd>
                <dt>Target peserta</dt><dd>{{ pending.summary.target }}</dd>
                <dt>Harga jual / peserta</dt><dd>{{ rupiah(pending.summary.harga) }}</dd>
                <dt>HPP</dt><dd>{{ rupiah(pending.summary.hpp) }}</dd>
                <dt>Profit</dt><dd>{{ rupiah(pending.summary.profit) }}</dd>
                <dt>BEP</dt><dd>{{ pending.summary.bep_peserta ?? 'Tidak tercapai' }}</dd>
            </dl>
            <p v-for="w in pending.summary.warnings" :key="w" class="notice">{{ w }}</p>
            <p class="muted">{{ pending.action === 'finalkan' ? 'Setelah Final, program terkunci dan tersedia di Penjualan Program.' : 'Program yang dibatalkan tidak dapat dibuka kembali.' }}</p>
        </ConfirmDialog>
    </AppLayout>
</template>
