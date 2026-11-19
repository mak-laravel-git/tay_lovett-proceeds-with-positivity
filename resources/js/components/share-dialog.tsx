import { usePage } from '@inertiajs/react';
import {
    Copy,
    Facebook,
    Link2,
    Linkedin,
    MessageCircle,
    Send,
    Share2,
    Twitter,
} from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useClipboard } from '@/hooks/use-clipboard';
import { quoteUrl, shareTarget, type ShareNetwork } from '@/lib/quote-share';

type ShareDialogProps = {
    quote: { slug: string; text: string } | null;
    onClose: () => void;
};

const networks: { id: ShareNetwork; label: string; icon: typeof Facebook }[] = [
    { id: 'facebook', label: 'Facebook', icon: Facebook },
    { id: 'x', label: 'X / Twitter', icon: Twitter },
    { id: 'whatsapp', label: 'WhatsApp', icon: MessageCircle },
    { id: 'linkedin', label: 'LinkedIn', icon: Linkedin },
    { id: 'telegram', label: 'Telegram', icon: Send },
];

export function ShareDialog({ quote, onClose }: ShareDialogProps) {
    const { site } = usePage().props;
    const [, copy] = useClipboard();

    if (!quote) {
        return null;
    }

    const url = quoteUrl(quote.slug);
    const text = `${quote.text} — ${site.name}`;
    const canNativeShare =
        typeof navigator !== 'undefined' &&
        typeof navigator.share === 'function';

    const open = (target: string) => {
        window.open(target, '_blank', 'noopener,noreferrer');
        onClose();
    };

    return (
        <Dialog open onOpenChange={(value) => !value && onClose()}>
            <DialogContent className="theme-light max-w-[450px] rounded-2xl bg-white p-6 text-ink">
                <DialogHeader>
                    <DialogTitle>Share this quote</DialogTitle>
                    <DialogDescription>
                        Choose a social network or copy the link. The link shows
                        a preview card with the quote.
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-2 sm:grid-cols-2">
                    {networks.map(({ id, label, icon: Icon }) => (
                        <Button
                            key={id}
                            variant="outline"
                            onClick={() => open(shareTarget(id, url, text))}
                        >
                            <Icon /> {label}
                        </Button>
                    ))}
                    {canNativeShare && (
                        <Button
                            variant="outline"
                            onClick={() => {
                                void navigator
                                    .share({ title: site.name, text, url })
                                    .catch(() => undefined);
                                onClose();
                            }}
                        >
                            <Share2 /> More…
                        </Button>
                    )}
                    <Button
                        variant="outline"
                        onClick={() => {
                            void copy(text);
                            toast.success('Quote copied');
                            onClose();
                        }}
                    >
                        <Copy /> Copy quote
                    </Button>
                </div>
                <Button
                    variant="secondary"
                    onClick={() => {
                        void copy(url);
                        toast.success('Link copied');
                        onClose();
                    }}
                >
                    <Link2 /> Copy link
                </Button>
            </DialogContent>
        </Dialog>
    );
}
