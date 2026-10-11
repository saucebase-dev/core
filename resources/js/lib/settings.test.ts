import type { Page } from '@inertiajs/core';
import { describe, expect, it } from 'vitest';
import {
    BRAND_FALLBACKS,
    brandAssetUrl,
    siteTitle,
    type Settings,
} from './settings';

function general(
    overrides: Partial<Settings['general']> = {},
): Settings['general'] {
    return {
        site_name: 'Saucebase',
        site_tagline: null,
        site_description: null,
        site_logo_on_light: null,
        site_logo_on_dark: null,
        site_icon_on_light: null,
        site_icon_on_dark: null,
        ...overrides,
    };
}

function page(siteName?: string): Page {
    return {
        props: { settings: { general: { site_name: siteName } } },
    } as unknown as Page;
}

describe('brandAssetUrl', () => {
    it('uses the uploaded asset', () => {
        expect(
            brandAssetUrl(
                general({ site_logo_on_dark: '/storage/logo.svg' }),
                'site_logo_on_dark',
            ),
        ).toBe('/storage/logo.svg');
    });

    it('falls back to the bundled artwork when nothing was uploaded', () => {
        expect(brandAssetUrl(general(), 'site_icon_on_light')).toBe(
            BRAND_FALLBACKS.site_icon_on_light,
        );
    });
});

describe('siteTitle', () => {
    it('joins the page title and the site name', () => {
        expect(siteTitle('Dashboard', page('Saucebase'))).toBe(
            'Dashboard - Saucebase',
        );
    });

    it('never renders a bare separator', () => {
        expect(siteTitle('', page('Saucebase'))).toBe('Saucebase');
        expect(siteTitle('Dashboard', page(''))).toBe('Dashboard');
        expect(siteTitle('Dashboard', { props: {} } as unknown as Page)).toBe(
            'Dashboard',
        );
    });
});
