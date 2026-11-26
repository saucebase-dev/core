import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { Trash2 } from 'lucide-react';
import type { ComponentProps } from 'react';
import { expect, screen, waitFor } from 'storybook/test';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from './tooltip';

type Args = ComponentProps<typeof Tooltip> & {
    side?: 'top' | 'right' | 'bottom' | 'left';
};

/**
 * A short label on hover or focus, for a control whose icon alone doesn't say what it
 * does. Never the only place for information the user needs: touch screens can't hover.
 *
 * Needs a `TooltipProvider` above it. The sidebar's `SidebarProvider` (AppLayout) has one;
 * public pages (SiteLayout, CardLayout) don't, so wrap it there.
 */
const meta = preview.meta({
    title: 'UI/Tooltip',
    component: Tooltip,
    args: { side: 'top' } as Args,
    argTypes: {
        // The story fills it itself: its control would do nothing.
        children: { control: false },
        side: {
            control: 'select',
            options: ['top', 'right', 'bottom', 'left'],
        },
    },
    render: ({ side, ...root }: Args) => (
        <TooltipProvider>
            <div className="flex justify-center p-12">
                <Tooltip {...root}>
                    <TooltipTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Delete project"
                        >
                            <Trash2 />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent side={side}>Delete project</TooltipContent>
                </Tooltip>
            </div>
        </TooltipProvider>
    ),
});
export default meta;

/** Shows when its trigger gets focus or a hover. */
export const Default = meta.story({
    play: async ({ canvas, userEvent }) => {
        await userEvent.hover(
            canvas.getByRole('button', { name: 'Delete project' }),
        );
        const tooltip = await screen.findByRole('tooltip');

        await waitFor(() =>
            expect(tooltip).toHaveTextContent('Delete project'),
        );
    },
});

/** Open from the start, below its trigger. */
export const Bottom = meta.story({
    args: { side: 'bottom', defaultOpen: true } as Args,
    parameters: { docs: { story: { inline: false, iframeHeight: '180px' } } },
});

/** Open from the start, to the right of its trigger. */
export const Right = meta.story({
    args: { side: 'right', defaultOpen: true } as Args,
    parameters: { docs: { story: { inline: false, iframeHeight: '180px' } } },
});
