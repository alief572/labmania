<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import TutorialButton from '../../components/TutorialButton.vue';
import { computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import InvoiceAmounts from '../../components/InvoiceAmounts.vue';
import InvoiceJournal from '../../components/InvoiceJournal.vue';
import { money, type Snapshot, type Journal } from '../../types/salesOrder';
import { useConfirmedMutation } from '../../composables/confirmedMutation';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
const props = defineProps<{ source: Omit<Snapshot, 'produk'> & { amounts: Record<string, string>; termin?: { ke: number; dari: number; persen: string; keterangan: string } | null }; coa: number | ''; accounts: { id: number; no_perkiraan: string; nama: string; kdcab: string }[]; journal: Journal[]; configurationErrors: Record<string, string[]>; tanggal: string }>();
const form = useForm({ id_sales_order: props.source.so.id, termin_ke: props.source.termin?.ke ?? '', tanggal: props.tanggal, no_faktur: '', coa_penjualan: props.coa });
const save = () => confirmation.form(form, 'post', '/invoicing', { title: 'Buat Invoice dari Sales Order?', text: `${props.source.so.nomor}: ${money(props.source.amounts.total_tagihan)}. Periksa tanggal, faktur dan COA sebelum menyimpan.`, confirmLabel: 'Ya, buat Invoice' });
const b2b = computed(() => props.source.program.jenis === 'b2b');
const sectionStart = computed(() => b2b.value ? 2 : 1);
const recipient = computed(() => props.source.quotation.tujuan === 'company' ? props.source.company?.name_customer || props.source.quotation.kepada_nama : props.source.peserta[0]?.nama || props.source.quotation.kepada_nama);
const treatmentLabels: Record<string, string> = { PKP: 'PKP', WAPU: 'WAPU', KB: 'Kawasan Berikat', NON: 'Tanpa PPN' };
const preview = () => router.get(appUrl('/invoicing/create'), { id_sales_order: form.id_sales_order, tanggal: form.tanggal, coa_penjualan: form.coa_penjualan }, { preserveState: true, preserveScroll: true });
</script>
<template>
    <Head title="Add Invoicing" />
    <AppLayout title="Add Invoicing" full-width>
        <div class="pf-formbar"><Link class="kt-btn" :href="appUrl('/invoicing')">Kembali</Link><h1>Add Invoicing</h1><TutorialButton /></div>
        <div class="notice invoice-context"><p>No. Program <span class="code">{{ source.program.nomor }}</span> <span class="tag" :class="b2b ? 'tag-b2b' : 'tag-public'">{{ b2b ? 'B2B' : 'Public' }}</span> &middot; {{ source.judul }}</p><p>Sales Order <span class="code">{{ source.so.nomor }}</span> &middot; Quotation <span class="code">{{ source.quotation.nomor }}</span></p></div>
        <form class="invoice-create" @submit.prevent="save">
            <p v-for="error in form.errors" :key="error" class="error" role="alert">{{ error }}</p>
            <section class="panel"><h2><span class="section-number">1</span> Data Invoice</h2>
                <div class="invoice-head"><div class="invoice-fields">
                    <div class="field"><span>Kepada</span><div class="ro">{{ recipient || '-' }} <span class="tag" :class="source.quotation.tujuan === 'company' ? 'tag-b2b' : 'tag-public'">{{ source.quotation.tujuan === 'company' ? 'Company' : 'Personal' }}</span></div></div>
                    <div class="field"><span>Alamat</span><div class="ro address">{{ source.quotation.alamat || '-' }}</div></div>
                    <div class="field"><span>Ditujukan</span><div class="ro">{{ source.quotation.up_nama || recipient || '-' }}</div></div>
                    <div class="field"><span>Perlakuan PPN</span><div class="ro">{{ treatmentLabels[source.perlakuan_ppn] || source.perlakuan_ppn }}</div></div>
                </div><div class="invoice-fields">
                    <label>Tanggal Invoice<input v-model="form.tanggal" type="date" required @change="preview" /></label>
                    <label>Nomor Invoice<input value="Otomatis saat Save" readonly /><small class="muted">Otomatis oleh sistem saat Save</small></label>
                    <label v-if="source.quotation.is_ppn">Nomor Faktur<input v-model="form.no_faktur" maxlength="100" placeholder="Nomor Faktur" required /></label>
                    <div v-else class="field"><span>Nomor Faktur</span><div class="ro muted">Tidak perlu: tanpa PPN</div></div>
                </div></div>
            </section>
            <section v-if="b2b" class="panel"><h2><span class="section-number">2</span> Cara Tagih</h2><p>Quotation <b>all-in ke company</b>: satu invoice untuk seluruh {{ source.peserta.length }} peserta Deal.</p></section>
            <section class="panel"><h2><span class="section-number">{{ sectionStart + 1 }}</span> Rincian Tagihan</h2>
                <div class="table-wrap"><table class="invoice-lines"><caption class="sr-only">Rincian tagihan Invoice</caption><thead><tr><th>No.</th><th>No. Program</th><th>Item</th><th>Peserta</th><th class="money">{{ b2b ? 'Harga all-in' : 'Harga / peserta' }}</th><th class="money">Diskon</th><th class="money">Nominal</th></tr></thead><tbody><tr><td>1</td><td class="code">{{ source.program.nomor }}</td><td>{{ source.judul }}</td><td><b>{{ b2b ? 'Paket all-in' : `${source.peserta.length} peserta` }}</b><small>{{ b2b ? `${source.peserta.length} peserta (ditagih ke company)` : source.peserta.slice(0, 4).map(p => p.nama).join(', ') }}<template v-if="!b2b && source.peserta.length > 4">, +{{ source.peserta.length - 4 }} lainnya</template></small></td><td class="money">{{ money(b2b ? source.quotation.harga_allin : source.quotation.harga_peserta) }}</td><td class="money">{{ money(source.quotation.diskon) }}<small>{{ b2b ? 'total quotation' : 'per peserta' }}</small></td><td class="money"><b>{{ money(source.amounts.penjualan) }}</b><small v-if="source.termin">Termin {{ source.termin.ke }}: {{ source.termin.persen }}%</small></td></tr></tbody></table></div>
                <p v-if="source.termin" class="notice"><b>Termin {{ source.termin.ke }} dari {{ source.termin.dari }}</b> ({{ source.termin.persen }}%): {{ source.termin.keterangan }}. Termin lain ditagih dengan invoice terpisah.</p>
            </section>
            <section class="panel"><h2><span class="section-number">{{ sectionStart + 2 }}</span> Perhitungan Invoice</h2><div class="invoice-amounts"><InvoiceAmounts :amounts="source.amounts" :treatment="source.perlakuan_ppn" :taxed="!!source.quotation.is_ppn" /></div></section>
            <section class="panel"><h2><span class="section-number">{{ sectionStart + 3 }}</span> Jurnal</h2>
                <label class="invoice-coa">COA Penjualan<select v-model="form.coa_penjualan" required @change="preview"><option value="">Pilih akun sah</option><option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.no_perkiraan }} &middot; {{ a.nama }}<template v-if="a.kdcab"> &middot; Cabang {{ a.kdcab }}</template></option></select></label>
                <p v-for="(errors, key) in configurationErrors" :key="key" class="error" role="alert">{{ errors.join(' ') }}</p>
                <div class="invoice-journal"><InvoiceJournal :rows="journal" :program="source.program.nomor" /></div>
            </section>
            <div class="form-actions invoice-create-actions"><Link class="btn btn-outline" :href="appUrl('/invoicing')">Back</Link><button type="submit" class="primary" :disabled="form.processing || confirming || Object.keys(configurationErrors).length > 0">{{ form.processing ? 'Menyimpan...' : 'Save' }}</button></div>
        </form>
    </AppLayout>
