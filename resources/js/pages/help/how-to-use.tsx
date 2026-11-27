import { usePage } from '@inertiajs/react';
import {
    ArrowLeftRight,
    Bookmark,
    CircleDollarSign,
    ClipboardList,
    Compass,
    House,
    LogIn,
    type LucideIcon,
    PenLine,
    Share2,
    UserPlus,
    UserRound,
    Wallet,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { LegalDocument } from '@/components/legal-document';
import { Notice } from '@/components/ui-brand';

type GuideSection = {
    /** Used as the address of the section, for example /how-to-use#wallet. */
    id: string;
    title: string;
    icon: LucideIcon;
    steps: ReactNode[];
    note?: ReactNode;
};

/** A button, menu or heading name exactly as it appears on screen. */
function Label({ children }: { children: ReactNode }) {
    return <b className="font-bold text-ink">{children}</b>;
}

// Keep this text in step with the screens it describes. Several studio and survey steps depend on admin
// settings, so those are worded as things the user "may" be asked to do.
const sections: GuideSection[] = [
    {
        id: 'create-account',
        title: 'Create your account',
        icon: UserPlus,
        steps: [
            <>
                Open the <Label>Create account</Label> tab on the sign-in
                screen.
            </>,
            <>
                Enter your full name, email address and a password, type the
                password again, then press <Label>Create account</Label>.
            </>,
            <>
                We email you a 6-digit code. Enter it and press{' '}
                <Label>Verify email</Label>.
            </>,
            <>
                If the email does not arrive, check your spam folder or press{' '}
                <Label>Resend code</Label>.
            </>,
        ],
        note: 'If the page says new accounts cannot be created, registration is closed for now. Please check back later.',
    },
    {
        id: 'log-in',
        title: 'Log in',
        icon: LogIn,
        steps: [
            <>
                Enter your email and password, then press <Label>Log in</Label>.
                Tick <Label>Keep me signed in</Label> only on your own device.
            </>,
            'If you have added a passkey, you can use the fingerprint button next to Log in instead.',
            'If two-factor authentication is on, enter the 6-digit code from your authenticator app, or one of your recovery codes.',
            <>
                Forgot your password? Press <Label>Forgot password?</Label>,
                enter your email, type the 6-digit code we send, then choose a
                new password.
            </>,
        ],
    },
    {
        id: 'navigation',
        title: 'Find your way around',
        icon: Compass,
        steps: [
            <>
                On a computer, use the menu on the left:{' '}
                <Label>Inspiration Feed</Label>, <Label>Quote Studio</Label>,{' '}
                <Label>Surveys</Label>, <Label>My Wallet</Label>,{' '}
                <Label>My Earnings</Label>, <Label>Saved Quotes</Label> and{' '}
                <Label>Withdrawals</Label>.
            </>,
            <>
                On a phone, use the bar at the bottom: <Label>Feed</Label>,{' '}
                <Label>Create</Label>, <Label>Surveys</Label> and{' '}
                <Label>Saved</Label>. Tap <Label>More</Label> for your wallet,
                earnings, withdrawals, sharing, profile and security.
            </>,
            <>
                Press your name at the top of the screen to open{' '}
                <Label>Profile</Label> or <Label>Security</Label>, or to{' '}
                <Label>Log out</Label>.
            </>,
        ],
    },
    {
        id: 'feed',
        title: 'Read the Inspiration Feed',
        icon: House,
        steps: [
            'The feed shows published quotes. Use the category buttons above them to see one topic at a time.',
            <>
                Press <Label>Save</Label> to keep a quote, <Label>Copy</Label>{' '}
                to copy its text, or <Label>Share</Label> to send it to someone.
            </>,
            <>
                Press <Label>Generate Ad</Label> under a quote to open the
                survey window. You stay on the same page.
            </>,
            'Your points balance is shown above the quotes.',
        ],
        note: 'Points are added only after the survey provider confirms a completed survey. This can take a few minutes.',
    },
    {
        id: 'create-quote',
        title: 'Create your own quote',
        icon: PenLine,
        steps: [
            <>
                Open <Label>Quote Studio</Label> (<Label>Create</Label> on a
                phone). Read the message at the top: it tells you what you will
                be asked to do.
            </>,
            <>
                Choose an inspiration category and press{' '}
                <Label>Create quote</Label>.
            </>,
            'Follow the steps on screen. You may be asked to watch a short ad, complete a survey, and watch a second ad.',
            <>
                If you close an ad or press <Label>Not now</Label>, the next
                step may stay locked. Press <Label>Try again</Label> when you
                are ready.
            </>,
            <>
                If you finished a survey and the page has not moved on, press{' '}
                <Label>I finished a survey: check again</Label>.
            </>,
            <>
                Your quote is either published to the feed straight away or
                waits for you to press <Label>Publish</Label>. Some quotes may
                need a review first.
            </>,
            <>
                Find your approved and published quotes under{' '}
                <Label>Your quotes</Label>, where you can copy, share, publish
                or view them.
            </>,
        ],
        note: 'A survey completed to create a quote may unlock the quote without earning points. The page tells you when this is the case.',
    },
    {
        id: 'share',
        title: 'Share quotes and the website',
        icon: Share2,
        steps: [
            <>
                Press <Label>Share</Label> on a quote, then choose Facebook, X,
                WhatsApp, LinkedIn or Telegram, or copy the quote or its link.
            </>,
            'A shared link shows a preview card with the quote. Publish your quote first, because unpublished quotes stay private.',
            <>
                To invite someone to the website, use{' '}
                <Label>Share website</Label> in the menu (under{' '}
                <Label>More</Label> on a phone).
            </>,
        ],
    },
    {
        id: 'saved',
        title: 'Keep your favourite quotes',
        icon: Bookmark,
        steps: [
            <>
                Press <Label>Save</Label> on a quote in the feed. The button
                changes to <Label>Saved</Label>.
            </>,
            <>
                Open <Label>Saved Quotes</Label> to read your saved quotes again
                or share them.
            </>,
        ],
    },
    {
        id: 'surveys',
        title: 'Complete surveys and earn points',
        icon: ClipboardList,
        steps: [
            <>
                Open <Label>Surveys</Label>. Each card is a survey or offer
                partner and shows whether it is available.
            </>,
            <>
                Press the button on a card to see what is available, then press{' '}
                <Label>Start</Label> on the one you want.
            </>,
            <>
                Complete it in the window that opens. If nothing appears, press{' '}
                <Label>Not loading? Open in a window</Label>.
            </>,
            'Wait for the provider to confirm your completion. Your points update after that.',
        ],
        note: 'Opening or viewing a survey does not earn points. You may not qualify for every survey, and an activity that is not completed earns nothing.',
    },
    {
        id: 'earnings',
        title: 'Check your earnings',
        icon: CircleDollarSign,
        steps: [
            <>
                <Label>My Earnings</Label> shows your points balance and the
                total points you have earned.
            </>,
            'The history shows each activity with its status (for example In progress, Waiting for confirmation or Completed) and its reward (for example Pending, Credited or Reversed).',
            'Use the buttons above the history to show one provider at a time.',
        ],
        note: 'This page counts points only. It does not show an estimate of money.',
    },
    {
        id: 'wallet',
        title: 'Understand your wallet',
        icon: Wallet,
        steps: [
            <>
                <Label>My Wallet</Label> shows your{' '}
                <Label>Available balance</Label> and your{' '}
                <Label>Pending earnings</Label>.
            </>,
            'Pending earnings cannot be withdrawn.',
            <>
                <Label>Account activity</Label> lists your wallet transactions
                when there are any.
            </>,
        ],
        note: 'Completing activities does not guarantee money in your wallet. Only eligible, verified earnings can appear there.',
    },
    {
        id: 'withdrawals',
        title: 'Request a withdrawal',
        icon: ArrowLeftRight,
        steps: [
            <>
                Open <Label>Withdrawals</Label>.
            </>,
            <>
                Enter the amount <Label>in cents</Label> (for example, 500 means
                $5.00). It must not be more than your available balance.
            </>,
            <>
                Add a short destination label, then press{' '}
                <Label>Request withdrawal</Label>.
            </>,
            <>
                Follow the status under <Label>Your transfer history</Label>.
            </>,
        ],
        note: 'Every request is reviewed manually. Sending a request does not send money.',
    },
    {
        id: 'account',
        title: 'Manage your account',
        icon: UserRound,
        steps: [
            <>
                On <Label>Profile</Label>, add a photo (PNG, JPG or WebP, up to
                2 MB) and update your name.
            </>,
            'To change your email, enter the new address and your current password, then confirm the code sent to the new address. Your old email stays active until then.',
            <>
                On <Label>Security</Label>, change your password and, when
                offered, set up two-factor authentication or a passkey. You may
                be asked to confirm it is you first.
            </>,
            <>
                To remove your account, press <Label>Delete my account</Label>{' '}
                at the bottom of your profile, when it is shown. This cannot be
                undone.
            </>,
        ],
    },
];

export default function HowToUse() {
    const { site } = usePage().props;

    return (
        <LegalDocument title="How to Use" framed={false}>
            <p>
                {site.name} is a place to read and create encouraging
                AI-generated quotes, and to earn points by completing partner
                surveys. This guide explains each part of the website.
            </p>

            <nav
                aria-label="On this page"
                className="mt-5 rounded-2xl border bg-white p-5"
            >
                <h2 className="text-xs font-black tracking-[1.7px] text-ink uppercase">
                    On this page
                </h2>
                <ul className="mt-3 grid gap-1 min-[561px]:grid-cols-2">
                    {sections.map(({ id, title, icon: Icon }) => (
                        <li key={id}>
                            <a
                                href={`#${id}`}
                                className="flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-sm font-bold text-ink transition-colors hover:bg-brand-soft hover:text-brand-deep"
                            >
                                <Icon
                                    className="size-4 shrink-0 text-brand-deep"
                                    aria-hidden
                                />
                                {title}
                            </a>
                        </li>
                    ))}
                </ul>
            </nav>

            {sections.map(({ id, title, icon: Icon, steps, note }) => (
                <section
                    key={id}
                    id={id}
                    aria-labelledby={`${id}-title`}
                    className="mt-4 scroll-mt-4 rounded-2xl border bg-white p-5 min-[561px]:p-7"
                >
                    <div className="flex items-center gap-3">
                        <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-sky-pale text-brand-deep">
                            <Icon className="size-5" aria-hidden />
                        </span>
                        <h2
                            id={`${id}-title`}
                            className="text-lg leading-tight font-extrabold text-ink"
                        >
                            {title}
                        </h2>
                    </div>

                    <ol className="mt-4 grid gap-3">
                        {steps.map((step, index) => (
                            <li key={index} className="flex gap-3 text-sm">
                                <span
                                    aria-hidden
                                    className="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-brand-soft text-xs font-black text-brand-deep"
                                >
                                    {index + 1}
                                </span>
                                <span className="min-w-0">{step}</span>
                            </li>
                        ))}
                    </ol>

                    {note ? (
                        <div className="mt-4">
                            <Notice>{note}</Notice>
                        </div>
                    ) : null}
                </section>
            ))}

            {site.contact.support_email ? (
                <p className="mt-6 text-sm">
                    Need more help? Write to{' '}
                    <a
                        className="font-semibold break-all text-brand-deep underline"
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
