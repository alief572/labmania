import { stripBase } from './appUrl.ts';
type Item = { label: string; url: string; group: string | null; sectionKey: string; subgroupKey: string; subgroupLabel?: string; icon?: string | null; count?: number };
type Entry = Item & { active: boolean };
type Section = { key: string; label: string; icon: string | null; groups: { key: string; label: string; items: Entry[] }[] };

export function navigationSections(items: Item[], url: string, search = ''): Section[] {
    const path = stripBase(url.split('?')[0]);
    const matches = items.filter(item => item.url === path || (item.url !== '/' && path.startsWith(item.url + '/')) || (item.url === '/master/rate/fixed' && path === '/master/rate/variable'));
    const active = matches.sort((a, b) => b.url.length - a.url.length)[0]?.url;
    const sections: Section[] = [];
    const query = search.trim().toLocaleLowerCase('id');
    for (const item of items) {
        const module = item.group || '';
        if (query && ![module, item.subgroupLabel || '', item.label].some(text => text.toLocaleLowerCase('id').includes(query))) continue;
        let section = sections.find(section => section.key === item.sectionKey);
        if (!section) sections.push(section = { key: item.sectionKey, label: module, icon: item.icon ?? null, groups: [] });
        let group = section.groups.find(group => group.key === item.subgroupKey);
        if (!group) section.groups.push(group = { key: item.subgroupKey, label: item.subgroupLabel || '', items: [] });
        group.items.push({ ...item, active: item.url === active });
    }
    return sections;
}

export function navigationBreadcrumb(items: Item[], url: string): string[] {
    const section = navigationSections(items, url).find(section => section.groups.some(group => group.items.some(item => item.active)));
    const group = section?.groups.find(group => group.items.some(item => item.active));
    return [section?.label, group?.label].filter((label): label is string => !!label);
}
