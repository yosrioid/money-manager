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
import {
    index as budgetsIndex,
    income as budgetsIncome,
} from '@/routes/budgets';
import { index, summary } from '@/routes/transactions';

interface AccountMovement {
    id: number;
    name: string;
    currency_code: string;
    opening_balance: number;
    closing_balance: number;
    change: number;
}

interface BudgetTotal {
    budget: number | null;
    actual: number;
    currency: string | null;
}

const props = defineProps<{
    month: string;
    count: number;
    totals: {
        income: Record<string, number>;
        expense: Record<string, number>;
        net: Record<string, number>;
    };
    accountMovements: AccountMovement[];
    budgetSummary: {
        expense: BudgetTotal;
        income: BudgetTotal;
    };
    netAssets: Record<string, number>;
    netAssetBase: number;
    unsupportedCurrencies: string[];
    netAssetTarget: number | null;
    defaultCurrency: string;
    previousMonth: string;
    nextMonth: string;
    navigationShortcutsEnabled: boolean;
}>();

const remaining = (total: BudgetTotal): number | null =>
    total.budget === null ? null : total.budget - total.actual;

const netAssetRemaining = computed(() =>
    props.netAssetTarget === null
        ? null
        : props.netAssetTarget - props.netAssetBase,
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transactions', href: index() },
            { title: 'Summary', href: summary() },
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

const currencies = (record: Record<string, number>): string[] =>
    Object.keys(record);

usePeriodNavigation({
    enabled: () => props.navigationShortcutsEnabled,
    previousHref: () => summary({ query: { month: props.previousMonth } }).url,
    nextHref: () => summary({ query: { month: props.nextMonth } }).url,
});
</script>

<template>
    <Head title="Transaction summary" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Summary"
                description="Period totals and account movement"
            />
        </div>

        <TransactionViewNav current="summary" />

        <div class="flex items-center justify-between gap-4">
            <Button variant="outline" size="sm" as-child>
                <Link :href="summary({ query: { month: previousMonth } })"
                    ><ChevronLeft /> Previous</Link
                >
            </Button>
            <Heading variant="small" :title="monthLabel" />
            <Button variant="outline" size="sm" as-child>
                <Link :href="summary({ query: { month: nextMonth } })"
                    >Next <ChevronRight
                /></Link>
            </Button>
        </div>

        <Card>
            <CardContent class="space-y-2 p-4 text-sm">
                <div class="flex items-center justify-between">
                    <Heading variant="small" title="Period totals" />
                    <Badge variant="outline">{{ count }} records</Badge>
                </div>
                <div
                    v-if="
                        currencies(totals.income).length ||
                        currencies(totals.expense).length
                    "
                    class="grid gap-1"
                >
                    <div
                        v-for="currency in currencies(totals.income)"
                        :key="`income-${currency}`"
                        class="text-emerald-600 dark:text-emerald-400"
                    >
                        Income: +{{ totals.income[currency] }} {{ currency }}
                    </div>
                    <div
                        v-for="currency in currencies(totals.expense)"
                        :key="`expense-${currency}`"
                        class="text-red-600 dark:text-red-400"
                    >
                        Expense: -{{ totals.expense[currency] }} {{ currency }}
                    </div>
                    <div
                        v-for="currency in currencies(totals.net)"
                        :key="`net-${currency}`"
                        class="font-medium text-foreground"
                    >
                        Net: {{ totals.net[currency] }} {{ currency }}
                    </div>
                </div>
                <p v-else class="text-muted-foreground">
                    No income or expense activity this month.
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="space-y-3 p-4 text-sm">
                <Heading variant="small" title="Account movement" />
                <div v-if="accountMovements.length" class="grid gap-2">
                    <div
                        v-for="account in accountMovements"
                        :key="account.id"
                        class="flex items-center justify-between gap-4 border-b pb-2 last:border-b-0 last:pb-0"
                    >
                        <span class="font-medium text-foreground">{{
                            account.name
                        }}</span>
                        <div class="flex flex-wrap gap-3 text-muted-foreground">
                            <span
                                >Opening: {{ account.opening_balance }}
                                {{ account.currency_code }}</span
                            >
                            <span
                                >Closing: {{ account.closing_balance }}
                                {{ account.currency_code }}</span
                            >
                            <span
                                :class="
                                    account.change >= 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-600 dark:text-red-400'
                                "
                            >
                                Change: {{ account.change }}
                                {{ account.currency_code }}
                            </span>
                        </div>
                    </div>
                </div>
                <p v-else class="text-muted-foreground">
                    No active accounts in this workspace.
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="space-y-3 p-4 text-sm">
                <Heading variant="small" title="Budget summary" />
                <div class="grid gap-2">
                    <div
                        class="flex items-center justify-between gap-4 border-b pb-2"
                    >
                        <Link :href="budgetsIndex({ query: { month } })">
                            <span class="font-medium text-foreground"
                                >Expense budget</span
                            >
                        </Link>
                        <div class="flex flex-wrap gap-3 text-muted-foreground">
                            <span v-if="budgetSummary.expense.budget !== null">
                                Budget: {{ budgetSummary.expense.budget }}
                                {{ budgetSummary.expense.currency ?? '' }}
                            </span>
                            <span v-else>No budget set</span>
                            <span>
                                Actual: {{ budgetSummary.expense.actual }}
                                {{ budgetSummary.expense.currency ?? '' }}
                            </span>
                            <span
                                v-if="remaining(budgetSummary.expense) !== null"
                                :class="
                                    (remaining(
                                        budgetSummary.expense,
                                    ) as number) >= 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-600 dark:text-red-400'
                                "
                            >
                                Remaining:
                                {{ remaining(budgetSummary.expense) }}
                                {{ budgetSummary.expense.currency ?? '' }}
                            </span>
                        </div>
                    </div>
                    <div
                        class="flex items-center justify-between gap-4 last:pb-0"
                    >
                        <Link :href="budgetsIncome({ query: { month } })">
                            <span class="font-medium text-foreground"
                                >Planned income</span
                            >
                        </Link>
                        <div class="flex flex-wrap gap-3 text-muted-foreground">
                            <span v-if="budgetSummary.income.budget !== null">
                                Planned: {{ budgetSummary.income.budget }}
                                {{ budgetSummary.income.currency ?? '' }}
                            </span>
                            <span v-else>No target set</span>
                            <span>
                                Actual: {{ budgetSummary.income.actual }}
                                {{ budgetSummary.income.currency ?? '' }}
                            </span>
                            <span
                                v-if="remaining(budgetSummary.income) !== null"
                                :class="
                                    (remaining(
                                        budgetSummary.income,
                                    ) as number) <= 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-600 dark:text-red-400'
                                "
                            >
                                Remaining: {{ remaining(budgetSummary.income) }}
                                {{ budgetSummary.income.currency ?? '' }}
                            </span>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="space-y-3 p-4 text-sm">
                <Heading variant="small" title="Net asset" />
                <div class="grid gap-1">
                    <div
                        v-for="currency in Object.keys(netAssets)"
                        :key="currency"
                        class="font-medium text-foreground"
                    >
                        {{ netAssets[currency] }} {{ currency }}
                    </div>
                    <p
                        v-if="!Object.keys(netAssets).length"
                        class="text-muted-foreground"
                    >
                        No accounts are included in your net asset total.
                    </p>
                </div>
                <div
                    v-if="Object.keys(netAssets).length"
                    class="font-medium text-foreground"
                >
                    Total: {{ netAssetBase }} {{ defaultCurrency }}
                </div>
                <p
                    v-if="unsupportedCurrencies.length"
                    class="text-muted-foreground"
                >
                    {{ unsupportedCurrencies.join(', ') }} balances are not
                    included in the total above. Configure an exchange rate
                    in workspace settings to include them.
                </p>
                <div
                    v-if="netAssetTarget !== null"
                    class="flex flex-wrap gap-3 text-muted-foreground"
                >
                    <span
                        >Target: {{ netAssetTarget }}
                        {{ defaultCurrency }}</span
                    >
                    <span
                        :class="
                            (netAssetRemaining as number) <= 0
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-red-600 dark:text-red-400'
                        "
                    >
                        Remaining to target: {{ netAssetRemaining }}
                        {{ defaultCurrency }}
                    </span>
                </div>
                <p v-else class="text-muted-foreground">
                    No net asset target set. You can set one in workspace
                    settings.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
