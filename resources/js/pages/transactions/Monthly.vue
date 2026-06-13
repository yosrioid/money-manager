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
import { calendar, index, monthly } from '@/routes/transactions';

interface MonthSummary {
    income: Record<string, number>;
    expense: Record<string, number>;
    net: Record<string, number>;
    count: number;
}

const props = defineProps<{
    year: string;
    months: Record<string, MonthSummary>;
    previousYear: string;
    nextYear: string;
    navigationShortcutsEnabled: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transactions', href: index() },
            { title: 'Monthly', href: monthly() },
        ],
    },
});

const monthFormatter = new Intl.DateTimeFormat(undefined, { month: 'long' });

interface MonthCell {
    month: string;
    label: string;
    summary: MonthSummary | null;
}

const cells = computed<MonthCell[]>(() => {
    const result: MonthCell[] = [];

    for (let month = 1; month <= 12; month++) {
        const key = `${props.year}-${String(month).padStart(2, '0')}`;

        result.push({
            month: key,
            label: monthFormatter.format(
                new Date(Number(props.year), month - 1, 1),
            ),
            summary: props.months[key] ?? null,
        });
    }

    return result;
});

usePeriodNavigation({
    enabled: () => props.navigationShortcutsEnabled,
    previousHref: () => monthly({ query: { year: props.previousYear } }).url,
    nextHref: () => monthly({ query: { year: props.nextYear } }).url,
});

const currencies = (record: Record<string, number>): string[] =>
    Object.keys(record);
</script>

<template>
    <Head title="Monthly transactions" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Monthly"
                description="Monthly income and expense comparison"
            />
        </div>

        <TransactionViewNav current="monthly" />

        <div class="flex items-center justify-between gap-4">
            <Button variant="outline" size="sm" as-child>
                <Link :href="monthly({ query: { year: previousYear } })"
                    ><ChevronLeft /> Previous</Link
                >
            </Button>
            <Heading variant="small" :title="year" />
            <Button variant="outline" size="sm" as-child>
                <Link :href="monthly({ query: { year: nextYear } })"
                    >Next <ChevronRight
                /></Link>
            </Button>
        </div>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="cell in cells" :key="cell.month">
                <CardContent class="space-y-1 p-3 text-sm">
                    <Link
                        :href="calendar({ query: { month: cell.month } })"
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
                    <p v-else class="text-muted-foreground">No activity.</p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
