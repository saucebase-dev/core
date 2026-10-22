/// <reference path="./generated.d.ts" />

/**
 * The shapes core shares with every page, and the names both frameworks' libraries use.
 *
 * Declared once here: nothing in this file depends on Vue or React, so neither tree
 * keeps a copy of its own.
 */

import type { Settings } from '../lib/settings';

/** The `navigation` prop: every menu the layouts draw, by group. */
export interface Navigation {
    main: MenuItem[];
    secondary: MenuItem[];
    settings: MenuItem[];
    user: MenuItem[];
    landing: MenuItem[];
}

export interface MenuBadge {
    /** Text or number; without it the badge is a small dot. */
    content?: string | number;
    variant?: 'default' | 'secondary' | 'destructive' | 'outline';
    class?: string;
}

export interface MenuItem {
    id?: string;
    title: string;
    /** Laravel route name, for matching the active item. */
    route?: string;
    url?: string;
    slug?: string;
    /** A name registered with `registerIcon()`. */
    icon?: string | null;
    permission?: string;
    /** A name registered with `registerAction()`, for an item that is a button. */
    action?: string;
    type?: 'label' | 'separator';
    active?: boolean;
    /** A plain anchor instead of an Inertia link. */
    external?: boolean;
    newPage?: boolean;
    /** `true` for a simple dot. */
    badge?: MenuBadge | boolean;
    children?: MenuItem[];
    class?: string;
}

export interface Breadcrumb {
    title: string;
    url?: string;
    active?: boolean;
    attributes?: {
        label?: string;
        [key: string]: any;
    };
    children?: any[];
    depth?: number;
}

/**
 * Where a module may hang a component without core knowing the module exists.
 *
 * Names follow `{area}::{region}.{position}`, like Filament's render hooks:
 * - area: who renders it. `app` (shell), `site` (public layout), `dashboard`
 *   (signed-in layout), `home` (index page), or a module.
 * - position: `before`/`after` sit outside the region, `start`/`end` inside it.
 *   A region with one fixed spot names it instead (`sidebar.brand`).
 *
 * `dashboard::sidebar.brand` replaces core's `AppBrand`; `dashboard::user-menu.subtitle`
 * replaces the email in the sidebar's user menu.
 *
 * A module adds its own slots by augmenting this interface, then renders them:
 *
 *     declare module '@saucebase/core/types' {
 *         interface GlobalComponentSlots { 'blog::post.after': true }
 *     }
 */
export interface GlobalComponentSlots {
    'app::page.before': true;
    'app::page.after': true;
    'site::header.before': true;
    'site::header.after': true;
    'site::content.before': true;
    'site::content.after': true;
    'site::footer.before': true;
    'site::footer.after': true;
    'home::content.before': true;
    'home::content.after': true;
    'dashboard::header.before': true;
    'dashboard::header.after': true;
    'dashboard::content.before': true;
    'dashboard::content.after': true;
    'dashboard::sidebar.start': true;
    'dashboard::sidebar.end': true;
    'dashboard::sidebar.brand': true;
    'dashboard::user-menu.subtitle': true;
}

export type GlobalComponentSlot = keyof GlobalComponentSlots;

/** What a navigation item does when it names an `action` instead of a URL. */
export type ActionHandler = (event: MouseEvent) => void | Promise<void>;

/** The props core's middleware shares with every page. Modules add their own the same way. */
declare module '@inertiajs/core' {
    interface PageProps {
        locale?: string;
        locales?: Record<string, string>;
        modules?: Record<string, string>;
        navigation?: Record<string, any>;
        breadcrumbs?: Breadcrumb[];
        toast?: Saucebase.Core.Data.ToastData;
        settings: Settings;
    }

    /** What the server flashes for one request: a toast, and whatever a module adds. */
    interface InertiaConfig {
        flashDataType: {
            toast?: Saucebase.Core.Data.ToastData;
            [key: string]: unknown;
        };
    }
}
