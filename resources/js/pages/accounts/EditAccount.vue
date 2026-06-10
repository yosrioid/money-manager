<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AccountController from '@/actions/App/Http/Controllers/AccountController';
import AccountForm from '@/components/accounts/AccountForm.vue';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/accounts';

interface Account {
    id: number;
    name: string;
    type: string;
    currency_code: string;
    balance: number;
    account_group_id: number | null;
    description: string | null;
    is_visible: boolean;
    include_in_total: boolean;
}

defineProps<{
    account: Account;
    accountGroups: { id: number; name: string }[];
    currencies: { code: string; name: string; symbol: string }[];
    accountTypes: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Accounts', href: index() }],
    },
});
</script>

<template>
    <Head :title="`Edit ${account.name}`" />

    <div class="flex flex-col gap-6">
        <Heading
            :title="`Edit ${account.name}`"
            description="Update account details and visibility"
        />
        <AccountForm
            :form="AccountController.update.form(account.id)"
            :account="account"
            :account-groups="accountGroups"
            :currencies="currencies"
            :account-types="accountTypes"
            submit-label="Save account"
        />
    </div>
</template>
