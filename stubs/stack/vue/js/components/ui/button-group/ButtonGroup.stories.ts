import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { expect } from 'storybook/test';
import { ButtonGroup, ButtonGroupSeparator, ButtonGroupText } from '.';

const components = {
    Button,
    ButtonGroup,
    ButtonGroupSeparator,
    ButtonGroupText,
};

/**
 * Related actions joined into one control: the views of a list, a pager, a split
 * button. The buttons share edges; each keeps its own label. Give the group an
 * `aria-label` that names what its buttons act on.
 */
const meta = preview.meta({
    title: 'UI/ButtonGroup',
    component: ButtonGroup,
    render: (args) => ({
        components,
        setup: () => ({ args }),
        template: `
            <ButtonGroup v-bind="args" aria-label="Invoice actions">
                <Button variant="outline">Download</Button>
                <Button variant="outline">Send</Button>
                <Button variant="outline">Void</Button>
            </ButtonGroup>`,
    }),
});
export default meta;

/** Buttons side by side. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        const group = canvas.getByRole('group', { name: 'Invoice actions' });
        await expect(group.querySelectorAll('button')).toHaveLength(3);
    },
});

/** Stacked. */
export const Vertical = meta.story({ args: { orientation: 'vertical' } });

/** With a label in front and a separator between solid buttons. */
export const WithTextAndSeparator = meta.story({
    render: (args) => ({
        components,
        setup: () => ({ args }),
        template: `
            <ButtonGroup v-bind="args" aria-label="Pages">
                <ButtonGroupText>Page 2 of 8</ButtonGroupText>
                <Button variant="secondary">Previous</Button>
                <ButtonGroupSeparator />
                <Button variant="secondary">Next</Button>
            </ButtonGroup>`,
    }),
});
