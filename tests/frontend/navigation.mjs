import assert from 'node:assert/strict';
import { navigationBreadcrumb, navigationSections } from '../../resources/js/composables/navigation.ts';

const items = [
    { label: 'Beranda DB', url: '/', group: null, sectionKey: '1', subgroupKey: '1' },
    { label: 'Jenis Produk', url: '/master/jenis-produk', group: 'Master Data DB', sectionKey: '2', subgroupKey: '1' },
    { label: 'Master User', url: '/master/user', group: 'Master Data DB', sectionKey: '2', subgroupKey: '2' },
    { label: 'Master Trainer', url: '/master/trainer', group: 'Master Data DB', sectionKey: '2', subgroupKey: '3' },
    { label: 'Template Konfirmasi', url: '/master/template-konfirmasi', group: 'Master Data DB', sectionKey: '2', subgroupKey: '4' },
    { label: 'Role & Permission', url: '/permissions', group: 'Pengaturan DB', sectionKey: '3', subgroupKey: '1' },
    { label: 'Manage Peserta', url: '/manage-peserta', group: 'Operasional', sectionKey: '4', subgroupKey: '1' },
    { label: 'Monitoring Peserta', url: '/manage-peserta/monitoring', group: 'Operasional', sectionKey: '4', subgroupKey: '1' },
];
const entries = sections => sections.flatMap(section => section.groups.flatMap(group => group.items));
const sections = navigationSections(items, '/manage-peserta/monitoring?status=belum');
assert.deepEqual(entries(sections).filter(item => item.active).map(item => item.url), ['/manage-peserta/monitoring']);
assert.equal(entries(navigationSections(items, '/master/user/20')).find(item => item.active).url, '/master/user');
assert.deepEqual(sections.find(section => section.label === 'Master Data DB').groups.map(group => group.key), ['1', '2', '3', '4']);
assert.equal(entries(navigationSections(items, '/', 'trainer'))[0].label, 'Master Trainer');
assert.equal(entries(navigationSections(items, '/', ' TIDAK ADA ')).length, 0);
assert.equal(entries(navigationSections(items.filter(item => item.url !== '/permissions'), '/permissions')).some(item => item.url === '/permissions'), false);
assert.equal(entries(navigationSections(items, '/manage-peserta-lain')).some(item => item.active), false);
assert.equal(items[5].group, 'Pengaturan DB');
assert.deepEqual(navigationSections([...items].reverse(), '/').find(section => section.label === 'Master Data DB').groups.map(group => group.key), ['4', '3', '2', '1']);
assert.equal(sections.find(section => section.key === '3').label, 'Pengaturan DB');
assert.deepEqual(entries(sections).map(item => item.label), items.map(item => item.label));
const rate = entries(navigationSections([{ label: 'Rate dari DB', url: '/master/rate/fixed', group: 'Master Data DB', sectionKey: '2', subgroupKey: '1' }], '/master/rate/variable'));
assert.equal(rate.length, 1);
assert.equal(rate[0].label, 'Rate dari DB');
assert.equal(rate[0].active, true);
const duplicate = navigationSections([{ ...items[1], sectionKey: '90' }, items[1]], '/');
assert.equal(duplicate.length, 2);
console.log('LULUS: struktur/label/urutan DB, kelompok, pencarian, active route terpanjang dan identitas modul.');

const grouped = items.map(item => ({ ...item, subgroupLabel: item.sectionKey === '2' ? { 1: 'Product', 2: 'User', 3: 'Peserta & Trainer', 4: 'Dokumen' }[item.subgroupKey] : '' }));
assert.deepEqual(navigationSections(grouped, '/master/user/20').find(s => s.key === '2').groups.map(g => g.label), ['Product', 'User', 'Peserta & Trainer', 'Dokumen']);
assert.deepEqual(navigationBreadcrumb(grouped, '/master/user/20'), ['Master Data DB', 'User']);
assert.deepEqual(navigationBreadcrumb(grouped, '/manage-peserta/monitoring'), ['Operasional']);
assert.deepEqual(navigationBreadcrumb(grouped, '/tidak-ada'), []);
assert.deepEqual(navigationBreadcrumb(grouped, '/'), []);
assert.equal(navigationSections(grouped, '/', 'dokumen')[0].groups[0].items[0].label, 'Template Konfirmasi');
assert.equal(navigationSections(grouped.filter(i => i.subgroupKey !== '2'), '/master/user').some(s => s.groups.some(g => g.label === 'User')), false);
assert.deepEqual(navigationBreadcrumb([{ ...grouped[1], label: 'Rate', url: '/master/rate/fixed' }], '/master/rate/variable'), ['Master Data DB', 'Product']);
assert.deepEqual(navigationBreadcrumb([{ ...grouped[1], group: 'Nama modul baru', subgroupLabel: '' }], '/master/jenis-produk'), ['Nama modul baru']);
console.log('LULUS: submenu tiga level, breadcrumb, pencarian submenu, kelompok kosong, dan Variable Cost.');

// Deployment subpath: page.url berprefix tetap mencocokkan item navigasi tanpa prefix.
globalThis.document = { querySelector: () => ({ getAttribute: () => '/labmania' }) };
try {
    const prefixed = entries(navigationSections(items, '/labmania/manage-peserta/monitoring?status=belum'));
    assert.deepEqual(prefixed.filter(item => item.active).map(item => item.url), ['/manage-peserta/monitoring']);
    assert.equal(entries(navigationSections(items, '/labmania/master/user/20')).find(item => item.active).url, '/master/user');
    assert.deepEqual(entries(navigationSections(items, '/labmania')).filter(item => item.active).map(item => item.url), ['/']);
    assert.deepEqual(navigationBreadcrumb(items, '/labmania/master/user'), ['Master Data DB']);
} finally { delete globalThis.document; }
console.log('LULUS: navigasi dengan prefix /labmania.');
