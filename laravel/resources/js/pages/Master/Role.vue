<script setup lang="ts">
import ActionMenu from "../../components/ActionMenu.vue";
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';

type Row = { id_group: number; nm_group: string; ket: string | null; st_default: number | null; n_user: number; n_hak: number };
const props = defineProps<{ rows: { data: Row[] }; admin: number; can: { add: boolean; manage: boolean; delete: boolean } }>();
const editing = ref<Row | null>(null);
const adding = ref(false);
const form = useForm({ nama: '', ket: '' });
function open(row: Row | null) { form.reset(); form.clearErrors(); editing.value = row; adding.value = !row; if (row) Object.assign(form, { nama: row.nm_group, ket: row.ket ?? '' }); }
const close = () => { editing.value = null; adding.value = false; };
function save() {
    const done = { preserveScroll: true, onSuccess: close };
    if (editing.value) form.put(`/master/role/${editing.value.id_group}`, done);
    else form.post('/master/role', done);
}
const removing = ref<Row | null>(null);
const remove = () => router.delete(`/master/role/${removing.value!.id_group}`, { preserveScroll: true, onFinish: () => { removing.value = null; } });
const system = (r: Row) => r.id_group === props.admin || !!r.st_default;
</script>

<template>
    <Head title="Master Role" />
    <AppLayout title="Master Role">
        <div class="page-head"><h1>Master Role</h1><button v-if="can.add" type="button" class="primary" @click="open(null)">+ Tambah Role</button></div>
        <p v-if="($page.props.errors as Record<string, string>).hapus" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).hapus }}</p>
        <div class="table-wrap"><table>
            <caption class="sr-only">Daftar role</caption>
            <thead><tr><th scope="col">No</th><th scope="col">Nama Role</th><th scope="col">User terdaftar</th><th scope="col">Hak diatur</th><th scope="col">Aksi</th></tr></thead>
            <tbody>
                <tr v-for="(row, i) in rows.data" :key="row.id_group">
                    <td>{{ i + 1 }}</td><td>{{ row.nm_group }}<small v-if="row.ket">{{ row.ket }}</small><small v-if="row.id_group === admin"><span class="badge">Terkunci</span></small></td>
                    <td>{{ row.n_user }}</td><td>{{ row.id_group === admin ? 'Semua' : row.n_hak }}</td>
                    <td><ActionMenu >
                        <button v-if="can.manage" type="button" @click="open(row)">Edit</button>
                        <Link v-if="row.id_group !== admin" :href="`/permissions?role=${row.id_group}`">Atur Hak</Link>
                        <button v-if="can.delete && !system(row)" type="button" class="danger" @click="removing = row">Hapus</button>
                    </ActionMenu></td>
                </tr>
            </tbody>
        </table></div>

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit Role ${editing.nm_group}` : 'Tambah Role'" :busy="form.processing" @confirm="save" @cancel="close">
            <div class="stack">
                <FormField id="rnama" label="Nama Role" :error="form.errors.nama" v-slot="{ describedBy, invalid }"><input id="rnama" v-model="form.nama" maxlength="50" required :readonly="editing?.id_group === admin" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="rket" label="Keterangan" :error="form.errors.ket" v-slot="{ describedBy, invalid }"><input id="rket" v-model="form.ket" maxlength="100" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
            </div>
        </ConfirmDialog>
        <ConfirmDialog v-if="removing" :title="`Hapus role ${removing.nm_group}?`" confirm-label="Hapus" danger @confirm="remove" @cancel="removing = null"><p>Hak role ini ikut dihapus. Role yang masih dipakai user tidak dapat dihapus.</p></ConfirmDialog>
    </AppLayout>
</template>
