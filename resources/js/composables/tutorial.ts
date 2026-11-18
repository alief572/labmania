import { nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Driver, DriveStep } from 'driver.js';
import { tutorials, type TutorialKey } from './tutorials';

let active: Driver | undefined;
let revision = 0;
let observer: MutationObserver | undefined;
let unsubscribe: (() => void) | undefined;

export function stopTutorial() {
    revision++;
    observer?.disconnect();
    unsubscribe?.();
    observer = undefined;
    unsubscribe = undefined;
    const previous = active;
    active = undefined;
    previous?.destroy();
}

export function visible(element: Element): boolean {
    return element.isConnected && !!element.getClientRects().length && !element.closest('[inert], [aria-hidden="true"]') && getComputedStyle(element).visibility !== 'hidden';
}

export function tutorialTarget(root: ParentNode, target: string): Element | undefined {
    const [kind, label] = target.split(':');
    const selectors: Record<string, string> = {
        filters: '.filters, .filter-bar', actions: '[data-tour="actions"], .page-actions',
        'row-action': '[data-tour="row-action"]', table: '[data-tour="table"], .table-wrap, table',
        tabs: '.tabs, .quotation-steps', form: 'form, .form-grid', 'form-actions': '.form-actions',
        stats: '.stats, .stat-grid, .stat-cards', status: '.status-panel, .status-strip, .badge',
        link: '[data-tour="link"]', gate: '.gate-notice, .notice, .alert',
        field: 'label', section: 'h2, h3, legend', column: 'th',
    };
    const candidates = root.querySelectorAll(`[data-tour="${CSS.escape(target)}"], ${selectors[kind] || '[data-tour-missing]'}`);
    return Array.from(candidates).find(element => {
        if (!visible(element) || (label && !element.textContent?.toLocaleLowerCase().includes(label.toLocaleLowerCase()))) return false;
        if (kind === 'actions') return Array.from(element.querySelectorAll('a, button')).some(action => !action.closest('.tutorial-control') && !action.matches(':disabled') && visible(action));
        return true;
    });
}

export async function startTutorial(key: TutorialKey, trigger: HTMLElement, root: ParentNode = document.querySelector('#main') || document.body) {
    stopTutorial();
    const current = revision;
    unsubscribe = router.on('start', () => stopTutorial());
    let driver: typeof import('driver.js').driver;
    try { ({ driver } = await import('driver.js')); }
    catch (error) { if (revision === current) stopTutorial(); throw error; }
    if (revision !== current || !trigger.isConnected) return;
    await nextTick();
    if (revision !== current || !trigger.isConnected) return;
    const steps: DriveStep[] = [];
    for (const definition of tutorials[key]) {
        const element = definition.target ? tutorialTarget(root, definition.target) : undefined;
        if (definition.target && !element) continue;
        const destination = key === 'qtStart' && definition.next === 'qtForm' ? trigger : definition.next ? root.querySelector<HTMLElement>(`[data-tour-next="${definition.next}"]`) : undefined;
        if (definition.next && (!destination || !visible(destination))) continue;
        steps.push({ element, popover: {
            title: definition.title, description: definition.description,
            ...(destination ? { nextBtnText: 'Lanjut →', doneBtnText: 'Lanjut →', onNextClick: () => {
                const nextKey = definition.next as TutorialKey;
                if (!visible(destination) || destination.matches(':disabled')) return;
                stopTutorial();
                if (key === 'qtStart' && nextKey === 'qtForm') { void startTutorial(nextKey, trigger, root); return; }
                if (destination instanceof HTMLAnchorElement) {
                    const url = new URL(destination.href, location.href);
                    if (url.origin !== location.origin) return;
                    router.visit(url.href, { onSuccess: async () => {
                        await nextTick();
                        document.querySelector<HTMLElement>(`[data-tutorial-key="${nextKey}"]`)?.click();
                    } });
                } else if (destination instanceof HTMLButtonElement && destination.type === 'button') {
                    destination.click();
                    void nextTick().then(() => startTutorial(nextKey, trigger, root));
                }
            } } : {}),
        } });
    }
    active = driver({ steps, showProgress: true, progressText: '{{current}} dari {{total}}',
        nextBtnText: 'Lanjut', prevBtnText: 'Kembali', doneBtnText: 'Selesai',
        overlayOpacity: 0.55, stagePadding: 6, stageRadius: 8,
        popoverClass: 'lm-tour', disableActiveInteraction: true,
        onDestroyed: () => { if (current === revision) { active = undefined; stopTutorial(); } if (trigger.isConnected) trigger.focus(); },
    });
    observer = new MutationObserver(() => {
        const element = active?.getActiveElement();
        if (element && !visible(element)) stopTutorial();
    });
    observer.observe(document.body, { childList: true, subtree: true });
    active.drive();
}
