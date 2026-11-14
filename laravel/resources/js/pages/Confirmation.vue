<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import type { SharedProps } from '../types';
import { postPdf } from '../composables/pdf';
import { rupiah } from '../composables/costing';
type Person = { nama: string; jabatan: string; email: string; wa: string; nik: string };
type Company = { nama_perusahaan: string; alamat_perusahaan: string; pic_nama: string; pic_jabatan: string; pic_hp: string; pic_email: string };
const props = defineProps<{ token: string; closed: string | null; info: { nomor: string; judul: string; jenis: string; tgl_mulai: string; tgl_selesai: string; waktu: string; lokasi: string; kuota: number; sisa: number; kepada: string; berlaku_sampai: string; pernyataan: string; fields: string[]; kolom: string[]; paket_maks: number | null; tambahan: string | null; sales_nama: string | null; sales_telp: string | null }; fieldLabels: Record<string, string>; company: Company | null }>();
const page = usePage<SharedProps>();
const blankPerson = (): Person => ({ nama: '', jabatan: '', email: '', wa: '', nik: '' });
const form = useForm({ nama: '', nik: '', jabatan: '', instansi: '', alamat: '', hp: '', wa: '', email: '', pengalaman: '', lsp: '', habis: '', ex: {} as Record<string, string>, ...({ nama_perusahaan: '', alamat_perusahaan: '', pic_nama: '', pic_jabatan: '', pic_hp: '', pic_email: '', ...props.company }), peserta: Array.from({ length: Math.min(1000, Math.max(10, props.info.kuota)) }, blankPerson), surat: null as File | null, setuju: false });
const b2b = props.info.jenis === 'b2b'; const busy = ref(false); const error = ref(''); const paste = ref('');
const errorList = computed(() => Object.values(form.errors));
async function preview() { busy.value = true; error.value = ''; try { await postPdf(`/konfirmasi/${props.token}/preview`, form.data()); } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; } }
function submit() { form.post(`/konfirmasi/${props.token}`, { forceFormData: true, onSuccess: () => { form.reset('surat', 'setuju', 'nama', 'nik', 'email', 'wa', 'hp'); } }); }
function pasteRows() {
    const columns = ['nama', ...props.info.kolom] as (keyof Person)[];
    const lines = paste.value.split(/\r?\n/).filter((line) => line.trim());
    if (lines.length > 1000) { error.value = 'Maksimal 1.000 baris per kiriman. Hubungi Sales untuk daftar lebih besar.'; return; }
    form.peserta = lines.map((line) => { const p = blankPerson(); line.split('\t').forEach((cell, i) => { if (columns[i]) p[columns[i]] = cell.trim(); }); return p; }); paste.value = '';
}
const type = (key: string) => key === 'email' ? 'email' : key === 'habis' ? 'date' : ['hp', 'wa'].includes(key) ? 'tel' : 'text';
</script>
<template>
    <Head title="Konfirmasi Kehadiran LabMania" />
    <main class="confirmation-page"><header><p class="muted">LabMania · Konfirmasi Kehadiran</p><h1>{{ info.judul }}</h1><p>{{ info.nomor }} · {{ info.kepada }}</p></header>
        <section class="panel"><h2>Informasi program</h2><dl><dt>Tanggal</dt><dd>{{ info.tgl_mulai }} s.d. {{ info.tgl_selesai }}</dd><dt>Waktu</dt><dd>{{ info.waktu || 'Hubungi Sales' }}</dd><dt>Lokasi</dt><dd>{{ info.lokasi || 'Hubungi Sales' }}</dd><dt>Berlaku sampai</dt><dd>{{ info.berlaku_sampai }} (paling lambat H-14 acara)</dd><dt v-if="!b2b">Sisa kuota</dt><dd v-if="!b2b">{{ info.sisa }} dari {{ info.kuota }}</dd><dt>Kontak Sales</dt><dd>{{ info.sales_nama }} {{ info.sales_telp }}</dd></dl></section>
        <p v-if="page.props.flash.success" class="notice" role="status">{{ page.props.flash.success }}</p><p v-if="closed" class="notice" role="status">{{ closed }} Hubungi Sales untuk bantuan.</p>
        <form v-else class="stack" @submit.prevent="submit"><ul v-if="errorList.length" class="error" role="alert"><li v-for="e in errorList" :key="e">{{ e }}</li></ul><p v-if="error" class="error" role="alert">{{ error }}</p>
            <fieldset :disabled="form.processing" class="panel"><legend>{{ b2b ? '1. Data Company dan PIC' : '1. Data peserta' }}</legend>
                <div v-if="!b2b" class="form-grid"><label v-for="key in info.fields" :key="key">{{ fieldLabels[key] || key }}<input v-if="fieldLabels[key]" v-model="(form as unknown as Record<string, string>)[key]" :type="type(key)" :required="key === 'nama'" :maxlength="key === 'nama' || key === 'instansi' ? 150 : key === 'alamat' ? 3000 : ['hp', 'wa', 'nik'].includes(key) ? 20 : 100" :pattern="['hp', 'wa'].includes(key) ? '[0-9]{9,15}' : undefined" :autocomplete="key === 'nama' ? 'name' : key === 'email' ? 'email' : 'off'" /><input v-else v-model="form.ex[key]" maxlength="1000" /></label></div>
                <div v-else class="form-grid"><label>Nama perusahaan<input v-model="form.nama_perusahaan" maxlength="150" required /></label><label>Alamat perusahaan<textarea v-model="form.alamat_perusahaan" maxlength="3000" required></textarea></label><label>Nama PIC<input v-model="form.pic_nama" maxlength="100" required autocomplete="name" /></label><label>Jabatan PIC<input v-model="form.pic_jabatan" maxlength="100" required /></label><label>Nomor HP PIC (angka 9–15 digit)<input v-model="form.pic_hp" type="tel" pattern="[0-9]{9,15}" required /></label><label>Email PIC<input v-model="form.pic_email" type="email" maxlength="100" required autocomplete="email" /></label></div>
            </fieldset>
            <fieldset v-if="b2b" :disabled="form.processing" class="panel"><legend>2. Daftar peserta (minimal 10)</legend><p>Paket sampai {{ info.paket_maks }} peserta. Di atasnya tambahan {{ rupiah(info.tambahan) }} / orang.</p><details><summary>Tempel daftar dari Excel / WhatsApp</summary><p>Urutan kolom: Nama{{ info.kolom.length ? ', ' + info.kolom.map((k) => fieldLabels[k]).join(', ') : '' }}. Pisahkan kolom dengan Tab dan peserta dengan baris baru. Daftar tempel mengganti daftar di bawah.</p><label>Daftar peserta<textarea v-model="paste" rows="6"></textarea></label><button type="button" @click="pasteRows">Gunakan daftar</button></details>
                <div v-for="(p, i) in form.peserta" :key="i" class="participant-row"><h3>Peserta {{ i + 1 }}</h3><div class="form-grid"><label>Nama Lengkap<input v-model="p.nama" maxlength="150" required /></label><label v-for="k in (info.kolom as (keyof Person)[])" :key="k">{{ fieldLabels[k] }}<input v-model="p[k]" :type="k === 'email' ? 'email' : k === 'wa' ? 'tel' : 'text'" :maxlength="k === 'wa' || k === 'nik' ? 20 : 100" :pattern="k === 'wa' ? '[0-9]{9,15}' : undefined" /></label></div><button v-if="form.peserta.length > 10" type="button" class="danger" @click="form.peserta.splice(i, 1)">Hapus peserta {{ i + 1 }}</button></div><button type="button" :disabled="form.peserta.length >= 1000" @click="form.peserta.push(blankPerson())">Tambah peserta</button><p>{{ form.peserta.length }} peserta. PIC mengirim satu kali.</p>
            </fieldset>
            <fieldset :disabled="form.processing" class="panel"><legend>{{ b2b ? '3' : '2' }}. Surat konfirmasi dan persetujuan</legend><p>Cetak surat dari isian, tandatangani dengan pena{{ b2b ? ' dan beri cap perusahaan' : '' }}, lalu unggah foto atau hasil pindai.</p><button type="button" :disabled="busy" @click="preview">{{ busy ? 'Menyiapkan PDF…' : 'Cetak surat dari isian' }}</button><label>Surat bertanda tangan (PDF/JPG/PNG, maksimal 5 MB)<input type="file" accept=".pdf,.jpg,.jpeg,.png" required @change="form.surat = ($event.target as HTMLInputElement).files?.[0] ?? null" /></label><p v-if="form.surat">{{ form.surat.name }}</p><label class="consent"><input v-model="form.setuju" type="checkbox" required /> {{ info.pernyataan }}</label></fieldset>
            <progress v-if="form.progress" :value="form.progress.percentage" max="100" aria-label="Progres unggahan"></progress><button class="primary" :disabled="form.processing || busy">{{ form.processing ? 'Mengirim…' : b2b ? 'Kirim daftar peserta' : 'Kirim konfirmasi' }}</button>
        </form>
    </main>
</template>
