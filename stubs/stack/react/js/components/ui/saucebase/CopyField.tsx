import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from '@/components/ui/input-group';
import { useT } from '@saucebase/react/i18n';
import { Check, Copy } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

interface CopyFieldProps {
    value: string;
    testId: string;
    className?: string;
}

export function CopyField({ value, testId, className }: CopyFieldProps) {
    const t = useT();

    // The tick is feedback the user can see without reading the toast, so it clears
    // itself on a timer rather than waiting for something else to re-render.
    const [copied, setCopied] = useState(false);
    const copyTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(
        () => () => {
            if (copyTimerRef.current) clearTimeout(copyTimerRef.current);
        },
        [],
    );

    const copy = () => {
        navigator.clipboard
            .writeText(value)
            .then(() => {
                toast.success(t('Copied to clipboard'));
                if (copyTimerRef.current) clearTimeout(copyTimerRef.current);
                setCopied(true);
                copyTimerRef.current = setTimeout(() => setCopied(false), 2000);
            })
            .catch(() => toast.error(t('Failed to copy')));
    };

    return (
        <InputGroup className={className}>
            <InputGroupInput
                readOnly
                value={value}
                data-testid={testId}
                onFocus={(e) => e.target.select()}
            />
            <InputGroupAddon align="inline-end">
                <InputGroupButton
                    type="button"
                    size="icon-xs"
                    aria-label={t('Copy')}
                    data-testid={`copy-${testId}`}
                    onClick={copy}
                >
                    {copied ? <Check className="text-emerald-500" /> : <Copy />}
                </InputGroupButton>
            </InputGroupAddon>
        </InputGroup>
    );
}
