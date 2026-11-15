<script setup lang="ts">
import { useConfirmedMutation } from "../../composables/confirmedMutation";
import ServerTable from "../../components/ServerTable.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import PageHead from '../../components/PageHead.vue';

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Row = { id_group: number; nm_group: string; ket: string | null; st_default: number | null; n_user: number; n_hak: number };
const props = defineProps<{ admin: number; can: { add: boolean; manage: boolean; delete: boolean; permissions: boolean } }>();
const editing = ref<Row | null>(null);
const adding = ref(false);
const form = useForm({ nama: '', ket: '' });
function open(row: Row | null) { form.reset(); form.clearErrors(); editing.value = row; adding.value = !row; if (row) Object.assign(form, { nama: row.nm_group, ket: row.ket ?? '' }); }
const close = () => { editing.value = null; adding.value = false; };
function save() {
    return confirmation.form(form, editing.value ? 'put' : 'post', editing.value ? `/master/role/${editing.value.id_group}` : '/master/role', { title: `${editing.value ? 'Simpan perubahan role' : 'Tambahkan role'} ${form.nama}?` }, {
        preserveScroll: true, onSuccess: () => { close(); reload(); },
    });
}
const remove = (row: Row) => confirmation.visit('delete', `/master/role/${row.id_group}`, {}, {
    title: `Hapus ${row.nm_group} (${row.id_group})?`, text: 'Hak role ini ikut dihapus. Role yang masih dipakai user tidak dapat dihapus.', confirmLabel: 'Ya, hapus', danger: true,
}, { preserveScroll: true, onSuccess: reload });
const system = (r: Row) => r.id_group === props.admin || !!r.st_default;

const table = ref<InstanceType<typeof ServerTable> | null>(null);
const reload = () => table.value?.reload();
const columns = computed(() => [{ title: 'No', orderable: false }, { title: 'Nama Role', orderable: true }, { title: 'User terdaftar', orderable: true }, { title: 'Hak diatur', orderable: true }, { title: 'Action', orderable: false }]);
</script>

<template>
    <Head title="Master Role" />
    <AppLayout title="Master Role" full-width>
        <PageHead title="Master Role" description="Daftar role yang dipakai di Master User dan diatur haknya di Role & Permission. Role diinput manual. Role yang masih dipakai user tidak dapat dihapus."><button v-if="can.add" type="button" class="primary" @click="open(null)" :disabled="confirming">+ Tambah Role</button></PageHead>
        <p v-if="($page.props.errors as Record<string, string>).hapus" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).hapus }}</p>
        <ServerTable ref="table" url="/master/role/data" :columns="columns" :label="'Role'">
            <template #cell-0="{ number }">{{ number }}</template>
            <template #cell-1="{ row }">{{ row.nm_group }}<small v-if="row.ket">{{ row.ket }}</small><small v-if="row.id_group === admin"><span class="badge">Terkunci</span></small></template>
            <template #cell-2="{ row }">{{ row.n_user }}</template>
            <template #cell-3="{ row }">{{ row.id_group === admin ? 'Semua' : row.n_hak }}</template>
            <template #cell-4="{ row }"><ActionMenu >
                        <button v-if="can.manage" type="button" @click="open(row)" :disabled="confirming">Edit</button>
                        <Link v-if="can.permissions && row.id_group !== admin" :href="`/permissions?role=${row.id_group}`">Atur Hak</Link>
                        <button v-if="can.delete && !system(row)" type="button" class="danger" @click="remove(row)" :disabled="confirming">Hapus</button>
                    </ActionMenu></template>
        </ServerTable>

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit Role ${editing.nm_group}` : 'Tambah Role'" :busy="form.processing || confirming" @confirm="save" @cancel="close">
            <div class="stack">
                <FormField id="rnama" label="Nama Role" :error="form.errors.nama" v-slot="{ describedBy, invalid }"><input id="rnama" v-model="form.nama" maxlength="50" required :readonly="editing?.id_group === admin" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="rket" label="Keterangan" :error="form.errors.ket" v-slot="{ describedBy, invalid }"><input id="rket" v-model="form.ket" maxlength="100" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
            </div>
        </ConfirmDialog>
    </AppLayout>
</template>
