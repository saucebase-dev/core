import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import IconAlertCircle from '~icons/lucide/alert-circle';
import IconInfo from '~icons/lucide/info';
import { Alert, AlertDescription, AlertTitle } from '.';

/**
 * A callout inside a page: something the reader should know before going on. Screen
 * readers announce it (`role="alert"`).
 *
 * For the outcome of a form, App/AlertMessage wraps this with colours and icons per
 * status. Compose `Alert` yourself when it needs a title or more than one line.
 */
const meta = preview.meta({
    title: 'UI/Alert',
    component: Alert,
    // Slots the story fills itself: their controls would do nothing.
    argTypes: {
        default: { control: false },
    },
    render: (args) => ({
        components: { Alert, AlertDescription, AlertTitle, IconInfo },
        setup: () => ({ args }),
        template: `
            <Alert v-bind="args">
                <IconInfo />
                <AlertTitle>Scheduled maintenance</AlertTitle>
                <AlertDescription>The dashboard is read-only on Sunday from 02:00 to 03:00 UTC.</AlertDescription>
            </Alert>`,
    }),
});
export default meta;

/** Icon, title and a line. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.getByRole('alert')).toHaveTextContent(
            'Scheduled maintenance',
        );
    },
});

/** Something went wrong. */
export const Destructive = meta.story({
    args: { variant: 'destructive' },
    render: (args) => ({
        components: { Alert, AlertDescription, AlertTitle, IconAlertCircle },
        setup: () => ({ args }),
        template: `
            <Alert v-bind="args">
                <IconAlertCircle />
                <AlertTitle>Payment failed</AlertTitle>
                <AlertDescription>Your card was declined. Update it to keep your plan.</AlertDescription>
            </Alert>`,
    }),
});

/** Only a line, without icon or title. */
export const DescriptionOnly = meta.story({
    render: (args) => ({
        components: { Alert, AlertDescription },
        setup: () => ({ args }),
        template: `
            <Alert v-bind="args">
                <AlertDescription>Invitations expire after 7 days.</AlertDescription>
            </Alert>`,
    }),
});
