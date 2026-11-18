<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { startTutorial, stopTutorial } from '../composables/tutorial';
import { tutorialKey, type TutorialKey } from '../composables/tutorials';

const props = withDefaults(defineProps<{ tutorial?: TutorialKey | false }>(), { tutorial: undefined });
const page = usePage();
const key = computed(() => props.tutorial === false ? undefined : props.tutorial || tutorialKey(page.component, page.props));
const loading = ref(false);
const error = ref('');
let disposed = false;
async function start(event: MouseEvent) {
    if (!key.value || loading.value) return;
    loading.value = true;
    error.value = '';
    const button = event.currentTarget as HTMLElement;
    try { await startTutorial(key.value, button, button.closest('.dialog') || document.querySelector('#main') || document.body); }
    catch { if (!disposed) error.value = 'Tutorial gagal dimuat. Tekan Tutorial untuk mencoba lagi.'; }
    finally { loading.value = false; }
}
onBeforeUnmount(() => { disposed = true; stopTutorial(); });
</script>

<template>
    <span v-if="key" class="tutorial-control">
        <button type="button" class="tour-btn" :data-tutorial-key="key" :disabled="loading" @click="start"><span aria-hidden="true">?</span> {{ loading ? 'Memuat tutorial…' : 'Tutorial' }}</button>
        <span v-if="error" role="alert" class="error">{{ error }}</span>
    </span>
</template>
