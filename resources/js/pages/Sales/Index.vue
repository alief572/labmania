<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import PageHead from '../../components/PageHead.vue';
import CancelProgramDialog from '../../components/CancelProgramDialog.vue';

import ActionMenu from "../../components/ActionMenu.vue";

import ServerTable from "../../components/ServerTable.vue";

import { Head, Link, router, useForm } from '@inertiajs/vue3';

import { onBeforeUnmount, reactive, ref, watch } from 'vue';

import AppLayout from '../../layouts/AppLayout.vue';

import { rupiah } from '../../composables/costing';
import { useConfirmedMutation } from '../../composables/confirmedMutation';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Row = { reschedulable: boolean; closed_date: string | null; warnings: string[]; costing: {total_hpp: number; pendapatan: number; profit: number; bep_peserta: number | null}; durasi_hari: number; id: number; reschedule_count: number; nomor: string; master_nomor: string; jenis: string; judul: string; name_customer: string | null; tgl_mulai: string; tgl_selesai: string; lokasi: string; jam_pembelajaran: number | null; trainer_nama: string | null; asisten_nama: string | null; status: string; peserta_deal: number; target_peserta: number; harga_jual_peserta: string };

const props = defineProps<{ rows: { data: Row[]; total: number; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null }; filters: Record<string, string>; can: { add: boolean; manage: boolean; approve: boolean; cancel: boolean; quotation: boolean }; years: number[] }>();

const f = reactive({ q: '', jenis: '', status: '', tahun: '', bulan: '', ...props.filters });

