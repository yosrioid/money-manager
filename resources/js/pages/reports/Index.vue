<script setup lang="ts">
import { Deferred, Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Download } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { usePeriodNavigation } from '@/composables/usePeriodNavigation';
import { exportMethod as exportReport, index } from '@/routes/reports';

interface AmountMap {
    [currency: string]: number;
}

interface Summary {
    income: AmountMap;
    expense: AmountMap;
    net: AmountMap;
    count: number;
}

interface CategoryRow {
    category_id: number;
    name: string;
    parent_id: number | null;
    parent_name: string | null;
    amount: number;
    currency: string;
}

interface MerchantRow {
    merchant_id: number | null;
    name: string;
    amount: number;
    currency: string;
}

interface AccountActivityRow {
    account_id: number;
    name: string;
    currency_code: string;
    opening_balance: number;
    closing_balance: number;
    change: number;
    income: number;
    expense: number;
    count: number;
}

interface NetWorthTrendPoint {
    month: string;
    net: AmountMap;
}

interface FilterOption {
    id: number;
    name: string;
}

const props = defineProps<{
    month: string;
    previousMonth: string;
    nextMonth: string;
    filters: {
        account_ids: number[];
        category_ids: number[];
        merchant_id: number | null;
        tag_ids: number[];
    };
    filterOptions: {
        accounts: FilterOption[];
        categories: FilterOption[];
        merchants: FilterOption[];
        tags: FilterOption[];
    };
    summary?: Summary;
    comparison?: { current: Summary; previous: Summary };
    categoryBreakdown?: { expense: CategoryRow[]; income: CategoryRow[] };
    merchantBreakdown?: MerchantRow[];
    accountActivity?: AccountActivityRow[];
    netWorth?: { assets: AmountMap; liabilities: AmountMap; net: AmountMap };
    netWorthTrend?: NetWorthTrendPoint[];
    visibleWidgets: string[];
    navigationShortcutsEnabled: boolean;
}>();

const isVisible = (widget: string): boolean =>
    props.visibleWidgets.includes(widget);

defineOptions({
    layout: { breadcrumbs: [{ title: 'Reports', href: index() }] },
});

const monthFormatter = new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: 'long',
});

const monthLabel = computed(() =>
    monthFormatter.format(new Date(`${props.month}T00:00:00`)),
);

const accountIds = ref<string[]>(props.filters.account_ids.map(String));
const categoryIds = ref<string[]>(props.filters.category_ids.map(String));
const tagIds = ref<string[]>(props.filters.tag_ids.map(String));
const merchantId = ref<string>(props.filters.merchant_id?.toString() ?? '');

const selectedValues = (event: Event): string[] =>
    Array.from((event.target as HTMLSelectElement).selectedOptions).map(
        (option) => option.value,
    );

