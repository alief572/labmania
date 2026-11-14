import assert from 'node:assert/strict';
import { navigationSections } from '../../resources/js/composables/navigation.ts';

const items = [
    { label: 'Beranda', url: '/', group: null },
    { label: 'Jenis Produk', url: '/master/jenis-produk', group: 'Master Data' },
    { label: 'Master User', url: '/master/user', group: 'Master Data' },
    { label: 'Master Trainer', url: '/master/trainer', group: 'Master Data' },
    { label: 'Template Konfirmasi', url: '/master/template-konfirmasi', group: 'Master Data' },
    { label: 'Role & Permission', url: '/permissions', group: 'Pengaturan' },
    { label: 'Manage Peserta', url: '/manage-peserta', group: 'Manage Peserta' },
    { label: 'Monitoring Peserta', url: '/manage-peserta/monitoring', group: 'Manage Peserta' },
];
const entries = sections => sections.flatMap(section => section.groups.flatMap(group => group.items));
const sections = navigationSections(items, '/manage-peserta/monitoring?status=belum');
assert.deepEqual(entries(sections).filter(item => item.active).map(item => item.url), ['/manage-peserta/monitoring']);
assert.equal(entries(navigationSections(items, '/master/user/20')).find(item => item.active).url, '/master/user');
assert.deepEqual(sections.find(section => section.label === 'Master Data').groups.map(group => group.label), ['Product', 'User', 'Peserta & Trainer', 'Dokumen']);
assert.equal(entries(navigationSections(items, '/', 'peserta & trainer'))[0].label, 'Master Trainer');
assert.equal(entries(navigationSections(items, '/', ' TIDAK ADA ')).length, 0);
assert.equal(entries(navigationSections(items.filter(item => item.url !== '/permissions'), '/permissions')).some(item => item.url === '/permissions'), false);
assert.equal(entries(navigationSections(items, '/manage-peserta-lain')).some(item => item.active), false);
assert.equal(items[5].group, 'Pengaturan');
assert.deepEqual(navigationSections([...items].reverse(), '/').find(section => section.label === 'Master Data').groups.map(group => group.label), ['Product', 'User', 'Peserta & Trainer', 'Dokumen']);
const rate = entries(navigationSections([{ label: 'Fixed Cost', url: '/master/rate/fixed', group: 'Master Data' }, { label: 'Variable Cost', url: '/master/rate/variable', group: 'Master Data' }], '/master/rate/variable'));
assert.equal(rate.length, 1);
assert.equal(rate[0].label, 'Master Rate');
assert.equal(rate[0].active, true);
console.log('LULUS: grup mockup, pencarian, active route terpanjang, batas URL dan menu hasil permission.');
