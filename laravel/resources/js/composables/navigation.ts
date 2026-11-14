type Item = { label: string; url: string; group: string | null; count?: number };
type Entry = Item & { active: boolean };
type Section = { label: string; groups: { label: string; items: Entry[] }[] };

export function navigationSections(items: Item[], url: string, search = ''): Section[] {
    const path = url.split('?')[0];
    const normalized = items.filter(item => item.url !== '/master/rate/variable' || !items.some(other => other.url === '/master/rate/fixed'))
        .map(item => item.url === '/master/rate/fixed' ? { ...item, label: 'Master Rate' } : item);
    const matches = normalized.filter(item => item.url === path || (item.url !== '/' && path.startsWith(item.url + '/')) || (item.url === '/master/rate/fixed' && path === '/master/rate/variable'));
    const active = matches.sort((a, b) => b.url.length - a.url.length)[0]?.url;
    const sections: Section[] = [];
    const query = search.trim().toLocaleLowerCase('id');
    for (const item of normalized) {
        const module = item.url === '/permissions' ? 'Master Data' : item.group || '';
        let subgroup = '';
        if (module === 'Master Data') {
            subgroup = /\/(user|role)$/.test(item.url) || item.url === '/permissions' ? 'User'
                : /\/(peserta|trainer|company)$/.test(item.url) ? 'Peserta & Trainer'
                : item.url.includes('/template-') ? 'Dokumen' : 'Product';
        }
        if (query && ![module, subgroup, item.label].some(text => text.toLocaleLowerCase('id').includes(query))) continue;
        let section = sections.find(section => section.label === module);
        if (!section) sections.push(section = { label: module, groups: [] });
        let group = section.groups.find(group => group.label === subgroup);
        if (!group) section.groups.push(group = { label: subgroup, items: [] });
        group.items.push({ ...item, active: item.url === active });
    }
    const masterOrder = ['Product', 'User', 'Peserta & Trainer', 'Dokumen'];
    sections.find(section => section.label === 'Master Data')?.groups.sort((a, b) => masterOrder.indexOf(a.label) - masterOrder.indexOf(b.label));
    return sections;
}
