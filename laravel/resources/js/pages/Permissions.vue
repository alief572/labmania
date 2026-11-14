<script setup lang="ts">
import { computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../layouts/AppLayout.vue';
import type { Menu } from '../types';

const props = defineProps<{ kind: 'role' | 'user'; targetId: number | null; targetName?: string; roles: { id_group: number; nm_group: string }[]; users: { id_user: number; nm_lengkap: string }[]; menus: Menu[]; selected: string[]; inherited: string[]; editable: boolean; canEnroll?: boolean }>();
const enroll = () => router.post(`/permissions/users/${props.targetId}/enroll-token`, {}, { preserveScroll: true });
const form = useForm({ grants: [...props.selected] });
const actions = ['View', 'Add', 'Manage', 'Delete', 'Approve'];
const chosen = computed(() => new Set([...form.grants, ...props.inherited]));
function toggle(menu: Menu, action: string, checked: boolean) {
    const values = new Set(form.grants);
    const key = `${menu.id}:${action}`;
    if (checked) { values.add(key); if (action !== 'View') values.add(`${menu.id}:View`); }
    else { values.delete(key); if (action === 'View') actions.forEach((a) => values.delete(`${menu.id}:${a}`)); }
    form.grants = [...values];
}
function choose(event: Event, kind: string) {
    const id = (event.target as HTMLSelectElement).value;
    if (id) router.get(kind === 'role' ? `/permissions?role=${id}` : `/permissions/users/${id}`, {}, { preserveState: false });
}
const save = () => form.put(`/permissions/${props.kind === 'role' ? 'roles' : 'users'}/${props.targetId}`);
</script>

<template>
    <Head :title="kind === 'role' ? 'Role & Permission' : 'Override Permission'" />
    <AppLayout :title="kind === 'role' ? 'Role & Permission' : 'Override Permission'">
        <h1>{{ kind === 'role' ? 'Role & Permission' : 'Override Permission' }}</h1>
        <p v-if="kind === 'user'" class="muted">{{ targetName }} · Hak dari role terkunci; override menambah hak.</p>
        <div class="filters">
            <label v-if="roles.length">Role<select :value="kind === 'role' ? targetId : ''" @change="choose($event, 'role')"><option v-for="role in roles" :key="role.id_group" :value="role.id_group">{{ role.nm_group }}</option></select></label>
            <label v-if="users.length">Override user<select :value="kind === 'user' ? targetId : ''" @change="choose($event, 'user')"><option value="">Pilih user</option><option v-for="user in users" :key="user.id_user" :value="user.id_user">{{ user.nm_lengkap }}</option></select></label>
        </div>
        <div v-if="kind === 'user' && canEnroll" class="notice">
            <p>Autentikator akun ini belum aktif.</p>
            <button type="button" class="primary" @click="enroll">Buat token enroll</button>
        </div>
        <form @submit.prevent="save">
            <p v-if="!editable" class="notice">Hak akses ini hanya dapat dilihat.</p>
            <p v-if="form.errors.grants" class="error" role="alert">{{ form.errors.grants }}</p>
            <div class="table-wrap"><table><caption class="sr-only">Hak akses per menu</caption><thead><tr><th scope="col">Menu</th><th v-for="action in actions" :key="action" scope="col">{{ action }}</th></tr></thead><tbody>
                <tr v-for="menu in menus.filter((item) => item.actions.length)" :key="menu.id"><th scope="row">{{ menu.judul }}</th><td v-for="action in actions" :key="action"><label v-if="menu.actions.includes(action)" class="checkbox-target"><input type="checkbox" :aria-label="`${menu.judul}: ${action}`" :checked="chosen.has(`${menu.id}:${action}`)" :disabled="!editable || inherited.includes(`${menu.id}:${action}`)" @change="toggle(menu, action, ($event.target as HTMLInputElement).checked)" /></label><span v-else aria-label="Tidak berlaku">Tidak berlaku</span><small v-if="inherited.includes(`${menu.id}:${action}`)">role</small></td></tr>
                <tr v-if="!menus.some((item) => item.actions.length)"><td colspan="6">Belum ada menu yang dikonfigurasi.</td></tr>
            </tbody></table></div>
            <div v-if="editable && targetId" class="form-actions"><button class="primary" :disabled="form.processing">{{ form.processing ? 'Menyimpan…' : 'Simpan Hak Akses' }}</button></div>
        </form>
    </AppLayout>
</template>
