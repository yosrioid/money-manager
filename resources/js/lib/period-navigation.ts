export type PeriodNavigationDirection = 'previous' | 'next';

/**
 * Minimum horizontal swipe distance, in pixels, before it counts as a
 * navigation gesture rather than a scroll or tap.
 */
export const SWIPE_THRESHOLD_PX = 50;

/**
 * Resolves a touch swipe into a previous/next period navigation, or `null`
 * if the gesture is too short or more vertical than horizontal (a scroll).
 */
export function resolveSwipeDirection(
    deltaX: number,
    deltaY: number,
): PeriodNavigationDirection | null {
    if (
        Math.abs(deltaX) < SWIPE_THRESHOLD_PX ||
        Math.abs(deltaX) < Math.abs(deltaY)
    ) {
        return null;
    }

    return deltaX < 0 ? 'next' : 'previous';
}

/**
 * Resolves a keydown event into a previous/next period navigation via the
 * arrow keys, or `null` if the key is unrelated or the event originated from
 * a form control where arrow keys have their own meaning.
 */
export function resolveArrowKeyDirection(
    event: Pick<KeyboardEvent, 'key' | 'target'>,
): PeriodNavigationDirection | null {
    if (
        event.target instanceof HTMLElement &&
        ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName)
    ) {
        return null;
    }

    if (event.key === 'ArrowLeft') {
        return 'previous';
    }

    if (event.key === 'ArrowRight') {
        return 'next';
    }

    return null;
}
