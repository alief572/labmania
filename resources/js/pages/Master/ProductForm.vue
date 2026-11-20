<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import PageHead from '../../components/PageHead.vue';
import FormField from '../../components/FormField.vue';
import { useConfirmedMutation } from '../../composables/confirmedMutation';

type Opt = { id: number; nama: string };
const props = defineProps<{ options: { produk: Opt[]; bidang: Opt[]; varian: Opt[]; sertifikasi: Opt[] } }>();
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
const fields = [['produk', 'Produk'], ['bidang', 'Bidang'], ['varian', 'Varian'], ['sertifikasi', 'Sertifikat']] as const;
const blank = () => ({ produk: '', bidang: '', varian: '', sertifikasi: '' }) as Record<string, string | number>;
const form = useForm({ judul: '', rows: [blank()] });
const n = computed(() => form.rows.length);
const errors = computed(() => form.errors as Record<string, string>);
const rowError = (i: number) => errors.value[`rows.${i}`] ?? fields.map(([k]) => errors.value[`rows.${i}.${k}`]).find(Boolean);
const addRow = () => { form.rows.push(blank()); };
const delRow = (i: number) => { if (form.rows.length > 1) form.rows.splice(i, 1); };
const save = () => confirmation.form(form, 'post', '/master/product', { title: `Buat ${n.value} Product berjudul ${form.judul}?`, text: 'Jika salah satu baris bentrok, tidak ada yang tersimpan.' });
</script>

<template>
    <Head title="Tambah Product" />
    <AppLayout title="Product" full-width>
        <PageHead title="Tambah Product" />
        <form class="product-form" @submit.prevent="save">
            <section class="panel">
            <FormField id="cf-judul" label="Judul" :error="form.errors.judul" v-slot="{ describedBy, invalid }">
                <input id="cf-judul" v-model="form.judul" maxlength="150" required placeholder="Judul / topik paket, diisi manual" :aria-invalid="invalid" :aria-describedby="describedBy" />
            </FormField>
            <div class="sub-head"><h2>Kombinasi Atribut</h2><span class="muted">{{ n }} baris → akan menjadi {{ n }} paket produk</span></div>
            <div class="table-wrap combine-table"><table class="tr-table">
                <caption class="sr-only">Kombinasi atribut Product</caption>
                <thead><tr><th class="num">#</th><th v-for="[, label] in fields" :key="label">{{ label }}</th><th class="act">Action</th></tr></thead>
                <tbody>
                    <template v-for="(row, i) in form.rows" :key="i">
                        <tr :class="{ 'row-err': rowError(i) }">
                            <td class="num">{{ i + 1 }}</td>
                            <td v-for="[key, label] in fields" :key="key">
                                <select v-model="row[key]" :aria-label="`${label} baris ${i + 1}`" required><option value="">— Pilih {{ label }} —</option><option v-for="o in props.options[key]" :key="o.id" :value="o.id">{{ o.nama }}</option></select>
                            </td>
                            <td class="act"><button type="button" class="btn btn-ghost sm" :disabled="n === 1 || confirming" @click="delRow(i)">Hapus</button></td>
                        </tr>
                        <tr v-if="rowError(i)" class="row-msg"><td></td><td colspan="5" class="error" role="alert">{{ rowError(i) }}</td></tr>
                    </template>
                </tbody>
            </table></div>
            <button type="button" class="btn btn-outline" :disabled="confirming || n >= 50" @click="addRow">+ Tambah Baris</button>
            <p class="hint-line">Antar paket minimal satu dari empat atribut harus berbeda. Hanya data master berstatus Aktif yang dapat dipilih.</p>
            </section>
            <div class="form-actions page-actions"><Link class="btn btn-ghost" :href="appUrl('/master/product')">Batal</Link><button class="btn btn-primary" :disabled="form.processing || confirming">Simpan {{ n }} Paket</button></div>
        </form>
    </AppLayout>
</template>

<style scoped>
.product-form .sub-head { margin: 22px 0 10px; }
.combine-table { margin-bottom: 12px; }
.combine-table table { min-width: 760px; }
.combine-table th { padding: 10px 14px; font-size: 11.5px; letter-spacing: .04em; }
.combine-table td { padding: 11px 14px; }
.combine-table .num { width: 56px; }
.combine-table .act { width: 96px; text-align: right; white-space: nowrap; }
.combine-table select { width: 100%; min-width: 0; }
.combine-table .row-msg td { padding-top: 0; }
.product-form > .form-actions { margin-top: 16px; gap: 8px; flex-wrap: wrap; }
</style>