const applyFilters = (): void => {
    router.get(
        index().url,
        {
            month: props.month,
            account_ids: accountIds.value,
            category_ids: categoryIds.value,
            tag_ids: tagIds.value,
            merchant_id: merchantId.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const exportUrl = computed(
    () =>
        exportReport({
            query: {
                month: props.month,
                account_ids: accountIds.value,
                category_ids: categoryIds.value,
                tag_ids: tagIds.value,
                merchant_id: merchantId.value || undefined,
            },
        }).url,
);

const formatAmounts = (amounts: AmountMap): string => {
    const entries = Object.entries(amounts);

    if (entries.length === 0) {
        return '—';
    }

    return entries
        .map(([currency, amount]) => `${amount} ${currency}`)
        .join(', ');
};

const groupedCategoryRows = (
    rows: CategoryRow[],
): Array<{ key: string; name: string; amount: number; currency: string }> => {
    const groups = new Map<
        string,
        { key: string; name: string; amount: number; currency: string }
    >();

    for (const row of rows) {
        const key =
            row.parent_id !== null
                ? `parent-${row.parent_id}`
                : `category-${row.category_id}`;
        const name = row.parent_name ?? row.name;

        const existing = groups.get(key);

        if (existing) {
            existing.amount += row.amount;
        } else {
            groups.set(key, {
                key,
                name,
                amount: row.amount,
                currency: row.currency,
            });
        }
    }

    return Array.from(groups.values()).sort((a, b) => b.amount - a.amount);
};

const netWorthTrendMax = (points: NetWorthTrendPoint[]): number => {
    let max = 0;

    for (const point of points) {
        for (const amount of Object.values(point.net)) {
            max = Math.max(max, Math.abs(amount));
        }
    }

    return max || 1;
};

usePeriodNavigation({
    enabled: () => props.navigationShortcutsEnabled,
    previousHref: () => index({ query: { month: props.previousMonth } }).url,
    nextHref: () => index({ query: { month: props.nextMonth } }).url,
});
</script>

<template>
    <Head title="Reports" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Reports"
                description="Statistics and reports for your workspace"
            />
            <Button as-child variant="outline" size="sm">
                <a :href="exportUrl"><Download /> Export Excel</a>
            </Button>
        </div>

        <div class="flex items-center justify-between gap-4">
            <Button variant="outline" size="sm" as-child>
                <Link
                    :href="
                        index({ query: { ...filters, month: previousMonth } })
                    "
                    ><ChevronLeft /> Previous</Link
                >
            </Button>
            <Heading variant="small" :title="monthLabel" />
            <Button variant="outline" size="sm" as-child>
                <Link :href="index({ query: { ...filters, month: nextMonth } })"
                    >Next <ChevronRight
                /></Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Filters</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-4">
                <div class="grid gap-1">
                    <label class="text-sm font-medium" for="report-accounts"
                        >Accounts</label
                    >
                    <select
                        id="report-accounts"
                        multiple
                        class="h-24 rounded-md border bg-background p-2 text-sm"
                        :value="accountIds"
                        @change="accountIds = selectedValues($event)"
                    >
                        <option
                            v-for="account in filterOptions.accounts"
                            :key="account.id"
                            :value="String(account.id)"
                        >
                            {{ account.name }}
                        </option>
                    </select>
                </div>

                <div class="grid gap-1">
                    <label class="text-sm font-medium" for="report-categories"
                        >Categories</label
                    >
                    <select
                        id="report-categories"
                        multiple
                        class="h-24 rounded-md border bg-background p-2 text-sm"
                        :value="categoryIds"
                        @change="categoryIds = selectedValues($event)"
                    >
                        <option
                            v-for="category in filterOptions.categories"
                            :key="category.id"
                            :value="String(category.id)"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <div class="grid gap-1">
                    <label class="text-sm font-medium" for="report-merchant"
                        >Merchant</label
                    >
                    <select
                        id="report-merchant"
                        class="h-24 rounded-md border bg-background p-2 text-sm"
                        v-model="merchantId"
                    >
                        <option value="">All merchants</option>
                        <option
                            v-for="merchant in filterOptions.merchants"
                            :key="merchant.id"
                            :value="String(merchant.id)"
                        >
                            {{ merchant.name }}
                        </option>
                    </select>
                </div>

                <div class="grid gap-1">
                    <label class="text-sm font-medium" for="report-tags"
                        >Tags</label
                    >
                    <select
                        id="report-tags"
                        multiple
                        class="h-24 rounded-md border bg-background p-2 text-sm"
                        :value="tagIds"
                        @change="tagIds = selectedValues($event)"
                    >
                        <option
                            v-for="tag in filterOptions.tags"
                            :key="tag.id"
                            :value="String(tag.id)"
                        >
                            {{ tag.name }}
                        </option>
                    </select>
                </div>

                <div class="md:col-span-4">
                    <Button size="sm" @click="applyFilters"
                        >Apply filters</Button
                    >
                </div>
            </CardContent>
        </Card>

        <Deferred v-if="isVisible('summary')" data="summary" group="reports">
            <template #fallback>
                <Skeleton class="h-32 w-full" />
            </template>

            <Card v-if="summary">
                <CardHeader>
                    <CardTitle>Summary</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-2 text-sm md:grid-cols-4">
                    <div>
                        <p class="text-muted-foreground">Income</p>
                        <p class="font-medium">
                            {{ formatAmounts(summary.income) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Expense</p>
                        <p class="font-medium">
                            {{ formatAmounts(summary.expense) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Net</p>
                        <p class="font-medium">
                            {{ formatAmounts(summary.net) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Transactions</p>
                        <p class="font-medium">{{ summary.count }}</p>
                    </div>
                </CardContent>
            </Card>
        </Deferred>

        <Deferred
            v-if="isVisible('comparison')"
            data="comparison"
            group="reports"
        >
            <template #fallback>
                <Skeleton class="h-24 w-full" />
            </template>

            <Card v-if="comparison">
                <CardHeader>
                    <CardTitle>Compared to previous period</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-2 text-sm md:grid-cols-3">
                    <div>
                        <p class="text-muted-foreground">Income (previous)</p>
                        <p class="font-medium">
                            {{ formatAmounts(comparison.previous.income) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Expense (previous)</p>
                        <p class="font-medium">
                            {{ formatAmounts(comparison.previous.expense) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Net (previous)</p>
                        <p class="font-medium">
                            {{ formatAmounts(comparison.previous.net) }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </Deferred>

        <div
            v-if="isVisible('categoryBreakdown')"
            class="grid gap-4 md:grid-cols-2"
        >
            <Deferred data="categoryBreakdown" group="reports">
                <template #fallback>
                    <Skeleton class="h-48 w-full" />
                </template>

                <Card v-if="categoryBreakdown">
                    <CardHeader>
                        <CardTitle>Expense by category</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-2 text-sm">
                        <div
                            v-if="categoryBreakdown.expense.length === 0"
                            class="text-muted-foreground"
                        >
                            No expense activity yet.
                        </div>
                        <div
                            v-for="row in groupedCategoryRows(
                                categoryBreakdown.expense,
                            )"
                            :key="row.key"
                            class="flex items-center justify-between"
                        >
                            <span>{{ row.name }}</span>
                            <span class="font-medium"
                                >{{ row.amount }} {{ row.currency }}</span
                            >
                        </div>
                    </CardContent>
                </Card>
            </Deferred>

            <Deferred data="categoryBreakdown" group="reports">
                <template #fallback>
                    <Skeleton class="h-48 w-full" />
                </template>

                <Card v-if="categoryBreakdown">
                    <CardHeader>
                        <CardTitle>Income by category</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-2 text-sm">
                        <div
                            v-if="categoryBreakdown.income.length === 0"
                            class="text-muted-foreground"
                        >
                            No income activity yet.
                        </div>
                        <div
                            v-for="row in groupedCategoryRows(
                                categoryBreakdown.income,
                            )"
                            :key="row.key"
                            class="flex items-center justify-between"
                        >
                            <span>{{ row.name }}</span>
                            <span class="font-medium"
                                >{{ row.amount }} {{ row.currency }}</span
                            >
                        </div>
                    </CardContent>
                </Card>
            </Deferred>
        </div>

        <Deferred
            v-if="isVisible('merchantBreakdown')"
            data="merchantBreakdown"
            group="reports"
        >
            <template #fallback>
                <Skeleton class="h-48 w-full" />
            </template>

            <Card v-if="merchantBreakdown">
                <CardHeader>
                    <CardTitle>Spending by merchant</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-2 text-sm">
                    <div
                        v-if="merchantBreakdown.length === 0"
                        class="text-muted-foreground"
                    >
                        No expense activity yet.
                    </div>
                    <div
                        v-for="row in merchantBreakdown"
                        :key="row.merchant_id ?? 'none'"
                        class="flex items-center justify-between"
                    >
                        <span>{{ row.name }}</span>
                        <span class="font-medium"
                            >{{ row.amount }} {{ row.currency }}</span
                        >
                    </div>
                </CardContent>
            </Card>
        </Deferred>

        <Deferred
            v-if="isVisible('accountActivity')"
            data="accountActivity"
            group="reports"
        >
            <template #fallback>
                <Skeleton class="h-48 w-full" />
            </template>

            <Card v-if="accountActivity">
                <CardHeader>
                    <CardTitle>Account activity</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-3 text-sm">
                    <div
                        v-for="row in accountActivity"
                        :key="row.account_id"
                        class="grid gap-1 rounded-lg border p-3 md:grid-cols-5"
                    >
                        <span class="font-medium md:col-span-1">{{
                            row.name
                        }}</span>
                        <span class="text-muted-foreground"
                            >Opening: {{ row.opening_balance }}
                            {{ row.currency_code }}</span
                        >
                        <span class="text-muted-foreground"
                            >Closing: {{ row.closing_balance }}
                            {{ row.currency_code }}</span
                        >
                        <span class="text-muted-foreground"
                            >Change: {{ row.change }}
                            {{ row.currency_code }}</span
                        >
                        <span class="text-muted-foreground"
                            >{{ row.count }} transaction(s)</span
                        >
                    </div>
                </CardContent>
            </Card>
        </Deferred>

        <Deferred
            v-if="isVisible('netWorth')"
            data="netWorth"
            group="net-worth"
        >
            <template #fallback>
                <Skeleton class="h-32 w-full" />
            </template>

            <Card v-if="netWorth">
                <CardHeader>
                    <CardTitle>Assets, liabilities, and net worth</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-2 text-sm md:grid-cols-3">
                    <div>
                        <p class="text-muted-foreground">Assets</p>
                        <p class="font-medium">
                            {{ formatAmounts(netWorth.assets) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Liabilities</p>
                        <p class="font-medium">
                            {{ formatAmounts(netWorth.liabilities) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Net worth</p>
                        <p class="font-medium">
                            {{ formatAmounts(netWorth.net) }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </Deferred>

        <Deferred
            v-if="isVisible('netWorthTrend')"
            data="netWorthTrend"
            group="net-worth"
        >
            <template #fallback>
                <Skeleton class="h-48 w-full" />
            </template>

            <Card v-if="netWorthTrend">
                <CardHeader>
                    <CardTitle>Net worth trend</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-2 text-sm">
                    <div
                        v-for="point in netWorthTrend"
                        :key="point.month"
                        class="grid gap-1"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">{{
                                point.month
                            }}</span>
                            <span class="font-medium">{{
                                formatAmounts(point.net)
                            }}</span>
                        </div>
                        <div
                            v-for="(amount, currency) in point.net"
                            :key="currency"
                            class="h-2 rounded bg-muted"
                        >
                            <div
                                class="h-2 rounded bg-primary"
                                :class="
                                    amount < 0 ? 'bg-red-500' : 'bg-primary'
                                "
                                :style="{
                                    width: `${(Math.abs(amount) / netWorthTrendMax(netWorthTrend)) * 100}%`,
                                }"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </Deferred>
    </div>
</template>
