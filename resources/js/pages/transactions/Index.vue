<script setup lang="ts">
import { Head, InfiniteScroll, Link, router } from '@inertiajs/vue3';
import {
    Clock,
    Copy,
    Download,
    History,
    Plus,
    Search,
    Upload,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import TransactionViewNav from '@/components/TransactionViewNav.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import importTransactions from '@/routes/imports/transactions';
import {
    bulkDuplicate,
    create as createTransaction,
    exportMethod as exportTransactions,
    index,
    show,
} from '@/routes/transactions';
import {
    index as exportsIndex,
    store as storeExport,
} from '@/routes/transactions/exports';

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

interface NamedOption {
    id: number;
    name: string;
    color?: string | null;
}

interface Filters {
    type: string | null;
    status: string | null;
    category_id: number | null;
    account_id: number | null;
    tag_id: number | null;
    from: string | null;
    to: string | null;
}

interface FilterOptions {
    types: string[];
    statuses: string[];
    categories: NamedOption[];
    accounts: NamedOption[];
    tags: NamedOption[];
}

const props = defineProps<{
    transactions: {
        data: TransactionRow[];
        links: PaginationLink[];
    };
    search: string;
    sort: string;
    sortOptions: string[];
    filters: Filters;
    filterOptions: FilterOptions;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Transactions', href: index() }],
    },
});

const search = ref(props.search);
const type = ref(props.filters.type ?? '');
const status = ref(props.filters.status ?? '');
const categoryId = ref(props.filters.category_id?.toString() ?? '');
const accountId = ref(props.filters.account_id?.toString() ?? '');
const tagId = ref(props.filters.tag_id?.toString() ?? '');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const sort = ref(props.sort);

const hasActiveFilters = computed(
    () =>
        !!search.value ||
        !!type.value ||
        !!status.value ||
        !!categoryId.value ||
        !!accountId.value ||
        !!tagId.value ||
        !!from.value ||
        !!to.value,
);

const buildQuery = (): Record<string, string> => {
    const query: Record<string, string> = {};

    if (search.value) {
        query.q = search.value;
    }

    if (type.value) {
        query.type = type.value;
    }

    if (status.value) {
        query.status = status.value;
    }

    if (categoryId.value) {
        query.category_id = categoryId.value;
    }

    if (accountId.value) {
        query.account_id = accountId.value;
    }

    if (tagId.value) {
        query.tag_id = tagId.value;
    }

    if (from.value) {
        query.from = from.value;
    }

    if (to.value) {
        query.to = to.value;
    }

    if (sort.value && sort.value !== 'date_desc') {
        query.sort = sort.value;
    }

    return query;
};

const applyFilters = (): void => {
    router.get(index().url, buildQuery(), {
        preserveState: true,
        replace: true,
    });
};

const exportUrl = computed(
    () => exportTransactions({ query: buildQuery() }).url,
);

const queueExport = (): void => {
    router.post(storeExport().url, buildQuery());
};

const clearFilters = (): void => {
    search.value = '';
    type.value = '';
    status.value = '';
    categoryId.value = '';
    accountId.value = '';
    tagId.value = '';
    from.value = '';
    to.value = '';
    sort.value = 'date_desc';
    applyFilters();
};

const typeLabel = (value: string): string =>
    value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');

const sortLabels: Record<string, string> = {
    date_desc: 'Date (newest first)',
    date_asc: 'Date (oldest first)',
    amount_desc: 'Amount (highest first)',
    amount_asc: 'Amount (lowest first)',
    description_asc: 'Description (A-Z)',
    description_desc: 'Description (Z-A)',
};

const dateFormatter = new Intl.DateTimeFormat(undefined, { dateStyle: 'full' });

const isDateSorted = computed(
    () => sort.value === 'date_desc' || sort.value === 'date_asc',
);

