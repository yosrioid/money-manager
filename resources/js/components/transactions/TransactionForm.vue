<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/accounts';
import type { RouteFormDefinition } from '@/wayfinder';

interface Account {
    id: number;
    name: string;
    currency_code: string;
}

interface Category {
    id: number;
    name: string;
    type: string;
}

const props = defineProps<{
    form: RouteFormDefinition<'post'>;
    accounts: Account[];
    categories: Category[];
}>();

const type = ref('expense');

const filteredCategories = computed(() =>
    props.categories.filter((category) => category.type === type.value),
);
</script>

<template>
    <Form v-bind="form" class="space-y-6" v-slot="{ errors, processing }">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="grid gap-2">
                <Label for="type">Type</Label>
                <select
                    id="type"
                    name="type"
                    v-model="type"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                    required
                >
                    <option value="expense">Expense</option>
                    <option value="income">Income</option>
                </select>
                <InputError :message="errors.type" />
            </div>

            <div class="grid gap-2">
                <Label for="account_id">Account</Label>
                <select
                    id="account_id"
                    name="account_id"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                    required
                >
                    <option
                        v-for="account in accounts"
                        :key="account.id"
                        :value="account.id"
                    >
                        {{ account.name }} ({{ account.currency_code }})
                    </option>
                </select>
                <InputError :message="errors.account_id" />
            </div>

            <div class="grid gap-2">
                <Label for="category_id">Category</Label>
                <select
                    id="category_id"
                    name="category_id"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                    required
                >
                    <option
                        v-for="category in filteredCategories"
                        :key="category.id"
                        :value="category.id"
                    >
                        {{ category.name }}
                    </option>
                </select>
                <InputError :message="errors.category_id" />
            </div>

            <div class="grid gap-2">
                <Label for="amount">Amount in minor units</Label>
                <Input id="amount" name="amount" type="number" min="1" required />
                <InputError :message="errors.amount" />
            </div>

            <div class="grid gap-2">
                <Label for="occurred_at">Date and time</Label>
                <Input
                    id="occurred_at"
                    name="occurred_at"
                    type="datetime-local"
                    required
                />
                <InputError :message="errors.occurred_at" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="description">Description</Label>
            <textarea
                id="description"
                name="description"
                rows="3"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                placeholder="What was this transaction for?"
                required
            />
            <InputError :message="errors.description" />
        </div>

        <div class="flex items-center gap-3">
            <Button :disabled="processing">Save transaction</Button>
            <Button variant="outline" as-child>
                <Link :href="index()">Cancel</Link>
            </Button>
        </div>
    </Form>
</template>
