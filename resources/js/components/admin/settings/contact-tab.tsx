import { useForm } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import {
    SettingField,
    SettingsForm,
    textOf,
    type SettingsGroup,
} from '@/components/admin/settings/shared';

type ContactKey =
    | 'support_email'
    | 'phone'
    | 'address'
    | 'facebook_url'
    | 'x_url'
    | 'instagram_url'
    | 'youtube_url';

const fields: Array<{
    key: ContactKey;
    label: string;
    type: string;
    placeholder: string;
    help?: string;
}> = [
    {
        key: 'support_email',
        label: 'Support email',
        type: 'email',
        placeholder: 'help@example.com',
        help: 'Shown on public pages so people can reach you.',
    },
    { key: 'phone', label: 'Phone', type: 'tel', placeholder: '+1 555 0100' },
    {
        key: 'address',
        label: 'Address',
        type: 'text',
        placeholder: 'Street, city, country',
    },
    {
        key: 'facebook_url',
        label: 'Facebook',
        type: 'url',
        placeholder: 'https://facebook.com/yourpage',
    },
    {
        key: 'x_url',
        label: 'X (Twitter)',
        type: 'url',
        placeholder: 'https://x.com/yourhandle',
    },
    {
        key: 'instagram_url',
        label: 'Instagram',
        type: 'url',
        placeholder: 'https://instagram.com/yourhandle',
    },
    {
        key: 'youtube_url',
        label: 'YouTube',
        type: 'url',
        placeholder: 'https://youtube.com/@yourchannel',
    },
];

export function ContactTab({ group }: { group: SettingsGroup }) {
    const form = useForm<Record<ContactKey, string>>({
        support_email: textOf(group, 'support_email'),
        phone: textOf(group, 'phone'),
        address: textOf(group, 'address'),
        facebook_url: textOf(group, 'facebook_url'),
        x_url: textOf(group, 'x_url'),
        instagram_url: textOf(group, 'instagram_url'),
        youtube_url: textOf(group, 'youtube_url'),
    });

    return (
        <SettingsForm
            group="contact"
            title="Contact & Social"
            description="How people reach you. Empty fields are hidden. Links must start with https://."
            processing={form.processing}
            onSubmit={() =>
                form.post('/admin/settings/contact', { preserveScroll: true })
            }
        >
            <div className="grid gap-5 sm:grid-cols-2">
                {fields.map((field) => (
                    <SettingField
                        key={field.key}
                        id={field.key}
                        label={field.label}
                        help={field.help}
                        source={group[field.key].source}
                        error={form.errors[field.key]}
                    >
                        <Input
                            id={field.key}
                            type={field.type}
                            value={form.data[field.key]}
                            onChange={(event) =>
                                form.setData(field.key, event.target.value)
                            }
                            placeholder={field.placeholder}
                            className="bg-white"
                        />
                    </SettingField>
                ))}
            </div>
        </SettingsForm>
    );
}
