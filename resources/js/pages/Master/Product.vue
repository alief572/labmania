<script setup lang="ts">
import { useConfirmedMutation } from "../../composables/confirmedMutation";
import ServerTable from "../../components/ServerTable.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import PageHead from '../../components/PageHead.vue';

import StatusBadge from '../../components/StatusBadge.vue';

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Opt = { id: number; nama: string; aktif?: number };
type Row = { id: number; nomor: string; judul: string; aktif: number; id_jenis_produk: number; id_jenis_bidang: number; id_varian_pelaksanaan: number | null; id_jenis_sertifikasi: number; nama_produk: string | null; nama_bidang: string | null; nama_varian: string | null; nama_sertifikasi: string | null };
const props = defineProps<{
    filters: { q: string; status: string };
    options: { produk: Opt[]; bidang: Opt[]; varian: Opt[]; sertifikasi: Opt[] };
    can: { add: boolean; manage: boolean };
    lingkupMax: { max_items: number; max_length: number };
}>();
const fields = [['produk', 'Produk'], ['bidang', 'Bidang'], ['varian', 'Varian'], ['sertifikasi', 'Sertifikat']] as const;
const filter = ref({ ...props.filters });
watch(() => filter.value.status, () => { appliedFilters.value = { ...filter.value }; table.value?.reload(true); });

const mode = ref<'edit' | null>(null);
const current = ref<Row | null>(null);
const editForm = useForm({ judul: '', produk: '' as string | number, bidang: '' as string | number, varian: '' as string | number, sertifikasi: '' as string | number });

const openAdd = () => router.get('/master/product/create');
const attrOptions = (key: 'produk' | 'bidang' | 'varian' | 'sertifikasi', value: unknown) => props.options[key].filter((o) => o.aktif !== 0 || String(o.id) === String(value));
const usedBy = ref(0);
function openEdit(row: Row) {
    editForm.clearErrors(); current.value = row; mode.value = 'edit'; usedBy.value = 0;
    fetch(`/master/product/${row.id}`, { headers: { Accept: 'application/json' } }).then((r) => r.json()).then((d) => { if (current.value?.id === row.id) usedBy.value = d.dipakai ?? 0; }).catch(() => undefined);
    Object.assign(editForm, { judul: row.judul, produk: row.id_jenis_produk, bidang: row.id_jenis_bidang, varian: row.id_varian_pelaksanaan ?? '', sertifikasi: row.id_jenis_sertifikasi });
}
const close = () => { mode.value = null; current.value = null; };
const done = { preserveScroll: true, onSuccess: () => { close(); reload(); } };
function saveEdit() {
    return confirmation.form(editForm, 'put', `/master/product/${current.value!.id}`, {
        title: `Simpan perubahan ${editForm.judul} (${current.value!.nomor})?`,
    }, done);
}
const toggle = (row: Row) => confirmation.visit('post', `/master/product/${row.id}/toggle`, {}, {
    title: `${row.aktif ? 'Nonaktifkan' : 'Aktifkan'} ${row.judul} (${row.nomor})?`,
    confirmLabel: row.aktif ? 'Ya, nonaktifkan' : 'Ya, aktifkan', danger: !!row.aktif,
}, { preserveScroll: true, onSuccess: reload });

const table = ref<InstanceType<typeof ServerTable> | null>(null);
const reload = () => table.value?.reload();
const appliedFilters = ref({ ...props.filters });
const columns = computed(() => [{ title: 'No', orderable: false }, { title: 'Product', orderable: true }, { title: 'Produk', orderable: true }, { title: 'Bidang', orderable: true }, { title: 'Varian', orderable: true }, { title: 'Sertifikat', orderable: true }, { title: 'Status', orderable: true }, { title: 'Action', orderable: false }]);
</script>

