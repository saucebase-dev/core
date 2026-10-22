import type { Component } from 'vue';
import type { GlobalComponentSlot } from '../../types';

export type { GlobalComponentSlot };

/** Modules register from their `app` entry, which `moduleSetup.ts` runs at boot, so an adopter wires nothing by hand. */
const slots = new Map<GlobalComponentSlot, { component: Component; order: number }[]>();

/** Lower `order` renders first; equal orders keep registration order. */
export function registerGlobalComponent(
    slot: GlobalComponentSlot,
    component: Component,
    { order = 100 }: { order?: number } = {},
): void {
    const entries = slots.get(slot) ?? [];
    const at = entries.findIndex((entry) => entry.order > order);

    entries.splice(at === -1 ? entries.length : at, 0, { component, order });
    slots.set(slot, entries);
}

export function getGlobalComponents(slot: GlobalComponentSlot): Component[] {
    return (slots.get(slot) ?? []).map((entry) => entry.component);
}

/** Whether anything has claimed a slot. Lets a layout fall back to its own default rather than rendering an empty region. */
export function hasGlobalComponent(slot: GlobalComponentSlot): boolean {
    return (slots.get(slot)?.length ?? 0) > 0;
}
