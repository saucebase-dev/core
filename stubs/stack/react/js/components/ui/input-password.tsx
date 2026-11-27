import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { useT } from '@saucebase/react/i18n';
import { EyeIcon, EyeOffIcon } from 'lucide-react';
import { useState, type ComponentProps } from 'react';

/** A password input with a button that shows or hides what was typed. */
export function InputPassword({
    className,
    ...props
}: Omit<ComponentProps<'input'>, 'type'>) {
    const t = useT();
    const [visible, setVisible] = useState(false);
    const label = visible ? t('Hide password') : t('Show password');
    const testId = (props as { 'data-testid'?: string })['data-testid'];

    return (
        <div className="relative">
            <Input
                {...props}
                type={visible ? 'text' : 'password'}
                className={cn('pr-10', className)}
            />
            <Button
                type="button"
                variant="ghost"
                size="icon"
                className="absolute inset-y-0 right-0 h-full px-3 py-2 hover:bg-transparent"
                title={label}
                aria-label={label}
                data-testid={testId ? `${testId}-toggle` : 'password-toggle'}
                onClick={() => setVisible(!visible)}
            >
                {visible ? (
                    <EyeOffIcon className="text-muted-foreground size-4" />
                ) : (
                    <EyeIcon className="text-muted-foreground size-4" />
                )}
            </Button>
        </div>
    );
}
