import { describe, expect, it } from 'vitest';

import { isSubmitShortcut } from './keyboard-shortcuts';

describe('isSubmitShortcut', () => {
    it('matches Ctrl+Enter', () => {
        expect(
            isSubmitShortcut(
                new KeyboardEvent('keydown', { key: 'Enter', ctrlKey: true }),
            ),
        ).toBe(true);
    });

    it('matches Cmd+Enter', () => {
        expect(
            isSubmitShortcut(
                new KeyboardEvent('keydown', { key: 'Enter', metaKey: true }),
            ),
        ).toBe(true);
    });

    it('does not match plain Enter', () => {
        expect(
            isSubmitShortcut(new KeyboardEvent('keydown', { key: 'Enter' })),
        ).toBe(false);
    });

    it('does not match Ctrl with another key', () => {
        expect(
            isSubmitShortcut(
                new KeyboardEvent('keydown', { key: 's', ctrlKey: true }),
            ),
        ).toBe(false);
    });
});
