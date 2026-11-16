import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

// Aturan matriks Role & Permission (mockup akEdit) harus sama dengan yang dipaksa server (PermissionController::save).
const src = readFileSync(new URL('../../resources/js/pages/Permissions.vue', import.meta.url), 'utf8');
for (const rule of [
    /if \(checked\) \{ v\.add\(key\(m, a\)\); if \(a !== 'View'\) v\.add\(key\(m, 'View'\)\); \}/,        // Add/Manage/Delete/Approve otomatis View
    /if \(a === 'View'\) actions\.forEach\(\(x\) => v\.delete\(key\(m, x\)\)\)/,                         // lepas View = lepas semua hak menu
    /const actions = \['View', 'Add', 'Manage', 'Delete', 'Approve'\]/,                                  // urutan aksi sama dengan mockup AK_ACTS
    /`\/permissions\/roles\/\$\{editing\.value!\.id_group\}`/,                                            // simpan lewat endpoint yang sudah teruji
]) assert.match(src, rule, `Aturan matriks hilang: ${rule}`);

// Model tiruan logika toggle untuk memastikan perilaku, bukan hanya teks.
const ACTS = ['View', 'Add', 'Manage', 'Delete', 'Approve'];
const key = (id, a) => `${id}:${a}`;
function toggle(grants, id, a, checked) {
    const v = new Set(grants);
    if (checked) { v.add(key(id, a)); if (a !== 'View') v.add(key(id, 'View')); }
    else { v.delete(key(id, a)); if (a === 'View') ACTS.forEach((x) => v.delete(key(id, x))); }
    return [...v].sort();
}
assert.deepEqual(toggle([], 3, 'Manage', true), ['3:Manage', '3:View']);
assert.deepEqual(toggle(['3:View', '3:Manage', '3:Add', '4:View'], 3, 'View', false), ['4:View']);
assert.deepEqual(toggle(['3:View', '3:Manage'], 3, 'Manage', false), ['3:View']);
console.log('permissionMatrix ok');
