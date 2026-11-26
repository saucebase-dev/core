import preview from '@/.storybook/preview';
import { Badge } from '@/components/ui/badge';
import { expect } from 'storybook/test';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableEmpty,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '.';

const components = {
    Badge,
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableEmpty,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
};

const invoices = [
    {
        number: 'INV-0042',
        date: '1 Nov 2026',
        status: 'Paid',
        amount: '$49.00',
    },
    {
        number: 'INV-0041',
        date: '1 Oct 2026',
        status: 'Paid',
        amount: '$49.00',
    },
    {
        number: 'INV-0040',
        date: '1 Sep 2026',
        status: 'Overdue',
        amount: '$49.00',
    },
];

/**
 * Rows of records with the same fields: invoices, members, API tokens. Scrolls sideways
 * on a narrow screen. Sorting and paging are the page's job.
 */
const meta = preview.meta({
    title: 'UI/Table',
    component: Table,
    render: () => ({
        components,
        setup: () => ({ invoices }),
        template: `
            <Table>
                <TableCaption>Your last three invoices.</TableCaption>
                <TableHeader>
                    <TableRow>
                        <TableHead>Invoice</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Amount</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="invoice in invoices" :key="invoice.number">
                        <TableCell class="font-medium">{{ invoice.number }}</TableCell>
                        <TableCell>{{ invoice.date }}</TableCell>
                        <TableCell>
                            <Badge :variant="invoice.status === 'Paid' ? 'secondary' : 'destructive'">{{ invoice.status }}</Badge>
                        </TableCell>
                        <TableCell class="text-right">{{ invoice.amount }}</TableCell>
                    </TableRow>
                </TableBody>
                <TableFooter>
                    <TableRow>
                        <TableCell colspan="3">Total</TableCell>
                        <TableCell class="text-right">$147.00</TableCell>
                    </TableRow>
                </TableFooter>
            </Table>`,
    }),
});
export default meta;

/** Header, rows, a total and a caption. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.getAllByRole('row')).toHaveLength(5);
        await expect(
            canvas.getByRole('columnheader', { name: 'Amount' }),
        ).toBeVisible();
    },
});

/** No rows yet: one message across every column. */
export const EmptyState = meta.story({
    name: 'Empty',
    render: () => ({
        components,
        template: `
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Invoice</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Amount</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty :colspan="3">No invoices yet.</TableEmpty>
                </TableBody>
            </Table>`,
    }),
    play: async ({ canvas }) => {
        await expect(canvas.getByText('No invoices yet.')).toBeVisible();
    },
});
