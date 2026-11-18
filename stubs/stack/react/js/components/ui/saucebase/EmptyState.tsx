import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { cn } from '@/lib/utils';
import type { ComponentProps, ReactNode } from 'react';

type EmptyStateProps = ComponentProps<'div'> & {
    title: string;
    description?: string;
    icon?: ReactNode;
};

/**
 * The common empty state: an icon, a title, a line and an action. `className` lands on the
 * container. Other sizes or an illustration: compose `@/components/ui/empty` instead.
 */
export default function EmptyState({
    title,
    description,
    icon,
    children,
    className,
    ...props
}: EmptyStateProps) {
    return (
        <Empty
            className={cn(
                'flex-none gap-2 border px-6 py-10 md:px-6 md:py-10',
                className,
            )}
            {...props}
        >
            <EmptyHeader className="max-w-none">
                {icon && (
                    <EmptyMedia
                        variant="icon"
                        className="text-muted-foreground mb-0 rounded-full [&_svg:not([class*='size-'])]:size-5"
                    >
                        {icon}
                    </EmptyMedia>
                )}
                <EmptyTitle className="text-base tracking-normal">
                    {title}
                </EmptyTitle>
                {description && (
                    <EmptyDescription>{description}</EmptyDescription>
                )}
            </EmptyHeader>
            {children && (
                <EmptyContent className="mt-2">{children}</EmptyContent>
            )}
        </Empty>
    );
}
