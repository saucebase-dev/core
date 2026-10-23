// import { registerIcon } from '@/lib/navigation';
// import IconExample from '~icons/lucide/example';

// Custom styles: create resources/css/style.css in this module and uncomment.
// import '@modules/{module}/resources/css/style.css';

/**
 * {Module} module setup
 * Called before the first render, on the server too: register things here,
 * and leave anything that needs window or document to afterMount()
 */
export function setup() {
    console.debug('{Module} module loaded');

    // Register icons for navigation items defined in routes/navigation.php
    // registerIcon('{module-}', IconExample);
}

/**
 * {Module} module after mount logic
 * Called in the browser after the app has mounted
 */
export function afterMount() {
    console.debug('{Module} module after mount logic executed');
}
