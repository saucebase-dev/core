import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import { Button } from './button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from './card';

/**
 * A surface that groups one thing: a settings section, a plan, a summary. Header (title,
 * description, an action on the right), content, and a footer for its buttons.
 */
const meta = preview.meta({
    title: 'UI/Card',
    component: Card,
    render: () => (
        <Card className="max-w-md">
            <CardHeader>
                <CardTitle>Profile</CardTitle>
                <CardDescription>How other members see you.</CardDescription>
            </CardHeader>
            <CardContent>
                <p className="text-sm">Ada Lovelace · ada@example.com</p>
            </CardContent>
            <CardFooter className="justify-end">
                <Button>Save</Button>
            </CardFooter>
        </Card>
    ),
});
export default meta;

/** Header, content and footer. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.getByText('Profile')).toBeVisible();
        await expect(
            canvas.getByRole('button', { name: 'Save' }),
        ).toBeVisible();
    },
});

/** An action in the header's corner. */
export const WithHeaderAction = meta.story({
    render: () => (
        <Card className="max-w-md">
            <CardHeader>
                <CardTitle>Team members</CardTitle>
                <CardDescription>4 of 5 seats used.</CardDescription>
                <CardAction>
                    <Button variant="outline" size="sm">
                        Invite
                    </Button>
                </CardAction>
            </CardHeader>
            <CardContent>
                <p className="text-muted-foreground text-sm">
                    Ada, Grace, Alan, Linus.
                </p>
            </CardContent>
        </Card>
    ),
});

/** Only content: a plain surface. */
export const ContentOnly = meta.story({
    render: () => (
        <Card className="max-w-md">
            <CardContent>
                <p className="text-sm">Your trial ends on 12 December.</p>
            </CardContent>
        </Card>
    ),
});
