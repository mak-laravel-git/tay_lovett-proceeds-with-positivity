import { usePage } from '@inertiajs/react';
import {
    Copy,
    Facebook,
    Link2,
    Linkedin,
    Mail,
    MessageCircle,
    Share2,
    Smartphone,
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
import { shareTarget, websiteUrl, type ShareNetwork } from '@/lib/quote-share';

type WebsiteShareDialogProps = {
    open: boolean;
    onClose: () => void;
};

const networks: {
    id: ShareNetwork;
    label: string;
    icon: typeof Facebook;
}[] = [
    { id: 'facebook', label: 'Facebook', icon: Facebook },
    { id: 'x', label: 'X / Twitter', icon: Twitter },
    { id: 'whatsapp', label: 'WhatsApp', icon: MessageCircle },
    { id: 'linkedin', label: 'LinkedIn', icon: Linkedin },
    { id: 'email', label: 'Email', icon: Mail },
    { id: 'sms', label: 'Text / SMS', icon: Smartphone },
];

export function WebsiteShareDialog({ open, onClose }: WebsiteShareDialogProps) {
    const { site } = usePage().props;
    const [, copy] = useClipboard();

    if (!open) {
        return null;
    }

    const url = websiteUrl();
    const blurb =
        site.tagline?.trim() ||
        site.description?.trim() ||
        `Discover motivational quotes on ${site.name}.`;
    const canNativeShare =
        typeof navigator !== 'undefined' &&
        typeof navigator.share === 'function';

    const openTarget = (target: string) => {
        window.open(target, '_blank', 'noopener,noreferrer');
        onClose();
    };

    return (
        <Dialog open onOpenChange={(value) => !value && onClose()}>
            <DialogContent className="theme-light max-w-[450px] rounded-2xl bg-white p-6 text-ink">
                <DialogHeader>
                    <DialogTitle>Share website</DialogTitle>
                    <DialogDescription>
                        Share the public site link. Guests who open it see login
                        first — no private quotes or account data are included.
                    </DialogDescription>
                </DialogHeader>
                <p className="rounded-xl border bg-sky-pale/50 px-3 py-2 text-xs break-all text-muted-ink">
                    {url}
                </p>
                <div className="grid gap-2 sm:grid-cols-2">
                    {networks.map(({ id, label, icon: Icon }) => (
                        <Button
                            key={id}
                            variant="outline"
                            onClick={() => {
                                const target = shareTarget(id, url, blurb);
                                if (id === 'email' || id === 'sms') {
                                    window.location.href = target;
                                    onClose();
                                    return;
                                }
                                openTarget(target);
                            }}
                        >
                            <Icon /> {label}
                        </Button>
                    ))}
                    {canNativeShare && (
                        <Button
                            variant="outline"
                            onClick={() => {
                                void navigator
                                    .share({
                                        title: site.name,
                                        text: blurb,
                                        url,
                                    })
                                    .catch(() => undefined);
                                onClose();
                            }}
                        >
                            <Share2 /> More…
                        </Button>
                    )}
                </div>
                <Button
                    variant="secondary"
                    onClick={() => {
                        void copy(url);
                        toast.success('Website link copied');
                        onClose();
                    }}
                >
                    <Link2 /> Copy website link
                </Button>
                <Button
                    variant="ghost"
                    className="text-muted-foreground"
                    onClick={() => {
                        void copy(`${blurb}\n${url}`);
                        toast.success('Message copied');
                        onClose();
                    }}
                >
                    <Copy /> Copy message + link
                </Button>
            </DialogContent>
        </Dialog>
    );
}
