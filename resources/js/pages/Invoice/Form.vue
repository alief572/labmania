<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import PageHead from '../../components/PageHead.vue';
import { computed, ref } from 'vue';
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
const query = ref('');
const accounts = computed(() => props.accounts.filter((a) => `${a.no_perkiraan} ${a.nama} ${a.kdcab} ${a.id}`.toLowerCase().includes(query.value.toLowerCase()) || a.id === Number(form.coa_penjualan)));
const preview = () => router.get(appUrl('/invoicing/create'), { id_sales_order: form.id_sales_order, tanggal: form.tanggal, coa_penjualan: form.coa_penjualan }, { preserveState: true, preserveScroll: true });
</script>
<template>
    <Head title="Add Invoicing" /><AppLayout title="Add Invoicing"><PageHead title="Add Invoicing"><Link :href="appUrl('/invoicing')">Kembali</Link></PageHead><p>{{ source.program.nomor }} · {{ source.program.jenis.toUpperCase() }} · {{ source.judul }}</p><p>{{ source.so.nomor }} · {{ source.quotation.nomor }}</p>
        <form @submit.prevent="save"><p v-for="error in form.errors" :key="error" class="error" role="alert">{{ error }}</p><section class="panel"><h2><span class="section-number">1</span> Data Invoice</h2><p>Kepada: {{ source.quotation.tujuan === 'company' ? source.quotation.kepada_nama : source.peserta[0]?.nama }} · {{ source.quotation.alamat }}</p><p>Ditujukan: {{ source.quotation.up_nama || source.peserta[0]?.nama }} · Perlakuan PPN: {{ source.perlakuan_ppn }}</p><div class="form-grid"><label>Tanggal Invoice<input v-model="form.tanggal" type="date" required /></label><label>No. Invoice<input value="Otomatis saat Save" disabled /></label><label v-if="source.quotation.is_ppn">Nomor Faktur<input v-model="form.no_faktur" maxlength="100" required /></label></div></section>
            <section class="panel"><h2><span class="section-number">2</span> Cara Tagih</h2><p>{{ source.quotation.skema_harga==='allin'?'All-in 1 company':'Satu Invoice per Sales Order' }}</p><p class="hint">Mengikuti quotation dan Sales Order; tidak dapat diubah pada Invoice.</p><template v-if="source.termin"><p><b>Termin {{ source.termin.ke }} dari {{ source.termin.dari }}</b> · {{ source.termin.persen }}% · {{ source.termin.keterangan }}</p><p class="hint">TOP: satu termin satu invoice. Nilai mengikuti persentase termin dari nilai Deal; termin terakhir menampung selisih pembulatan.</p></template></section>
            <section class="panel"><h2><span class="section-number">3</span> Rincian Tagihan</h2><p>{{ source.quotation.skema_harga === 'allin' ? 'All-in 1 company' : 'Satu Invoice per SO' }} · {{ source.peserta.length }} peserta Deal</p><div class="table-wrap"><table><caption class="sr-only">Peserta Invoice</caption><thead><tr><th>Peserta</th><th>Instansi</th></tr></thead><tbody><tr v-for="p in source.peserta" :key="p.id"><td>{{ p.nama }}</td><td>{{ p.instansi }}</td></tr></tbody></table></div><p>{{ source.judul }} · Harga {{ money(source.quotation.skema_harga === 'allin' ? source.quotation.harga_allin : source.quotation.harga_peserta) }}{{ source.quotation.skema_harga === 'allin' ? ' all-in' : ' / peserta' }} · Diskon {{ money(source.quotation.diskon) }}</p></section>
            <section class="panel"><h2><span class="section-number">4</span> Perhitungan Invoice</h2><InvoiceAmounts :amounts="source.amounts" :treatment="source.perlakuan_ppn" :taxed="!!source.quotation.is_ppn" /></section>

            <section class="panel"><h2><span class="section-number">5</span> Jurnal</h2><label>Cari COA Penjualan<input v-model="query" type="search" placeholder="Nomor atau nama akun" /></label><label>COA Penjualan<select v-model="form.coa_penjualan" required><option value="">Pilih akun sah</option><option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.no_perkiraan }} · {{ a.nama }} · Cabang {{ a.kdcab || '—' }} · ID {{ a.id }}</option></select></label><button type="button" @click="preview">Perbarui jurnal</button><p v-for="(errors, key) in configurationErrors" :key="key" class="error" role="alert">{{ errors.join(' ') }}</p><InvoiceJournal :rows="journal" :program="source.program.nomor" /></section>
            <div class="form-actions invoice-actions"><Link :href="appUrl('/invoicing')">Back</Link><button class="primary" :disabled="form.processing || confirming || Object.keys(configurationErrors).length > 0">{{ form.processing ? 'Menyimpan…' : 'Save' }}</button></div>
        </form>
    </AppLayout>
</template>
