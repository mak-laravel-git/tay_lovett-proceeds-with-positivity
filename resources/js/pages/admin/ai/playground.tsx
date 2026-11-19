import { Head, Link } from '@inertiajs/react';
import { Bot, Eraser, Send, TriangleAlert, User } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import {
    AdminNotice,
    AdminPage,
    AdminSectionCard,
} from '@/components/admin/admin-page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

type CredentialOption = {
    id: number;
    label: string;
    driver: string;
    driver_label: string;
    health: string;
};

type ModelOption = { id: number; driver: string; name: string };

type RouteStep = { label: string; driver: string; model: string };

type ChatMessage = {
    role: 'user' | 'assistant';
    content: string;
    error?: boolean;
    meta?: string;
};

const AUTO = 'auto';

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export default function Playground({
    credentials,
    models,
    route,
}: {
    credentials: CredentialOption[];
    models: ModelOption[];
    route: RouteStep[];
}) {
    const [providerId, setProviderId] = useState(AUTO);
    const [modelId, setModelId] = useState('');
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [draft, setDraft] = useState('');
    const [sending, setSending] = useState(false);
    const endRef = useRef<HTMLDivElement>(null);

    const credential = credentials.find(
        (item) => String(item.id) === providerId,
    );
    const modelOptions = models.filter(
        (item) => item.driver === credential?.driver,
    );
    const first = route[0];

    useEffect(() => {
        endRef.current?.scrollIntoView({ behavior: 'smooth', block: 'end' });
    }, [messages, sending]);

    const changeProvider = (value: string) => {
        setProviderId(value);
        const next = credentials.find((item) => String(item.id) === value);
        const firstModel = models.find((item) => item.driver === next?.driver);
        setModelId(firstModel ? String(firstModel.id) : '');
    };

    const send = async () => {
        const text = draft.trim();

        if (!text || sending) {
            return;
        }

        const history = messages
            .filter((message) => !message.error)
            .map(({ role, content }) => ({ role, content }));

        setMessages((current) => [...current, { role: 'user', content: text }]);
        setDraft('');
        setSending(true);

        try {
            const response = await fetch('/admin/ai/playground', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    message: text,
                    history,
                    credential_id: credential ? credential.id : null,
                    model_id: credential && modelId ? Number(modelId) : null,
                }),
            });
            const data = await response.json();

            if (!response.ok) {
                const message =
                    data?.errors?.message?.[0] ??
                    data?.message ??
                    'The request failed.';
                setMessages((current) => [
                    ...current,
                    { role: 'assistant', content: message, error: true },
                ]);

                return;
            }

            setMessages((current) => [
                ...current,
                {
                    role: 'assistant',
                    content: data.reply || '(empty reply)',
                    meta: `${data.provider} · ${data.model} · ${data.latency_ms} ms${
                        data.output_tokens ? ` · ${data.output_tokens} tokens out` : ''
                    }`,
                },
            ]);
        } catch {
            setMessages((current) => [
                ...current,
                {
                    role: 'assistant',
                    content: 'Could not reach the server. Check your connection.',
                    error: true,
                },
            ]);
        } finally {
            setSending(false);
        }
    };

    return (
        <>
            <Head title="Test chat" />
            <AdminPage
                title="Test chat"
                description="Talk to an AI provider directly to prove your setup works. Nothing here is published or shown to users."
                actions={
                    <Button asChild variant="outline">
                        <Link href="/admin/ai/routing">Open routing</Link>
                    </Button>
                }
            >
                <AdminSectionCard title="Provider">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label>Provider</Label>
                            <Select
                                value={providerId}
                                onValueChange={changeProvider}
                            >
                                <SelectTrigger
                                    className="w-full bg-white"
                                    aria-label="Provider"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={AUTO}>
                                        Auto (follow routing)
                                    </SelectItem>
                                    {credentials.map((item) => (
                                        <SelectItem
                                            key={item.id}
                                            value={String(item.id)}
                                        >
                                            {item.label} · {item.driver_label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <p className="text-xs text-muted-foreground">
                                {credential
                                    ? 'Only this provider is called. If it fails you see its error.'
                                    : first
                                      ? `Uses the live route. First choice: ${first.label} (${first.model}).`
                                      : 'No route is ready. Add a provider and model first.'}
                            </p>
                        </div>
                        <div className="grid gap-2">
                            <Label>Model</Label>
                            <Select
                                value={credential ? modelId : ''}
                                onValueChange={setModelId}
                                disabled={!credential}
                            >
                                <SelectTrigger
                                    className="w-full bg-white"
                                    aria-label="Model"
                                >
                                    <SelectValue
                                        placeholder={
                                            credential
                                                ? 'Choose model'
                                                : 'Chosen by routing'
                                        }
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {modelOptions.map((item) => (
                                        <SelectItem
                                            key={item.id}
                                            value={String(item.id)}
                                        >
                                            {item.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {credential && modelOptions.length === 0 ? (
                                <p className="text-xs text-[#a1283c]">
                                    This provider has no enabled model. Add
                                    one under Models.
                                </p>
                            ) : null}
                        </div>
                    </div>
                </AdminSectionCard>

                <AdminSectionCard
                    title="Conversation"
                    actions={
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            disabled={messages.length === 0 || sending}
                            onClick={() => setMessages([])}
                        >
                            <Eraser className="size-3.5" />
                            Clear
                        </Button>
                    }
                >
                    <div
                        className="grid max-h-[28rem] min-h-56 content-start gap-3 overflow-y-auto pr-1"
                        aria-live="polite"
                    >
                        {messages.length === 0 && !sending ? (
                            <p className="py-10 text-center text-sm text-muted-foreground">
                                Say hello. For example: "Write one kind
                                sentence about hope."
                            </p>
                        ) : null}
                        {messages.map((message, index) => (
                            <div
                                key={index}
                                className={cn(
                                    'flex gap-2.5',
                                    message.role === 'user' &&
                                        'flex-row-reverse',
                                )}
                            >
                                <span
                                    className={cn(
                                        'flex size-8 shrink-0 items-center justify-center rounded-full',
                                        message.role === 'user'
                                            ? 'bg-[#0a1018] text-[#76d8f8]'
                                            : message.error
                                              ? 'bg-[#ffedf0] text-[#ed2948]'
                                              : 'bg-[#e7f7ff] text-[#0f6f8f]',
                                    )}
                                >
                                    {message.role === 'user' ? (
                                        <User className="size-4" />
                                    ) : message.error ? (
                                        <TriangleAlert className="size-4" />
                                    ) : (
                                        <Bot className="size-4" />
                                    )}
                                </span>
                                <div
                                    className={cn(
                                        'max-w-[85%] rounded-2xl border px-3.5 py-2.5 text-sm',
                                        message.role === 'user'
                                            ? 'border-[#0a1018] bg-[#0a1018] text-white'
                                            : message.error
                                              ? 'border-[#ed2948]/30 bg-[#ffedf0] text-[#a1283c]'
                                              : 'border-[#d4e7ed] bg-[#f8fbfe] text-[#0a1018]',
                                    )}
                                >
                                    <p className="whitespace-pre-wrap">
                                        {message.content}
                                    </p>
                                    {message.meta ? (
                                        <Badge
                                            variant="outline"
                                            className="mt-2 border-[#d4e7ed] bg-white font-normal text-[#526475]"
                                        >
                                            {message.meta}
                                        </Badge>
                                    ) : null}
                                </div>
                            </div>
                        ))}
                        {sending ? (
                            <div className="flex gap-2.5">
                                <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#e7f7ff] text-[#0f6f8f]">
                                    <Bot className="size-4" />
                                </span>
                                <div className="grid w-56 gap-2 rounded-2xl border border-[#d4e7ed] bg-[#f8fbfe] p-3.5">
                                    <Skeleton className="h-3 w-full" />
                                    <Skeleton className="h-3 w-2/3" />
                                </div>
                            </div>
                        ) : null}
                        <div ref={endRef} />
                    </div>

                    <form
                        className="mt-4 flex items-end gap-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            void send();
                        }}
                    >
                        <Textarea
                            value={draft}
                            onChange={(event) => setDraft(event.target.value)}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter' && !event.shiftKey) {
                                    event.preventDefault();
                                    void send();
                                }
                            }}
                            rows={2}
                            maxLength={2000}
                            placeholder="Type a message. Enter sends, Shift+Enter adds a line."
                            aria-label="Message"
                            className="min-h-[3.25rem] resize-none bg-white"
                        />
                        <Button
                            type="submit"
                            disabled={sending || draft.trim() === ''}
                            className="h-[3.25rem] bg-[#ed2948] text-white hover:bg-[#d61f3d]"
                        >
                            <Send className="size-4" />
                            Send
                        </Button>
                    </form>
                </AdminSectionCard>

                <AdminNotice>
                    Test chat makes real provider calls, so it may cost a
                    fraction of a cent per message. It ignores the daily
                    budget and generation switch, and each call is listed in
                    Usage as "test chat".
                </AdminNotice>
            </AdminPage>
        </>
    );
}

Playground.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Test chat', href: '/admin/ai/playground' },
    ],
};
