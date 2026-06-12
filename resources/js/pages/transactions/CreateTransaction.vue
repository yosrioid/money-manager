<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TransactionController from '@/actions/App/Http/Controllers/TransactionController';
import Heading from '@/components/Heading.vue';
import TransactionForm from '@/components/transactions/TransactionForm.vue';
import { create } from '@/routes/transactions';

interface TransactionDraftData {
    type?: string;
    account_id?: number;
    category_id?: number;
    destination_account_id?: number;
    fee_amount?: string;
    fee_category_id?: number;
    amount?: string;
    description?: string;
    merchant_id?: number;
    memo?: string;
    tag_ids?: number[];
    occurred_at?: string;
    splits?: { category_id?: number; amount?: string }[];
}

const props = defineProps<{
    accounts: { id: number; name: string; currency_code: string }[];
    categories: { id: number; name: string; type: string }[];
    merchants: { id: number; name: string }[];
    tags: { id: number; name: string; color: string | null }[];
    timezone: string;
    idempotencyKey: string;
    draftId: number | null;
    initialData: TransactionDraftData | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Add transaction', href: create() }],
    },
});
</script>

<template>
    <Head
        :title="props.draftId ? 'Resume transaction draft' : 'Add transaction'"
    />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            :title="
                props.draftId ? 'Resume transaction draft' : 'Add transaction'
            "
            :description="
                props.draftId
                    ? 'Review the saved values, then update or post this transaction'
                    : 'Record income, an expense, or a transfer between accounts'
            "
        />
        <TransactionForm
            :form="TransactionController.store.form()"
            :draft-form="
                props.draftId
                    ? TransactionController.updateDraft.form(props.draftId)
                    : TransactionController.storeDraft.form()
            "
            :accounts="accounts"
            :categories="categories"
            :merchants="merchants"
            :tags="tags"
            :timezone="timezone"
            :idempotency-key="idempotencyKey"
            :draft-id="props.draftId"
            :initial-data="props.initialData"
        />
    </div>
</template>
