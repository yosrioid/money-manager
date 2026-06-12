<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, StickyNote } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import TransactionViewNav from '@/components/TransactionViewNav.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { calendar, day, index } from '@/routes/transactions';

interface DaySummary {
    income: Record<string, number>;
    expense: Record<string, number>;
    net: Record<string, number>;
    count: number;
}

const props = defineProps<{
    month: string;
    days: Record<string, DaySummary>;
    notes: Record<string, string>;
    previousMonth: string;
    nextMonth: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transactions', href: index() },
            { title: 'Calendar', href: calendar() },
        ],
    },
});

const monthFormatter = new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: 'long',
});

const monthLabel = computed(() =>
    monthFormatter.format(new Date(`${props.month}T00:00:00`)),
);

const weekdayLabels = (() => {
    const formatter = new Intl.DateTimeFormat(undefined, { weekday: 'short' });
    const days: string[] = [];

    for (let i = 0; i < 7; i++) {
        days.push(formatter.format(new Date(Date.UTC(2026, 5, 7 + i))));
    }

    return days;
})();

interface CalendarCell {
    date: string | null;
    day: number | null;
    summary: DaySummary | null;
    note: string | null;
}

const weeks = computed<CalendarCell[][]>(() => {
    const [year, month] = props.month.split('-').map(Number);
    const firstDay = new Date(year, month - 1, 1);
    const daysInMonth = new Date(year, month, 0).getDate();
    const startOffset = firstDay.getDay();

    const cells: CalendarCell[] = [];

    for (let i = 0; i < startOffset; i++) {
        cells.push({ date: null, day: null, summary: null, note: null });
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const date = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        cells.push({
            date,
            day,
            summary: props.days[date] ?? null,
            note: props.notes[date] ?? null,
        });
    }

    while (cells.length % 7 !== 0) {
        cells.push({ date: null, day: null, summary: null, note: null });
    }

    const result: CalendarCell[][] = [];

    for (let i = 0; i < cells.length; i += 7) {
        result.push(cells.slice(i, i + 7));
    }

    return result;
});

const currencies = (record: Record<string, number>): string[] =>
    Object.keys(record);
</script>

<template>
    <Head title="Transaction calendar" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Calendar"
                description="Daily income, expense, and net totals for the month"
            />
        </div>

        <TransactionViewNav current="calendar" />

        <div class="flex items-center justify-between gap-4">
            <Button variant="outline" size="sm" as-child>
                <Link :href="calendar({ query: { month: previousMonth } })"
                    ><ChevronLeft /> Previous</Link
                >
            </Button>
            <Heading variant="small" :title="monthLabel" />
            <Button variant="outline" size="sm" as-child>
                <Link :href="calendar({ query: { month: nextMonth } })"
                    >Next <ChevronRight
                /></Link>
            </Button>
        </div>

        <div
            class="grid grid-cols-7 gap-2 text-center text-sm font-medium text-muted-foreground"
        >
            <div v-for="label in weekdayLabels" :key="label">{{ label }}</div>
        </div>

        <div class="grid grid-cols-7 gap-2">
            <template v-for="(week, weekIndex) in weeks" :key="weekIndex">
                <Card
                    v-for="(cell, cellIndex) in week"
                    :key="cellIndex"
                    :class="['min-h-28', !cell.date && 'opacity-40']"
                >
                    <Link
                        v-if="cell.date"
                        :href="day({ query: { date: cell.date } })"
                        class="block"
                    >
                        <CardContent class="space-y-1 p-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">{{ cell.day }}</span>
                                <div class="flex items-center gap-1">
                                    <StickyNote
                                        v-if="cell.note"
                                        class="size-3.5 text-muted-foreground"
                                        :title="cell.note"
                                    />
                                    <Badge
                                        v-if="cell.summary"
                                        variant="outline"
                                    >
                                        {{ cell.summary.count }}
                                    </Badge>
                                </div>
                            </div>
                            <template v-if="cell.summary">
                                <div
                                    v-for="currency in currencies(
                                        cell.summary.income,
                                    )"
                                    :key="`income-${currency}`"
                                    class="text-emerald-600 dark:text-emerald-400"
                                >
                                    +{{ cell.summary.income[currency] }}
                                    {{ currency }}
                                </div>
                                <div
                                    v-for="currency in currencies(
                                        cell.summary.expense,
                                    )"
                                    :key="`expense-${currency}`"
                                    class="text-red-600 dark:text-red-400"
                                >
                                    -{{ cell.summary.expense[currency] }}
                                    {{ currency }}
                                </div>
                                <div
                                    v-for="currency in currencies(
                                        cell.summary.net,
                                    )"
                                    :key="`net-${currency}`"
                                    class="font-medium text-foreground"
                                >
                                    {{ cell.summary.net[currency] }}
                                    {{ currency }}
                                </div>
                            </template>
                        </CardContent>
                    </Link>
                </Card>
            </template>
        </div>
    </div>
</template>
