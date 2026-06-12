<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import TransactionViewNav from '@/components/TransactionViewNav.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { day, index } from '@/routes/transactions';

interface Tag {
    id: number;
    name: string;
    color: string | null;
}

interface Entry {
    type: 'account' | 'opening_balance_equity' | 'category';
    amount: number;
    currency_code: string;
    account: { id: number; name: string } | null;
    category: { id: number; name: string } | null;
}

interface TransactionRow {
    id: number;
    type: string;
    status: string;
    description: string;
    memo: string | null;
    occurred_at: string;
    local_date: string;
    merchant: { id: number; name: string } | null;
    tags: Tag[];
    entries: Entry[];
}

const props = defineProps<{
    date: string;
    transactions: TransactionRow[];
    previousDate: string;
    nextDate: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transactions', href: index() },
            { title: 'Day', href: day() },
        ],
    },
});

const dateFormatter = new Intl.DateTimeFormat(undefined, { dateStyle: 'full' });

const dateLabel = computed(() =>
    dateFormatter.format(new Date(`${props.date}T00:00:00`)),
);

const typeLabel = (value: string): string =>
    value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
</script>

<template>
    <Head title="Daily transactions" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading title="Day" description="Transactions for a single day" />
        </div>

        <TransactionViewNav current="daily" />

        <div class="flex items-center justify-between gap-4">
            <Button variant="outline" size="sm" as-child>
                <Link :href="day({ query: { date: previousDate } })"
                    ><ChevronLeft /> Previous</Link
                >
            </Button>
            <Heading variant="small" :title="dateLabel" />
            <Button variant="outline" size="sm" as-child>
                <Link :href="day({ query: { date: nextDate } })"
                    >Next <ChevronRight
                /></Link>
            </Button>
        </div>

        <div v-if="transactions.length" class="grid gap-4">
            <Card v-for="transaction in transactions" :key="transaction.id">
                <CardHeader>
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <CardTitle>{{ transaction.description }}</CardTitle>
                            <p
                                v-if="transaction.merchant"
                                class="text-sm text-muted-foreground"
                            >
                                {{ transaction.merchant.name }}
                            </p>
                        </div>
                        <div class="flex flex-wrap justify-end gap-2">
                            <Badge variant="secondary">
                                {{ typeLabel(transaction.type) }}
                            </Badge>
                            <Badge
                                v-if="transaction.status !== 'posted'"
                                variant="outline"
                            >
                                {{ typeLabel(transaction.status) }}
                            </Badge>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-3">
                    <p
                        v-if="transaction.memo"
                        class="text-sm text-muted-foreground"
                    >
                        {{ transaction.memo }}
                    </p>
                    <div class="grid gap-1 text-sm">
                        <p
                            v-for="(entry, entryIndex) in transaction.entries"
                            :key="entryIndex"
                        >
                            <span class="font-medium text-foreground">
                                {{
                                    entry.account?.name ?? entry.category?.name
                                }}
                            </span>
                            <span class="text-muted-foreground">
                                {{ entry.amount }} {{ entry.currency_code }}
                            </span>
                        </p>
                    </div>
                    <div
                        v-if="transaction.tags.length"
                        class="flex flex-wrap gap-2"
                    >
                        <Badge
                            v-for="tag in transaction.tags"
                            :key="tag.id"
                            variant="outline"
                        >
                            <span
                                v-if="tag.color"
                                class="mr-1 size-2.5 rounded-full"
                                :style="{ backgroundColor: tag.color }"
                            />
                            {{ tag.name }}
                        </Badge>
                    </div>
                </CardContent>
            </Card>
        </div>
        <Card v-else>
            <CardContent class="py-8 text-center">
                <p class="text-sm text-muted-foreground">
                    No transactions on this day.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
