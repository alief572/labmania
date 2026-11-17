import { appUrl } from './appUrl.ts';
import { ref } from 'vue';

export type Wilayah = { code: string; name: string };

/** Provinsi dan Kota/Kabupaten dari endpoint internal /master/wilayah (cache server 14 hari). */
export function useWilayah() {
    const provinces = ref<Wilayah[]>([]);
    const regencies = ref<Wilayah[]>([]);
    async function loadProvinces() {
        if (provinces.value.length) return;
        provinces.value = (await (await fetch(appUrl('/master/wilayah/provinces'), { headers: { Accept: 'application/json' } })).json()).data ?? [];
    }
    async function loadRegencies(provCode: string) {
        regencies.value = [];
        if (!provCode) return;
        regencies.value = (await (await fetch(appUrl(`/master/wilayah/regencies/${encodeURIComponent(provCode)}`), { headers: { Accept: 'application/json' } })).json()).data ?? [];
    }
    const nameOf = (list: Wilayah[], code: string) => list.find((w) => w.code === code)?.name ?? '';
    return { provinces, regencies, loadProvinces, loadRegencies, nameOf };
}
