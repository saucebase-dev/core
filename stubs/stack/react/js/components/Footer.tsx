import { Link } from '@inertiajs/react';
import { useSettings } from '@saucebase/react/hooks/useSettings';
import { useT } from '@saucebase/react/i18n';
import { Heart } from 'lucide-react';

export default function Footer() {
    const t = useT();
    const settings = useSettings();
    const name = settings.general.site_name;

    // A monospace letter is 0.6em wide, so this is the name's natural width at the
    // font size of 100 the drawing uses.
    const width = name.length * 60;

    return (
        <footer className="relative mt-auto w-full overflow-hidden pb-12 sm:pb-48">
            <div className="relative z-10 mx-4 py-8 min-[450px]:mx-auto min-[450px]:max-w-7xl min-[450px]:px-6">
                <div className="text-muted-foreground flex w-full flex-col items-center justify-between gap-4 text-sm md:flex-row">
                    <div className="flex flex-col items-center gap-y-1 md:flex-row md:items-center md:gap-x-6 md:gap-y-0">
                        <span data-testid="footer-app-name">
                            © {new Date().getFullYear()}{' '}
                            {settings.general.site_name}
                        </span>
                        <a
                            href="https://github.com/saucebase-dev/saucebase"
                            className="hover:text-foreground"
                        >
                            {t('GitHub')}
                        </a>
                        <a
                            href="https://saucebase-dev.github.io/docs/"
                            className="hover:text-foreground"
                        >
                            {t('Documentation')}
                        </a>
                        {route().has('privacy') && (
                            <Link
                                href={route('privacy')}
                                className="hover:text-foreground"
                            >
                                {t('Privacy')}
                            </Link>
                        )}
                        {route().has('terms') && (
                            <Link
                                href={route('terms')}
                                className="hover:text-foreground"
                            >
                                {t('Terms')}
                            </Link>
                        )}
                    </div>
                    <div className="flex items-center gap-1">
                        <span>{t('Crafted with')}</span>
                        <Heart className="h-4 w-4 text-red-500" />
                        <span>{t('for developers')}</span>
                        <span>{t('by :name', { name: 'Renan Roble' })}</span>
                    </div>
                </div>
            </div>
            {/*
                Drawn as SVG so the name always spans the width it is given: `textLength`
                stretches or squeezes it to fit, whatever its length, the font or the
                window. The name is already in the footer as text, so this copy is hidden
                from assistive technology.
            */}
            <div
                className="absolute inset-x-0 bottom-0 z-0 px-4"
                aria-hidden="true"
            >
                {name && (
                    <svg
                        viewBox={`0 0 ${width} 104`}
                        style={{ maxWidth: `${name.length * 140}px` }}
                        className="text-foreground/10 mx-auto block w-full font-mono font-black select-none"
                        data-testid="footer-watermark"
                    >
                        <text
                            y="80"
                            fontSize="100"
                            textLength={width}
                            lengthAdjust="spacingAndGlyphs"
                            fill="currentColor"
                        >
                            {name}
                        </text>
                    </svg>
                )}
            </div>
        </footer>
    );
}
