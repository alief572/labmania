<script setup lang="ts">
import QuotationCreateModal from '../../components/QuotationCreateModal.vue';
import { appUrl } from '../../composables/appUrl';
import ServerTable from "../../components/ServerTable.vue";
import PageHead from '../../components/PageHead.vue';
import ActionMenu from "../../components/ActionMenu.vue";
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useConfirmedMutation } from '../../composables/confirmedMutation';
import { onBeforeUnmount, reactive, ref, watch } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import QuotationParticipants, { type Participant } from '../../components/QuotationParticipants.vue';
import { rupiah } from '../../composables/costing';
type Quote = { redeal?: { kode: string; teks: string; sub: string | null } | null; id: number; nomor: string; tanggal: string; berlaku_sampai: string; kepada_nama: string | null; name_customer: string | null; tujuan: string; up_nama: string | null; status: string; terisi: number; kuota_peserta: number; link_closed: string | null; calc: { grand_total: string; n_peserta: number } };
const props = defineProps<{ summary: { deal: number; menunggu: number; batal: number; keluar: number; total: number; aktif: number; quotation: number; harga: number }; program: { id: number; nomor: string; judul: string; jenis: string; tgl_mulai: string; tgl_selesai: string; lokasi: string; target_peserta: number; closed_date: string | null }; quotations: Quote[]; peserta: Participant[]; hasParticipants: boolean; dataTab: boolean; filters: Record<string, string>; pesertaStatus: string; canAdd: boolean; canManage: boolean; canCancel: boolean }>();
const f = reactive({ q: '', status: '', tujuan: '', data: props.dataTab ? 1 : 0, peserta_status: props.pesertaStatus, ...props.filters });
const go = () => router.get(appUrl(`/quotation/batch/${props.program.id}`), Object.fromEntries(Object.entries(f).filter(([, v]) => v !== '' && v !== 0 && v !== null)), { preserveState: true, preserveScroll: true, replace: true });
let timer: ReturnType<typeof setTimeout> | undefined;
watch(() => [f.status, f.tujuan, f.peserta_status], () => { clearTimeout(timer); go(); });
watch(() => f.q, () => { clearTimeout(timer); timer = setTimeout(go, 300); });
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
const transition = useForm({});
function cancelQuotation(q: Quote) {
    return confirmation.form(transition, 'post', `/quotation/${q.id}/batalkan`, { title: `Batalkan ${q.nomor}?`, text: 'Quotation yang dibatalkan tidak dapat diproses kembali.', confirmLabel: 'Ya, batalkan', danger: true }, { preserveState: 'errors', preserveScroll: true });
}
const createProgram = ref<number | null | undefined>(undefined);
const pdfOpen = ref(false);
const pdfLoading = ref(false);
const pdfError = ref('');
const pdfUrl = ref('');
let pdfRequest: AbortController | undefined;
function closePdf() {
    pdfRequest?.abort(); pdfRequest = undefined;
    if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value);
    pdfUrl.value = ''; pdfOpen.value = false; pdfLoading.value = false;
}
async function openPdf() {
    closePdf(); pdfOpen.value = true; pdfLoading.value = true; pdfError.value = '';
    const request = new AbortController;
    pdfRequest = request;
    try {
        const response = await fetch(appUrl(`/quotation/batch/${props.program.id}/pdf`), { headers: { Accept: 'application/json' }, signal: request.signal });
        if (!response.ok) {
            const result = await response.json().catch(() => null);
            throw new Error(result?.errors ? Object.values(result.errors).flat().join('\n') : 'PDF tidak dapat dimuat. Periksa hak akses atau coba lagi.');
        }
        if (!response.headers.get('Content-Type')?.includes('application/pdf')) throw new Error('Sesi berakhir. Muat ulang halaman dan masuk kembali.');
        const blob = await response.blob();
        if (pdfRequest === request) pdfUrl.value = URL.createObjectURL(blob);
    } catch (error) {
        if (pdfRequest === request) pdfError.value = error instanceof Error ? error.message : 'PDF tidak dapat dimuat. Silakan coba lagi.';
    } finally {
        if (pdfRequest === request) pdfLoading.value = false;
    }
}
onBeforeUnmount(() => { clearTimeout(timer); closePdf(); });
const STATUS: Record<string, string> = { draft: 'Draft', terkirim: 'Terkirim', deal: 'Deal', batal: 'Batal' };
const fmt = (d: string) => (d ? new Date(`${d.slice(0, 10)}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—');
const range = (a: string, b: string) => (!b || a === b ? fmt(a) : `${fmt(a)} – ${fmt(b)}`);
const tab = (key: 'aktif' | 'batal') => { f.peserta_status = key; };
const active = () => (f.peserta_status === 'batal' ? 'batal' : 'aktif');
</script>

<template>
    <Head :title="program.nomor" />
    <AppLayout title="Quotation" full-width>
        <PageHead :title="dataTab ? `Data Peserta — ${program.nomor}` : `Quotation — ${program.nomor}`">
            <Link :href="appUrl('/quotation')">← Semua Penjualan Program</Link>
            <button v-if="dataTab && hasParticipants" type="button" class="primary" @click="openPdf">Export PDF</button>
            <button v-else-if="canAdd && !dataTab" type="button" class="primary" @click="createProgram = program.id">+ Create Quotation</button>
        </PageHead>
        <p class="batch-info">
            <span class="tag" :class="program.jenis === 'b2b' ? 'tag-b2b' : 'tag-public'">{{ program.jenis === 'b2b' ? 'B2B' : 'Public' }}</span> <b>{{ program.judul }}</b> · {{ range(program.tgl_mulai, program.tgl_selesai) }} · {{ program.lokasi || '—' }}<template v-if="program.closed_date"> · Selesai</template><template v-if="!dataTab"> · {{ rupiah(summary.harga) }} / peserta</template><br>
            <template v-if="dataTab"><b>{{ summary.total }}</b> peserta dari {{ summary.quotation }} quotation · {{ summary.deal }} deal · {{ summary.menunggu }} menunggu · {{ summary.batal }} batal</template>
            <template v-else>Peserta deal <b>{{ summary.deal }}</b> / {{ program.target_peserta }}<template v-if="summary.menunggu"> · kuota {{ summary.menunggu }} menunggu</template></template>
        </p>

        <p v-for="e in transition.errors" :key="e" class="error" role="alert">{{ e }}</p>

        <div class="batch-card">
            <div v-if="dataTab" class="tabs" role="tablist" aria-label="Status peserta">
                <button type="button" role="tab" :aria-selected="active() === 'aktif'" :class="{ active: active() === 'aktif' }" @click="tab('aktif')">Aktif<span class="nav-count">{{ summary.aktif }}</span></button>
                <button type="button" role="tab" :aria-selected="active() === 'batal'" :class="{ active: active() === 'batal' }" @click="tab('batal')">Batal<span class="nav-count">{{ summary.batal }}</span></button>
            </div>
            <div class="filters">
                <label class="grow">Cari<input v-model="f.q" type="search" maxlength="100" :placeholder="dataTab ? 'Cari peserta, instansi, kontak, atau nomor quotation…' : 'Cari nomor, kepada, peserta, atau sales…'" /></label>
                <template v-if="dataTab">
                    <label v-if="active() === 'aktif'">Status peserta<select v-model="f.peserta_status"><option value="aktif">Semua status peserta</option><option value="deal">Deal</option><option value="menunggu">Menunggu</option></select></label>
                </template>
                <template v-else>
                    <label>Tujuan<select v-model="f.tujuan"><option value="">Semua tujuan</option><option value="company">Company</option><option value="personal">Personal</option></select></label>
                    <label>Status<select v-model="f.status"><option value="">Semua status</option><option v-for="(label, s) in STATUS" :key="s" :value="s">{{ label }}</option></select></label>
                </template>
            </div>
            <QuotationParticipants v-if="dataTab" :rows="peserta" :data-url="appUrl(`/quotation/batch/${program.id}/data`)" :filters="{ ...filters, q: filters.q || '', data: 1, peserta_status: pesertaStatus }" :manage="canManage && !program.closed_date" />
            <ServerTable :keyword="filters.q || ''" v-else :url="appUrl(`/quotation/batch/${program.id}/data`)" label="Quotation per program" :columns='[{"title":"No","orderable":false},{"title":"Quotation","orderable":true},{"title":"Kepada","orderable":true},{"title":"Peserta","orderable":true},{"title":"Nilai","orderable":false,"className":"dt-body-right"},{"title":"Status","orderable":true},{"title":"Action","orderable":false}]' :filters="{ ...filters }" refresh-on-visit>
                <template #cell-0="{ number }">{{ number }}</template>
                <template #cell-1="{ row: p }"><Link class="code" :href="appUrl(`/quotation/${p.id}`)">{{ p.nomor }}</Link><small>{{ fmt(p.tanggal) }}<template v-if="p.berlaku_sampai"> s.d. {{ fmt(p.berlaku_sampai) }}</template></small></template>
                <template #cell-2="{ row: p }"><span class="tag" :class="p.tujuan === 'company' ? 'tag-b2b' : 'tag-public'">{{ p.tujuan === 'company' ? 'Company' : 'Personal' }}</span> {{ p.name_customer || p.kepada_nama }}<small v-if="p.up_nama">u.p. {{ p.up_nama }}</small></template>
                <template #cell-3="{ row: p }"><b>{{ p.status === 'deal' ? p.calc.n_peserta : p.terisi }}</b> <span class="muted">{{ p.status === 'deal' ? 'peserta deal' : `dari kuota ${p.kuota_peserta} terisi` }}</span></template>
                <template #cell-4="{ row: p }">{{ rupiah(p.calc.grand_total) }}<small>kuota {{ p.calc.n_peserta }}</small></template>
                <template #cell-5="{ row: p }"><span class="badge" :class="`st-${p.status}`">{{ STATUS[p.status] ?? p.status }}</span><small>{{ p.status === 'deal' ? `${p.calc.n_peserta} peserta deal` : (p.link_closed || 'Link terbuka') }}</small><small v-if="p.redeal" class="redeal" :data-redeal="p.redeal.kode"><b>{{ p.redeal.teks }}</b>{{ p.redeal.sub ? ` · ${p.redeal.sub}` : '' }}</small></template>
                <template #cell-6="{ row: p }"><ActionMenu><Link v-if="p.redeal && p.status === 'terkirim'" :href="appUrl(`/quotation/${p.id}`)">Deal ulang</Link><Link :href="appUrl(`/quotation/${p.id}`)">Lihat detail</Link><Link :href="appUrl(`/quotation/${p.id}?tab=data`)">Data Peserta</Link><button v-if="canCancel && ['draft', 'terkirim'].includes(p.status)" type="button" class="danger" :disabled="confirming || transition.processing" @click="cancelQuotation(p)">Batalkan</button></ActionMenu></template>
            </ServerTable>
        </div>
        <ConfirmDialog v-if="pdfOpen" :title="`Data Peserta: ${program.nomor}`" size="xwide" info @cancel="closePdf">
            <p v-if="pdfLoading" role="status">Menyiapkan daftar peserta dan surat konfirmasi…</p>
            <template v-else-if="pdfError">
                <p class="error pdf-error" role="alert">{{ pdfError }}</p>
                <p>Export PDF memerlukan surat konfirmasi asli untuk setiap peserta aktif. Lengkapi surat melalui konfirmasi kehadiran pada quotation terkait, lalu coba lagi.</p>
                <button type="button" class="btn btn-outline" @click="openPdf">Coba lagi</button>
            </template>
            <template v-else-if="pdfUrl">
                <div class="pdf-tools"><span class="muted">Daftar peserta dan surat konfirmasi yang diunggah, tanpa peserta Batal/Keluar.</span><a class="btn btn-outline" :href="pdfUrl" target="_blank" rel="noopener">Buka PDF</a><a class="btn btn-primary" :href="pdfUrl" :download="`Data-Peserta-${program.id}.pdf`">Export PDF</a></div>
                <iframe class="batch-pdf" :src="pdfUrl" title="Daftar peserta dan surat konfirmasi"></iframe>
            </template>
        </ConfirmDialog>
        <QuotationCreateModal v-if="createProgram !== undefined" :program-id="createProgram ?? undefined" @close="createProgram = undefined" />
    </AppLayout>
</template>

<style scoped>
.pdf-error { white-space: pre-line; }
.pdf-tools { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; }
.pdf-tools > span { flex: 1 1 320px; }
.batch-pdf { display: block; width: 100%; height: 68vh; border: 0; border-radius: 8px; background: #e9ecf2; }
</style>
