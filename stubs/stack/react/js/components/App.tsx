import { router } from '@inertiajs/react';
import { showToast } from '@saucebase/react/lib/toastActions';
import type { ReactNode } from 'react';
import { useEffect } from 'react';
import DynamicDialog from './DynamicDialog';
import SettingsModal from './SettingsModal';
import { Toaster } from './ui/sonner';

interface AppProps {
    children: ReactNode;
}

export default function App({ children }: AppProps) {
    useEffect(() => {
        return router.on('navigate', (event) => {
            const toastProp = event.detail.page.props.toast as
                | Saucebase.Core.Data.ToastData
                | undefined;
            if (toastProp) showToast(toastProp);
        });
    }, []);

    return (
        <>
            <Toaster />
            <DynamicDialog />
            <SettingsModal />
            {children}
        </>
    );
}
