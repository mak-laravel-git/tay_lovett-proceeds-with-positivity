/** The CSRF token Laravel keeps in the XSRF-TOKEN cookie, for requests made with fetch. */
export function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}
