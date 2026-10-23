import {
    getGlobalComponents,
    type GlobalComponentSlot,
} from '@saucebase/react/lib/globalComponents';

export function GlobalComponents({ position }: { position: GlobalComponentSlot }) {
    return getGlobalComponents(position).map((C, i) => <C key={i} />);
}
