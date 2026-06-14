<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { income, index, trend, weekly, yearly } from '@/routes/budgets';

defineProps<{
    current: 'monthly' | 'weekly' | 'yearly' | 'income' | 'trend';
}>();
</script>

<template>
    <nav class="flex flex-wrap gap-2" aria-label="Budget views">
        <Button
            :variant="current === 'monthly' ? 'default' : 'outline'"
            size="sm"
            as-child
        >
            <Link :href="index()">Monthly</Link>
        </Button>
        <Button
            :variant="current === 'weekly' ? 'default' : 'outline'"
            size="sm"
            as-child
        >
            <Link :href="weekly()">Weekly</Link>
        </Button>
        <Button
            :variant="current === 'yearly' ? 'default' : 'outline'"
            size="sm"
            as-child
        >
            <Link :href="yearly()">Yearly</Link>
        </Button>
        <Button
            :variant="current === 'income' ? 'default' : 'outline'"
            size="sm"
            as-child
        >
            <Link :href="income()">Income</Link>
        </Button>
        <Button
            :variant="current === 'trend' ? 'default' : 'outline'"
            size="sm"
            as-child
        >
            <Link :href="trend()">Trend</Link>
        </Button>
    </nav>
</template>
