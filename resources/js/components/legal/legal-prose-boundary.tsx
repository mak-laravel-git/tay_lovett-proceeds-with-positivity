import { Component, type ReactNode } from 'react';

/**
 * A safety net around rendering administrator-written HTML. If anything in that path ever throws (a browser
 * quirk, an unexpected DOMPurify failure), visitors still see something readable instead of a blank page.
 */
export class LegalProseBoundary extends Component<
    { html: string; children: ReactNode },
    { failed: boolean }
> {
    state = { failed: false };

    static getDerivedStateFromError() {
        return { failed: true };
    }

    componentDidUpdate(previous: { html: string }) {
        if (previous.html !== this.props.html && this.state.failed) {
            this.setState({ failed: false });
        }
    }

    render() {
        if (this.state.failed) {
            return (
                <p className="text-sm text-muted-ink">
                    This page could not be displayed. Please refresh, or try
                    again in a moment.
                </p>
            );
        }

        return this.props.children;
    }
}