</template>

<style scoped>
.invoice-context { display: block; }
.invoice-context p { margin: 0; }
.invoice-context p + p { margin-top: 8px; }
.invoice-create { display: grid; gap: 16px; }
.invoice-create > .panel { margin: 0; }
.invoice-create h2 { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin: 0 0 14px; padding: 0; border: 0; font-size: 15px; }
.invoice-create .section-number { margin: 0; }
.invoice-head { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 28px; }
.invoice-fields { display: grid; align-content: start; gap: 14px; }
.invoice-fields label, .invoice-fields .field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
.ro { padding: 7px 0; min-height: 34px; line-height: 1.45; }
.address { white-space: pre-line; }
.invoice-lines { width: 100%; min-width: 900px; }
.invoice-lines td { vertical-align: top; }
.invoice-lines td small { display: block; margin-top: 5px; }
.invoice-lines td:nth-child(3) { min-width: 220px; }
.invoice-lines td:nth-child(4) { min-width: 180px; }
.invoice-amounts :deep(dl) { display: grid; grid-template-columns: minmax(0, 1fr) auto; margin: 0; border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
.invoice-amounts :deep(dt), .invoice-amounts :deep(dd) { margin: 0; padding: 12px 14px; border-bottom: 1px solid var(--line); }
.invoice-amounts :deep(dd) { text-align: right; font-variant-numeric: tabular-nums; }
.invoice-amounts :deep(dt:nth-last-of-type(2)), .invoice-amounts :deep(dd:nth-last-of-type(2)) { font-weight: 700; background: var(--navy-50); color: var(--navy); }
.invoice-amounts :deep(dt:last-of-type), .invoice-amounts :deep(dd:last-of-type) { border: 0; }
.invoice-coa { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; max-width: 620px; }
.invoice-journal :deep(table) { width: 100%; min-width: 1050px; table-layout: fixed; }
.invoice-journal :deep(th), .invoice-journal :deep(td) { overflow-wrap: anywhere; vertical-align: top; }
.invoice-journal :deep(th:nth-child(5)), .invoice-journal :deep(th:nth-child(6)) { width: 20%; }
.invoice-journal :deep(th:nth-child(7)), .invoice-journal :deep(th:nth-child(8)) { width: 140px; }
.invoice-create-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 12px; margin: 0; padding: 0; }
@media (max-width: 760px) { .invoice-head { grid-template-columns: minmax(0, 1fr); gap: 20px; } .invoice-amounts :deep(dt), .invoice-amounts :deep(dd) { padding: 10px; } }
</style>