const groupedTransactions = computed(() => {
    const groups: { date: string; items: TransactionRow[] }[] = [];

    if (!isDateSorted.value) {
        if (props.transactions.data.length) {
            groups.push({ date: '', items: props.transactions.data });
        }

        return groups;
    }

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

const selectedIds = ref<Set<number>>(new Set());

const isSelected = (id: number): boolean => selectedIds.value.has(id);

const toggleSelected = (id: number): void => {
    const next = new Set(selectedIds.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    selectedIds.value = next;
};

const clearSelection = (): void => {
    selectedIds.value = new Set();
};

const duplicateSelected = (): void => {
    router.post(
        bulkDuplicate().url,
        { transaction_ids: Array.from(selectedIds.value) },
        { onSuccess: clearSelection },
    );
};
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

        <form class="flex flex-col gap-3" @submit.prevent="applyFilters">
            <div class="relative max-w-md">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    name="q"
                    placeholder="Search memo, merchant, category, account, or amount"
                    class="pl-8"
                />
            </div>

            <div class="flex flex-wrap items-end gap-2">
                <label class="grid gap-1 text-sm">
                    <span class="text-muted-foreground">Type</span>
                    <select
                        v-model="type"
                        class="h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs"
                    >
                        <option value="">All types</option>
                        <option
                            v-for="option in filterOptions.types"
                            :key="option"
                            :value="option"
                        >
                            {{ typeLabel(option) }}
                        </option>
                    </select>
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-muted-foreground">Status</span>
                    <select
                        v-model="status"
                        class="h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs"
                    >
                        <option value="">All statuses</option>
                        <option
                            v-for="option in filterOptions.statuses"
                            :key="option"
                            :value="option"
                        >
                            {{ typeLabel(option) }}
                        </option>
                    </select>
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-muted-foreground">Category</span>
                    <select
                        v-model="categoryId"
                        class="h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs"
                    >
                        <option value="">All categories</option>
                        <option
                            v-for="category in filterOptions.categories"
                            :key="category.id"
                            :value="category.id.toString()"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-muted-foreground">Account</span>
                    <select
                        v-model="accountId"
                        class="h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs"
                    >
                        <option value="">All accounts</option>
                        <option
                            v-for="account in filterOptions.accounts"
                            :key="account.id"
                            :value="account.id.toString()"
                        >
                            {{ account.name }}
                        </option>
                    </select>
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-muted-foreground">Tag</span>
                    <select
                        v-model="tagId"
                        class="h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs"
                    >
                        <option value="">All tags</option>
                        <option
                            v-for="tag in filterOptions.tags"
                            :key="tag.id"
                            :value="tag.id.toString()"
                        >
                            {{ tag.name }}
                        </option>
                    </select>
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-muted-foreground">Sort by</span>
                    <select
                        v-model="sort"
                        class="h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs"
                    >
                        <option
                            v-for="option in sortOptions"
                            :key="option"
                            :value="option"
                        >
                            {{ sortLabels[option] ?? typeLabel(option) }}
                        </option>
                    </select>
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-muted-foreground">From</span>
                    <input
                        v-model="from"
                        type="date"
                        class="h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs"
                    />
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-muted-foreground">To</span>
                    <input
                        v-model="to"
                        type="date"
                        class="h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs"
                    />
                </label>

                <Button type="submit" variant="outline" size="sm"
                    >Apply filters</Button
                >
                <Button
                    v-if="hasActiveFilters"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="clearFilters"
                >
                    <X /> Clear
                </Button>
                <Button as-child variant="outline" size="sm">
                    <a :href="exportUrl"><Download /> Export CSV</a>
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="queueExport"
                >
                    <Clock /> Queue export
                </Button>
                <Button as-child variant="outline" size="sm">
                    <Link :href="exportsIndex()"><History /> Exports</Link>
                </Button>
                <Button as-child variant="outline" size="sm">
                    <Link :href="importTransactions.create()"
                        ><Upload /> Import</Link
                    >
                </Button>
            </div>
        </form>

        <div
            v-if="selectedIds.size > 0"
            class="flex flex-wrap items-center justify-between gap-2 rounded-md border bg-muted/50 px-3 py-2"
        >
            <p class="text-sm text-muted-foreground">
                {{ selectedIds.size }} selected
            </p>
            <div class="flex flex-wrap gap-2">
                <Button size="sm" @click="duplicateSelected">
                    <Copy /> Duplicate as drafts
                </Button>
                <Button variant="ghost" size="sm" @click="clearSelection">
                    <X /> Clear selection
                </Button>
            </div>
        </div>

        <InfiniteScroll
            v-if="groupedTransactions.length"
            data="transactions"
            class="space-y-8"
        >
            <section
                v-for="group in groupedTransactions"
                :key="group.date"
                class="space-y-4"
            >
                <Heading
                    v-if="isDateSorted"
                    variant="small"
                    :title="formatDate(group.date)"
                />
                <div class="grid gap-4">
                    <Card
                        v-for="transaction in group.items"
                        :key="transaction.id"
                    >
                        <CardHeader>
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3">
                                    <Checkbox
                                        :model-value="
                                            isSelected(transaction.id)
                                        "
                                        class="mt-1"
                                        :aria-label="`Select ${transaction.description}`"
                                        @update:model-value="
                                            toggleSelected(transaction.id)
                                        "
                                    />
                                    <div class="space-y-1">
                                        <CardTitle>
                                            <Link
                                                :href="show(transaction.id)"
                                                class="hover:underline"
                                                >{{
                                                    transaction.description
                                                }}</Link
                                            >
                                        </CardTitle>
                                        <p
                                            v-if="transaction.merchant"
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ transaction.merchant.name }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="flex flex-wrap items-center justify-end gap-2"
                                >
                                    <span
                                        v-if="!isDateSorted"
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ formatDate(transaction.local_date) }}
                                    </span>
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

            <template #loading>
                <p class="text-center text-sm text-muted-foreground">
                    Loading more transactions…
                </p>
            </template>
        </InfiniteScroll>
        <Card v-else>
            <CardContent class="py-8 text-center">
                <p class="text-sm text-muted-foreground">
                    {{
                        hasActiveFilters
                            ? 'No transactions match your search or filters.'
                            : 'No transactions yet. Add the first transaction for this workspace.'
                    }}
                </p>
            </CardContent>
        </Card>
    </div>
</template>
