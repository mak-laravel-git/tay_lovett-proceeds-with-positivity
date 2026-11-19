import { usePage } from '@inertiajs/react';
import { LegalDocument } from '@/components/legal-document';
import { LegalProse } from '@/components/legal/legal-prose';
import { LegalProseBoundary } from '@/components/legal/legal-prose-boundary';

export default function LegalShow({
    title,
    html,
    isDefault,
    updatedAt,
}: {
    title: string;
    html: string;
    isDefault: boolean;
    updatedAt: string | null;
}) {
    const { site } = usePage().props;

    return (
        <LegalDocument title={title} placeholder={isDefault} updatedAt={updatedAt}>
            <LegalProseBoundary html={html}>
                <LegalProse html={html} />
            </LegalProseBoundary>
            {site.contact.support_email ? (
                <p className="mt-8 border-t border-line pt-5 text-sm text-muted-ink">
                    Questions? Write to{' '}
                    <a
                        className="font-semibold text-brand-deep underline"
                        href={`mailto:${site.contact.support_email}`}
                    >
                        {site.contact.support_email}
                    </a>
                    .
                </p>
            ) : null}
        </LegalDocument>
    );
}
