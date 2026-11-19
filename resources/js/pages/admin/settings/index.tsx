import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { AdminPage } from '@/components/admin/admin-page';
import { AuthenticationTab } from '@/components/admin/settings/authentication-tab';
import { ContactTab } from '@/components/admin/settings/contact-tab';
import { GeneralTab } from '@/components/admin/settings/general-tab';
import { LegalTab, type LegalDocumentRow } from '@/components/admin/settings/legal-tab';
import {
    MaintenanceTab,
    type MaintenanceStatus,
} from '@/components/admin/settings/maintenance-tab';
import type { SettingsGroup } from '@/components/admin/settings/shared';
import { SmtpTab } from '@/components/admin/settings/smtp-tab';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

type Props = {
    groups: Record<'general' | 'smtp' | 'authentication' | 'contact', SettingsGroup>;
    tab: string;
    logoUrl: string | null;
    faviconUrl: string | null;
    activeMailer: string;
    adminEmail: string;
    legalDocuments: LegalDocumentRow[];
    maintenanceStatus: MaintenanceStatus;
};

const tabs = [
    { value: 'general', label: 'General' },
    { value: 'smtp', label: 'SMTP' },
    { value: 'authentication', label: 'Authentication' },
    { value: 'contact', label: 'Contact & Social' },
    { value: 'maintenance', label: 'Maintenance' },
    { value: 'legal', label: 'Legal pages' },
];

export default function ApplicationSettings({
    groups,
    tab,
    logoUrl,
    faviconUrl,
    activeMailer,
    adminEmail,
    legalDocuments,
    maintenanceStatus,
}: Props) {
    const [active, setActive] = useState(tab);

    const change = (value: string) => {
        setActive(value);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', value);
        window.history.replaceState(window.history.state, '', url);
    };

    // Remount a tab when its saved values change so inputs show the new or reset values.
    const keyOf = (name: keyof Props['groups']) =>
        `${name}:${JSON.stringify(groups[name])}`;

    return (
        <>
            <Head title="Application settings" />
            <AdminPage
                title="Application settings"
                description="Site details and system options. Saved values are used straight away. A value you never saved comes from the .env file."
            >
                <Tabs value={active} onValueChange={change} className="gap-4">
                    <div className="overflow-x-auto pb-1">
                        <TabsList>
                            {tabs.map((item) => (
                                <TabsTrigger
                                    key={item.value}
                                    value={item.value}
                                    className="px-4"
                                >
                                    {item.label}
                                </TabsTrigger>
                            ))}
                        </TabsList>
                    </div>

                    <TabsContent value="general">
                        <GeneralTab
                            key={`${keyOf('general')}:${logoUrl}:${faviconUrl}`}
                            group={groups.general}
                            logoUrl={logoUrl}
                            faviconUrl={faviconUrl}
                        />
                    </TabsContent>
                    <TabsContent value="smtp">
                        <SmtpTab
                            key={keyOf('smtp')}
                            group={groups.smtp}
                            activeMailer={activeMailer}
                            adminEmail={adminEmail}
                        />
                    </TabsContent>
                    <TabsContent value="authentication">
                        <AuthenticationTab
                            key={keyOf('authentication')}
                            group={groups.authentication}
                        />
                    </TabsContent>
                    <TabsContent value="contact">
                        <ContactTab
                            key={keyOf('contact')}
                            group={groups.contact}
                        />
                    </TabsContent>
                    <TabsContent value="maintenance">
                        <MaintenanceTab
                            key={`maintenance:${JSON.stringify(maintenanceStatus)}`}
                            status={maintenanceStatus}
                        />
                    </TabsContent>
                    <TabsContent value="legal">
                        <LegalTab documents={legalDocuments} />
                    </TabsContent>
                </Tabs>
            </AdminPage>
        </>
    );
}

ApplicationSettings.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Application settings', href: '/admin/settings' },
    ],
};
