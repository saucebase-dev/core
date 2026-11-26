import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { Check, ChevronsUpDown } from '@lucide/vue';
import { expect, screen, waitFor } from 'storybook/test';
import { ref } from 'vue';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
} from '.';

const timezones = [
    { value: 'america-new_york', label: 'New York (UTC−5)' },
    { value: 'america-sao_paulo', label: 'São Paulo (UTC−3)' },
    { value: 'europe-london', label: 'London (UTC+0)' },
    { value: 'europe-berlin', label: 'Berlin (UTC+1)' },
    { value: 'asia-tokyo', label: 'Tokyo (UTC+9)' },
];

const components = {
    Button,
    Check,
    ChevronsUpDown,
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
};

/**
 * A select the user can type into, to filter a long list: a timezone, a country, a
 * member. For a few options without search, use Select.
 */
const meta = preview.meta({
    title: 'UI/Combobox',
    component: Combobox,
    render: (args) => ({
        components,
        setup: () => ({
            args,
            timezones,
            value: ref<(typeof timezones)[number] | undefined>(),
        }),
        template: `
            <Combobox v-bind="args" v-model="value" by="value">
                <ComboboxAnchor as-child>
                    <ComboboxTrigger as-child>
                        <Button variant="outline" class="w-[220px] justify-between">
                            {{ value?.label ?? 'Select timezone' }}
                            <ChevronsUpDown class="opacity-50" />
                        </Button>
                    </ComboboxTrigger>
                </ComboboxAnchor>
                <ComboboxList class="w-[220px]">
                    <ComboboxInput placeholder="Search timezone…" />
                    <ComboboxEmpty>No timezone found.</ComboboxEmpty>
                    <ComboboxGroup heading="Timezones">
                        <ComboboxItem v-for="zone in timezones" :key="zone.value" :value="zone">
                            {{ zone.label }}
                            <ComboboxItemIndicator><Check /></ComboboxItemIndicator>
                        </ComboboxItem>
                    </ComboboxGroup>
                </ComboboxList>
            </Combobox>`,
    }),
});
export default meta;

/** Typing filters the list; picking one shows it on the trigger. */
export const Default = meta.story({
    play: async ({ canvas, userEvent }) => {
        // reka-ui names the trigger "Show popup", whatever it shows.
        await userEvent.click(canvas.getByRole('button'));
        await userEvent.type(
            await screen.findByPlaceholderText('Search timezone…'),
            'tok',
        );
        const tokyo = await screen.findByRole('option', { name: /Tokyo/ });
        await waitFor(() => expect(tokyo).toBeVisible());
        await expect(
            screen.queryByRole('option', { name: /Berlin/ }),
        ).toBeNull();

        await userEvent.click(tokyo);

        await waitFor(() =>
            expect(canvas.getByRole('button')).toHaveTextContent('Tokyo'),
        );
    },
});

/** Nothing matches the search. */
export const NoResults = meta.story({
    play: async ({ canvas, userEvent }) => {
        // reka-ui names the trigger "Show popup", whatever it shows.
        await userEvent.click(canvas.getByRole('button'));
        await userEvent.type(
            await screen.findByPlaceholderText('Search timezone…'),
            'mars',
        );

        await expect(
            await screen.findByText('No timezone found.'),
        ).toBeVisible();
    },
});
