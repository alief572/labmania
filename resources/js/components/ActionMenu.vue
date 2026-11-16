<script lang="ts">
let nextMenuId = 0;
</script>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
// DataTables renders cells as independent Vue roots, where useId() starts over.
const id = `action-menu-${++nextMenuId}`;
const trigger = ref<HTMLButtonElement | null>(null);
const panel = ref<HTMLElement | null>(null);
const expanded = ref(false);
function position(event: Event) {
    expanded.value = (event as ToggleEvent).newState === 'open';
    if (!expanded.value || !trigger.value || !panel.value) return;
    const rect = trigger.value.getBoundingClientRect();
    panel.value.style.left = `${Math.max(8, Math.min(rect.right - panel.value.offsetWidth, window.innerWidth - panel.value.offsetWidth - 8))}px`;
    panel.value.style.top = `${Math.max(8, Math.min(rect.bottom + 4, window.innerHeight - panel.value.offsetHeight - 8))}px`;
}
function close() { panel.value?.hidePopover(); }
function chosen(event: MouseEvent) {
    if ((event.target as HTMLElement).closest('button, a')) close();
}
function scrolled(event: Event) { if (event.target !== panel.value) close(); }
onMounted(() => { window.addEventListener('resize', close); window.addEventListener('scroll', scrolled, true); });
onBeforeUnmount(() => { window.removeEventListener('resize', close); window.removeEventListener('scroll', scrolled, true); });
</script>

<template>
    <button ref="trigger" type="button" class="gear" :popovertarget="id" :aria-expanded="expanded" aria-label="Buka aksi baris" title="Aksi">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 3-1 3-3 1v4l-2 1 2 2v3l3 1 1 3h5l1-3 3-1v-3l3-2-3-2V7l-3-1-1-3z"/><circle cx="11.5" cy="12" r="3"/></svg>
    </button>
    <div :id="id" ref="panel" popover="auto" class="action-popover" @toggle="position" @click="chosen"><slot /></div>
</template>
