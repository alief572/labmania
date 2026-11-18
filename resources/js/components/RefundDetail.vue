<script setup lang="ts">
import { appUrl } from '../composables/appUrl';
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { money } from '../types/salesOrder';
import MoneyInput from './MoneyInput.vue';
import { useConfirmedMutation } from '../composables/confirmedMutation';

// Detail satu Refund (RF-FR-03 sampai RF-FR-07). `mode` menentukan formulir yang tampil di bawah ringkasan:
// proses = catat transfer (Disetujui), keputusan = Setujui/Tolak (Menunggu), perbaiki = ajukan ulang (Ditolak), kosong = hanya lihat.
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
const emit = defineEmits<{ saved: []; busy: [value: boolean] }>();
type History = { jenis: 'setujui' | 'tolak' | 'ajukan_ulang'; catatan: string | null; nominal_lama: string | null; nominal_baru: string | null; alasan_lama: string | null; alasan_baru: string | null; waktu: string; oleh: string | null };
const props = defineProps<{
    refund: { id: number; nomor: string; tanggal: string; sebab: string; alasan: string; status: string; nominal_tagihan: string; nominal_refund: string | null; bank: string | null; no_rekening: string | null; atas_nama: string | null; catatan: string | null; bukti_transfer_nama: string | null; selesai_date: string | null };
    snapshot: { peserta: string | null; company: string | null; invoice: string; nomor_so: string; nomor_quotation: string };
    riwayat: History[]; maks: string; canManage: boolean; canAjukan: boolean; canApprove: boolean; closed: boolean;
    mode?: 'proses' | 'keputusan' | 'perbaiki' | null; returnToList?: boolean;
}>();
const STATUS: Record<string, string> = { menunggu: 'Menunggu Approval', disetujui: 'Disetujui, menunggu transfer', ditolak: 'Ditolak, perlu diperbaiki', selesai: 'Selesai' };
const SEBAB: Record<string, string> = { reschedule: 'Tidak ikut setelah reschedule', batal: 'Penjualan Program dibatalkan' };
const JENIS: Record<string, string> = { setujui: 'Disetujui', tolak: 'Ditolak', ajukan_ulang: 'Diajukan ulang' };
const modal = () => (props.returnToList ? '?modal=1' : '');
const lastReject = computed(() => [...props.riwayat].reverse().find((h) => h.jenis === 'tolak'));

const proses = useForm({ nominal_refund: props.refund.nominal_tagihan.replace(/\.0+$/, ''), bank: '', no_rekening: '', atas_nama: '', catatan: '', bukti: null as File | null });
const keputusan = useForm({ keputusan: 'setujui', catatan: '' });
const perbaiki = useForm({ alasan: props.refund.alasan, nominal: props.refund.nominal_tagihan.replace(/\.0+$/, ''), catatan: '' });
watch(() => proses.processing || keputusan.processing || perbaiki.processing || confirming.value, (value) => emit('busy', value), { immediate: true });

const saveProses = () => confirmation.form(proses, 'post', `/refund/${props.refund.id}${modal()}`, {
    title: `Selesaikan Refund ${props.refund.nomor}?`, text: `Nominal ${money(proses.nominal_refund)}. Transfer dilakukan di luar aplikasi; penyelesaian tidak dapat diedit.`, confirmLabel: 'Ya, simpan penyelesaian',
}, { forceFormData: true, onSuccess: () => emit('saved') });
const saveKeputusan = () => confirmation.form(keputusan, 'post', `/refund/${props.refund.id}/keputusan`, {
    title: `${keputusan.keputusan === 'setujui' ? 'Setujui' : 'Tolak'} Refund ${props.refund.nomor}?`,
    text: keputusan.keputusan === 'setujui' ? 'Refund lanjut ke Finance untuk ditransfer.' : 'Refund kembali ke Sales/Marketing untuk diperbaiki sesuai alasan.',
    confirmLabel: keputusan.keputusan === 'setujui' ? 'Ya, setujui' : 'Ya, tolak', danger: keputusan.keputusan === 'tolak',
}, { onSuccess: () => emit('saved') });
const savePerbaiki = () => confirmation.form(perbaiki, 'post', `/refund/${props.refund.id}/ajukan-ulang`, {
    title: `Ajukan ulang Refund ${props.refund.nomor}?`, text: 'Refund kembali ke Approval Refund untuk diputuskan.', confirmLabel: 'Ya, ajukan ulang',
}, { onSuccess: () => emit('saved') });
const errors = (form: { errors: Record<string, string> }) => Object.values(form.errors);
</script>

