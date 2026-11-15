<script setup lang="ts">
import ServerTable from "../../components/ServerTable.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import { ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import { useConfirmedMutation } from '../../composables/confirmedMutation';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Row = { id: number; parent_id: number; kode: string; judul: string; link: string; ikon: string | null; kelompok: number; aktif: number; sistem: number; aksi: string[]; level: number; tipe: 'modul' | 'menu' };
const props = defineProps<{ rows: Row[]; moduls: { id: number; kode: string; judul: string }[]; groupLabels: Record<string, Record<string, string>>; icons: string[]; aksi: Record<string, string>; unmapped: { id: number; judul: string; link: string }[]; can: { add: boolean; manage: boolean } }>();
const labelsFor = (parent: number) => props.groupLabels[props.moduls.find(m => m.id === parent)?.kode ?? ''] ?? {};
const errors = () => usePage().props.errors as Record<string, string>;
const editing = ref<Row | null>(null);
const adding = ref(false);
const form = useForm({ tipe: 'menu' as 'modul' | 'menu', judul: '', link: '', ikon: '', parent_id: 0, kelompok: 1, aksi: ['lihat'] as string[] });
function open(row: Row | null) {
    form.reset(); form.clearErrors(); editing.value = row; adding.value = !row;
    if (row) Object.assign(form, { tipe: row.tipe, judul: row.judul, link: row.tipe === 'modul' ? '' : row.link, ikon: row.ikon ?? '', parent_id: row.parent_id, kelompok: row.kelompok, aksi: [...row.aksi] });
}
const close = () => { editing.value = null; adding.value = false; };
function save() {
    const done = { preserveScroll: true, onSuccess: close };
    return confirmation.form(form, editing.value ? 'put' : 'post', editing.value ? `/pengaturan/menu/${editing.value.id}` : '/pengaturan/menu',
        { title: `Simpan menu ${form.judul}?`, text: 'Perubahan aksi menu dapat mencabut izin terkait dari role dan user.' }, done);
}
const toggle = (r: Row) => confirmation.visit('post', `/pengaturan/menu/${r.id}/toggle`, {}, { title: `${r.aktif ? 'Nonaktifkan' : 'Aktifkan'} menu ${r.judul}?`, danger: !!r.aktif }, { preserveScroll: true });
const move = (r: Row, dir: 'up' | 'down') => confirmation.visit('post', `/pengaturan/menu/${r.id}/pindah/${dir}`, {}, { title: `Pindahkan menu ${r.judul} ke ${dir === 'up' ? 'atas' : 'bawah'}?` }, { preserveScroll: true });
const isMenu = () => form.tipe === 'menu';
</script>

