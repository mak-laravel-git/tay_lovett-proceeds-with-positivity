import { Form, Head } from '@inertiajs/react';
import { Fingerprint, KeyRound, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import { AuthField, authButton, authInput } from '@/components/auth/auth-parts';
import { OtpField } from '@/components/auth/otp-field';
import { PasskeyButton } from '@/components/auth/passkey-button';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { store } from '@/routes/password/confirm';

type Method = 'passkey' | 'authenticator' | 'password';

export default function ConfirmPassword({
    hasPasskeys,
    hasTwoFactor,
}: {
    hasPasskeys: boolean;
    hasTwoFactor: boolean;
}) {
    const [method, setMethod] = useState<Method>(
        hasPasskeys ? 'passkey' : 'password',
    );
    const [code, setCode] = useState('');
    const [useRecovery, setUseRecovery] = useState(false);

    const methods: Array<{
        value: Method;
        label: string;
        icon: typeof KeyRound;
        available: boolean;
        missing: string;
    }> = [
        {
            value: 'passkey',
            label: 'Passkey',
            icon: Fingerprint,
            available: hasPasskeys,
            missing: 'You have not added a passkey yet.',
        },
        {
            value: 'authenticator',
            label: 'Authenticator',
            icon: ShieldCheck,
            available: hasTwoFactor,
            missing: 'Two-factor authentication is not turned on.',
        },
        {
            value: 'password',
            label: 'Password',
            icon: KeyRound,
            available: true,
            missing: '',
        },
    ];

    return (
        <>
            <Head title="Confirm it is you" />

            <div className="grid gap-5">
                <div
                    role="tablist"
                    aria-label="Confirmation method"
                    className="grid grid-cols-3 gap-1.5 rounded-xl bg-sky-pale p-1.5"
                >
                    {methods.map(({ value, label, icon: Icon, available, missing }) => {
                        const button = (
                            <button
                                key={value}
                                type="button"
                                role="tab"
                                aria-selected={method === value}
                                disabled={!available}
                                onClick={() => setMethod(value)}
                                className={cn(
                                    'flex flex-col items-center gap-1 rounded-lg px-1 py-2 text-xs font-extrabold transition-colors',
                                    method === value
                                        ? 'bg-brand text-white'
                                        : 'text-ink hover:bg-white/70',
                                    !available &&
                                        'cursor-not-allowed text-muted-ink/60 hover:bg-transparent',
                                )}
                            >
                                <Icon className="size-4" aria-hidden />
                                {label}
                            </button>
                        );

                        return available ? (
                            button
                        ) : (
                            <Tooltip key={value}>
                                <TooltipTrigger asChild>
                                    <span tabIndex={0} className="block">
                                        {button}
                                    </span>
                                </TooltipTrigger>
                                <TooltipContent>{missing}</TooltipContent>
                            </Tooltip>
                        );
                    })}
                </div>

                {method === 'passkey' && (
                    <div className="grid gap-3">
                        <p className="text-sm text-muted-ink">
                            Use the fingerprint, face or screen lock on this
                            device.
                        </p>
                        <PasskeyButton
                            variant="wide"
                            routes={{
                                options: confirmOptions(),
                                submit: confirmStore(),
                            }}
                            label="Confirm with passkey"
                        />
                    </div>
                )}

                {method === 'authenticator' && (
                    <Form
                        action="/confirm-access/two-factor"
                        method="post"
                        className="grid gap-4"
                        resetOnError
                    >
                        {({ processing, errors, submit, clearErrors }) => (
                            <>
                                {useRecovery ? (
                                    <AuthField
                                        id="recovery_code"
                                        label="Recovery code"
                                        error={errors.recovery_code}
                                    >
                                        <Input
                                            id="recovery_code"
                                            name="recovery_code"
                                            autoFocus
                                            required
                                            placeholder="xxxxx-xxxxx"
                                            className={authInput}
                                        />
                                    </AuthField>
                                ) : (
                                    <div className="grid gap-3">
                                        <p className="text-sm text-muted-ink">
                                            Enter the 6-digit code from your
                                            authenticator app.
                                        </p>
                                        <OtpField
                                            value={code}
                                            onChange={setCode}
                                            onComplete={() =>
                                                window.setTimeout(submit, 0)
                                            }
                                            disabled={processing}
                                            invalid={Boolean(errors.code)}
                                        />
                                        {errors.code ? (
                                            <p
                                                role="alert"
                                                className="text-center text-sm font-medium text-brand-deep"
                                            >
                                                {errors.code}
                                            </p>
                                        ) : null}
                                    </div>
                                )}
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className={authButton}
                                >
                                    {processing && <Spinner />}
                                    Confirm
                                </Button>
                                <button
                                    type="button"
                                    className="text-sm font-semibold text-brand-deep underline underline-offset-4"
                                    onClick={() => {
                                        setUseRecovery(!useRecovery);
                                        setCode('');
                                        clearErrors();
                                    }}
                                >
                                    {useRecovery
                                        ? 'Use an authenticator code instead'
                                        : 'Use a recovery code instead'}
                                </button>
                            </>
                        )}
                    </Form>
                )}

                {method === 'password' && (
                    <Form
                        {...store.form()}
                        resetOnSuccess={['password']}
                        className="grid gap-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <AuthField
                                    id="password"
                                    label="Password"
                                    error={errors.password}
                                >
                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        placeholder="Your password"
                                        autoComplete="current-password"
                                        autoFocus
                                        className="h-11 rounded-xl border-line bg-white"
                                    />
                                </AuthField>
                                <Button
                                    disabled={processing}
                                    className={authButton}
                                    data-test="confirm-password-button"
                                >
                                    {processing && <Spinner />}
                                    Confirm password
                                </Button>
                            </>
                        )}
                    </Form>
                )}
            </div>
        </>
    );
}

ConfirmPassword.layout = {
    title: 'Confirm it is you',
    description:
        'This is a protected area. Choose a way to confirm before you continue.',
};
