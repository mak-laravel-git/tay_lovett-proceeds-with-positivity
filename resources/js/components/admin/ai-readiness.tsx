import { CircleCheck, CircleX } from 'lucide-react';
import { AdminSectionCard } from '@/components/admin/admin-page';
import type { ReadinessCheck } from '@/types/ai';

export function AiReadiness({ checks }: { checks: ReadinessCheck[] }) {
    const ready = checks.every((check) => check.ok);

    return (
        <AdminSectionCard
            title={ready ? 'Ready to generate' : 'Setup checklist'}
            description={
                ready
                    ? 'Every requirement is met. Quote Studio can call a provider.'
                    : 'Quote Studio fails until every item below is done.'
            }
        >
            <ul className="grid gap-2 sm:grid-cols-2">
                {checks.map((check) => (
                    <li
                        key={check.key}
                        className="flex items-start gap-2.5 rounded-xl border border-[#d4e7ed] bg-[#f8fbfe] p-3"
                    >
                        {check.ok ? (
                            <CircleCheck className="mt-0.5 size-4 shrink-0 text-emerald-600" />
                        ) : (
                            <CircleX className="mt-0.5 size-4 shrink-0 text-[#ed2948]" />
                        )}
                        <div>
                            <p className="text-sm font-medium text-[#0a1018]">
                                {check.label}
                            </p>
                            {!check.ok ? (
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    {check.hint}
                                </p>
                            ) : null}
                        </div>
                    </li>
                ))}
            </ul>
        </AdminSectionCard>
    );
}
