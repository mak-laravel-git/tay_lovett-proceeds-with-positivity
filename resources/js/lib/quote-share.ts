/**
 * Public share links always use the quote slug, never the database id.
 * The /q/{slug} page renders the Open Graph tags and image that social
 * networks scrape, so every network below gets the same rich preview.
 */
export function quoteUrl(slug: string): string {
    return `${window.location.origin}/q/${slug}`;
}

export type ShareNetwork =
    | 'facebook'
    | 'x'
    | 'whatsapp'
    | 'linkedin'
    | 'telegram';

export function shareTarget(
    network: ShareNetwork,
    url: string,
    text: string,
): string {
    const u = encodeURIComponent(url);
    const t = encodeURIComponent(text);

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
    }
}
