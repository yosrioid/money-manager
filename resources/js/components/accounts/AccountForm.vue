<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/accounts';
import type { RouteFormDefinition } from '@/wayfinder';

interface AccountData {
    name: string;
    type: string;
    currency_code: string;
    balance: number;
    account_group_id: number | null;
    description: string | null;
    is_visible: boolean;
    include_in_total: boolean;
}

interface Option {
    value: string;
    label: string;
}

interface Currency {
    code: string;
    name: string;
    symbol: string;
}

interface AccountGroup {
    id: number;
    name: string;
}

defineProps<{
    form: RouteFormDefinition<'post'>;
    account?: AccountData;
    accountGroups: AccountGroup[];
    currencies: Currency[];
    accountTypes: Option[];
    defaultCurrency?: string;
    submitLabel: string;
}>();
</script>

<template>
    <Form v-bind="form" class="space-y-6" v-slot="{ errors, processing }">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="grid gap-2">
                <Label for="name">Account name</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="account?.name"
                    required
                    placeholder="Main bank account"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="account_group_id">Account group</Label>
                <select
                    id="account_group_id"
                    name="account_group_id"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                >
                    <option value="">No group</option>
                    <option
                        v-for="group in accountGroups"
                        :key="group.id"
                        :value="group.id"
                        :selected="group.id === account?.account_group_id"
                    >
                        {{ group.name }}
                    </option>
                </select>
                <InputError :message="errors.account_group_id" />
            </div>

            <div class="grid gap-2">
                <Label for="type">Account type</Label>
                <select
                    id="type"
                    name="type"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                    required
                >
                    <option
                        v-for="type in accountTypes"
                        :key="type.value"
                        :value="type.value"
                        :selected="type.value === account?.type"
                    >
                        {{ type.label }}
                    </option>
                </select>
                <InputError :message="errors.type" />
            </div>

            <div class="grid gap-2">
                <Label for="currency_code">Currency</Label>
                <select
                    id="currency_code"
                    name="currency_code"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                    required
                >
                    <option
                        v-for="currency in currencies"
                        :key="currency.code"
                        :value="currency.code"
                        :selected="
                            currency.code ===
                            (account?.currency_code ?? defaultCurrency)
                        "
                    >
                        {{ currency.code }} - {{ currency.name }} ({{
                            currency.symbol
                        }})
                    </option>
                </select>
                <InputError :message="errors.currency_code" />
            </div>
        </div>

        <div v-if="!account" class="grid gap-2">
            <Label for="opening_balance">Opening balance in minor units</Label>
            <Input
                id="opening_balance"
                name="opening_balance"
                type="number"
                :default-value="0"
                required
            />
            <p class="text-sm text-muted-foreground">
                This value is posted as an immutable balanced ledger transaction
                when the account is created.
            </p>
            <InputError :message="errors.opening_balance" />
        </div>
        <div v-else class="grid gap-2">
            <Label>Current ledger balance</Label>
            <p class="text-sm font-medium">
                {{ account.balance }} {{ account.currency_code }}
            </p>
            <p class="text-sm text-muted-foreground">
                Opening balances and posted entries cannot be edited directly.
            </p>
        </div>

        <div class="grid gap-2">
            <Label for="description">Description</Label>
            <textarea
                id="description"
                name="description"
                rows="3"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                placeholder="Optional notes about this account"
                :value="account?.description ?? ''"
            />
            <InputError :message="errors.description" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="flex items-center gap-3 text-sm">
                <input type="hidden" name="is_visible" value="0" />
                <input
                    name="is_visible"
                    type="checkbox"
                    value="1"
                    :checked="account?.is_visible ?? true"
                    class="size-4 rounded border-input"
                />
                Show in account selection
            </label>
            <label class="flex items-center gap-3 text-sm">
                <input type="hidden" name="include_in_total" value="0" />
                <input
                    name="include_in_total"
                    type="checkbox"
                    value="1"
                    :checked="account?.include_in_total ?? true"
                    class="size-4 rounded border-input"
                />
                Include in summary totals
            </label>
        </div>

        <div class="flex items-center gap-3">
            <Button :disabled="processing">{{ submitLabel }}</Button>
            <Button variant="outline" as-child>
                <Link :href="index()">Cancel</Link>
            </Button>
        </div>
    </Form>
</template>
