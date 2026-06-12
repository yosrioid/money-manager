<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import TransactionViewNav from '@/components/TransactionViewNav.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { index, summary } from '@/routes/transactions';

interface AccountMovement {
    id: number;
    name: string;
    currency_code: string;
    opening_balance: number;
    closing_balance: number;
    change: number;
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
    previousMonth: string;
    nextMonth: string;
}>();

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
    </div>
</template>
