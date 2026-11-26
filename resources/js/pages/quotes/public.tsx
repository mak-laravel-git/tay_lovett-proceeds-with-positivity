import { Head } from '@inertiajs/react';
import { Share2 } from 'lucide-react';
import { useState } from 'react';
import { DisplayAdSlot } from '@/components/ads/display-ad-slot';
import { ShareDialog } from '@/components/share-dialog';
import { Button } from '@/components/ui/button';

type PublicQuoteData = { slug: string; text: string; category: string };

export default function PublicQuote({ quote }: { quote: PublicQuoteData }) {
    const [sharing, setSharing] = useState<PublicQuoteData | null>(null);

    return (
        <main className="mx-auto flex min-h-dvh max-w-xl flex-col justify-center p-6">
            <Head title={quote.text.slice(0, 60)} />
            <p className="text-xs font-extrabold tracking-widest text-brand-deep uppercase">
                {quote.category}
            </p>
            <p className="mt-4 text-3xl font-semibold">{quote.text}</p>
            <div className="mt-6">
                <Button variant="outline" onClick={() => setSharing(quote)}>
                    <Share2 /> Share
                </Button>
            </div>
            <DisplayAdSlot location="public_quote_below" className="mt-10" />
            <ShareDialog quote={sharing} onClose={() => setSharing(null)} />
        </main>
    );
}
