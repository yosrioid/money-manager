<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { calendar, index, weekly } from '@/routes/transactions';

defineProps<{
    current: 'daily' | 'calendar' | 'weekly';
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
            :variant="current === 'calendar' ? 'default' : 'outline'"
            size="sm"
            as-child
        >
            <Link :href="calendar()">Calendar</Link>
        </Button>
    </nav>
</template>
