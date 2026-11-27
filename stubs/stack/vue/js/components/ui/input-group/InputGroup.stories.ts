import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import IconSearch from '~icons/lucide/search';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
    InputGroupText,
    InputGroupTextarea,
} from '.';

const components = {
    IconSearch,
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
    InputGroupText,
    InputGroupTextarea,
};

/**
 * An input with something attached inside its edge: an icon, a prefix or suffix, a
 * button. The group draws one border and one focus ring for all of it; clicking an addon
 * focuses the input.
 *
 * UI/CopyField is one, ready-made.
 */
const meta = preview.meta({
    title: 'UI/InputGroup',
    component: InputGroup,
    decorators: [() => ({ template: '<div class="max-w-sm"><story /></div>' })],
    render: () => ({
        components,
        template: `
            <InputGroup>
                <InputGroupInput aria-label="Search" placeholder="Search projects" />
                <InputGroupAddon><IconSearch /></InputGroupAddon>
            </InputGroup>`,
    }),
});
export default meta;

/** An icon before the input. Clicking the icon focuses the input. */
export const WithIcon = meta.story({
    play: async ({ canvas, userEvent, canvasElement }) => {
        await userEvent.click(
            canvasElement.querySelector('[data-slot="input-group-addon"]')!,
        );

        await expect(
            canvas.getByRole('textbox', { name: 'Search' }),
        ).toHaveFocus();
    },
});

/** Fixed text on either side: a URL's start and end. */
export const PrefixAndSuffix = meta.story({
    render: () => ({
        components,
        template: `
            <InputGroup>
                <InputGroupAddon><InputGroupText>https://</InputGroupText></InputGroupAddon>
                <InputGroupInput aria-label="Workspace URL" placeholder="acme" class="pl-0.5" />
                <InputGroupAddon align="inline-end"><InputGroupText>.saucebase.app</InputGroupText></InputGroupAddon>
            </InputGroup>`,
    }),
});

/** A button inside the edge. */
export const WithButton = meta.story({
    render: () => ({
        components,
        template: `
            <InputGroup>
                <InputGroupInput aria-label="Coupon code" placeholder="Coupon code" />
                <InputGroupAddon align="inline-end">
                    <InputGroupButton variant="secondary">Apply</InputGroupButton>
                </InputGroupAddon>
            </InputGroup>`,
    }),
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('button', { name: 'Apply' }),
        ).toBeVisible();
    },
});

/** A textarea with a footer: a character count and a send button. */
export const WithTextarea = meta.story({
    render: () => ({
        components,
        template: `
            <InputGroup>
                <InputGroupTextarea aria-label="Comment" placeholder="Leave a comment" />
                <InputGroupAddon align="block-end">
                    <InputGroupText>0/280</InputGroupText>
                    <InputGroupButton variant="default" size="sm" class="ml-auto">Send</InputGroupButton>
                </InputGroupAddon>
            </InputGroup>`,
    }),
});

/** Rejected by the server: the whole group turns red. */
export const Invalid = meta.story({
    render: () => ({
        components,
        template: `
            <InputGroup>
                <InputGroupAddon><InputGroupText>https://</InputGroupText></InputGroupAddon>
                <InputGroupInput aria-label="Workspace URL" default-value="acme" aria-invalid="true" class="pl-0.5" />
            </InputGroup>`,
    }),
});
