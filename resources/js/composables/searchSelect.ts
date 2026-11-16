// Select native dengan banyak opsi dibuka sebagai panel dengan kolom cari (FR-12, NFR-10).
// Delegasi event di document: tidak ada template yang diubah, v-model/required/disabled tetap milik <select>.
// Opt-out: data-native pada <select>.
export const MIN_OPTIONS = 6;

type Opt = { value: string; label: string; disabled: boolean };

const fold = (s: string) => s.normalize('NFD').replace(/\p{M}/gu, '').toLocaleLowerCase('id');

export function filterOptions<T extends { label: string }>(options: T[], query: string): T[] {
    const q = fold(query.trim());
    return q ? options.filter(o => fold(o.label).includes(q)) : options;
}

let panel: HTMLDivElement | null = null;
let owner: HTMLSelectElement | null = null;

function close(refocus = true) {
    panel?.remove();
    const select = owner;
    panel = owner = null;
    if (refocus) select?.focus();
}

function enhanced(el: EventTarget | null): el is HTMLSelectElement {
    return el instanceof HTMLSelectElement && !el.multiple && !el.disabled && !el.hasAttribute('data-native') && el.options.length >= MIN_OPTIONS;
}

function open(select: HTMLSelectElement) {
    close(false);
    const options: Opt[] = Array.from(select.options).map(o => ({ value: o.value, label: o.text.trim(), disabled: o.disabled }));
    let shown = options;
    let active = Math.max(0, options.findIndex(o => o.value === select.value));

    const box = document.createElement('div');
    box.className = 'ss-panel';
    const input = document.createElement('input');
    input.type = 'search';
    input.placeholder = 'Cari…';
    input.autocomplete = 'off';
    input.setAttribute('aria-label', 'Cari pilihan');
    const list = document.createElement('ul');
    list.setAttribute('role', 'listbox');
    box.append(input, list);

    const pick = (o: Opt | undefined) => {
        if (!o || o.disabled) return;
        select.value = o.value;
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));
        close();
    };
    const render = () => {
        list.replaceChildren(...shown.map((o, i) => {
            const li = document.createElement('li');
            li.setAttribute('role', 'option');
            li.textContent = o.label || ' ';
            if (o.disabled) li.setAttribute('aria-disabled', 'true');
            if (o.value === select.value) li.setAttribute('aria-selected', 'true');
            if (i === active) li.classList.add('active');
            li.addEventListener('mousedown', e => { e.preventDefault(); pick(o); });
            return li;
        }));
        if (!shown.length) {
            const li = document.createElement('li');
            li.className = 'empty';
            li.textContent = 'Tidak ada hasil.';
            list.append(li);
        }
        list.querySelector('.active')?.scrollIntoView({ block: 'nearest' });
    };
    input.addEventListener('input', () => { shown = filterOptions(options, input.value); active = 0; render(); });
    input.addEventListener('keydown', e => {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            active = shown.length ? (active + (e.key === 'ArrowDown' ? 1 : -1) + shown.length) % shown.length : 0;
            render();
        } else if (e.key === 'Enter') { e.preventDefault(); pick(shown[active]); }
        else if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(); }
        else if (e.key === 'Tab') close(false);
    });

    // Di dalam <dialog> modal, panel harus ikut top layer agar tidak tertutup.
    (select.closest('dialog') ?? document.body).append(box);
    const r = select.getBoundingClientRect();
    const below = innerHeight - r.bottom, flip = below < 260 && r.top > below;
    box.style.left = `${r.left}px`;
    box.style.width = `${r.width}px`;
    if (flip) box.style.bottom = `${innerHeight - r.top + 4}px`; else box.style.top = `${r.bottom + 4}px`;
    panel = box;
    owner = select;
    render();
    input.focus();
}

export function installSearchSelect() {
    document.addEventListener('mousedown', e => {
        if (panel?.contains(e.target as Node)) return;
        if (enhanced(e.target) && e.button === 0) { e.preventDefault(); e.target.focus(); open(e.target); return; }
        if (panel) close(false);
    }, true);
    document.addEventListener('keydown', e => {
        if (!enhanced(e.target) || panel) return;
        if (['Enter', ' ', 'ArrowDown', 'ArrowUp'].includes(e.key)) { e.preventDefault(); open(e.target); }
    }, true);
    addEventListener('resize', () => close(false));
    addEventListener('scroll', e => { if (panel && !panel.contains(e.target as Node)) close(false); }, true);
}
