import { cn } from '@/lib/utils';
import type { ComponentProps, ReactNode } from 'react';

type EmptyStateProps = ComponentProps<'div'> & {
    title: string;
    description?: string;
    icon?: ReactNode;
};

export default function EmptyState({
    title,
    description,
    icon,
    children,
    className,
    ...props
}: EmptyStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center gap-2 rounded-lg border border-dashed px-6 py-10 text-center',
                className,
            )}
            {...props}
        >
            {icon && (
                <div className="bg-muted text-muted-foreground flex size-10 items-center justify-center rounded-full [&>svg]:size-5">
                    {icon}
                </div>
            )}
            <p className="font-medium">{title}</p>
            {description && (
                <p className="text-muted-foreground text-sm">{description}</p>
            )}
            {children && <div className="mt-2">{children}</div>}
        </div>
    );
}
