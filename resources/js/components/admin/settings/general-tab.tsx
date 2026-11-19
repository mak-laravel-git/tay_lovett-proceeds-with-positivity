import { useForm } from '@inertiajs/react';
import FileUpload from '@/components/file-upload';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    SettingField,
    SettingsForm,
    textOf,
    type SettingsGroup,
} from '@/components/admin/settings/shared';

export function GeneralTab({
    group,
    logoUrl,
    faviconUrl,
}: {
    group: SettingsGroup;
    logoUrl: string | null;
    faviconUrl: string | null;
}) {
    const form = useForm<{
        site_name: string;
        tagline: string;
        meta_description: string;
        footer_text: string;
        logo: File | null;
        favicon: File | null;
        remove_logo: boolean;
        remove_favicon: boolean;
    }>({
        site_name: textOf(group, 'site_name'),
        tagline: textOf(group, 'tagline'),
        meta_description: textOf(group, 'meta_description'),
        footer_text: textOf(group, 'footer_text'),
        logo: null,
        favicon: null,
        remove_logo: false,
        remove_favicon: false,
    });

    const single = (file: File | File[] | null): File | null =>
        Array.isArray(file) ? (file[0] ?? null) : file;

    return (
        <SettingsForm
            group="general"
            title="General"
            description="Name, branding and search text shown across the site."
            processing={form.processing}
            onSubmit={() =>
                form.post('/admin/settings/general', {
                    forceFormData: true,
                    preserveScroll: true,
                })
            }
        >
            <div className="grid gap-5 sm:grid-cols-2">
                <SettingField
                    id="site_name"
                    label="Site name"
                    help="Shown in the browser tab, emails, sidebar and login."
                    source={group.site_name.source}
                    error={form.errors.site_name}
                >
                    <Input
                        id="site_name"
                        value={form.data.site_name}
                        onChange={(event) =>
                            form.setData('site_name', event.target.value)
                        }
                        className="bg-white"
                    />
                </SettingField>
                <SettingField
                    id="tagline"
                    label="Tagline"
                    help="Short line under the logo."
                    source={group.tagline.source}
                    error={form.errors.tagline}
                >
                    <Input
                        id="tagline"
                        value={form.data.tagline}
                        onChange={(event) =>
                            form.setData('tagline', event.target.value)
                        }
                        className="bg-white"
                    />
                </SettingField>
            </div>

            <SettingField
                id="meta_description"
                label="Meta description"
                help="Used by search engines and link previews. Up to 300 characters."
                source={group.meta_description.source}
                error={form.errors.meta_description}
            >
                <Textarea
                    id="meta_description"
                    rows={3}
                    value={form.data.meta_description}
                    onChange={(event) =>
                        form.setData('meta_description', event.target.value)
                    }
                    className="bg-white"
                />
            </SettingField>

            <SettingField
                id="footer_text"
                label="Footer text"
                help="Small print under public pages, for example a copyright line."
                source={group.footer_text.source}
                error={form.errors.footer_text}
            >
                <Input
                    id="footer_text"
                    value={form.data.footer_text}
                    onChange={(event) =>
                        form.setData('footer_text', event.target.value)
                    }
                    className="bg-white"
                />
            </SettingField>

            <div className="grid gap-5 sm:grid-cols-2">
                <SettingField
                    id="logo"
                    label="Logo"
                    help="PNG, JPG or WebP up to 2 MB. Square images work best."
                    source={group.logo_path.source}
                    error={form.errors.logo}
                >
                    <FileUpload
                        value={form.data.logo}
                        onChange={(file) => {
                            form.setData((data) => ({
                                ...data,
                                logo: single(file),
                                remove_logo: false,
                            }));
                        }}
                        existingFiles={
                            logoUrl && !form.data.remove_logo && !form.data.logo
                                ? [
                                      {
                                          id: 'logo',
                                          path: 'logo',
                                          name: 'Current logo',
                                          url: logoUrl,
                                          mime_type: 'image/png',
                                      },
                                  ]
                                : []
                        }
                        onRemoveExisting={() =>
                            form.setData('remove_logo', true)
                        }
                        accept="image/png,image/jpeg,image/webp"
                        maxSize={2}
                        placeholder="Drop a logo or click"
                        hint="PNG, JPG or WebP · 2 MB max"
                        isUploading={form.processing}
                        uploadProgress={form.progress?.percentage ?? null}
                    />
                </SettingField>
                <SettingField
                    id="favicon"
                    label="Favicon"
                    help="PNG, ICO or WebP up to 512 KB. Shown in the browser tab."
                    source={group.favicon_path.source}
                    error={form.errors.favicon}
                >
                    <FileUpload
                        value={form.data.favicon}
                        onChange={(file) => {
                            form.setData((data) => ({
                                ...data,
                                favicon: single(file),
                                remove_favicon: false,
                            }));
                        }}
                        existingFiles={
                            faviconUrl &&
                            !form.data.remove_favicon &&
                            !form.data.favicon
                                ? [
                                      {
                                          id: 'favicon',
                                          path: 'favicon',
                                          name: 'Current favicon',
                                          url: faviconUrl,
                                          mime_type: 'image/png',
                                      },
                                  ]
                                : []
                        }
                        onRemoveExisting={() =>
                            form.setData('remove_favicon', true)
                        }
                        accept="image/png,image/x-icon,image/vnd.microsoft.icon,image/webp"
                        maxSize={0.5}
                        placeholder="Drop a favicon or click"
                        hint="PNG, ICO or WebP · 512 KB max"
                        isUploading={form.processing}
                        uploadProgress={form.progress?.percentage ?? null}
                    />
                </SettingField>
            </div>
        </SettingsForm>
    );
}
