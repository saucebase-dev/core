import { Link } from '@inertiajs/react';
import { useSettings } from '@saucebase/react/hooks/useSettings';
import { useT } from '@saucebase/react/i18n';
import { Heart } from 'lucide-react';

export default function Footer() {
    const t = useT();
    const settings = useSettings();
    const name = settings.general.site_name;

    // Sized by letter count so the name stays on one line. The box ends at the baseline
    // (text-box), so a name gets room below only for letters that reach under it.
    const nameStyle = {
        fontSize: `min(22vw, 420px, calc((100vw - 20px) * ${2 / (name.length + 0.2)}))`,
        paddingBottom: /[gjpqy]/.test(name) ? '0.3em' : '0.03em',
    };

    return (
        <footer className="relative mt-auto w-full overflow-hidden">
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
            <div className="w-full font-mono">
                <p
                    className="text-center leading-[1.3] font-black -tracking-widest whitespace-nowrap select-none [text-box:trim-end_text_alphabetic]"
                    style={nameStyle}
                    data-testid="footer-watermark"
                >
                    <span className="text-foreground/10 inline-block pr-[0.1em]">
                        {name}
                    </span>
                </p>
            </div>
        </footer>
    );
}