<template>
    <div class="refund-detail">
        <section class="panel">
            <h2>{{ snapshot.peserta || snapshot.company }}</h2>
            <span class="badge" :class="`st-${refund.status}`">{{ STATUS[refund.status] }}</span>
            <dl class="summary">
                <div><dt>Sebab</dt><dd>{{ SEBAB[refund.sebab] ?? refund.sebab }}</dd></div>
                <div><dt>Sales Order</dt><dd class="code">{{ snapshot.nomor_so }}</dd></div>
                <div><dt>Quotation</dt><dd class="code">{{ snapshot.nomor_quotation }}</dd></div>
                <div><dt>Invoice</dt><dd><span class="code">{{ snapshot.invoice }}</span> · sudah dibayar</dd></div>
                <div><dt>Nominal tagihan</dt><dd>{{ money(refund.nominal_tagihan) }}</dd></div>
                <div><dt>Alasan</dt><dd>{{ refund.alasan }}</dd></div>
            </dl>
            <template v-for="(h, i) in riwayat" :key="i">
                <p class="hint-line"><b>{{ JENIS[h.jenis] }}</b> · {{ h.oleh ?? '—' }} · {{ h.waktu }}<template v-if="h.catatan"> · {{ h.catatan }}</template>
                    <template v-if="h.jenis === 'ajukan_ulang' && h.nominal_lama !== h.nominal_baru"> · nominal {{ money(h.nominal_lama) }} → {{ money(h.nominal_baru) }}</template></p>
            </template>
            <template v-if="refund.status === 'selesai'">
                <p>Transfer {{ money(refund.nominal_refund || '0') }} melalui {{ refund.bank }}, {{ refund.no_rekening }}, a.n. {{ refund.atas_nama }}.</p>
                <p v-if="refund.catatan">{{ refund.catatan }}</p>
                <a :href="appUrl(`/documents/refund/${refund.id}`)">Unduh {{ refund.bukti_transfer_nama }}</a>
            </template>
        </section>
        <p v-if="closed" class="notice">Program Closed; perubahan terkunci.</p>

        <form v-if="mode === 'keputusan' && refund.status === 'menunggu' && canApprove && !closed" class="panel stack" @submit.prevent="saveKeputusan">
            <h2>Keputusan Approval</h2>
            <fieldset class="konf-opsi"><legend class="sr-only">Keputusan</legend>
                <label class="chk"><input v-model="keputusan.keputusan" type="radio" name="keputusan" value="setujui" /> <span><b>Setujui</b> <span class="muted">refund lanjut ke Finance untuk ditransfer</span></span></label>
                <label class="chk"><input v-model="keputusan.keputusan" type="radio" name="keputusan" value="tolak" /> <span><b>Tolak</b> <span class="muted">kembali ke Sales/Marketing untuk diperbaiki sesuai alasan</span></span></label>
            </fieldset>
            <label>Catatan <span v-if="keputusan.keputusan === 'tolak'" class="req" aria-hidden="true">*</span><textarea v-model="keputusan.catatan" maxlength="300" rows="2" :required="keputusan.keputusan === 'tolak'" :placeholder="keputusan.keputusan === 'tolak' ? 'Wajib: apa yang harus diperbaiki' : 'Opsional'" /></label>
            <p v-for="e in errors(keputusan)" :key="e" class="error" role="alert">{{ e }}</p>
            <button class="primary" :disabled="keputusan.processing || confirming">Simpan keputusan</button>
        </form>

        <form v-if="mode === 'perbaiki' && refund.status === 'ditolak' && canAjukan && !closed" class="panel stack" @submit.prevent="savePerbaiki">
            <h2>Perbaiki dan ajukan ulang</h2>
            <p v-if="lastReject" class="notice"><b>Ditolak</b> oleh {{ lastReject.oleh ?? '—' }} · {{ lastReject.waktu }}<br>Alasan: {{ lastReject.catatan }}</p>
            <label>Alasan refund <span class="req" aria-hidden="true">*</span><textarea v-model="perbaiki.alasan" maxlength="500" rows="2" required /></label>
            <label>Nominal tagihan <span class="req" aria-hidden="true">*</span><MoneyInput v-model="perbaiki.nominal" :max-digits="18" required /><small>Maksimal {{ money(maks) }} (nilai invoice).</small></label>
            <label>Catatan perbaikan <span class="req" aria-hidden="true">*</span><textarea v-model="perbaiki.catatan" maxlength="300" rows="2" required placeholder="Apa yang diperbaiki sesuai alasan penolakan" /></label>
            <p v-for="e in errors(perbaiki)" :key="e" class="error" role="alert">{{ e }}</p>
            <button class="primary" :disabled="perbaiki.processing || confirming">Ajukan ulang</button>
        </form>

        <form v-if="mode === 'proses' && refund.status === 'disetujui' && canManage && !closed" class="panel stack" @submit.prevent="saveProses">
            <h2>Catat transfer Refund</h2><p>Transfer dilakukan Finance di luar aplikasi. Simpan setelah transfer; penyelesaian tidak dapat diedit.</p>
            <label>Nominal refund<MoneyInput v-model="proses.nominal_refund" :max-digits="18" required /></label>
            <div class="form-grid"><label>Bank<input v-model="proses.bank" maxlength="50" required /></label><label>Nomor rekening<input v-model="proses.no_rekening" maxlength="30" required /></label><label>Atas nama<input v-model="proses.atas_nama" maxlength="100" required /></label></div>
            <label>Keterangan<textarea v-model="proses.catatan" maxlength="500" /><small>Wajib diisi bila nominal refund kurang dari batas dana.</small></label>
            <label>Bukti transfer PDF/JPG/PNG maksimal 5 MB<input type="file" accept=".pdf,.jpg,.jpeg,.png" required @change="proses.bukti = ($event.target as HTMLInputElement).files?.[0] || null" /></label>
            <p v-for="e in errors(proses)" :key="e" class="error" role="alert">{{ e }}</p>
            <button class="primary" :disabled="proses.processing || confirming">Simpan penyelesaian</button>
        </form>
    </div>
</template>
