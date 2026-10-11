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
 * `top` and `bottom` wrap the page, above and below everything a layout renders.
 * `sidebar-brand` replaces the block above the sidebar navigation, whose default is
 * core's own `AppBrand`. `user-subtitle` replaces the email under the user's first
 * name in the sidebar's user menu.
 *
 * Adding a slot means adding a case here and to `slots` in each framework's
 * `globalComponents.ts`, and rendering it in the layout that owns that region.
 */
export type GlobalComponentSlot =
    'top' | 'bottom' | 'sidebar-brand' | 'user-subtitle';

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
