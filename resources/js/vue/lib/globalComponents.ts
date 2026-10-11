import type { Component } from 'vue';
import type { GlobalComponentSlot } from '../../types';

export type { GlobalComponentSlot };

/** Modules register from their `app` entry, which `moduleSetup.ts` runs at boot, so an adopter wires nothing by hand. */
const slots: Record<GlobalComponentSlot, Component[]> = {
    top: [],
    bottom: [],
    'sidebar-brand': [],
    'user-subtitle': [],
};

export function registerGlobalComponent(
    slot: GlobalComponentSlot,
    component: Component,
): void {
    slots[slot].push(component);
}

export function getGlobalComponents(slot: GlobalComponentSlot): Component[] {
    return slots[slot];
}

/**
 * Whether anything has claimed a slot.
 *
 * Lets a layout fall back to its own default rather than rendering an empty region.
 */
export function hasGlobalComponent(slot: GlobalComponentSlot): boolean {
    return slots[slot].length > 0;
}
