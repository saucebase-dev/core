import { preview } from '@/.storybook/preview';
import { Button } from './button';

const meta = preview.meta({
    title: 'UI/Button',
    component: Button,
    args: { children: 'Save', variant: 'default', size: 'default' },
    argTypes: {
        variant: {
            control: 'select',
            options: ['default', 'destructive', 'outline', 'secondary', 'ghost', 'link'],
        },
        size: { control: 'select', options: ['default', 'sm', 'lg'] },
    },
});

export const Primary = meta.story();
export const Secondary = meta.story({ args: { variant: 'secondary' } });
export const Outline = meta.story({ args: { variant: 'outline' } });
export const Destructive = meta.story({ args: { variant: 'destructive' } });
export const Ghost = meta.story({ args: { variant: 'ghost' } });
export const Link = meta.story({ args: { variant: 'link' } });
