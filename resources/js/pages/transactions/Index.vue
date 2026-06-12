<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import TransactionViewNav from '@/components/TransactionViewNav.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { create as createTransaction, index } from '@/routes/transactions';

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

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    transactions: {
        data: TransactionRow[];
        links: PaginationLink[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Transactions', href: index() }],
    },
});

const dateFormatter = new Intl.DateTimeFormat(undefined, { dateStyle: 'full' });

const typeLabel = (value: string): string =>
    value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');

const groupedTransactions = computed(() => {
    const groups: { date: string; items: TransactionRow[] }[] = [];

    for (const transaction of props.transactions.data) {
        const lastGroup = groups[groups.length - 1];

        if (lastGroup && lastGroup.date === transaction.local_date) {
            lastGroup.items.push(transaction);
        } else {
            groups.push({ date: transaction.local_date, items: [transaction] });
        }
    }

    return groups;
});

const formatDate = (date: string): string =>
    dateFormatter.format(new Date(`${date}T00:00:00`));
</script>

<template>
    <Head title="Transactions" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Transactions"
                description="Browse posted transactions grouped by date"
            />
            <Button as-child>
                <Link :href="createTransaction()"
                    ><Plus /> Add transaction</Link
                >
            </Button>
        </div>

        <TransactionViewNav current="daily" />

        <div v-if="groupedTransactions.length" class="space-y-8">
            <section
                v-for="group in groupedTransactions"
                :key="group.date"
                class="space-y-4"
            >
                <Heading variant="small" :title="formatDate(group.date)" />
                <div class="grid gap-4">
                    <Card
                        v-for="transaction in group.items"
                        :key="transaction.id"
                    >
                        <CardHeader>
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <CardTitle>{{
                                        transaction.description
                                    }}</CardTitle>
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
                                    v-for="(
                                        entry, entryIndex
                                    ) in transaction.entries"
                                    :key="entryIndex"
                                >
                                    <span class="font-medium text-foreground">
                                        {{
                                            entry.account?.name ??
                                            entry.category?.name
                                        }}
                                    </span>
                                    <span class="text-muted-foreground">
                                        {{ entry.amount }}
                                        {{ entry.currency_code }}
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
            </section>
        </div>
        <Card v-else>
            <CardContent class="py-8 text-center">
                <p class="text-sm text-muted-foreground">
                    No transactions yet. Add the first transaction for this
                    workspace.
                </p>
            </CardContent>
        </Card>

        <nav
            v-if="transactions.links.length > 3"
            class="flex flex-wrap gap-2"
            aria-label="Pagination"
        >
            <Button
                v-for="(link, linkIndex) in transactions.links"
                :key="linkIndex"
                variant="outline"
                size="sm"
                :disabled="!link.url"
                :class="{ 'border-primary': link.active }"
                as-child
            >
                <Link v-if="link.url" :href="link.url"
                    ><span v-html="link.label"
                /></Link>
                <span v-else v-html="link.label" />
            </Button>
        </nav>
    </div>
</template>
