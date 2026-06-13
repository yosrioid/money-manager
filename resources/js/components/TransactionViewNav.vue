<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    calendar,
    index,
    monthly,
    summary,
    weekly,
} from '@/routes/transactions';

defineProps<{
    current: 'daily' | 'calendar' | 'weekly' | 'monthly' | 'summary';
}>();
</script>

<template>
    <nav class="flex flex-wrap gap-2" aria-label="Transaction views">
        <Button
            :variant="current === 'daily' ? 'default' : 'outline'"
            size="sm"
            as-child
        >
            <Link :href="index()">Daily</Link>
        </Button>
        <Button
            :variant="current === 'weekly' ? 'default' : 'outline'"
            size="sm"
            as-child
        >
            <Link :href="weekly()">Weekly</Link>
        </Button>
        <Button
            :variant="current === 'monthly' ? 'default' : 'outline'"
            size="sm"
            as-child
        >
            <Link :href="monthly()">Monthly</Link>
        </Button>
        <Button
            :variant="current === 'calendar' ? 'default' : 'outline'"
            size="sm"
            as-child
        >
            <Link :href="calendar()">Calendar</Link>
        </Button>
        <Button
            :variant="current === 'summary' ? 'default' : 'outline'"
            size="sm"
            as-child
        >
            <Link :href="summary()">Summary</Link>
        </Button>
    </nav>
</template>
