import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import '@saucebase/core/types';

export interface User {
    id: number;
    name: string;
    email: string;
    avatar: string;
    last_login_at: string | null;
    role?: string;
}

/**
 * A page's own props on top of every shared prop.
 *
 * Built from the augmented Inertia interface rather than a copy of the list, so
 * a prop core or a module shares (auth's `auth`, for one) is covered here
 * without this file having to name it.
 */
export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & InertiaPageProps;
