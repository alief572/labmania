<script setup lang="ts">
import PageHead from '../../components/PageHead.vue';

import ActionMenu from "../../components/ActionMenu.vue";

import ServerTable from "../../components/ServerTable.vue";

import { Head, Link, router, useForm } from '@inertiajs/vue3';

import { reactive } from 'vue';

import AppLayout from '../../layouts/AppLayout.vue';

import { rupiah } from '../../composables/costing';
import { useConfirmedMutation } from '../../composables/confirmedMutation';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Row = { closed_date: string | null; warnings: string[]; costing: {total_hpp: number; pendapatan: number; profit: number; bep_peserta: number | null}; durasi_hari: number; id: number; reschedule_count: number; nomor: string; master_nomor: string; jenis: string; judul: string; name_customer: string | null; tgl_mulai: string; tgl_selesai: string; lokasi: string; jam_pembelajaran: number | null; trainer_nama: string | null; asisten_nama: string | null; status: string; peserta_deal: number; target_peserta: number; harga_jual_peserta: string };

const props = defineProps<{ rows: { data: Row[]; total: number; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null }; filters: Record<string, string>; can: { add: boolean; manage: boolean; approve: boolean; cancel: boolean; quotation: boolean }; years: number[] }>();

const f = reactive({ q: '', jenis: '', status: '', tahun: '', bulan: '', ...props.filters });

const search = () => router.get('/penjualan-program', f);

const transition = useForm({});
function process(row:Row,action:'finalkan'|'batalkan') {
    transition.clearErrors();
    return confirmation.form(transition,'post',`/penjualan-program/${row.id}/${action}`,{
        title:`${action==='finalkan'?'Finalkan':'Batalkan'} ${row.nomor}?`,
        text:`${row.judul}\n${row.durasi_hari} hari, ${row.target_peserta} peserta, ${rupiah(row.harga_jual_peserta)} / peserta.\nPendapatan ${rupiah(row.costing.pendapatan)}, HPP ${rupiah(row.costing.total_hpp)}, profit ${rupiah(row.costing.profit)}, BEP ${row.costing.bep_peserta ?? 'tidak tercapai'}.\n${row.warnings.join('\n')}\nDraft terkunci setelah diproses.`,
        confirmLabel:action==='finalkan'?'Ya, finalkan':'Ya, batalkan',danger:action==='batalkan',
    },{preserveScroll:true});
}
</script>

<template>

    <Head title="Penjualan Program" /><AppLayout title="Penjualan Program" full-width>

        <PageHead title="Daftar Penjualan Program" description="Tarik Master Program Final, pilih Public atau B2B, isi tanggal & lokasi, lalu sesuaikan harga dan costing — form sama untuk kedua jenis, perubahan tidak mengubah Master Program. Setelah Final, Public maupun B2B dibuatkan Quotation (modul Quotation)."><Link v-if="can.add" class="primary" href="/penjualan-program/create">+ Buat Penjualan Program</Link></PageHead>

        <form class="filters" @submit.prevent="search">

            <label>Jenis<select v-model="f.jenis"><option value="">Semua</option><option value="public">Public</option><option value="b2b">B2B</option></select></label>

            <label>Status<select v-model="f.status"><option value="">Semua</option><option value="draft">Draft</option><option value="final">Final</option><option value="batal">Batal</option></select></label>

            <label>Tahun acara<select v-model="f.tahun"><option value="">Semua</option><option v-for="y in years" :key="y" :value="y">{{ y }}</option></select></label>

            <label>Bulan acara<select v-model="f.bulan"><option value="">Semua</option><option v-for="n in 12" :key="n" :value="n">{{ new Date(2026, n - 1).toLocaleString('id-ID', { month: 'long' }) }}</option></select></label>

            <button class="primary">Terapkan</button><Link href="/penjualan-program">Reset</Link>

        </form>

        <ServerTable url="/penjualan-program/data" label="Daftar Penjualan Program" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "Nomor", "orderable": true, "className": ""}, {"title": "Jenis", "orderable": true, "className": ""}, {"title": "Master Program", "orderable": true, "className": ""}, {"title": "Pelaksanaan", "orderable": true, "className": ""}, {"title": "Target peserta", "orderable": true, "className": "dt-body-right"}, {"title": "Harga jual / peserta", "orderable": true, "className": "dt-body-right"}, {"title": "Status", "orderable": true, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters }" refresh-on-visit ><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }"><Link :href="`/penjualan-program/${p.id}`">{{ p.nomor }}</Link></template><template #cell-2="{ row: p, number }"><span class="badge">{{ p.jenis.toUpperCase() }}</span><small>{{ p.name_customer }}</small></template><template #cell-3="{ row: p, number }">{{ p.judul }}<small>{{ p.master_nomor }}</small></template><template #cell-4="{ row: p, number }">{{ p.tgl_mulai }} s.d. {{ p.tgl_selesai }} · {{ p.jam_pembelajaran ?? "Belum diisi" }} JP<small>{{ p.lokasi }}</small><small>Trainer: {{ p.trainer_nama || "Belum diisi" }} · Asisten: {{ p.asisten_nama || "Belum diisi" }}</small><small v-if="p.reschedule_count">Reschedule {{ p.reschedule_count }}×</small></template><template #cell-5="{ row: p, number }">{{ p.target_peserta }}<small v-if="p.status==='final'">Deal {{ p.peserta_deal }}</small></template><template #cell-6="{ row: p, number }">{{ rupiah(p.harga_jual_peserta) }}</template><template #cell-7="{ row: p, number }"><span class="badge" :class="`st-${p.status}`">{{ p.status }}</span></template><template #cell-8="{ row: p, number }"><ActionMenu><Link :href="`/penjualan-program/${p.id}`">{{ p.status==='draft' && can.manage ? "Edit" : "Lihat" }}</Link><button v-if="p.status==='draft' && !p.closed_date && can.approve" type="button" :disabled="confirming" @click="process(p,'finalkan')">Finalkan</button><button v-if="p.status==='draft' && !p.closed_date && can.cancel" type="button" class="danger" :disabled="confirming" @click="process(p,'batalkan')">Batalkan</button><Link v-if="p.status==='final' && !p.closed_date && can.quotation" :href="`/quotation/create?id_penjualan=${p.id}`">Buat Quotation</Link><Link v-if="p.status==='final'" :href="`/penjualan-program/${p.id}/reschedule`">Reschedule / Riwayat</Link><a v-if="p.status==='final'" :href="`/penjualan-program/${p.id}/pdf`" target="_blank" rel="noopener">Cetak Surat Penawaran</a></ActionMenu></template></ServerTable>

        <p v-for="error in transition.errors" :key="error" class="error" role="alert">{{error}}</p>
    </AppLayout>

</template>

