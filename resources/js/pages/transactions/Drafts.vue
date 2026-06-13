<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index } from '@/routes/transactions';
import { edit } from '@/routes/transactions/drafts';

interface DraftRow {
    id: number;
    type: string;
    description: string;
    updated_at: string;
}

defineProps<{
    drafts: DraftRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transactions', href: index() },
            { title: 'Drafts', href: index() },
        ],
    },
});

const typeLabel = (value: string): string =>
    value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');

const dateTimeFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
});

const formatDateTime = (value: string): string =>
    dateTimeFormatter.format(new Date(value));
</script>

<template>
    <Head title="Draft transactions" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Draft transactions"
                description="Resume or review unsent transaction drafts"
            />
            <Button variant="outline" size="sm" as-child>
                <Link :href="index()"><ArrowLeft /> Back to transactions</Link>
            </Button>
        </div>

        <div v-if="drafts.length" class="grid gap-4">
            <Card v-for="draft in drafts" :key="draft.id">
                <CardHeader>
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <CardTitle>{{ draft.description }}</CardTitle>
                        <Badge variant="secondary">
                            {{ typeLabel(draft.type) }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent
                    class="flex flex-wrap items-center justify-between gap-3"
                >
                    <p class="text-sm text-muted-foreground">
                        Last updated {{ formatDateTime(draft.updated_at) }}
                    </p>
                    <Button size="sm" as-child>
                        <Link :href="edit(draft.id)">Resume draft</Link>
                    </Button>
                </CardContent>
            </Card>
        </div>
        <Card v-else>
            <CardContent class="py-8 text-center">
                <p class="text-sm text-muted-foreground">
                    No draft transactions. Drafts you save or duplicate will
                    appear here.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
