import {
    CircleCheckIcon,
    InfoIcon,
    Loader2Icon,
    OctagonXIcon,
    TriangleAlertIcon,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { Toaster as Sonner, type ToasterProps } from 'sonner';

function useDarkMode() {
    // Read after mount: there is no document while the page renders on the server.
    const [isDark, setIsDark] = useState(false);

    useEffect(() => {
        const read = () =>
            setIsDark(document.documentElement.classList.contains('dark'));

        read();

        const observer = new MutationObserver(read);
        observer.observe(document.documentElement, {
            attributeFilter: ['class'],
        });
        return () => observer.disconnect();
    }, []);

    return isDark;
}

const Toaster = ({ ...props }: ToasterProps) => {
    const isDark = useDarkMode();

    return (
        <Sonner
            theme={isDark ? 'dark' : 'light'}
            className="toaster group"
            icons={{
                success: <CircleCheckIcon className="size-4" />,
                info: <InfoIcon className="size-4" />,
                warning: <TriangleAlertIcon className="size-4" />,
                error: <OctagonXIcon className="size-4" />,
                loading: <Loader2Icon className="size-4 animate-spin" />,
            }}
            style={
                {
                    '--normal-bg': 'var(--popover)',
                    '--normal-text': 'var(--popover-foreground)',
                    '--normal-border': 'var(--border)',
                    '--border-radius': 'var(--radius)',
                } as React.CSSProperties
            }
            {...props}
        />
    );
};

export { Toaster };
