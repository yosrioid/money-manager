import { describe, expect, it } from 'vitest';

import { cn } from './utils';

describe('cn', () => {
    it('merges compatible classes and resolves conflicts', () => {
        expect(cn('px-2 text-sm', 'px-4')).toBe('text-sm px-4');
    });
});
