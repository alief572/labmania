<script setup lang="ts">
const props = withDefaults(defineProps<{ modelValue: string | number | null | undefined; maxDigits?: number }>(), { maxDigits: 13 });
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

function format(value: string | number | null | undefined): string {
    const text = String(value ?? '');
    if (!/^\d+(?:\.0+)?$/.test(text)) return text;
    return text.replace(/\.0+$/, '').replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function input(event: Event) {
    if ((event as InputEvent).isComposing) return;
    const el = event.target as HTMLInputElement;
    const text = el.value;
    const position = el.selectionStart ?? text.length;
    const digits = text.replace(/\./g, '');
    const value = digits.replace(/^0+(?=\d)/, '');
    if (!/^[\d.]*$/.test(text) || value.length > props.maxDigits) {
        el.value = format(props.modelValue);
        return;
    }
    const before = Math.max(0, text.slice(0, position).replace(/\./g, '').length - (digits.length - value.length));
    el.value = format(value);
    emit('update:modelValue', value);
    let caret = 0, count = 0;
    while (caret < el.value.length && count < before) {
        if (/\d/.test(el.value[caret])) count++;
        caret++;
    }
    el.setSelectionRange(caret, caret);
}

function paste(event: ClipboardEvent) {
    const text = event.clipboardData?.getData('text').trim() ?? '';
    if (!text) return;
    event.preventDefault();
    if (!/^\d+$/.test(text) && !/^\d{1,3}(?:\.\d{3})+$/.test(text)) return;
    const el = event.target as HTMLInputElement;
    el.setRangeText(text.replace(/\./g, ''), el.selectionStart ?? 0, el.selectionEnd ?? el.value.length, 'end');
    input(event);
}

function keydown(event: KeyboardEvent) {
    const el = event.target as HTMLInputElement;
    const position = el.selectionStart ?? 0;
    if (el.selectionEnd !== position) return;
    if (event.key === 'Backspace' && el.value[position - 1] === '.') {
        event.preventDefault();
        el.setRangeText('', position - 2, position, 'end');
        input(event);
    } else if (event.key === 'Delete' && el.value[position] === '.') {
        event.preventDefault();
        el.setRangeText('', position, position + 2, 'end');
        input(event);
    }
}
</script>

<template>
    <input type="text" inputmode="numeric" class="money-input" :value="format(modelValue)"
        :maxlength="maxDigits + Math.floor((maxDigits - 1) / 3)" pattern="[0-9.]*" @input="input" @paste="paste" @keydown="keydown" @compositionend="input" />
</template>

<style scoped>
.money-input { text-align: right; font-variant-numeric: tabular-nums; }
</style>
