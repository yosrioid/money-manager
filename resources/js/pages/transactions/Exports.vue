<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Download } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index } from '@/routes/transactions';
import { download } from '@/routes/transactions/exports';

interface ExportRow {
    id: number;
    status: 'pending' | 'processing' | 'ready' | 'failed';
    failed_reason: string | null;
    created_at: string | null;
    ready_at: string | null;
}

defineProps<{
    exports: ExportRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transactions', href: index() },
            { title: 'Exports', href: index() },
        ],
    },
});

const statusVariant = (
    status: ExportRow['status'],
): 'default' | 'secondary' | 'destructive' => {
    if (status === 'ready') {
        return 'default';
    }

    if (status === 'failed') {
        return 'destructive';
    }

    return 'secondary';
};

const statusLabel = (status: ExportRow['status']): string =>
    status.charAt(0).toUpperCase() + status.slice(1);

const dateTimeFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
});

const formatDateTime = (value: string | null): string =>
    value === null ? '-' : dateTimeFormatter.format(new Date(value));
</script>

<template>
    <Head title="Transaction exports" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Transaction exports"
                description="Large CSV exports are generated in the background and appear here once ready"
            />
            <Button variant="outline" size="sm" as-child>
                <Link :href="index()"><ArrowLeft /> Back to transactions</Link>
            </Button>
        </div>

        <div v-if="exports.length" class="grid gap-4">
            <Card v-for="item in exports" :key="item.id">
                <CardHeader>
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <CardTitle>Export #{{ item.id }}</CardTitle>
                        <Badge :variant="statusVariant(item.status)">
                            {{ statusLabel(item.status) }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent
                    class="flex flex-wrap items-center justify-between gap-3"
                >
                    <div class="text-sm text-muted-foreground">
                        <p>Queued {{ formatDateTime(item.created_at) }}</p>
                        <p v-if="item.status === 'ready'">
                            Ready {{ formatDateTime(item.ready_at) }}
                        </p>
                        <p
                            v-if="item.status === 'failed'"
                            class="text-destructive"
                        >
                            {{ item.failed_reason ?? 'The export failed.' }}
                        </p>
                    </div>
                    <Button v-if="item.status === 'ready'" size="sm" as-child>
                        <a :href="download(item.id).url"
                            ><Download /> Download</a
                        >
                    </Button>
                </CardContent>
            </Card>
        </div>
        <Card v-else>
            <CardContent class="py-8 text-center">
                <p class="text-sm text-muted-foreground">
                    No exports yet. Use "Queue export" on the transactions page
                    to generate a CSV in the background.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
