import type { route as ziggyRoute } from 'ziggy-js';

declare global {
    var route: typeof ziggyRoute;
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof ziggyRoute;
    }
}
