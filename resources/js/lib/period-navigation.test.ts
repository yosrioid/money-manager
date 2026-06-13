import { describe, expect, it } from 'vitest';

import {
    resolveArrowKeyDirection,
    resolveSwipeDirection,
} from './period-navigation';

describe('resolveSwipeDirection', () => {
    it('resolves a leftward swipe to next', () => {
        expect(resolveSwipeDirection(-80, 0)).toBe('next');
    });

    it('resolves a rightward swipe to previous', () => {
        expect(resolveSwipeDirection(80, 0)).toBe('previous');
    });

    it('ignores swipes shorter than the threshold', () => {
        expect(resolveSwipeDirection(-30, 0)).toBeNull();
    });

    it('ignores swipes that are more vertical than horizontal', () => {
        expect(resolveSwipeDirection(60, 100)).toBeNull();
    });
});

describe('resolveArrowKeyDirection', () => {
    it('resolves ArrowLeft to previous', () => {
        expect(
            resolveArrowKeyDirection({ key: 'ArrowLeft', target: null }),
        ).toBe('previous');
    });

    it('resolves ArrowRight to next', () => {
        expect(
            resolveArrowKeyDirection({ key: 'ArrowRight', target: null }),
        ).toBe('next');
    });

    it('ignores unrelated keys', () => {
        expect(
            resolveArrowKeyDirection({ key: 'Enter', target: null }),
        ).toBeNull();
    });

    it('ignores arrow keys originating from form controls', () => {
        const input = document.createElement('input');

        expect(
            resolveArrowKeyDirection({ key: 'ArrowLeft', target: input }),
        ).toBeNull();
    });
});
