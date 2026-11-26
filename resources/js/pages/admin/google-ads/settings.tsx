import { Head, Link, useForm } from '@inertiajs/react';
import {
    AdminNotice,
    AdminPage,
    AdminSectionCard,
} from '@/components/admin/admin-page';
import { Field, SwitchField } from '@/components/admin/form-fields';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type Props = {
    settings: {
        rewarded_enabled: boolean;
        display_enabled: boolean;
        adsense_client_id: string;
        gam_network_code: string;
        test_mode: boolean;
        ads_txt: string;
    };
    ads_txt_url: string;
};

export default function GoogleAdsSettings({ settings, ads_txt_url }: Props) {
    const form = useForm(settings);
    const { data, setData, errors } = form;

    return (
        <>
            <Head title="Google ads" />
            <AdminPage
                title="Google ads"
                description="Account settings for regular ads and rewarded ads. The two are independent."
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/admin/google-ads/placements">
                            Manage ad units
                        </Link>
                    </Button>
                }
            >
                <AdminNotice>
                    No Google API key is needed. Rewarded web ads come from Ad
                    Manager; regular ads from AdSense or Ad Manager. Visitors in
                    the EEA, UK and Switzerland need a Google-certified consent
                    message, which you switch on in your Google account (Privacy
                    &amp; messaging). Impressions and revenue are reported in
                    Google’s dashboards, not here.
                </AdminNotice>

                <form
                    className="grid gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.put('/admin/google-ads', { preserveScroll: true });
                    }}
                >
                    <AdminSectionCard
                        title="Switches"
                        description="Both are off until you turn them on."
                    >
                        <div className="grid gap-4 sm:grid-cols-2">
                            <SwitchField
                                id="rewarded_enabled"
                                label="Rewarded ads"
                                hint="Used by the quote workflow. Off: both ad steps are skipped."
                                checked={data.rewarded_enabled}
                                onChange={(checked) =>
                                    setData('rewarded_enabled', checked)
                                }
                            />
                            <SwitchField
                                id="display_enabled"
                                label="Regular ads"
                                hint="Shown in the page locations you assign to ad units."
                                checked={data.display_enabled}
                                onChange={(checked) =>
                                    setData('display_enabled', checked)
                                }
                            />
                            <SwitchField
                                id="test_mode"
                                label="Test mode"
                                hint="Uses Google’s sample Ad Manager units and marks AdSense requests as tests. Turn off for live ads."
                                checked={data.test_mode}
                                onChange={(checked) =>
                                    setData('test_mode', checked)
                                }
                            />
                        </div>
                    </AdminSectionCard>

                    <AdminSectionCard
                        title="Accounts"
                        description="Identifiers from your Google accounts."
                    >
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field
                                label="AdSense client ID"
                                hint="Needed for regular AdSense units."
                                error={errors.adsense_client_id}
                            >
                                <Input
                                    placeholder="ca-pub-1234567890123456"
                                    value={data.adsense_client_id}
                                    onChange={(event) =>
                                        setData(
                                            'adsense_client_id',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Ad Manager network code (reference)"
                                hint="Each Ad Manager unit path already starts with it."
                                error={errors.gam_network_code}
                            >
                                <Input
                                    inputMode="numeric"
                                    value={data.gam_network_code}
                                    onChange={(event) =>
                                        setData(
                                            'gam_network_code',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="ads.txt"
                                hint={`Published at ${ads_txt_url}. Paste the lines Google gives you.`}
                                error={errors.ads_txt}
                                className="sm:col-span-2"
                            >
                                <Textarea
                                    rows={6}
                                    className="font-mono text-xs"
                                    placeholder="google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0"
                                    value={data.ads_txt}
                                    onChange={(event) =>
                                        setData('ads_txt', event.target.value)
                                    }
                                />
                            </Field>
                        </div>
                    </AdminSectionCard>

                    <Button
                        type="submit"
                        className="w-fit"
                        disabled={form.processing}
                    >
                        Save settings
                    </Button>
                </form>
            </AdminPage>
        </>
    );
}

GoogleAdsSettings.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Google ads', href: '/admin/google-ads' },
    ],
};
