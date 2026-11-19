import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import postcss from 'postcss';

const css = postcss.parse(readFileSync(new URL('../../resources/css/app.css', import.meta.url), 'utf8'));
const variables = {};
css.walkRules(':root', rule => rule.walkDecls(decl => { variables[decl.prop] = decl.value; }));
function declarations(selector) {
    const result = {};
    css.walkRules(rule => {
        if (rule.selector.split(',').map(value => value.trim()).includes(selector)) rule.walkDecls(decl => { result[decl.prop] = decl.value; });
    });
    assert.ok(Object.keys(result).length, `Aturan ${selector} tersedia`);
    return result;
}
function luminance(value) {
    value = value.replace(/var\((--[\w-]+)\)/g, (_, name) => variables[name]);
    if (value === 'white') value = '#fff';
    assert.match(value, /^#[\da-f]{3}(?:[\da-f]{3})?$/i, `Warna konkret ${value}`);
    const hex = value.length === 4 ? value.slice(1).split('').map(digit => digit + digit).join('') : value.slice(1);
    return hex.match(/../g).map(channel => parseInt(channel, 16) / 255).map(channel => channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4).reduce((sum, channel, i) => sum + channel * [0.2126, 0.7152, 0.0722][i], 0);
}
const states = [];
for (const base of ['.primary', '.btn-primary', '.btn-accent', '.btn-outline', '.btn-danger', '.tour-btn']) {
    states.push([base], [base, `${base}:hover:not(:disabled)`]);
}
for (const container of ['.page-head', '.page-head-actions']) {
    const create = `${container} > a[href$="/create"]`;
    states.push(['.primary', create], ['.primary', create, '.primary:hover:not(:disabled)', `${create}:hover`]);
}
states.push(
    ['.primary', 'button.primary.danger', '.primary.danger:hover:not(:disabled)'],
    ['.server-table .dt-paging button', '.server-table .dt-paging button.current', '.server-table .dt-paging button:hover:not(:disabled)', '.server-table .dt-paging button.current:hover:not(:disabled)'],
    ['.pg', '.pg.on', '.pg:hover:not(:disabled)', '.pg.on:hover:not(:disabled)'],
);
for (const selectors of states) {
    const style = Object.assign({}, ...selectors.map(declarations));
    const a = luminance(style.color), b = luminance(style.background);
    const ratio = (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
    assert.ok(ratio >= 4.5, `${selectors.at(-1)}: kontras ${ratio.toFixed(2)} harus minimal 4.5`);
}
const paging = declarations('.server-table .dt-container');
for (const suffix of ['current', 'current-hover']) {
    const a = luminance(paging[`--dt-paging-button_color-${suffix}`]);
    const b = luminance(paging[`--dt-paging-button_background-${suffix}`]);
    assert.ok((Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05) >= 4.5, `Variabel native DataTables ${suffix}`);
}
console.log(`Button contrast: ${states.length + 2} keadaan normal/hover termasuk tombol create dan pagination aktif lulus WCAG AA.`);
