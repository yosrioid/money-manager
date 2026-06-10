<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AccountController from '@/actions/App/Http/Controllers/AccountController';
import AccountForm from '@/components/accounts/AccountForm.vue';
import Heading from '@/components/Heading.vue';
import { create } from '@/routes/accounts';

defineProps<{
    accountGroups: { id: number; name: string }[];
    currencies: { code: string; name: string; symbol: string }[];
    accountTypes: { value: string; label: string }[];
    defaultCurrency: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Create account', href: create() }],
    },
});
</script>

<template>
    <Head title="Create account" />

    <div class="flex flex-col gap-6">
        <Heading
            title="Create account"
            description="Add a financial account to your active workspace"
        />
        <AccountForm
            :form="AccountController.store.form()"
            :account-groups="accountGroups"
            :currencies="currencies"
            :account-types="accountTypes"
            :default-currency="defaultCurrency"
            submit-label="Create account"
        />
    </div>
</template>
