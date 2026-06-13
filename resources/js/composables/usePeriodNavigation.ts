import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import {
    resolveArrowKeyDirection,
    resolveSwipeDirection,
} from '@/lib/period-navigation';

/**
 * Enables swipe and arrow-key navigation between the previous/next period
 * on calendar, weekly, monthly, summary, and daily transaction views, when the
 * workspace's `navigation_shortcuts_enabled` preference is on.
 */
export function usePeriodNavigation(options: {
    enabled: () => boolean;
    previousHref: () => string;
    nextHref: () => string;
}) {
    let touchStartX = 0;
    let touchStartY = 0;

    const visit = (direction: 'previous' | 'next') => {
        router.visit(
            direction === 'previous'
                ? options.previousHref()
                : options.nextHref(),
        );
    };

    const handleKeydown = (event: KeyboardEvent) => {
        if (!options.enabled()) {
            return;
        }

        const direction = resolveArrowKeyDirection(event);

        if (direction) {
            visit(direction);
        }
    };

    const handleTouchStart = (event: TouchEvent) => {
        const touch = event.touches[0];

        if (!touch) {
            return;
        }

        touchStartX = touch.clientX;
        touchStartY = touch.clientY;
    };

    const handleTouchEnd = (event: TouchEvent) => {
        if (!options.enabled()) {
            return;
        }

        const touch = event.changedTouches[0];

        if (!touch) {
            return;
        }

        const direction = resolveSwipeDirection(
            touch.clientX - touchStartX,
            touch.clientY - touchStartY,
        );

        if (direction) {
            visit(direction);
        }
    };

    onMounted(() => {
        window.addEventListener('keydown', handleKeydown);
        window.addEventListener('touchstart', handleTouchStart, {
            passive: true,
        });
        window.addEventListener('touchend', handleTouchEnd, {
            passive: true,
        });
    });

    onUnmounted(() => {
        window.removeEventListener('keydown', handleKeydown);
        window.removeEventListener('touchstart', handleTouchStart);
        window.removeEventListener('touchend', handleTouchEnd);
    });
}
