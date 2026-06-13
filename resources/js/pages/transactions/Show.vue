<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index, show } from '@/routes/transactions';
import { update as updateStatisticsInclusion } from '@/routes/transactions/statistics-inclusion';

interface Tag {
    id: number;
    name: string;
    color: string | null;
}

interface Entry {
    id: number;
    type: 'account' | 'opening_balance_equity' | 'category';
    amount: number;
    currency_code: string;
    account: { id: number; name: string } | null;
    category: { id: number; name: string } | null;
}

interface TransactionLink {
    id: number;
    description: string;
}

interface TransactionDetail {
    id: number;
    type: string;
    status: string;
    description: string;
    memo: string | null;
    include_in_statistics: boolean;
    currency_code: string;
    occurred_at: string;
    posted_at: string | null;
    local_date: string;
    merchant: { id: number; name: string } | null;
    creator: { id: number; name: string } | null;
    tags: Tag[];
    entries: Entry[];
    reverses: TransactionLink | null;
    reversal: TransactionLink | null;
    replaces: TransactionLink | null;
    replacement: TransactionLink | null;
}

interface AuditLogEntry {
    id: number;
    action: string;
    actor: { id: number; name: string } | null;
    created_at: string;
    metadata: Record<string, unknown> | null;
}

defineProps<{
    transaction: TransactionDetail;
    auditLogs: AuditLogEntry[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Transactions', href: index() }],
    },
});

const typeLabel = (value: string): string =>
    value
        .split(/[._]/)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');

const dateTimeFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'full',
    timeStyle: 'short',
});

const formatDateTime = (value: string): string =>
    dateTimeFormatter.format(new Date(value));
</script>

<template>
    <Head title="Transaction detail" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Transaction detail"
                description="Full transaction, entries, audit state, and links"
            />
            <Button variant="outline" size="sm" as-child>
                <Link :href="index()"><ArrowLeft /> Back to transactions</Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <div class="flex flex-wrap items-start justify-between gap-3">
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
            <CardContent class="space-y-4">
                <p
                    v-if="transaction.memo"
                    class="text-sm text-muted-foreground"
                >
                    {{ transaction.memo }}
                </p>

                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground">Occurred at</dt>
                        <dd>{{ formatDateTime(transaction.occurred_at) }}</dd>
                    </div>
                    <div v-if="transaction.posted_at">
                        <dt class="text-muted-foreground">Posted at</dt>
                        <dd>{{ formatDateTime(transaction.posted_at) }}</dd>
                    </div>
                    <div v-if="transaction.creator">
                        <dt class="text-muted-foreground">Recorded by</dt>
                        <dd>{{ transaction.creator.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Currency</dt>
                        <dd>{{ transaction.currency_code }}</dd>
                    </div>
                </dl>

                <div
                    v-if="transaction.posted_at"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-md border p-3"
                >
                    <div class="space-y-1">
                        <p class="text-sm font-medium">Statistics inclusion</p>
                        <p class="text-sm text-muted-foreground">
                            {{
                                transaction.include_in_statistics
                                    ? 'Included in period statistics and summaries.'
                                    : 'Excluded from period statistics and summaries. Ledger balances are unchanged.'
                            }}
                        </p>
                    </div>
                    <Form
                        v-bind="updateStatisticsInclusion.form(transaction.id)"
                        v-slot="{ processing }"
                    >
                        <input
                            type="hidden"
                            name="include_in_statistics"
                            :value="
                                transaction.include_in_statistics ? '0' : '1'
                            "
                        />
                        <Button
                            type="submit"
                            variant="outline"
                            size="sm"
                            :disabled="processing"
                        >
                            {{
                                transaction.include_in_statistics
                                    ? 'Exclude from statistics'
                                    : 'Include in statistics'
                            }}
                        </Button>
                    </Form>
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

                <div
                    v-if="
                        transaction.reverses ||
                        transaction.reversal ||
                        transaction.replaces ||
                        transaction.replacement
                    "
                    class="grid gap-2 text-sm"
                >
                    <p v-if="transaction.reverses">
                        Reverses
                        <Link
                            :href="show(transaction.reverses.id)"
                            class="font-medium hover:underline"
                            >{{ transaction.reverses.description }}</Link
                        >
                    </p>
                    <p v-if="transaction.reversal">
                        Reversed by
                        <Link
                            :href="show(transaction.reversal.id)"
                            class="font-medium hover:underline"
                            >{{ transaction.reversal.description }}</Link
                        >
                    </p>
                    <p v-if="transaction.replaces">
                        Replaces
                        <Link
                            :href="show(transaction.replaces.id)"
                            class="font-medium hover:underline"
                            >{{ transaction.replaces.description }}</Link
                        >
                    </p>
                    <p v-if="transaction.replacement">
                        Replaced by
                        <Link
                            :href="show(transaction.replacement.id)"
                            class="font-medium hover:underline"
                            >{{ transaction.replacement.description }}</Link
                        >
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Entries</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-1 text-sm">
                    <p v-for="entry in transaction.entries" :key="entry.id">
                        <span class="font-medium text-foreground">
                            {{ entry.account?.name ?? entry.category?.name }}
                        </span>
                        <span class="text-muted-foreground">
                            {{ entry.amount }} {{ entry.currency_code }}
                        </span>
                        <Badge variant="outline" class="ml-2">
                            {{ typeLabel(entry.type) }}
                        </Badge>
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Audit history</CardTitle>
            </CardHeader>
            <CardContent>
                <ul v-if="auditLogs.length" class="grid gap-3 text-sm">
                    <li
                        v-for="log in auditLogs"
                        :key="log.id"
                        class="flex flex-wrap items-center justify-between gap-2 border-b pb-2 last:border-b-0 last:pb-0"
                    >
                        <span>
                            <span class="font-medium text-foreground">{{
                                typeLabel(log.action)
                            }}</span>
                            <span
                                v-if="log.actor"
                                class="text-muted-foreground"
                            >
                                by {{ log.actor.name }}</span
                            >
                        </span>
                        <span class="text-muted-foreground">{{
                            formatDateTime(log.created_at)
                        }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    No audit history recorded for this transaction.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
