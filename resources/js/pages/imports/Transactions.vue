<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Upload } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import importsTransactions from '@/routes/imports/transactions';
import { index } from '@/routes/transactions';

interface ImportRowPreview {
    date: string;
    type: string;
    description: string;
    memo: string;
    merchant: string;
    account: string;
    category: string;
    amount: string;
    currency: string;
    tags: string;
}

interface ImportRow {
    row: number;
    status: 'valid' | 'error';
    errors: string[];
    preview: ImportRowPreview;
}

interface ImportSummary {
    total: number;
    valid: number;
    invalid: number;
}

const props = defineProps<{
    token?: string;
    rows?: ImportRow[];
    summary?: ImportSummary;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transactions', href: index() },
            { title: 'Import', href: importsTransactions.create() },
        ],
    },
});

const file = ref<File | null>(null);
const processing = ref(false);

function onFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    file.value = target.files?.[0] ?? null;
}

function submitPreview(): void {
    if (!file.value) {
        return;
    }

    processing.value = true;

    router.post(
        importsTransactions.preview().url,
        { file: file.value },
        {
            forceFormData: true,
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

function confirmImport(): void {
    if (!props.token) {
        return;
    }

    processing.value = true;

    router.post(
        importsTransactions.store().url,
        { token: props.token },
        {
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Import transactions" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Import transactions"
            description="Upload a CSV or Excel file of income and expense transactions to preview and import"
        />

        <Card>
            <CardHeader>
                <CardTitle>Upload file</CardTitle>
            </CardHeader>
            <CardContent class="flex flex-col gap-4">
                <p class="text-sm text-muted-foreground">
                    Columns: Date (YYYY-MM-DD), Type (income or expense),
                    Description, Memo, Merchant, Account, Category, Amount,
                    Currency, Tags. Account and Category names must match
                    existing records exactly.
                </p>
                <div class="grid gap-2">
                    <Label for="import-file">CSV or Excel file</Label>
                    <Input
                        id="import-file"
                        type="file"
                        accept=".csv,.txt,.xlsx,.xls"
                        @change="onFileChange"
                    />
                </div>
                <div>
                    <Button
                        :disabled="!file || processing"
                        @click="submitPreview"
                    >
                        <Upload /> Preview
                    </Button>
                </div>
            </CardContent>
        </Card>

        <Card v-if="summary">
            <CardHeader>
                <CardTitle>Preview</CardTitle>
            </CardHeader>
            <CardContent class="flex flex-col gap-4">
                <div class="flex flex-wrap gap-2 text-sm">
                    <Badge variant="outline">{{ summary.total }} rows</Badge>
                    <Badge variant="secondary">{{ summary.valid }} valid</Badge>
                    <Badge v-if="summary.invalid > 0" variant="destructive">
                        {{ summary.invalid }} invalid
                    </Badge>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="p-2">Row</th>
                                <th class="p-2">Status</th>
                                <th class="p-2">Date</th>
                                <th class="p-2">Type</th>
                                <th class="p-2">Description</th>
                                <th class="p-2">Account</th>
                                <th class="p-2">Category</th>
                                <th class="p-2">Amount</th>
                                <th class="p-2">Issues</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in rows"
                                :key="row.row"
                                class="border-b last:border-0"
                            >
                                <td class="p-2">{{ row.row }}</td>
                                <td class="p-2">
                                    <Badge
                                        :variant="
                                            row.status === 'valid'
                                                ? 'secondary'
                                                : 'destructive'
                                        "
                                    >
                                        {{ row.status }}
                                    </Badge>
                                </td>
                                <td class="p-2">{{ row.preview.date }}</td>
                                <td class="p-2">{{ row.preview.type }}</td>
                                <td class="p-2">
                                    {{ row.preview.description }}
                                </td>
                                <td class="p-2">{{ row.preview.account }}</td>
                                <td class="p-2">
                                    {{ row.preview.category }}
                                </td>
                                <td class="p-2">{{ row.preview.amount }}</td>
                                <td class="p-2 text-red-600 dark:text-red-400">
                                    <span
                                        v-for="(error, index) in row.errors"
                                        :key="index"
                                        class="block"
                                    >
                                        {{ error }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div>
                    <Button
                        :disabled="summary.valid === 0 || processing"
                        @click="confirmImport"
                    >
                        Import {{ summary.valid }} valid rows
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
