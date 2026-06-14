<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import BudgetViewNav from '@/components/BudgetViewNav.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { usePeriodNavigation } from '@/composables/usePeriodNavigation';
import { index, trend } from '@/routes/budgets';

interface TrendRow {
    month: string;
    budget: number | null;
    actual: number;
    currency: string | null;
}

const props = defineProps<{
    month: string;
    expense: TrendRow[];
    income: TrendRow[];
    previousMonth: string;
    nextMonth: string;
    navigationShortcutsEnabled: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Budgets', href: index() },
            { title: 'Trend', href: trend() },
        ],
    },
});

const monthFormatter = new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: 'short',
});

const monthLabel = (value: string): string =>
    monthFormatter.format(new Date(`${value}-01T00:00:00`));

const remaining = (row: TrendRow): number | null =>
    row.budget === null ? null : row.budget - row.actual;

usePeriodNavigation({
    enabled: () => props.navigationShortcutsEnabled,
    previousHref: () => trend({ query: { month: props.previousMonth } }).url,
    nextHref: () => trend({ query: { month: props.nextMonth } }).url,
});
</script>

<template>
    <Head title="Budget Trend" />

    <div class="flex flex-col gap-8 p-4">
        <Heading
            title="Budgets"
            description="Historical actual spending and income versus budget"
        />

        <BudgetViewNav current="trend" />

        <div class="flex items-center justify-between gap-4">
            <Button variant="outline" size="sm" as-child>
                <Link :href="trend({ query: { month: previousMonth } })"
                    ><ChevronLeft /> Previous</Link
                >
            </Button>
            <Heading variant="small" :title="monthLabel(month.slice(0, 7))" />
            <Button variant="outline" size="sm" as-child>
                <Link :href="trend({ query: { month: nextMonth } })"
                    >Next <ChevronRight
                /></Link>
            </Button>
        </div>

        <Card>
            <CardContent class="space-y-3 p-4">
                <Heading variant="small" title="Expense" />
                <div class="grid gap-2">
                    <div
                        v-for="row in expense"
                        :key="row.month"
                        class="grid gap-3 rounded-lg border p-3 text-sm md:grid-cols-[6rem_10rem_10rem_10rem]"
                    >
                        <span class="self-center font-medium text-foreground">{{
                            monthLabel(row.month)
                        }}</span>
                        <span class="self-center text-muted-foreground">
                            Budget: {{ row.budget ?? 'None' }}
                            {{
                                row.budget !== null ? (row.currency ?? '') : ''
                            }}
                        </span>
                        <span class="self-center text-muted-foreground">
                            Actual: {{ row.actual }} {{ row.currency ?? '' }}
                        </span>
                        <span
                            v-if="remaining(row) !== null"
                            class="self-center"
                            :class="
                                (remaining(row) as number) >= 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-red-600 dark:text-red-400'
                            "
                        >
                            Remaining: {{ remaining(row) }}
                            {{ row.currency ?? '' }}
                        </span>
                        <span v-else class="self-center text-muted-foreground"
                            >No budget set</span
                        >
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="space-y-3 p-4">
                <Heading variant="small" title="Income" />
                <div class="grid gap-2">
                    <div
                        v-for="row in income"
                        :key="row.month"
                        class="grid gap-3 rounded-lg border p-3 text-sm md:grid-cols-[6rem_10rem_10rem_10rem]"
                    >
                        <span class="self-center font-medium text-foreground">{{
                            monthLabel(row.month)
                        }}</span>
                        <span class="self-center text-muted-foreground">
                            Planned: {{ row.budget ?? 'None' }}
                            {{
                                row.budget !== null ? (row.currency ?? '') : ''
                            }}
                        </span>
                        <span class="self-center text-muted-foreground">
                            Actual: {{ row.actual }} {{ row.currency ?? '' }}
                        </span>
                        <span
                            v-if="remaining(row) !== null"
                            class="self-center"
                            :class="
                                (remaining(row) as number) <= 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-red-600 dark:text-red-400'
                            "
                        >
                            Remaining: {{ remaining(row) }}
                            {{ row.currency ?? '' }}
                        </span>
                        <span v-else class="self-center text-muted-foreground"
                            >No target set</span
                        >
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
