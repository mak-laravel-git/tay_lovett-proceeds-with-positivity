/**
 * Public share helpers. Quote links use the slug (never a database id).
 * Website links use the canonical site origin only — no auth tokens or private routes.
 */

export function quoteUrl(slug: string): string {
    return `${window.location.origin}/q/${slug}`;
}

/** Canonical public website URL (guest lands on login-first). */
export function websiteUrl(): string {
    return `${window.location.origin}/`;
}

export type ShareNetwork =
    | 'facebook'
    | 'x'
    | 'whatsapp'
    | 'linkedin'
    | 'telegram'
    | 'email'
    | 'sms';

export function shareTarget(
    network: ShareNetwork,
    url: string,
    text: string,
): string {
    const u = encodeURIComponent(url);
    const t = encodeURIComponent(text);
    const subject = encodeURIComponent(text);

    switch (network) {
        case 'facebook':
            return `https://www.facebook.com/sharer/sharer.php?u=${u}`;
        case 'x':
            return `https://twitter.com/intent/tweet?text=${t}&url=${u}`;
        case 'whatsapp':
            return `https://wa.me/?text=${encodeURIComponent(`${text} ${url}`)}`;
        case 'linkedin':
            return `https://www.linkedin.com/sharing/share-offsite/?url=${u}`;
        case 'telegram':
            return `https://t.me/share/url?url=${u}&text=${t}`;
        case 'email':
            return `mailto:?subject=${subject}&body=${encodeURIComponent(`${text}\n\n${url}`)}`;
        case 'sms':
            return `sms:?&body=${encodeURIComponent(`${text} ${url}`)}`;
    }
}
