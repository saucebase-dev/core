declare namespace Saucebase {
namespace Core {
namespace Data {
export type GeneralSettingsData = {
site_name: string,
site_tagline: string | null,
site_description: string | null,
site_logo_on_light: string | null,
site_logo_on_dark: string | null,
site_icon_on_light: string | null,
site_icon_on_dark: string | null,
};
export type ToastActionData = {
label: string,
type?: Saucebase.Core.Enums.ToastActionType,
onClick?: string,
};
export type ToastData = {
message: string,
type: Saucebase.Core.Enums.ToastType,
description?: string,
action?: Saucebase.Core.Data.ToastActionData,
duration?: number,
position?: Saucebase.Core.Enums.ToastPosition,
};
}
namespace Enums {
export type ToastActionType = 'route' | 'url' | 'external_url' | 'function';
export type ToastPosition = 'top-left' | 'top-center' | 'top-right' | 'bottom-left' | 'bottom-center' | 'bottom-right';
export type ToastType = 'default' | 'success' | 'error' | 'info' | 'warning' | 'loading';
}
}
}
