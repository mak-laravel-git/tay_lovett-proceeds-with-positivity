import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { AuthField, authButton, authInput } from '@/components/auth/auth-parts';
import { OtpField } from '@/components/auth/otp-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/two-factor/login';

export default function TwoFactorChallenge() {
    const [showRecoveryInput, setShowRecoveryInput] = useState<boolean>(false);
    const [code, setCode] = useState<string>('');

    const authConfigContent = useMemo<{
        title: string;
        description: string;
        toggleText: string;
    }>(() => {
        if (showRecoveryInput) {
            return {
                title: 'Recovery code',
                description:
                    'Enter one of your emergency recovery codes to sign in.',
                toggleText: 'Use an authenticator code instead',
            };
        }

        return {
            title: 'Authentication code',
            description:
                'Enter the 6-digit code from your authenticator app.',
            toggleText: 'Use a recovery code instead',
        };
    }, [showRecoveryInput]);

    setLayoutProps({
        title: authConfigContent.title,
        description: authConfigContent.description,
    });

    return (
        <>
            <Head title="Two-factor authentication" />

            <Form
                {...store.form()}
                className="grid gap-5"
                resetOnError
                resetOnSuccess={!showRecoveryInput}
            >
                {({ errors, processing, clearErrors, submit }) => (
                    <>
                        {showRecoveryInput ? (
                            <AuthField
                                id="recovery_code"
                                label="Recovery code"
                                error={errors.recovery_code}
                            >
                                <Input
                                    id="recovery_code"
                                    name="recovery_code"
                                    type="text"
                                    placeholder="xxxxx-xxxxx"
                                    autoFocus
                                    required
                                    className={authInput}
                                />
                            </AuthField>
                        ) : (
                            <div className="grid gap-3">
                                <OtpField
                                    value={code}
                                    onChange={setCode}
                                    onComplete={() => window.setTimeout(submit, 0)}
                                    disabled={processing}
                                    invalid={Boolean(errors.code)}
                                />
                                {errors.code ? (
                                    <p role="alert" className="text-center text-sm font-medium text-brand-deep">
                                        {errors.code}
                                    </p>
                                ) : null}
                            </div>
                        )}

                        <Button type="submit" disabled={processing} className={authButton}>
                            {processing && <Spinner />}
                            Continue
                        </Button>

                        <button
                            type="button"
                            className="text-sm font-semibold text-brand-deep underline underline-offset-4"
                            onClick={() => {
                                setShowRecoveryInput(!showRecoveryInput);
                                clearErrors();
                                setCode('');
                            }}
                        >
                            {authConfigContent.toggleText}
                        </button>
                    </>
                )}
            </Form>
        </>
    );
}