<template>
    <Head title="Product" />
    <AppLayout title="Product" full-width>
        <PageHead title="Product" description="Daftar paket produk (kombinasi Produk, Bidang, Varian, Sertifikat). Product berstatus Aktif dapat dipilih saat membuat Master Program, satu atau lebih per program."><button v-if="can.add" type="button" class="primary" @click="openAdd" :disabled="confirming">+ Tambah Product</button></PageHead>
        <p v-if="($page.props.errors as Record<string, string>).status" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).status }}</p>
        <div class="filters">
            <label class="grow">Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Cari nomor, judul, atau atribut…" /></label>
            <label>Status<select v-model="filter.status"><option value="all">Semua status</option><option value="on">Aktif</option><option value="off">Nonaktif</option></select></label>
        </div>
        <ServerTable ref="table" url="/master/product/data" :columns="columns" :filters="appliedFilters" :keyword="filter.q" label="Product">
            <template #cell-0="{ number }">{{ number }}</template>
            <template #cell-1="{ row }"><span class="code">{{ row.nomor }}</span><br>{{ row.judul }}</template>
            <template #cell-2="{ row }"><template v-if="row.aktif_produk === 0 || row.aktif_produk === '0'"><span class="attr-off" title="Atribut ini sudah dinonaktifkan">{{ row.nama_produk }}</span><small>(nonaktif)</small></template><template v-else>{{ row.nama_produk || '—' }}</template></template>
            <template #cell-3="{ row }"><template v-if="row.aktif_bidang === 0 || row.aktif_bidang === '0'"><span class="attr-off" title="Atribut ini sudah dinonaktifkan">{{ row.nama_bidang }}</span><small>(nonaktif)</small></template><template v-else>{{ row.nama_bidang || '—' }}</template></template>
            <template #cell-4="{ row }"><template v-if="!row.nama_varian">—<small>Belum diisi; lengkapi lewat Edit</small></template><template v-else-if="row.aktif_varian === 0 || row.aktif_varian === '0'"><span class="attr-off" title="Atribut ini sudah dinonaktifkan">{{ row.nama_varian }}</span><small>(nonaktif)</small></template><template v-else>{{ row.nama_varian }}</template></template>
            <template #cell-5="{ row }"><template v-if="row.aktif_sertifikasi === 0 || row.aktif_sertifikasi === '0'"><span class="attr-off" title="Atribut ini sudah dinonaktifkan">{{ row.nama_sertifikasi }}</span><small>(nonaktif)</small></template><template v-else>{{ row.nama_sertifikasi || '—' }}</template></template>
            <template #cell-6="{ row }"><StatusBadge :active="row.aktif" /></template>
            <template #cell-7="{ row }"><ActionMenu v-if="can.manage" >
                        <button type="button" @click="openEdit(row)" :disabled="confirming">Edit</button>
                        <button type="button" @click="toggle(row)" :disabled="confirming">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    </ActionMenu></template>
        </ServerTable>

        <ConfirmDialog v-if="mode === 'edit' && current" :title="`Edit Paket Produk ${current.nomor}`" :busy="editForm.processing || confirming" @confirm="saveEdit" @cancel="close">
            <div class="grid2">
                <FormField id="enomor" label="Nomor Product" v-slot="{ describedBy }"><input id="enomor" :value="current.nomor" readonly aria-readonly="true" :aria-describedby="describedBy" /></FormField>
                <FormField id="ejudul" label="Judul" :error="editForm.errors.judul" v-slot="{ describedBy, invalid }"><input id="ejudul" v-model="editForm.judul" maxlength="150" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField v-for="[key, label] in fields" :id="`e${key}`" :key="key" :label="label" :error="(editForm.errors as Record<string, string>)[key]" v-slot="{ describedBy, invalid }">
                    <select :id="`e${key}`" v-model="(editForm as any)[key]" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Pilih {{ label }} —</option><option v-for="o in attrOptions(key, (editForm as any)[key])" :key="o.id" :value="o.id">{{ o.nama }}{{ o.aktif === 0 ? ' (nonaktif)' : '' }}</option></select>
                </FormField>
            </div>
            <p v-if="(editForm.errors as Record<string, string>).kombinasi" class="error" role="alert">{{ (editForm.errors as Record<string, string>).kombinasi }}</p>
            <p v-if="usedBy" class="hint-line">Paket ini dipakai {{ usedBy }} Master Program — perubahan judul/atribut ikut tampil di sana.</p>
        </ConfirmDialog>

    </AppLayout>
</template>
