import { Head, useForm } from '@inertiajs/react';
import { LockKeyhole } from 'lucide-react';
import { useRef } from 'react';
import {
    AccountSection,
    FieldRow,
    accountButton,
    accountInput,
} from '@/components/account/account-section';
import type { Props as ManagePasskeysProps } from '@/components/manage-passkeys';
import ManagePasskeys from '@/components/manage-passkeys';
import type { Props as ManageTwoFactorProps } from '@/components/manage-two-factor';
import ManageTwoFactor from '@/components/manage-two-factor';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

// oxfmt-ignore
type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

export default function Security(props: Props) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);
    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    return (
        <>
            <Head title="Security" />

            <AccountSection
                title="Password"
                description="Use a long, unique password you do not use anywhere else."
                icon={<LockKeyhole className="size-5" />}
            >
                <form
                    className="grid max-w-md gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.put('/settings/password', {
                            preserveScroll: true,
                            onSuccess: () => form.reset(),
                            onError: (errors) => {
                                form.reset('password', 'password_confirmation', 'current_password');

                                if (errors.password) {
                                    passwordInput.current?.focus();
                                } else if (errors.current_password) {
                                    currentPasswordInput.current?.focus();
                                }
                            },
                        });
                    }}
                >
                    <FieldRow
                        label="Current password"
                        htmlFor="current_password"
                        error={form.errors.current_password}
                    >
                        <PasswordInput
                            id="current_password"
                            ref={currentPasswordInput}
                            value={form.data.current_password}
                            onChange={(event) =>
                                form.setData('current_password', event.target.value)
                            }
                            autoComplete="current-password"
                            className={accountInput}
                        />
                    </FieldRow>
                    <FieldRow label="New password" htmlFor="password" error={form.errors.password}>
                        <PasswordInput
                            id="password"
                            ref={passwordInput}
                            value={form.data.password}
                            onChange={(event) => form.setData('password', event.target.value)}
                            autoComplete="new-password"
                            passwordrules={props.passwordRules}
                            className={accountInput}
                        />
                    </FieldRow>
                    <FieldRow
                        label="Confirm new password"
                        htmlFor="password_confirmation"
                        error={form.errors.password_confirmation}
                    >
                        <PasswordInput
                            id="password_confirmation"
                            value={form.data.password_confirmation}
                            onChange={(event) =>
                                form.setData('password_confirmation', event.target.value)
                            }
                            autoComplete="new-password"
                            passwordrules={props.passwordRules}
                            className={accountInput}
                        />
                    </FieldRow>
                    <div>
                        <Button
                            type="submit"
                            disabled={form.processing}
                            className={accountButton}
                            data-test="update-password-button"
                        >
                            {form.processing && <Spinner />}
                            Update password
                        </Button>
                    </div>
                </form>
            </AccountSection>

            <ManageTwoFactor
                canManageTwoFactor={props.canManageTwoFactor}
                requiresConfirmation={props.requiresConfirmation}
                twoFactorEnabled={props.twoFactorEnabled}
            />

            <ManagePasskeys
                canManagePasskeys={props.canManagePasskeys}
                passkeys={props.passkeys}
            />
        </>
    );
}
