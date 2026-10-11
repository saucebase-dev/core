import { toast } from 'sonner';

/** Shows the toast the server flashed, in the style its type names. */
export function showToast(toastProp: Saucebase.Core.Data.ToastData): void {
    if (!toastProp.message) return;

    const options = {
        description: toastProp.description,
        duration: toastProp.duration,
        position: toastProp.position,
    };

    switch (toastProp.type) {
        case 'success':
            toast.success(toastProp.message, options);
            break;
        case 'error':
            toast.error(toastProp.message, options);
            break;
        case 'info':
            toast.info(toastProp.message, options);
            break;
        case 'warning':
            toast.warning(toastProp.message, options);
            break;
        case 'loading':
            toast.loading(toastProp.message, options);
            break;
        default:
            toast(toastProp.message, options);
    }
}
