import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { useT } from '@saucebase/react/i18n';
import { TriangleAlert } from 'lucide-react';
import type { ComponentProps } from 'react';

export default function DangerZone({
    title,
    showHeader = true,
    children,
    className,
    ...props
}: ComponentProps<typeof Card> & { showHeader?: boolean }) {
    const t = useT();

    return (
        <Card
            className={cn('border-destructive/50', className)}
            data-testid="danger-zone"
            {...props}
        >
            {showHeader && (
                <CardHeader>
                    <CardTitle className="text-destructive flex items-center gap-2">
                        <TriangleAlert className="size-4" />
                        {title ?? t('Danger zone')}
                    </CardTitle>
                </CardHeader>
            )}
            <CardContent className="space-y-6">{children}</CardContent>
        </Card>
    );
}
