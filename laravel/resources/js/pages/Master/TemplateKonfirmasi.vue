<script setup lang="ts">
import { useConfirmedMutation } from "../../composables/confirmedMutation";
import ServerTable from "../../components/ServerTable.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import PageHead from '../../components/PageHead.vue';

import StatusBadge from '../../components/StatusBadge.vue';

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Row = { id: number; kode: string; nama: string; label_kegiatan: string; id_jenis_produk: number | null; nama_produk: string | null; isian: string[]; pernyataan: string; catatan: string | null; aktif: number };
const props = defineProps<{
    fields: Record<string, string>; produk: { id: number; nama: string }[]; can: { add: boolean; manage: boolean };
}>();
const editing = ref<Row | null>(null);
const adding = ref(false);
const form = useForm({ nama: '', label_kegiatan: '', id_jenis_produk: '' as string | number, isian: [] as string[], pernyataan: '', catatan: '' });
const sample = ref<{ template: { nama: string; label_kegiatan: string; isian: string[]; pernyataan: string; catatan: string | null }; fields: Record<string, string>; program: string | null } | null>(null);
function open(row: Row | null) {
    form.reset(); form.clearErrors(); editing.value = row; adding.value = !row;
    if (row) Object.assign(form, { nama: row.nama, label_kegiatan: row.label_kegiatan, id_jenis_produk: row.id_jenis_produk ?? '', isian: [...row.isian], pernyataan: row.pernyataan, catatan: row.catatan ?? '' });
}
const close = () => { editing.value = null; adding.value = false; };
function save() {
    return confirmation.form(form, editing.value ? 'put' : 'post', editing.value ? `/master/template-konfirmasi/${editing.value.id}` : '/master/template-konfirmasi', { title: `${editing.value ? 'Simpan perubahan' : 'Tambahkan'} ${form.nama}${editing.value ? ` (${editing.value.kode})` : ''}?` }, {
        preserveScroll: true, onSuccess: () => { close(); reload(); },
    });
}
const toggle = (row: Row) => confirmation.visit('post', `/master/template-konfirmasi/${row.id}/toggle`, {}, {
    title: `${row.aktif ? 'Nonaktifkan' : 'Aktifkan'} ${row.nama} (${row.kode})?`,
    confirmLabel: row.aktif ? 'Ya, nonaktifkan' : 'Ya, aktifkan', danger: !!row.aktif,
}, { preserveScroll: true, onSuccess: reload });
async function contoh(row: Row) { sample.value = await (await fetch(`/master/template-konfirmasi/${row.id}/contoh`, { headers: { Accept: 'application/json' } })).json(); }

const table = ref<InstanceType<typeof ServerTable> | null>(null);
const reload = () => table.value?.reload();
const columns = computed(() => [{ title: 'No', orderable: false }, { title: 'Kode', orderable: true }, { title: 'Nama', orderable: true }, { title: 'Default untuk', orderable: true }, { title: 'Isian', orderable: false }, { title: 'Status', orderable: true }, { title: 'Action', orderable: false }]);
</script>

<template>
    <Head title="Template Formulir Konfirmasi" />
    <AppLayout title="Template Formulir Konfirmasi" full-width>
        <PageHead title="Template Formulir Konfirmasi" description="Template Formulir Konfirmasi Keikutsertaan yang dicetak bersama quotation. Template dipilih otomatis dari Jenis Produk paket, lalu isian dan kalimat pernyataannya dapat disesuaikan per quotation."><button v-if="can.add" type="button" class="primary" @click="open(null)" :disabled="confirming">+ Tambah Template</button></PageHead>
        <p class="notice">Perubahan di sini hanya berlaku untuk quotation <strong>baru</strong>. Tidak dapat dihapus — gunakan Nonaktifkan.</p>
        <ServerTable ref="table" url="/master/template-konfirmasi/data" :columns="columns" label="Template Formulir Konfirmasi">
            <template #cell-0="{ number }">{{ number }}</template>
            <template #cell-1="{ row }">{{ row.kode }}</template>
            <template #cell-2="{ row }">{{ row.nama }}<small>{{ row.label_kegiatan }}</small></template>
            <template #cell-3="{ row }">{{ row.nama_produk ?? 'Semua' }}</template>
            <template #cell-4="{ row }">{{ row.isian.length }} isian</template>
            <template #cell-5="{ row }"><StatusBadge :active="row.aktif" /></template>
            <template #cell-6="{ row }"><ActionMenu ><button type="button" @click="contoh(row)" :disabled="confirming">Lihat contoh</button><template v-if="can.manage"><button type="button" @click="open(row)" :disabled="confirming">Edit</button><button type="button" @click="toggle(row)" :disabled="confirming">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></template></ActionMenu></template>
        </ServerTable>

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit template ${editing.kode}` : 'Tambah template'" :busy="form.processing || confirming" @confirm="save" @cancel="close">
            <div class="stack">
                <FormField id="nama" label="Nama template" :error="form.errors.nama" v-slot="{ describedBy, invalid }"><input id="nama" v-model="form.nama" maxlength="80" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="lk" label="Label kegiatan" :error="form.errors.label_kegiatan" v-slot="{ describedBy, invalid }"><input id="lk" v-model="form.label_kegiatan" maxlength="80" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="jp" label="Default untuk Jenis Produk" :error="form.errors.id_jenis_produk" v-slot="{ describedBy, invalid }">
                    <select id="jp" v-model="form.id_jenis_produk" :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Semua</option><option v-for="p in produk" :key="p.id" :value="p.id">{{ p.nama }}</option></select>
                </FormField>
                <fieldset><legend>Isian formulir</legend>
                    <label v-for="(text, key) in fields" :key="key"><input v-model="form.isian" type="checkbox" :value="key" /> {{ text }}</label>
                    <p v-if="form.errors.isian" class="error" role="alert">{{ form.errors.isian }}</p>
                </fieldset>
                <FormField id="pn" label="Kalimat pernyataan" :error="form.errors.pernyataan" v-slot="{ describedBy, invalid }"><textarea id="pn" v-model="form.pernyataan" required :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
                <FormField id="ct" label="Catatan (satu butir per baris)" :error="form.errors.catatan" v-slot="{ describedBy, invalid }"><textarea id="ct" v-model="form.catatan" :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
            </div>
        </ConfirmDialog>

        <ConfirmDialog v-if="sample" :title="`Contoh: ${sample.template.nama}`" confirm-label="Tutup" @confirm="sample = null" @cancel="sample = null">
            <p class="muted">Program contoh: {{ sample.program ?? 'belum ada Penjualan Program Final' }}</p>
            <h3>{{ sample.template.label_kegiatan }}</h3>
            <ul><li v-for="k in sample.template.isian" :key="k">{{ sample.fields[k] }}</li></ul>
            <p>{{ sample.template.pernyataan }}</p>
            <p v-if="sample.template.catatan" style="white-space: pre-line">{{ sample.template.catatan }}</p>
        </ConfirmDialog>
    </AppLayout>
</template>
