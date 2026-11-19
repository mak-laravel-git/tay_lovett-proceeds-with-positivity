import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types/ads';

/** Previous / next controls and a "from–to of total" label for a server-paginated table. */
export function Pagination<T>({ page }: { page: Paginated<T> }) {
    const go = (url: string | null) => {
        if (url) {
            router.get(url, {}, { preserveScroll: true, preserveState: true });
        }
    };

    if (page.total === 0) {
        return null;
    }

    return (
        <div className="mt-3 flex flex-col items-center justify-between gap-2 text-sm text-muted-foreground sm:flex-row">
            <span>
                {page.from}–{page.to} of {page.total}
            </span>
            <div className="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!page.prev_page_url}
                    onClick={() => go(page.prev_page_url)}
                >
                    <ChevronLeft /> Previous
                </Button>
                <span className="tabular-nums">
                    Page {page.current_page} / {page.last_page}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!page.next_page_url}
                    onClick={() => go(page.next_page_url)}
                >
                    Next <ChevronRight />
                </Button>
            </div>
        </div>
    );
}
