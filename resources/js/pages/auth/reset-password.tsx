import { Form, Head } from '@inertiajs/react';
import {
    AuthField,
    authButton,
} from '@/components/auth/auth-parts';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

export default function ResetPassword({
    passwordRules,
}: {
    passwordRules: string;
}) {
    return (
        <>
            <Head title="Choose a new password" />

            <Form
                action="/reset-password"
                method="post"
                resetOnSuccess={['password', 'password_confirmation']}
                className="grid gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        <AuthField
                            id="password"
                            label="New password"
                            error={errors.password}
                        >
                            <PasswordInput
                                id="password"
                                name="password"
                                autoComplete="new-password"
                                autoFocus
                                required
                                passwordrules={passwordRules}
                                placeholder="New password"
                                className="h-11 rounded-xl border-line bg-white"
                            />
                        </AuthField>

                        <AuthField
                            id="password_confirmation"
                            label="Confirm new password"
                            error={errors.password_confirmation}
                        >
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                autoComplete="new-password"
                                required
                                placeholder="Repeat the password"
                                className="h-11 rounded-xl border-line bg-white"
                            />
                        </AuthField>

                        <Button type="submit" disabled={processing} className={authButton}>
                            {processing && <Spinner />}
                            Reset password
                        </Button>
                        <p className="text-center text-xs text-muted-ink">
                            You will be signed out everywhere and asked to log in
                            again.
                        </p>
                    </>
                )}
            </Form>
        </>
    );
}

ResetPassword.layout = {
    title: 'Choose a new password',
    description: 'Your code was accepted. Set a new password for your account.',
};
