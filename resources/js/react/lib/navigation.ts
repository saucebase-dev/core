import type { ComponentType } from 'react';
import type { ActionHandler } from '../../types';

export type { ActionHandler };

/**
 * What a navigation item does when it names an `action` instead of a URL, and which
 * component draws the icon it names. The app and modules fill both from their entries;
 * nothing is registered here.
 */
export type IconComponent = ComponentType<{ className?: string }>;

const actionRegistry: Record<string, ActionHandler> = {};
const iconRegistry: Record<string, IconComponent> = {};

export function registerAction(actionId: string, handler: ActionHandler): void {
    if (actionRegistry[actionId]) {
        console.warn(`Action handler already registered for: ${actionId}`);
    }

    actionRegistry[actionId] = handler;
}

export function handleAction(actionId: string, event: MouseEvent): void {
    const handler = actionRegistry[actionId];

    if (handler) {
        handler(event);
    } else {
        console.warn(`No handler registered for action: ${actionId}`);
    }
}

/**
 * @param name The `icon` a navigation item names in PHP.
 */
export function registerIcon(name: string, component: IconComponent): void {
    iconRegistry[name] = component;
}

export function resolveIcon(name: string): IconComponent | undefined {
    return iconRegistry[name];
}
