import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { expect } from 'storybook/test';
import { DangerZone } from '.';

/**
 * The last section of a settings page, for what can't be undone: deleting an account, a
 * workspace, a project. A red edge and heading set it apart; each action inside still
 * confirms before it acts (App/DynamicDialog).
 */
const meta = preview.meta({
    title: 'UI/DangerZone',
    component: DangerZone,
    // Props the story fills itself: their controls would do nothing.
    argTypes: {
        children: { control: false },
    },
    args: {
        children: (
            <div className="flex items-center justify-between gap-6">
                <div>
                    <p className="text-sm font-medium">Delete account</p>
                    <p className="text-muted-foreground text-sm">
                        Your data is deleted for good.
                    </p>
                </div>
                <Button variant="destructive">Delete account</Button>
            </div>
        ),
    },
});
export default meta;

/** With its default heading. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.getByText('Danger zone')).toBeVisible();
        await expect(
            canvas.getByRole('button', { name: 'Delete account' }),
        ).toBeVisible();
    },
});

/** A heading that names what's at stake. */
export const CustomTitle = meta.story({
    args: { title: 'Delete workspace' },
});

/** Without a heading, inside a section that already has one. */
export const WithoutHeader = meta.story({
    args: { showHeader: false },
    play: async ({ canvas }) => {
        await expect(canvas.queryByText('Danger zone')).toBeNull();
    },
});
