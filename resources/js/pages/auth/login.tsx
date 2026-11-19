import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import {
    AuthField,
    AuthNotice,
    authButton,
    authInput,
} from '@/components/auth/auth-parts';
import { PasskeyButton } from '@/components/auth/passkey-button';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    const [passkeyError, setPasskeyError] = useState<string | null>(null);

    return (
        <>
            <Head title="Log in" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="grid gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        {status ? <AuthNotice>{status}</AuthNotice> : null}

                        <AuthField id="email" label="Email address" error={errors.email}>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="username webauthn"
                                placeholder="email@example.com"
                                className={authInput}
                            />
                        </AuthField>

                        <AuthField
                            id="password"
                            label="Password"
                            error={errors.password}
                            aside={
                                canResetPassword ? (
                                    <TextLink
                                        href={request()}
                                        className="text-sm font-semibold text-brand-deep"
                                        tabIndex={5}
                                    >
                                        Forgot password?
                                    </TextLink>
                                ) : undefined
                            }
                        >
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                placeholder="Your password"
                                className="h-11 rounded-xl border-line bg-white"
                            />
                        </AuthField>

                        <div className="flex items-center gap-2.5">
                            <Checkbox id="remember" name="remember" tabIndex={3} />
                            <Label htmlFor="remember" className="text-sm text-muted-ink">
                                Keep me signed in
                            </Label>
                        </div>

                        <div className="flex items-center gap-2">
                            <Button
                                type="submit"
                                className={`${authButton} flex-1`}
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Log in
                            </Button>
                            <PasskeyButton onError={setPasskeyError} />
                        </div>

                        {passkeyError ? (
                            <AuthNotice tone="error">{passkeyError}</AuthNotice>
                        ) : null}

                        <p className="text-center text-sm text-muted-ink">
                            New here?{' '}
                            <TextLink
                                href={register()}
                                className="font-semibold text-brand-deep"
                                tabIndex={6}
                            >
                                Create an account
                            </TextLink>
                        </p>
                    </>
                )}
            </Form>
        </>
    );
}

Login.layout = {
    title: 'Welcome back',
    description:
        'Log in with your email and password, or tap the fingerprint button to use a passkey.',
};