<template>
    <Head title="Menu Sidebar" />
    <AppLayout title="Menu Sidebar" full-width>
        <div class="page-head"><h1>Menu Sidebar</h1><button v-if="can.add" type="button" class="primary" @click="open(null)">+ Tambah Menu</button></div>
        <p class="muted">Struktur menu dan aksi yang dapat diberi hak di Role &amp; Permission. Menu sistem tidak dapat dinonaktifkan dan linknya terkunci.</p>
        <div v-if="unmapped.length" class="notice" role="status">
            <p>Menu berikut belum mempunyai route Laravel yang tersedia dan belum ditampilkan di sidebar:</p>
            <ul><li v-for="menu in unmapped" :key="menu.id">{{ menu.judul }}: <code>{{ menu.link }}</code></li></ul>
        </div>
        <p v-if="errors().aktif" class="error" role="alert">{{ errors().aktif }}</p>
        <ServerTable url="/pengaturan/menu/data" label="Menu Sidebar" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "Menu", "orderable": true, "className": ""}, {"title": "Link", "orderable": true, "className": ""}, {"title": "Kelompok", "orderable": true, "className": ""}, {"title": "Aksi", "orderable": false, "className": ""}, {"title": "Status", "orderable": true, "className": ""}, {"title": "Option", "orderable": false, "className": ""}]' :filters="{}" refresh-on-visit ><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }"><b>{{ p.judul }}</b> <span v-if="p.sistem" class="badge">Sistem</span><small>{{ p.kode }}</small><small v-if="p.parent_judul">Induk: {{ p.parent_judul }}</small></template><template #cell-2="{ row: p, number }">{{ p.tipe==='modul'?'Modul':p.link }}</template><template #cell-3="{ row: p, number }">{{ p.tipe==='modul'?'—':labelsFor(p.parent_id)[p.kelompok] || p.kelompok }}</template><template #cell-4="{ row: p, number }"><span v-for="a in p.aksi" :key="a" class="badge">{{ aksi[a] ?? a }}</span></template><template #cell-5="{ row: p, number }"><span class="badge" :class="{on:!!p.aktif,off:!p.aktif}">{{ p.aktif?'Aktif':'Nonaktif' }}</span></template><template #cell-6="{ row: p, number }"><ActionMenu v-if="can.manage"><button type="button" @click="open(p)">Edit</button><button type="button" :aria-label="`Naikkan ${p.judul}`" @click="move(p,'up')">Naik</button><button type="button" :aria-label="`Turunkan ${p.judul}`" @click="move(p,'down')">Turun</button><button v-if="!p.sistem" type="button" @click="toggle(p)">{{ p.aktif?'Nonaktifkan':'Aktifkan' }}</button></ActionMenu></template></ServerTable>

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit Menu ${editing.judul}` : 'Tambah Menu'" :busy="form.processing || confirming" @confirm="save" @cancel="close">
            <div class="stack">
                <FormField v-if="adding" id="mtipe" label="Tipe" v-slot="{ describedBy }"><select id="mtipe" v-model="form.tipe" :aria-describedby="describedBy"><option value="menu">Menu</option><option value="modul">Modul (induk)</option></select></FormField>
                <FormField id="mjudul" label="Judul" :error="form.errors.judul" v-slot="{ describedBy, invalid }"><input id="mjudul" v-model="form.judul" maxlength="100" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <template v-if="isMenu()">
                    <FormField id="mlink" label="Link" hint="Huruf, angka, _ - dan /; tanpa awalan /." :error="form.errors.link" v-slot="{ describedBy, invalid }"><input id="mlink" v-model="form.link" maxlength="150" required :readonly="!!editing?.sistem" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="mparent" label="Modul induk" :error="form.errors.parent_id" v-slot="{ describedBy, invalid }"><select id="mparent" v-model.number="form.parent_id" :aria-invalid="invalid" :aria-describedby="describedBy"><option :value="0">Tingkat atas</option><option v-for="m in moduls" :key="m.id" :value="m.id">{{ m.judul }}</option></select></FormField>
                    <FormField id="mkel" label="Kelompok" v-slot="{ describedBy }"><input id="mkel" v-model.number="form.kelompok" type="number" min="1" max="99" :list="Object.keys(labelsFor(form.parent_id)).length ? 'menu-groups' : undefined" :aria-describedby="describedBy" /><datalist id="menu-groups"><option v-for="(label, key) in labelsFor(form.parent_id)" :key="key" :value="key">{{ label }}</option></datalist><small v-if="labelsFor(form.parent_id)[form.kelompok]">{{ labelsFor(form.parent_id)[form.kelompok] }}</small></FormField>
                    <fieldset><legend>Aksi yang berlaku</legend>
                        <label v-for="(label, key) in aksi" :key="key" class="check"><input v-model="form.aksi" type="checkbox" :value="key" :disabled="key === 'lihat'" /> {{ label }}</label>
                        <p class="muted">Aksi yang dihapus mencabut izin terkait dari semua role dan user.</p>
                        <p v-if="form.errors.aksi" class="error" role="alert">{{ form.errors.aksi }}</p>
                    </fieldset>
                </template>
                <FormField v-if="isMenu() ? form.parent_id === 0 : true" id="mikon" label="Ikon" :error="form.errors.ikon" v-slot="{ describedBy, invalid }"><select id="mikon" v-model="form.ikon" :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Tanpa ikon</option><option v-for="i in icons" :key="i" :value="i">{{ i }}</option></select></FormField>
            </div>
        </ConfirmDialog>
    </AppLayout>
</template>
