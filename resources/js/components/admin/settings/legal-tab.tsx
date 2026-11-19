import { Link } from '@inertiajs/react';
import { FileText, Pencil } from 'lucide-react';
import { AdminNotice, AdminSectionCard, StatusBadge } from '@/components/admin/admin-page';
import { Button } from '@/components/ui/button';

export type LegalDocumentRow = {
    slug: string;
    title: string;
    is_custom: boolean;
    updated_at: string | null;
    updated_by: string | null;
};

export function LegalTab({ documents }: { documents: LegalDocumentRow[] }) {
    return (
        <div className="grid gap-4">
            <AdminNotice tone="warn">
                Until you publish your own text, visitors see a short starter outline that no lawyer has reviewed.
                Replace both pages before launch.
            </AdminNotice>
            <AdminSectionCard
                title="Legal pages"
                description="The Privacy Policy and Terms of Service shown on the sign-in screen and in the footer."
            >
                <ul className="grid gap-3">
                    {documents.map((document) => (
                        <li
                            key={document.slug}
                            className="flex flex-col gap-3 rounded-2xl border border-[#d4e7ed] bg-[#f8fbfe] p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div className="flex items-start gap-3">
                                <span className="grid size-9 shrink-0 place-items-center rounded-xl bg-[#e7f7ff] text-[#0f6f8f]">
                                    <FileText className="size-4" />
                                </span>
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-semibold text-[#0a1018]">{document.title}</p>
                                        <StatusBadge
                                            value={document.is_custom ? 'published' : 'draft'}
                                            label={document.is_custom ? 'custom text' : 'starter text'}
                                        />
                                    </div>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        /{document.slug}
                                        {document.is_custom && document.updated_at
                                            ? ` · updated ${new Date(document.updated_at).toLocaleString()}${document.updated_by ? ` by ${document.updated_by}` : ''}`
                                            : ''}
                                    </p>
                                </div>
                            </div>
                            <Button asChild className="bg-[#ed2948] text-white hover:bg-[#d61f3d]">
                                <Link href={`/admin/settings/legal/${document.slug}`}>
                                    <Pencil className="size-4" />
                                    Edit page
                                </Link>
                            </Button>
                        </li>
                    ))}
                </ul>
            </AdminSectionCard>
        </div>
    );
}
