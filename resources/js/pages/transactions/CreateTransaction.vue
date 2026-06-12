<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TransactionController from '@/actions/App/Http/Controllers/TransactionController';
import Heading from '@/components/Heading.vue';
import TransactionForm from '@/components/transactions/TransactionForm.vue';
import { create } from '@/routes/transactions';

defineProps<{
    accounts: { id: number; name: string; currency_code: string }[];
    categories: { id: number; name: string; type: string }[];
    merchants: { id: number; name: string }[];
    tags: { id: number; name: string; color: string | null }[];
    timezone: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Add transaction', href: create() }],
    },
});
</script>

<template>
    <Head title="Add transaction" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Add transaction"
            description="Record income, an expense, or a transfer between accounts"
        />
        <TransactionForm
            :form="TransactionController.store.form()"
            :accounts="accounts"
            :categories="categories"
            :merchants="merchants"
            :tags="tags"
            :timezone="timezone"
        />
    </div>
</template>
