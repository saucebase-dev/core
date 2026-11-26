import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import {
    Breadcrumb,
    BreadcrumbEllipsis,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '.';

const components = {
    Breadcrumb,
    BreadcrumbEllipsis,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
};

/**
 * The way back up from a nested page: each level a link, the current page plain text.
 * Layout/AppLayout builds one from the server's `breadcrumbs`; compose it yourself
 * elsewhere.
 */
const meta = preview.meta({
    title: 'UI/Breadcrumb',
    component: Breadcrumb,
    render: () => ({
        components,
        template: `
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem><BreadcrumbLink href="#">Settings</BreadcrumbLink></BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem><BreadcrumbLink href="#">Workspace</BreadcrumbLink></BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem><BreadcrumbPage>Members</BreadcrumbPage></BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>`,
    }),
});
export default meta;

/** Links up to the current page. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('link', { name: 'Settings' }),
        ).toHaveAttribute('href', '#');
        await expect(canvas.getByText('Members')).toHaveAttribute(
            'aria-current',
            'page',
        );
    },
});

/** A deep trail, with the middle folded away. */
export const Collapsed = meta.story({
    render: () => ({
        components,
        template: `
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem><BreadcrumbLink href="#">Projects</BreadcrumbLink></BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem><BreadcrumbEllipsis /></BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem><BreadcrumbLink href="#">Releases</BreadcrumbLink></BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem><BreadcrumbPage>v3.1.1</BreadcrumbPage></BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>`,
    }),
});
