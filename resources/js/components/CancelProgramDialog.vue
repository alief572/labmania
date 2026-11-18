<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ConfirmDialog from './ConfirmDialog.vue';
import { appUrl } from '../composables/appUrl';
import { rupiah } from '../composables/costing';
import { useConfirmedMutation } from '../composables/confirmedMutation';

// Batalkan Penjualan Program (mockup qtCancel): dampak ditampilkan sebelum alasan wajib diisi.
type Impact = { allowed: boolean; nomor: string; quotation: number; sales_order: number; invoice_belum: number; invoice_bayar: number; refund: string; surat_tugas: number; pengajuan: number };
const props = defineProps<{ id: number; nomor: string }>();
const emit = defineEmits<{ close: [] }>();
const confirmation = useConfirmedMutation();
const { pending } = confirmation;
const impact = ref<Impact | null>(null);
const loadError = ref('');
const form = useForm({ alasan: '' });
const lines = computed(() => {
    const d = impact.value;
    if (!d) return [];
    return [
        d.quotation && `${d.quotation} quotation menjadi Batal dan link konfirmasinya ditutup`,
        d.sales_order && `${d.sales_order} Sales Order pindah ke tab SO Batal`,
        d.invoice_belum && `${d.invoice_belum} invoice belum dibayar dibatalkan`,
        d.invoice_bayar && `${d.invoice_bayar} invoice sudah dibayar tetap ada; refund ${rupiah(Number(d.refund))} masuk modul Refund (menunggu approval)`,
        d.surat_tugas && 'Surat Tugas trainer menjadi Batal',
        d.pengajuan && `${d.pengajuan} pengajuan biaya yang menunggu approval dibatalkan`,
    ].filter(Boolean) as string[];
});
onMounted(async () => {
    try {
        const response = await fetch(appUrl(`/penjualan-program/${props.id}/dampak-batal`), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) throw new Error(String(response.status));
        impact.value = await response.json();
    } catch { loadError.value = 'Dampak pembatalan gagal dimuat. Tutup dan coba lagi.'; }
});
function submit() {
    form.clearErrors();
    if (!form.alasan.trim()) { form.setError('alasan', 'Isi alasan pembatalan'); return; }
    void confirmation.form(form, 'post', `/penjualan-program/${props.id}/batalkan`, {
        title: `Batalkan ${props.nomor}?`, text: 'Penjualan Program Batal tidak dapat diubah.', confirmLabel: 'Ya, batalkan', danger: true,
    }, { preserveScroll: true, onSuccess: () => emit('close') });
}
</script>

<template>
    <ConfirmDialog title="Batalkan Penjualan Program" confirm-label="Batalkan Program" danger :busy="form.processing || pending" @confirm="submit" @cancel="emit('close')">
        <p>Batalkan <b>{{ nomor }}</b>? Penjualan Program Batal tidak dapat diubah.</p>
        <p v-if="loadError" class="error" role="alert">{{ loadError }}</p>
        <p v-else-if="impact && !impact.allowed" class="error" role="alert">Program sudah Closed atau Batal dan tidak dapat dibatalkan.</p>
        <div v-else-if="lines.length" class="notice"><span>Yang ikut terdampak:<ul><li v-for="line in lines" :key="line">{{ line }}</li></ul></span></div>
        <label>Alasan pembatalan <span class="req" aria-hidden="true">*</span>
            <textarea v-model="form.alasan" rows="2" maxlength="300" required placeholder="mis. peserta tidak memenuhi kuota minimum" :aria-invalid="!!form.errors.alasan" />
        </label>
        <p v-if="form.errors.alasan" class="error" role="alert">{{ form.errors.alasan }}</p>
    </ConfirmDialog>
</template>
