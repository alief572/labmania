<script setup lang="ts">
import TutorialButton from './TutorialButton.vue';
import type { TutorialKey } from '../composables/tutorials';
import { nextTick, onMounted, ref } from 'vue';
// size: lebar modal mockup (.wide 780px, .xwide 1080px). info: hanya tombol Tutup, untuk dialog baca-saja.
const props = defineProps<{ title: string; tutorial?: TutorialKey; confirmLabel?: string; busy?: boolean; danger?: boolean; size?: 'wide' | 'xwide'; info?: boolean }>();
const emit = defineEmits<{ confirm: []; cancel: [] }>();
const box = ref<HTMLElement | null>(null);
const previous = document.activeElement as HTMLElement | null;
onMounted(async () => { await nextTick(); (box.value?.querySelector<HTMLElement>('input:not(:disabled), select:not(:disabled), textarea:not(:disabled)') || box.value?.querySelector<HTMLElement>('button'))?.focus(); });
function close() { if (props.busy) return; emit('cancel'); previous?.focus(); }
function keys(event: KeyboardEvent) {
    if (document.querySelector('.driver-popover')) return;
    if (event.key === 'Escape') { if (document.querySelector('.swal2-container')) return; event.preventDefault(); close(); return; }
    if (event.key !== 'Tab') return;
    const items = box.value?.querySelectorAll<HTMLElement>('a[href], button:not(:disabled), input, select, textarea');
    if (!items?.length) return;
    const first = items[0], last = items[items.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
}
</script>

<template>
    <Teleport to="body">
    <div class="dialog-back" @mousedown.self="close">
        <div ref="box" class="dialog" :class="size" role="dialog" aria-modal="true" :aria-label="props.title" @keydown="keys">
            <header class="dialog-head"><h2>{{ title }}</h2><TutorialButton v-if="tutorial" :tutorial="tutorial" /><button type="button" class="dialog-close" aria-label="Tutup dialog" @click="close">×</button></header>
            <div class="dialog-body"><slot /></div>
            <div class="form-actions dialog-foot">
                <button type="button" @click="close">{{ info ? 'Tutup' : 'Batal' }}</button>
                <button v-if="!info" type="button" class="primary" :class="{ danger }" :disabled="busy" @click="emit('confirm')">{{ busy ? 'Memproses…' : (confirmLabel || 'Simpan') }}</button>
            </div>
        </div>
    </div>
    </Teleport>
</template>
