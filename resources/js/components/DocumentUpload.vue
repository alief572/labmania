<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{ modelValue: File[]; disabled?: boolean; optional?: boolean }>();
const emit = defineEmits<{ 'update:modelValue': [files: File[]] }>();
const dragging = ref(false);
const errors = ref<string[]>([]);
function add(files: File[]) {
    if (props.disabled) return;
    const selected = [...props.modelValue];
    errors.value = [];
    for (const file of files) {
        if (!/\.(pdf|jpe?g|png|docx?|xlsx?)$/i.test(file.name)) errors.value.push(`${file.name}: gunakan PDF, JPG, PNG, DOC/DOCX, atau XLS/XLSX.`);
        else if (!file.size) errors.value.push(`${file.name}: berkas kosong.`);
        else if (file.size > 5 * 1024 * 1024) errors.value.push(`${file.name}: ukuran melebihi 5 MB.`);
        else if (selected.length >= 20) errors.value.push(`${file.name}: maksimal 20 berkas per unggahan.`);
        else selected.push(file);
    }
    emit('update:modelValue', selected);
}
function choose(event: Event) {
    const input = event.target as HTMLInputElement;
    add(Array.from(input.files ?? [])); input.value = '';
}
function drop(event: DragEvent) {
    event.preventDefault(); dragging.value = false;
    add(Array.from(event.dataTransfer?.files ?? []));
}
function paste(event: ClipboardEvent) {
    if (props.disabled) return;
    const data = event.clipboardData;
    const files = Array.from(data?.files ?? []);
    if (!files.length) for (const item of Array.from(data?.items ?? [])) {
        const file = item.kind === 'file' ? item.getAsFile() : null;
        if (file) files.push(file);
    }
    if (!files.length) return;
    event.preventDefault(); add(files);
}
onMounted(() => document.addEventListener('paste', paste));
onBeforeUnmount(() => document.removeEventListener('paste', paste));
</script>

<template>
    <div class="document-upload">
        <p class="upload-title">{{ optional ? 'Tambah dokumen dari customer (opsional)' : 'Dokumen dari customer (wajib)' }}</p>
        <label class="pb-drop" :class="{ dragging, disabled }" @dragover.prevent="dragging = !disabled" @dragleave.prevent="dragging = false" @drop="drop">
            <input class="sr-only" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" :disabled="disabled" aria-label="Pilih file dokumen customer" @change="choose" />
            <b>Pilih file dokumen</b>
            <span>Tarik dan lepas berkas di sini, atau tempel berkas/gambar dengan Ctrl+V.</span>
            <small>PO, SPK, surat penunjukan, bukti bayar, atau dokumen lain. PDF, JPG, PNG, DOC/DOCX, XLS/XLSX; maksimal 5 MB per berkas dan 20 berkas. Boleh lebih dari satu.</small>
        </label>
        <ul v-if="errors.length" class="upload-errors error" role="alert"><li v-for="message in errors" :key="message">{{ message }}</li></ul>
        <ul class="dok-pick" aria-live="polite">
            <li v-for="(file, i) in modelValue" :key="i"><span class="file-name">{{ file.name }} <small class="muted">{{ Math.max(1, Math.round(file.size / 1024)) }} KB</small></span><button type="button" class="btn btn-ghost sm danger" :disabled="disabled" :aria-label="`Hapus ${file.name}`" @click="emit('update:modelValue', modelValue.filter((_, index) => index !== i))">Hapus</button></li>
            <li v-if="!modelValue.length" class="muted">Belum ada file</li>
        </ul>
    </div>
</template>

<style scoped>
.upload-title { margin: 0 0 8px; font-weight: 600; }
.pb-drop { display: flex; align-items: center; flex-direction: column; gap: 8px; padding: 24px 18px; border: 2px dashed var(--line); border-radius: 8px; background: #f6f8fb; text-align: center; cursor: pointer; }
.pb-drop small { color: var(--ink-70); font-size: 12px; line-height: 1.5; }
.pb-drop:hover, .pb-drop.dragging, .pb-drop:focus-within { border-color: var(--navy); background: var(--navy-50); }
.pb-drop:focus-within { outline: 2px solid var(--orange); outline-offset: 2px; }
.pb-drop.disabled { cursor: default; opacity: .6; }
.dok-pick { list-style: none; margin: 10px 0 18px; padding: 0; }
.dok-pick li { display: flex; align-items: center; gap: 12px; padding: 6px 0; border-bottom: 1px solid var(--line); }
.file-name { flex: 1; min-width: 0; overflow-wrap: anywhere; }
.file-name small { white-space: nowrap; }
.upload-errors { padding-left: 20px; }
</style>
