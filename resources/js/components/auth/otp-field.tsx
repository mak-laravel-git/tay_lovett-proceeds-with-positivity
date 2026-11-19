import { REGEXP_ONLY_DIGITS } from 'input-otp';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import { cn } from '@/lib/utils';

/**
 * Six boxes for a numeric code, styled for the auth screens. Calls `onComplete` once all digits are typed or pasted.
 */
export function OtpField({
    value,
    onChange,
    onComplete,
    disabled,
    invalid,
    autoFocus = true,
    name = 'code',
}: {
    value: string;
    onChange: (value: string) => void;
    onComplete?: (value: string) => void;
    disabled?: boolean;
    invalid?: boolean;
    autoFocus?: boolean;
    name?: string;
}) {
    return (
        <InputOTP
            name={name}
            maxLength={OTP_MAX_LENGTH}
            value={value}
            onChange={onChange}
            onComplete={onComplete}
            disabled={disabled}
            pattern={REGEXP_ONLY_DIGITS}
            inputMode="numeric"
            autoComplete="one-time-code"
            autoFocus={autoFocus}
            containerClassName="justify-center"
            aria-invalid={invalid}
        >
            <InputOTPGroup className="gap-2 max-[360px]:gap-1.5">
                {Array.from({ length: OTP_MAX_LENGTH }, (_, index) => (
                    <InputOTPSlot
                        key={index}
                        index={index}
                        className={cn(
                            'size-12 rounded-xl border border-line bg-white text-xl font-extrabold text-ink shadow-none max-[360px]:size-10',
                            'first:rounded-xl first:border-l last:rounded-xl',
                            invalid && 'border-brand',
                        )}
                    />
                ))}
            </InputOTPGroup>
        </InputOTP>
    );
}
