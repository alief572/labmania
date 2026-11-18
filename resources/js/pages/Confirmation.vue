<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import type { SharedProps } from '../types';
import { postPdf } from '../composables/pdf';
import { rupiah } from '../composables/costing';
import { useConfirmedMutation } from '../composables/confirmedMutation';
import PublicLayout from '../components/PublicLayout.vue';
import PublicHead from '../components/PublicHead.vue';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
type Person = { nama: string; jabatan: string; email: string; wa: string; nik: string };
type Company = { nama_perusahaan: string; alamat_perusahaan: string; pic_nama: string; pic_jabatan: string; pic_hp: string; pic_email: string };
const props = defineProps<{ token: string; closed: string | null; info: { nomor: string; judul: string; jenis: string; tgl_mulai: string; tgl_selesai: string; waktu: string; lokasi: string; kuota: number; sisa: number; kepada: string; berlaku_sampai: string; pernyataan: string; fields: string[]; kolom: string[]; paket_maks: number | null; tambahan: string | null; sales_nama: string | null; sales_telp: string | null }; fieldLabels: Record<string, string>; company: Company | null }>();
const page = usePage<SharedProps>();
const blankPerson = (): Person => ({ nama: '', jabatan: '', email: '', wa: '', nik: '' });
const form = useForm({ nama: '', nik: '', jabatan: '', instansi: '', alamat: '', hp: '', wa: '', email: '', pengalaman: '', lsp: '', habis: '', ex: {} as Record<string, string>, ...({ nama_perusahaan: '', alamat_perusahaan: '', pic_nama: '', pic_jabatan: '', pic_hp: '', pic_email: '', ...props.company }), peserta: Array.from({ length: Math.min(1000, Math.max(10, props.info.kuota)) }, blankPerson), surat: null as File | null, setuju: false });
const b2b = props.info.jenis === 'b2b'; const busy = ref(false); const error = ref(''); const paste = ref('');
const errorList = computed(() => Object.values(form.errors));
// Layar terima kasih (mockup .konf-ok): tampil setelah kirim berhasil, "Isi untuk peserta lain" kembali ke formulir.
const sentName = ref(''); const thanks = ref(false);
const head = computed(() => [`${props.info.tgl_mulai} s.d. ${props.info.tgl_selesai}${props.info.waktu ? ` · ${props.info.waktu}` : ''} · ${props.info.lokasi || 'lokasi menyusul'}`, `Quotation ${props.info.nomor} · ${props.info.kepada} · formulir berlaku s.d. ${props.info.berlaku_sampai}`]);
const sales = computed(() => [props.info.sales_nama, props.info.sales_telp].filter(Boolean).join(' '));
async function preview() { busy.value = true; error.value = ''; try { await postPdf(`/konfirmasi/${props.token}/preview`, form.data()); } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; } }
function submit() {
    const who = b2b ? form.pic_nama : form.nama;
    return confirmation.form(form, 'post', `/konfirmasi/${props.token}`, { title: 'Kirim konfirmasi keikutsertaan?', text: 'Periksa identitas peserta dan surat konfirmasi sebelum mengirim.', confirmLabel: 'Ya, kirim', successText: 'Konfirmasi berhasil dikirim.' }, { forceFormData: true, onSuccess: () => { sentName.value = who; thanks.value = true; form.reset('surat', 'setuju', 'nama', 'nik', 'email', 'wa', 'hp'); } });
}
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
    <PublicLayout subtitle="Konfirmasi Kehadiran Peserta">
        <template v-if="thanks">
            <div class="konf-ok"><span class="konf-ok-mark" aria-hidden="true">✓</span><h1 tabindex="-1">Terima kasih{{ sentName ? `, ${sentName}` : '' }}</h1></div>
            <PublicHead :judul="info.judul" :lines="head" />
            <div class="pub-closed" role="status"><p><b>{{ page.props.flash.success || 'Konfirmasi kehadiran Anda sudah kami terima.' }}</b></p><p>Data dan surat konfirmasi masuk ke quotation {{ info.nomor }}. Sales LabMania{{ info.sales_nama ? ` (${info.sales_nama})` : '' }} akan menghubungi Anda bila ada yang perlu dilengkapi.</p></div>
            <p v-if="!b2b" class="konf-lagi"><button type="button" class="btn btn-outline" @click="thanks = false">Isi untuk peserta lain</button></p>
        </template>
        <template v-else>
            <h1>Konfirmasi Kehadiran</h1>
            <PublicHead :judul="info.judul" :lines="head" :sisa="b2b || closed ? null : info.sisa" />
            <div v-if="closed" class="pub-closed" role="status"><p><b>Formulir ini sudah ditutup.</b></p><p>{{ closed }} Hubungi Sales LabMania{{ sales ? ` (${sales})` : '' }} untuk bantuan.</p></div>
            <form v-else @submit.prevent="submit">
                <p class="konf-lead">{{ b2b ? 'Formulir ini diisi satu kali oleh PIC perusahaan untuk seluruh peserta in-house. Isi data perusahaan, daftar peserta, lalu unggah surat konfirmasi yang sudah ditandatangani dan dicap.' : 'Isi data diri Anda sebagai calon peserta. Satu formulir untuk satu orang; bila mendaftarkan beberapa orang, kirim satu per satu.' }}</p>
                <ul v-if="errorList.length" class="error" role="alert"><li v-for="e in errorList" :key="e">{{ e }}</li></ul><p v-if="error" class="error" role="alert">{{ error }}</p>
                <fieldset :disabled="form.processing" class="konf-sec"><legend>{{ b2b ? '1. Data perusahaan dan PIC' : '1. Data diri' }}</legend><p class="konf-req"><span class="req" aria-hidden="true">*</span> wajib diisi</p>
                    <div v-if="!b2b" class="form-grid"><label v-for="key in info.fields" :key="key">{{ fieldLabels[key] || key }}<span v-if="key === 'nama'" class="req" aria-hidden="true"> *</span><input v-if="fieldLabels[key]" v-model="(form as unknown as Record<string, string>)[key]" :type="type(key)" :required="key === 'nama'" :maxlength="key === 'nama' || key === 'instansi' ? 150 : key === 'alamat' ? 3000 : ['hp', 'wa', 'nik'].includes(key) ? 20 : 100" :pattern="['hp', 'wa'].includes(key) ? '[0-9]{9,15}' : undefined" :autocomplete="key === 'nama' ? 'name' : key === 'email' ? 'email' : 'off'" /><input v-else v-model="form.ex[key]" maxlength="1000" /></label></div>
                    <div v-else class="form-grid"><label>Nama perusahaan<input v-model="form.nama_perusahaan" maxlength="150" required /></label><label>Alamat perusahaan<textarea v-model="form.alamat_perusahaan" maxlength="3000" required></textarea></label><label>Nama PIC<input v-model="form.pic_nama" maxlength="100" required autocomplete="name" /></label><label>Jabatan PIC<input v-model="form.pic_jabatan" maxlength="100" required /></label><label>Nomor HP PIC (angka 9–15 digit)<input v-model="form.pic_hp" type="tel" pattern="[0-9]{9,15}" required /></label><label>Email PIC<input v-model="form.pic_email" type="email" maxlength="100" required autocomplete="email" /></label></div>
                    <p v-if="!b2b" class="hint-line">Nama lengkap menjadi acuan penulisan di sertifikat.</p>
                </fieldset>
                <fieldset v-if="b2b" :disabled="form.processing" class="konf-sec"><legend>2. Daftar peserta (minimal 10)</legend><p class="konf-req">Paket sampai {{ info.paket_maks }} peserta. Di atasnya tambahan {{ rupiah(info.tambahan) }} / orang.</p><details><summary>Tempel daftar dari Excel / WhatsApp</summary><p>Urutan kolom: Nama{{ info.kolom.length ? ', ' + info.kolom.map((k) => fieldLabels[k]).join(', ') : '' }}. Pisahkan kolom dengan Tab dan peserta dengan baris baru. Daftar tempel mengganti daftar di bawah.</p><label>Daftar peserta<textarea v-model="paste" rows="6"></textarea></label><button type="button" class="btn btn-outline" @click="pasteRows">Gunakan daftar</button></details>
                    <div v-for="(p, i) in form.peserta" :key="i" class="participant-row"><h3>Peserta {{ i + 1 }}</h3><div class="form-grid"><label>Nama Lengkap<input v-model="p.nama" maxlength="150" required /></label><label v-for="k in (info.kolom as (keyof Person)[])" :key="k">{{ fieldLabels[k] }}<input v-model="p[k]" :type="k === 'email' ? 'email' : k === 'wa' ? 'tel' : 'text'" :maxlength="k === 'wa' || k === 'nik' ? 20 : 100" :pattern="k === 'wa' ? '[0-9]{9,15}' : undefined" /></label></div><button v-if="form.peserta.length > 10" type="button" class="danger" @click="form.peserta.splice(i, 1)">Hapus peserta {{ i + 1 }}</button></div><button type="button" class="btn btn-outline" :disabled="form.peserta.length >= 1000" @click="form.peserta.push(blankPerson())">+ Tambah peserta</button><p class="hint-line" aria-live="polite">{{ form.peserta.length }} peserta. PIC mengirim satu kali.</p>
                </fieldset>
                <fieldset :disabled="form.processing" class="konf-sec"><legend>{{ b2b ? '3' : '2' }}. Pernyataan</legend>
                    <p v-if="b2b">Dengan ini perusahaan kami menyatakan mengikutsertakan peserta di atas pada kegiatan ini, dan telah membaca serta menyetujui syarat &amp; ketentuan yang berlaku di LabMania.</p>
                    <template v-else><p>Dengan ini saya menyatakan:</p><ol class="konf-pernyataan"><li>{{ info.pernyataan }}</li><li>Saya telah membaca dan menyetujui syarat &amp; ketentuan yang berlaku di LabMania.</li></ol></template>
                    <label class="chk konf-setuju"><input v-model="form.setuju" type="checkbox" required /> {{ b2b ? 'Saya menyetujui pernyataan di atas atas nama perusahaan.' : 'Saya menyetujui pernyataan di atas.' }}</label>
                </fieldset>
                <fieldset :disabled="form.processing" class="konf-sec"><legend>{{ b2b ? '4' : '3' }}. Surat konfirmasi bertanda tangan</legend><p class="konf-req">Kami membutuhkan surat dengan tanda tangan basah{{ b2b ? ' dan cap perusahaan' : '' }}.</p>
                    <ol class="konf-langkah"><li>Cetak surat konfirmasi. Data yang Anda isi di atas sudah tercetak di surat.<div><button type="button" class="btn btn-outline konf-cetak-btn" :disabled="busy" @click="preview">{{ busy ? 'Menyiapkan PDF…' : 'Cetak surat dari isian' }}</button></div></li><li>Isi tempat, lalu tanda tangani surat dengan pena{{ b2b ? ' dan beri cap perusahaan' : '' }}.</li><li>Foto atau pindai surat itu, lalu unggah di bawah ini.</li></ol>
                    <label>Surat bertanda tangan <span class="req" aria-hidden="true">*</span><input class="konf-file" type="file" accept=".pdf,.jpg,.jpeg,.png" required @change="form.surat = ($event.target as HTMLInputElement).files?.[0] ?? null" /></label><p class="hint-line">PDF, JPG, atau PNG, maksimal 5 MB. Dari ponsel, Anda bisa langsung memotret surat.</p><p v-if="form.surat" class="hint-line">{{ form.surat.name }}</p>
                </fieldset>
                <progress v-if="form.progress" :value="form.progress.percentage" max="100" aria-label="Progres unggahan"></progress>
                <button class="primary konf-kirim" :disabled="form.processing || busy || confirming">{{ form.processing ? 'Mengirim…' : b2b ? 'Kirim daftar peserta' : 'Kirim konfirmasi' }}</button>
            </form>
        </template>
    </PublicLayout>
</template>
