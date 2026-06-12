<script setup lang="ts">
import { Form, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/accounts';
import { store as storeDraft } from '@/routes/transactions/drafts';
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

interface Merchant {
    id: number;
    name: string;
}

interface Tag {
    id: number;
    name: string;
    color: string | null;
}

const props = defineProps<{
    form: RouteFormDefinition<'post'>;
    accounts: Account[];
    categories: Category[];
    merchants: Merchant[];
    tags: Tag[];
    timezone: string;
    idempotencyKey: string;
}>();

const type = ref('expense');
const sourceAccountId = ref('');
const destinationAccountId = ref('');
const splitEnabled = ref(false);
const splitRows = ref([{ id: 1 }, { id: 2 }]);

const filteredCategories = computed(() =>
    props.categories.filter((category) => category.type === type.value),
);

const expenseCategories = computed(() =>
    props.categories.filter((category) => category.type === 'expense'),
);

const destinationAccounts = computed(() =>
    props.accounts.filter(
        (account) => account.id.toString() !== sourceAccountId.value,
    ),
);

watch(sourceAccountId, (accountId) => {
    if (destinationAccountId.value === accountId) {
        destinationAccountId.value = '';
    }
});

const addSplit = () => {
    splitRows.value.push({ id: Date.now() });
};

const removeSplit = (id: number) => {
    if (splitRows.value.length > 2) {
        splitRows.value = splitRows.value.filter((split) => split.id !== id);
    }
};

const saveDraft = (event: MouseEvent) => {
    const form = (event.currentTarget as HTMLButtonElement).form;

    if (form) {
        router.post(storeDraft.url(), new FormData(form));
    }
};
</script>

<template>
    <Form v-bind="form" class="space-y-6" v-slot="{ errors, processing }">
        <input type="hidden" name="idempotency_key" :value="idempotencyKey" />
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
                    <option value="transfer">Transfer</option>
                </select>
                <InputError :message="errors.type" />
            </div>

            <div class="grid gap-2">
                <Label for="account_id">
                    {{ type === 'transfer' ? 'Source account' : 'Account' }}
                </Label>
                <select
                    id="account_id"
                    name="account_id"
                    v-model="sourceAccountId"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                    required
                >
                    <option value="" disabled>Select an account</option>
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

            <div v-if="type !== 'transfer' && !splitEnabled" class="grid gap-2">
                <Label for="category_id">Category</Label>
                <select
                    id="category_id"
                    name="category_id"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                    :required="!splitEnabled"
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

            <div v-if="type === 'transfer'" class="grid gap-2">
                <Label for="destination_account_id">Destination account</Label>
                <select
                    id="destination_account_id"
                    name="destination_account_id"
                    v-model="destinationAccountId"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                    required
                >
                    <option value="" disabled>
                        Select a destination account
                    </option>
                    <option
                        v-for="account in destinationAccounts"
                        :key="account.id"
                        :value="account.id"
                    >
                        {{ account.name }} ({{ account.currency_code }})
                    </option>
                </select>
                <InputError :message="errors.destination_account_id" />
            </div>

            <div class="grid gap-2">
                <Label for="amount">Amount or calculation in minor units</Label>
                <Input
                    id="amount"
                    name="amount"
                    type="text"
                    inputmode="numeric"
                    placeholder="10000 + 5000"
                    required
                />
                <InputError :message="errors.amount" />
            </div>

            <div v-if="type === 'transfer'" class="grid gap-2">
                <Label for="fee_amount">Transfer fee in minor units</Label>
                <Input
                    id="fee_amount"
                    name="fee_amount"
                    type="number"
                    min="0"
                    placeholder="0"
                />
                <InputError :message="errors.fee_amount" />
            </div>

            <div v-if="type === 'transfer'" class="grid gap-2">
                <Label for="fee_category_id">Transfer fee category</Label>
                <select
                    id="fee_category_id"
                    name="fee_category_id"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                >
                    <option value="">No fee</option>
                    <option
                        v-for="category in expenseCategories"
                        :key="category.id"
                        :value="category.id"
                    >
                        {{ category.name }}
                    </option>
                </select>
                <InputError :message="errors.fee_category_id" />
            </div>

            <div v-if="type !== 'transfer'" class="grid gap-2">
                <Label for="merchant_id">Merchant or recipient</Label>
                <select
                    id="merchant_id"
                    name="merchant_id"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                >
                    <option value="">None</option>
                    <option
                        v-for="merchant in merchants"
                        :key="merchant.id"
                        :value="merchant.id"
                    >
                        {{ merchant.name }}
                    </option>
                </select>
                <InputError :message="errors.merchant_id" />
            </div>

            <div class="grid gap-2">
                <Label for="occurred_at">
                    Date and time ({{ timezone }})
                </Label>
                <Input
                    id="occurred_at"
                    name="occurred_at"
                    type="datetime-local"
                    required
                />
                <InputError :message="errors.occurred_at" />
            </div>
        </div>

        <div v-if="type !== 'transfer'" class="grid gap-4">
            <label class="flex items-center gap-2 text-sm font-medium">
                <input
                    v-model="splitEnabled"
                    type="checkbox"
                    class="size-4 rounded border-input"
                />
                Split across categories
            </label>

            <div v-if="splitEnabled" class="grid gap-3 rounded-md border p-4">
                <div
                    v-for="(split, index) in splitRows"
                    :key="split.id"
                    class="grid gap-3 md:grid-cols-[1fr_1fr_auto]"
                >
                    <select
                        :name="`splits[${index}][category_id]`"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                        required
                    >
                        <option value="" disabled selected>
                            Select category
                        </option>
                        <option
                            v-for="category in filteredCategories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                    <Input
                        :name="`splits[${index}][amount]`"
                        type="text"
                        inputmode="numeric"
                        placeholder="5000 * 2"
                        required
                    />
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="splitRows.length <= 2"
                        @click="removeSplit(split.id)"
                    >
                        Remove
                    </Button>
                </div>
                <InputError :message="errors.splits" />
                <Button type="button" variant="outline" @click="addSplit">
                    Add split
                </Button>
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

        <div class="grid gap-2">
            <Label for="memo">Memo and notes</Label>
            <textarea
                id="memo"
                name="memo"
                rows="4"
                maxlength="2000"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                placeholder="Optional details about this transaction"
            />
            <InputError :message="errors.memo" />
        </div>

        <fieldset v-if="tags.length" class="grid gap-3">
            <legend class="text-sm font-medium">Tags</legend>
            <div class="flex flex-wrap gap-3">
                <label
                    v-for="tag in tags"
                    :key="tag.id"
                    class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm"
                >
                    <input
                        type="checkbox"
                        name="tag_ids[]"
                        :value="tag.id"
                        class="size-4 rounded border-input"
                    />
                    <span
                        v-if="tag.color"
                        class="size-2.5 rounded-full"
                        :style="{ backgroundColor: tag.color }"
                    />
                    {{ tag.name }}
                </label>
            </div>
            <InputError :message="errors.tag_ids" />
        </fieldset>

        <div class="flex items-center gap-3">
            <Button :disabled="processing">Save transaction</Button>
            <Button
                type="button"
                variant="outline"
                :disabled="processing"
                @click="saveDraft"
            >
                Save draft
            </Button>
            <Button variant="outline" as-child>
                <Link :href="index()">Cancel</Link>
            </Button>
        </div>
    </Form>
</template>
