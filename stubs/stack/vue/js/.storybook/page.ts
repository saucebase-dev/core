import type { Page } from '@inertiajs/core';

/**
 * The Inertia page every story renders in: the shared props a page always has, empty,
 * with the story's own `parameters.inertia` on top. Partial on purpose: a story fills
 * only the props its component reads.
 */
export function page(props: Record<string, unknown> = {}): Page {
    return {
        component: 'Story',
        // The frame's own URL: Inertia writes it to history, and `/` is Storybook itself.
        url: location.pathname + location.search,
        version: null,
        rescuedProps: [],
        flash: {},
        rememberedState: {},
        props: {
            errors: {},
            auth: { user: null },
            modules: [],
            navigation: {},
            settings: {
                sections: [],
                general: {
                    site_name: 'Saucebase',
                    site_description: 'Laravel SaaS starter kit',
                },
            },
            ...props,
        },
    } as unknown as Page;
}

/** A signed-in user, as `auth.user`. */
export const user = {
    id: 1,
    name: 'Ada Lovelace',
    email: 'ada@example.com',
    avatar: '',
    last_login_at: null,
};

// Core's own navigation (stubs/app/routes/navigation.php) as the server shares it to a
// signed-in user on the dashboard. Modules add their items (Settings, Log out…) to these groups.
const welcome = {
    title: 'Welcome',
    slug: 'dashboard',
    url: '/dashboard',
    icon: 'dashboard',
    active: true,
};

const secondary = [
    {
        title: 'Star us on Github',
        slug: 'github',
        url: 'https://github.com/saucebase-dev/saucebase',
        icon: 'github',
        external: true,
        newPage: true,
    },
    {
        title: 'Documentation',
        slug: 'documentation',
        url: 'https://saucebase-dev.github.io/docs/',
        icon: 'documentation',
        external: true,
        newPage: true,
    },
];

export const navigation = { main: [welcome], secondary, user: [] };

/** Every item type the sidebar renders, for the design system: label, badge, submenu, and the
 * Admin link core adds for admins. Its link goes nowhere: a story has no admin panel. */
export const itemTypes = {
    main: [
        { title: 'Platform', type: 'label' },
        welcome,
        {
            title: 'Inbox',
            slug: 'inbox',
            url: '/inbox',
            icon: 'documentation',
            badge: { content: 3 },
        },
        {
            title: 'Projects',
            slug: 'projects',
            url: '/projects',
            icon: 'admin',
            children: [
                { title: 'Active', url: '/projects/active' },
                { title: 'Archived', url: '/projects/archived', badge: true },
            ],
        },
    ],
    secondary: [
        ...secondary,
        {
            title: 'Admin',
            slug: 'admin',
            url: '#',
            icon: 'admin',
            external: true,
            class: 'bg-yellow-500/10 text-yellow-600 hover:bg-yellow-500/20 hover:text-yellow-700 dark:hover:text-yellow-400',
        },
    ],
    user: [],
};