const search = () => router.get(appUrl('/penjualan-program'), Object.fromEntries(Object.entries(f).filter(([, v]) => v !== '' && v !== null)), { preserveState: true, preserveScroll: true, replace: true });
let timer: ReturnType<typeof setTimeout> | undefined;
watch(() => [f.jenis, f.status, f.tahun, f.bulan], () => { clearTimeout(timer); search(); });
watch(() => f.q, () => { clearTimeout(timer); timer = setTimeout(search, 300); });
onBeforeUnmount(() => clearTimeout(timer));
const STATUS_LABEL: Record<string, string> = { draft: 'Draft', final: 'Final', batal: 'Batal' };
const bulan = (n: number) => new Date(2026, n - 1).toLocaleString('id-ID', { month: 'long' });
const fmt = (d?: string) => (d ? new Date(`${d.slice(0, 10)}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—');
const range = (a?: string, b?: string) => (!b || a === b ? fmt(a) : `${fmt(a)} – ${fmt(b)}`);

const transition = useForm({});
const cancelling = ref<Row | null>(null);
function process(row:Row,action:'finalkan') {
    transition.clearErrors();
    return confirmation.form(transition,'post',`/penjualan-program/${row.id}/${action}`,{
        title:`Finalkan ${row.nomor}?`,
        text:`${row.judul}\n${row.durasi_hari} hari, ${row.target_peserta} peserta, ${rupiah(row.harga_jual_peserta)} / peserta.\nPendapatan ${rupiah(row.costing.pendapatan)}, HPP ${rupiah(row.costing.total_hpp)}, profit ${rupiah(row.costing.profit)}, BEP ${row.costing.bep_peserta ?? 'tidak tercapai'}.\n${row.warnings.join('\n')}\nDraft terkunci setelah diproses.`,
        confirmLabel:'Ya, finalkan',
    },{preserveScroll:true});
}
</script>

<template>

    <Head title="Penjualan Program" /><AppLayout title="Penjualan Program" full-width>

        <PageHead title="Daftar Penjualan Program" description="Tarik Master Program Final, pilih Public atau B2B, isi tanggal & lokasi, lalu sesuaikan harga dan costing — form sama untuk kedua jenis, perubahan tidak mengubah Master Program. Setelah Final, Public maupun B2B dibuatkan Quotation (modul Quotation)."><Link data-tour-next="qtStart" v-if="can.add" class="primary" :href="appUrl('/penjualan-program/mulai')">+ Buat Penjualan Program</Link></PageHead>

        <div class="filters">
            <label class="grow">Cari<input v-model="f.q" type="search" maxlength="100" placeholder="Cari nomor, program, atau lokasi…" /></label>
            <label>Jenis<select v-model="f.jenis"><option value="">Semua jenis</option><option value="public">Public</option><option value="b2b">B2B</option></select></label>
            <label>Status<select v-model="f.status"><option value="">Semua status</option><option v-for="(label, st) in STATUS_LABEL" :key="st" :value="st">{{ label }}</option></select></label>
            <label>Tahun<select v-model="f.tahun"><option value="">Semua tahun</option><option v-for="y in years" :key="y" :value="y">{{ y }}</option></select></label>
            <label>Bulan<select v-model="f.bulan"><option value="">Semua bulan</option><option v-for="n in 12" :key="n" :value="n">{{ bulan(n) }}</option></select></label>
        </div>

        <ServerTable :url="appUrl('/penjualan-program/data')" label="Daftar Penjualan Program" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "Nomor", "orderable": true, "className": ""}, {"title": "Jenis", "orderable": true, "className": ""}, {"title": "Master Program", "orderable": true, "className": ""}, {"title": "Pelaksanaan", "orderable": true, "className": ""}, {"title": "Target peserta", "orderable": true, "className": "dt-body-right"}, {"title": "Harga jual / peserta", "orderable": true, "className": "dt-body-right"}, {"title": "Status", "orderable": true, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters }" :keyword="f.q" refresh-on-visit ><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }"><Link class="code" :href="appUrl(`/penjualan-program/${p.id}`)">{{ p.nomor }}</Link></template><template #cell-2="{ row: p, number }"><span class="tag" :class="p.jenis === 'b2b' ? 'tag-b2b' : 'tag-public'">{{ p.jenis === 'b2b' ? 'B2B' : 'Public' }}</span><small v-if="p.jenis === 'b2b' && p.name_customer">{{ p.name_customer }}</small></template><template #cell-3="{ row: p, number }">{{ p.judul }}<small>{{ p.master_nomor }}</small></template><template #cell-4="{ row: p, number }">{{ range(p.tgl_mulai, p.tgl_selesai) }}<template v-if="p.jam_pembelajaran"> · {{ p.jam_pembelajaran }} JP</template><small>{{ p.lokasi }}</small><small>Trainer: {{ p.trainer_nama || "Belum diisi" }} · Asisten: {{ p.asisten_nama || "Belum diisi" }}</small><small v-if="p.reschedule_count">Reschedule {{ p.reschedule_count }}×</small></template><template #cell-5="{ row: p, number }">{{ p.target_peserta }}<small v-if="p.status==='final'">Deal {{ p.peserta_deal }}</small></template><template #cell-6="{ row: p, number }">{{ rupiah(p.harga_jual_peserta) }}</template><template #cell-7="{ row: p, number }"><span class="badge" :class="`st-${p.status}`">{{ STATUS_LABEL[p.status] ?? p.status }}</span><small v-if="p.status === 'final'">→ Quotation</small></template><template #cell-8="{ row: p, number }"><ActionMenu><Link :href="appUrl(`/penjualan-program/${p.id}`)">{{ p.status==='draft' && can.manage ? "Edit" : "Lihat" }}</Link><button v-if="p.status==='draft' && !p.closed_date && can.approve" type="button" :disabled="confirming" @click="process(p,'finalkan')">Finalkan</button><button v-if="(p.status==='draft' || p.status==='final') && !p.closed_date && can.cancel" type="button" class="danger" :disabled="confirming" @click="cancelling=p">Batalkan</button><Link v-if="p.status==='final' && !p.closed_date && can.quotation" :href="appUrl(`/quotation/create?id_penjualan=${p.id}`)">+ Buat Quotation</Link><Link v-if="p.reschedulable && can.manage" :href="appUrl(`/penjualan-program/${p.id}/reschedule`)">Reschedule</Link><a v-if="p.status==='final'" :href="appUrl(`/penjualan-program/${p.id}/pdf`)" target="_blank" rel="noopener">Cetak Surat Penawaran</a></ActionMenu></template></ServerTable>

        <p v-for="error in transition.errors" :key="error" class="error" role="alert">{{error}}</p>
        <CancelProgramDialog v-if="cancelling" :id="cancelling.id" :nomor="cancelling.nomor" @close="cancelling=null" />
    </AppLayout>

</template>

