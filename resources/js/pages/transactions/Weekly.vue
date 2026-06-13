<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import TransactionViewNav from '@/components/TransactionViewNav.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { usePeriodNavigation } from '@/composables/usePeriodNavigation';
import { day, index, weekly } from '@/routes/transactions';

interface DaySummary {
    income: Record<string, number>;
    expense: Record<string, number>;
    net: Record<string, number>;
    count: number;
}

const props = defineProps<{
    weekStart: string;
    days: Record<string, DaySummary>;
    totals: Record<string, Record<string, number>>;
    previousWeek: string;
    nextWeek: string;
    navigationShortcutsEnabled: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transactions', href: index() },
            { title: 'Weekly', href: weekly() },
        ],
    },
});

const weekDayFormatter = new Intl.DateTimeFormat(undefined, {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
});

const weekRangeFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
});

interface WeekCell {
    date: string;
    label: string;
    summary: DaySummary | null;
}

const cells = computed<WeekCell[]>(() => {
    const [year, month, dayOfMonth] = props.weekStart.split('-').map(Number);
    const start = new Date(year, month - 1, dayOfMonth);

    const result: WeekCell[] = [];

    for (let i = 0; i < 7; i++) {
        const current = new Date(start);
        current.setDate(start.getDate() + i);

        const date = `${current.getFullYear()}-${String(current.getMonth() + 1).padStart(2, '0')}-${String(current.getDate()).padStart(2, '0')}`;

        result.push({
            date,
            label: weekDayFormatter.format(current),
            summary: props.days[date] ?? null,
        });
    }

    return result;
});

const weekRangeLabel = computed(() => {
    const start = new Date(`${props.weekStart}T00:00:00`);
    const end = new Date(start);
    end.setDate(start.getDate() + 6);

    return `${weekRangeFormatter.format(start)} - ${weekRangeFormatter.format(end)}`;
});

const currencies = (record: Record<string, number>): string[] =>
    Object.keys(record);

usePeriodNavigation({
    enabled: () => props.navigationShortcutsEnabled,
    previousHref: () => weekly({ query: { week: props.previousWeek } }).url,
    nextHref: () => weekly({ query: { week: props.nextWeek } }).url,
});
</script>

<template>
    <Head title="Weekly transactions" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Weekly"
                description="Daily income, expense, and net totals for the week"
            />
        </div>

        <TransactionViewNav current="weekly" />

        <div class="flex items-center justify-between gap-4">
            <Button variant="outline" size="sm" as-child>
                <Link :href="weekly({ query: { week: previousWeek } })"
                    ><ChevronLeft /> Previous</Link
                >
            </Button>
            <Heading variant="small" :title="weekRangeLabel" />
            <Button variant="outline" size="sm" as-child>
                <Link :href="weekly({ query: { week: nextWeek } })"
                    >Next <ChevronRight
                /></Link>
            </Button>
        </div>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-7">
            <Card v-for="cell in cells" :key="cell.date">
                <CardContent class="space-y-1 p-2 text-xs">
                    <Link
                        :href="day({ query: { date: cell.date } })"
                        class="flex items-center justify-between font-medium hover:underline"
                    >
                        <span>{{ cell.label }}</span>
                        <Badge v-if="cell.summary" variant="outline">
                            {{ cell.summary.count }}
                        </Badge>
                    </Link>
                    <template v-if="cell.summary">
                        <div
                            v-for="currency in currencies(cell.summary.income)"
                            :key="`income-${currency}`"
                            class="text-emerald-600 dark:text-emerald-400"
                        >
                            +{{ cell.summary.income[currency] }} {{ currency }}
                        </div>
                        <div
                            v-for="currency in currencies(cell.summary.expense)"
                            :key="`expense-${currency}`"
                            class="text-red-600 dark:text-red-400"
                        >
                            -{{ cell.summary.expense[currency] }} {{ currency }}
                        </div>
                        <div
                            v-for="currency in currencies(cell.summary.net)"
                            :key="`net-${currency}`"
                            class="font-medium text-foreground"
                        >
                            {{ cell.summary.net[currency] }} {{ currency }}
                        </div>
                    </template>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardContent class="space-y-2 p-4 text-sm">
                <Heading variant="small" title="Weekly totals" />
                <div
                    v-if="
                        currencies(totals.income).length ||
                        currencies(totals.expense).length
                    "
                    class="grid gap-1"
                >
                    <div
                        v-for="currency in currencies(totals.income)"
                        :key="`total-income-${currency}`"
                        class="text-emerald-600 dark:text-emerald-400"
                    >
                        Income: +{{ totals.income[currency] }} {{ currency }}
                    </div>
                    <div
                        v-for="currency in currencies(totals.expense)"
                        :key="`total-expense-${currency}`"
                        class="text-red-600 dark:text-red-400"
                    >
                        Expense: -{{ totals.expense[currency] }} {{ currency }}
                    </div>
                    <div
                        v-for="currency in currencies(totals.net)"
                        :key="`total-net-${currency}`"
                        class="font-medium text-foreground"
                    >
                        Net: {{ totals.net[currency] }} {{ currency }}
                    </div>
                </div>
                <p v-else class="text-muted-foreground">
                    No income or expense activity this week.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
