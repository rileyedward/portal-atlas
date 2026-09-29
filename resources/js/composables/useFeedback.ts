import { reactive } from 'vue';

export type FeedbackSubject = {
    type: 'marker' | 'item' | 'objective' | 'map';
    id: number;
    name: string;
};

export type FeedbackOptions = {
    /** The thing the feedback is about; omit for general site feedback. */
    subject?: FeedbackSubject;
    /** Where on the page it was raised, e.g. "Found at" or "Search: radio". */
    context?: string;
    /** Pre-selected feedback type (a ReportType value). */
    type?: string;
    /** Pre-filled message. */
    message?: string;
    /** A map position the player picked (percent coordinates). */
    suggested?: { x: number; y: number };
};

/**
 * Global feedback form state. Any component can call openFeedback(); a single
 * <FeedbackDialog> mounted in the layout renders the form.
 */
const state = reactive<{ open: boolean; options: FeedbackOptions }>({
    open: false,
    options: {},
});

export function useFeedback() {
    function openFeedback(options: FeedbackOptions = {}): void {
        state.options = options;
        state.open = true;
    }

    function closeFeedback(): void {
        state.open = false;
    }

    return { state, openFeedback, closeFeedback };
}
