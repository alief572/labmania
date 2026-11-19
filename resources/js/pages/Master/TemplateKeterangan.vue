<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import { useConfirmedMutation } from "../../composables/confirmedMutation";
import ServerTable from "../../components/ServerTable.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import { computed, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import PageHead from '../../components/PageHead.vue';

import StatusBadge from '../../components/StatusBadge.vue';

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Row = { id: number; kode: string; kategori: string; teks: string; berlaku_untuk: string; is_default: number; aktif: number };
const props = defineProps<{
    filters: { kategori: string; status: string }; kategori: string[]; untuk: string[];
    can: { add: boolean; manage: boolean };
}>();
const label: Record<string, string> = { syarat: 'Syarat keikutsertaan', termasuk: 'Termasuk', tidak: 'Tidak termasuk', dokumen: 'Dokumen persyaratan', klausul: 'Klausul tanda tangan', semua: 'Semua', public: 'Public', b2b: 'B2B' };
const filter = ref({ ...props.filters, q: '' });
watch(() => [filter.value.kategori, filter.value.status], () => { appliedFilters.value = { ...filter.value }; table.value?.reload(true); });
const editing = ref<Row | null>(null);
const adding = ref(false);
const form = useForm({ kategori: '', teks: '', berlaku_untuk: 'semua', is_default: false });
function open(row: Row | null) {
    form.reset(); form.clearErrors(); editing.value = row; adding.value = !row;
    if (row) Object.assign(form, { kategori: row.kategori, teks: row.teks, berlaku_untuk: row.berlaku_untuk, is_default: !!row.is_default });
}
const close = () => { editing.value = null; adding.value = false; };
function save() {
    return confirmation.form(form, editing.value ? 'put' : 'post', editing.value ? `/master/template-keterangan/${editing.value.id}` : '/master/template-keterangan', { title: editing.value ? `Simpan perubahan template ${editing.value.kode}?` : 'Tambahkan template keterangan?' }, {
        preserveScroll: true, onSuccess: () => { close(); reload(); },
    });
}
const toggle = (row: Row) => confirmation.visit('post', `/master/template-keterangan/${row.id}/toggle`, {}, {
    title: `${row.aktif ? 'Nonaktifkan' : 'Aktifkan'} ${'template keterangan'} (${row.kode})?`,
    confirmLabel: row.aktif ? 'Ya, nonaktifkan' : 'Ya, aktifkan', danger: !!row.aktif,
}, { preserveScroll: true, onSuccess: reload });

const table = ref<InstanceType<typeof ServerTable> | null>(null);
const reload = () => table.value?.reload();
const appliedFilters = ref({ ...props.filters, q: '' });
const columns = computed(() => [{ title: 'No', orderable: false }, { title: 'Kode', orderable: true }, { title: 'Kategori', orderable: true }, { title: 'Teks', orderable: true }, { title: 'Berlaku untuk', orderable: true }, { title: 'Default', orderable: true }, { title: 'Status', orderable: true }, { title: 'Action', orderable: false }]);
</script>

<template>
    <Head title="Template Keterangan Quotation" />
    <AppLayout title="Template Keterangan Quotation" full-width>
        <PageHead title="Template Keterangan Quotation"><button v-if="can.add" type="button" class="primary" @click="open(null)" :disabled="confirming">+ Tambah Butir</button></PageHead>
        <p class="notice">Perubahan di sini hanya berlaku untuk quotation <strong>baru</strong>; quotation yang sudah dibuat menyimpan teksnya sendiri. Tidak dapat dihapus — gunakan Nonaktifkan.</p>
        <div class="filters">
            <label class="grow">Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Cari kode atau teks…" /></label>
            <label>Kategori<select v-model="filter.kategori"><option value="">Semua kategori</option><option v-for="k in kategori" :key="k" :value="k">{{ label[k] }}</option></select></label>
            <label>Status<select v-model="filter.status"><option value="all">Semua status</option><option value="on">Aktif</option><option value="off">Nonaktif</option></select></label>
        </div>
        <ServerTable ref="table" :url="appUrl('/master/template-keterangan/data')" :columns="columns" :filters="appliedFilters" :keyword="filter.q" label="Template Keterangan Quotation">
            <template #cell-0="{ number }">{{ number }}</template>
            <template #cell-1="{ row }"><span class="code">{{ row.kode }}</span></template>
            <template #cell-2="{ row }">{{ label[row.kategori] ?? row.kategori }}</template>
            <template #cell-3="{ row }">{{ row.teks }}</template>
            <template #cell-4="{ row }"><span v-if="row.berlaku_untuk === 'public' || row.berlaku_untuk === 'b2b'" class="tag" :class="row.berlaku_untuk === 'b2b' ? 'tag-b2b' : 'tag-public'">{{ label[row.berlaku_untuk] }}</span><template v-else>{{ label[row.berlaku_untuk] }}</template></template>
            <template #cell-5="{ row }"><b v-if="row.is_default">Ya</b><span v-else class="muted">Tidak</span></template>
            <template #cell-6="{ row }"><StatusBadge :active="row.aktif" /></template>
            <template #cell-7="{ row }"><ActionMenu v-if="can.manage" ><button type="button" @click="open(row)" :disabled="confirming">Edit</button><button type="button" @click="toggle(row)" :disabled="confirming">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></ActionMenu></template>
        </ServerTable>

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit butir ${editing.kode}` : 'Tambah butir'" :busy="form.processing || confirming" @confirm="save" @cancel="close">
            <div class="grid2">
                <FormField id="ketkode" label="Kode" v-slot="{ describedBy }"><input id="ketkode" :value="editing?.kode ?? ''" placeholder="Dibuat otomatis" readonly aria-readonly="true" :aria-describedby="describedBy" /></FormField>
                <FormField id="kat" label="Kategori" :error="form.errors.kategori" v-slot="{ describedBy, invalid }">
                    <select id="kat" v-model="form.kategori" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Pilih —</option><option v-for="k in kategori" :key="k" :value="k">{{ label[k] }}</option></select>
                </FormField>
                <FormField class="span2" id="teks" label="Teks" hint="Satu butir; tercetak sebagai satu baris di Keterangan Penawaran" :error="form.errors.teks" v-slot="{ describedBy, invalid }"><textarea id="teks" v-model="form.teks" rows="3" maxlength="500" required :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
                <FormField id="untuk" label="Berlaku untuk" hint="Jenis penjualan yang menerima butir ini" :error="form.errors.berlaku_untuk" v-slot="{ describedBy, invalid }">
                    <select id="untuk" v-model="form.berlaku_untuk" :aria-invalid="invalid" :aria-describedby="describedBy"><option v-for="u in untuk" :key="u" :value="u">{{ label[u] }}</option></select>
                </FormField>
                <div class="field"><span class="static-label">Default</span>
                    <label class="chk"><input v-model="form.is_default" type="checkbox" /> Otomatis masuk quotation baru</label>
                    <small>Bila tidak, butir hanya tersedia sebagai pilihan tambahan</small></div>
            </div>
        </ConfirmDialog>
    </AppLayout>
</template>
