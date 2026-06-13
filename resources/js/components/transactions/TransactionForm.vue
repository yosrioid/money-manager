<script setup lang="ts">
import { Form, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/accounts';
import * as transactionBookmarks from '@/routes/transaction-bookmarks';
import { create } from '@/routes/transactions';
import type { RouteFormDefinition } from '@/wayfinder';

interface Account {
    id: number;
    name: string;
    currency_code: string;
    is_favorite: boolean;
}

interface Category {
    id: number;
    name: string;
    type: string;
    is_favorite: boolean;
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

interface SplitRow {
    id: number;
    category_id?: number;
    amount?: string;
}

interface BookmarkRow {
    id: number;
    name: string;
    payload: TransactionDraftData;
}

const props = defineProps<{
    form: RouteFormDefinition<'post'>;
    draftForm: RouteFormDefinition<'post' | 'patch'>;
    accounts: Account[];
    categories: Category[];
    merchants: Merchant[];
    tags: Tag[];
    timezone: string;
    idempotencyKey: string;
    draftId: number | null;
    initialData: TransactionDraftData | null;
    bookmarks: BookmarkRow[];
    recentDescriptions: string[];
    recentMerchants: Merchant[];
    entryFormFields: string[];
}>();

const visibleOptionalFields = computed(() =>
    props.entryFormFields.filter((field) =>
        ['merchant', 'memo', 'tags'].includes(field),
    ),
);

const type = ref(props.initialData?.type ?? 'expense');
const sourceAccountId = ref(props.initialData?.account_id?.toString() ?? '');
const destinationAccountId = ref(
    props.initialData?.destination_account_id?.toString() ?? '',
);
const merchantId = ref(props.initialData?.merchant_id?.toString() ?? '');
const description = ref(props.initialData?.description ?? '');
const splitEnabled = ref(Boolean(props.initialData?.splits?.length));
const splitRows = ref<SplitRow[]>(
    props.initialData?.splits?.length
        ? props.initialData.splits.map((split, index) => ({
              id: index + 1,
              ...split,
          }))
        : [{ id: 1 }, { id: 2 }],
);

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
        const data = new FormData(form);

        if (props.draftForm.method === 'patch') {
            data.set('_method', 'patch');
        }

        router.post(props.draftForm.action, data, {
            forceFormData: true,
        });
    }
};

const bookmarkName = ref('');

const saveBookmark = (event: MouseEvent) => {
    const form = (event.currentTarget as HTMLButtonElement).form;

    if (form && bookmarkName.value.trim() !== '') {
        const data = new FormData(form);
        data.set('name', bookmarkName.value.trim());

        router.post(transactionBookmarks.store().url, data, {
            forceFormData: true,
            onSuccess: () => {
                bookmarkName.value = '';
            },
        });
    }
};

const renameDrafts = ref<Record<number, string>>(
    Object.fromEntries(
        props.bookmarks.map((bookmark) => [bookmark.id, bookmark.name]),
    ),
);

const renameBookmark = (bookmark: BookmarkRow) => {
    const name = renameDrafts.value[bookmark.id]?.trim();

    if (name && name !== bookmark.name) {
        router.patch(transactionBookmarks.update(bookmark.id).url, { name });
    }
};

const moveBookmark = (bookmark: BookmarkRow, direction: 'up' | 'down') => {
    router.patch(transactionBookmarks.move(bookmark.id).url, { direction });
};

const deleteBookmark = (bookmark: BookmarkRow) => {
    router.delete(transactionBookmarks.destroy(bookmark.id).url);
};
</script>

<template>
    <Form v-bind="form" class="space-y-6" v-slot="{ errors, processing }">
        <input type="hidden" name="idempotency_key" :value="idempotencyKey" />
        <input v-if="draftId" type="hidden" name="draft_id" :value="draftId" />
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
                        {{ account.is_favorite ? '★ ' : ''
                        }}{{ account.name }} ({{ account.currency_code }})
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
                    :value="initialData?.category_id ?? ''"
                >
                    <option
                        v-for="category in filteredCategories"
                        :key="category.id"
                        :value="category.id"
                    >
                        {{ category.is_favorite ? '★ ' : ''
                        }}{{ category.name }}
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
                        {{ account.is_favorite ? '★ ' : ''
                        }}{{ account.name }} ({{ account.currency_code }})
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
                    :value="initialData?.amount ?? ''"
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
                    :value="initialData?.fee_amount ?? ''"
                />
                <InputError :message="errors.fee_amount" />
            </div>

            <div v-if="type === 'transfer'" class="grid gap-2">
                <Label for="fee_category_id">Transfer fee category</Label>
                <select
                    id="fee_category_id"
                    name="fee_category_id"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                    :value="initialData?.fee_category_id ?? ''"
                >
                    <option value="">No fee</option>
                    <option
                        v-for="category in expenseCategories"
                        :key="category.id"
                        :value="category.id"
                    >
                        {{ category.is_favorite ? '★ ' : ''
                        }}{{ category.name }}
                    </option>
                </select>
                <InputError :message="errors.fee_category_id" />
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
                    :value="initialData?.occurred_at ?? ''"
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
                        :value="split.category_id ?? ''"
                    >
                        <option value="" disabled>Select category</option>
                        <option
                            v-for="category in filteredCategories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.is_favorite ? '★ ' : ''
                            }}{{ category.name }}
                        </option>
                    </select>
                    <Input
                        :name="`splits[${index}][amount]`"
                        type="text"
                        inputmode="numeric"
                        placeholder="5000 * 2"
                        required
                        :value="split.amount ?? ''"
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
                v-model="description"
                rows="3"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                placeholder="What was this transaction for?"
                required
            />
            <div
                v-if="recentDescriptions.length"
                class="flex flex-wrap items-center gap-2"
            >
                <span class="text-xs text-muted-foreground">Recent:</span>
                <Button
                    v-for="recentDescription in recentDescriptions"
                    :key="recentDescription"
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="description = recentDescription"
                >
                    {{ recentDescription }}
                </Button>
            </div>
            <InputError :message="errors.description" />
        </div>

        <template v-for="field in visibleOptionalFields" :key="field">
            <div
                v-if="field === 'merchant' && type !== 'transfer'"
                class="grid gap-2"
            >
                <Label for="merchant_id">Merchant or recipient</Label>
                <select
                    id="merchant_id"
                    name="merchant_id"
                    v-model="merchantId"
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
                <div
                    v-if="recentMerchants.length"
                    class="flex flex-wrap items-center gap-2"
                >
                    <span class="text-xs text-muted-foreground">Recent:</span>
                    <Button
                        v-for="merchant in recentMerchants"
                        :key="merchant.id"
                        type="button"
                        size="sm"
                        variant="outline"
                        @click="merchantId = merchant.id.toString()"
                    >
                        {{ merchant.name }}
                    </Button>
                </div>
                <InputError :message="errors.merchant_id" />
            </div>

            <div v-else-if="field === 'memo'" class="grid gap-2">
                <Label for="memo">Memo and notes</Label>
                <textarea
                    id="memo"
                    name="memo"
                    rows="4"
                    maxlength="2000"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                    placeholder="Optional details about this transaction"
                    :value="initialData?.memo ?? ''"
                />
                <InputError :message="errors.memo" />
            </div>

            <fieldset
                v-else-if="field === 'tags' && tags.length"
                class="grid gap-3"
            >
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
                            :checked="initialData?.tag_ids?.includes(tag.id)"
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
        </template>

        <fieldset class="grid gap-3 rounded-md border p-4">
            <legend class="text-sm font-medium">Bookmarks</legend>

            <div v-if="bookmarks.length" class="grid gap-2">
                <div
                    v-for="(bookmark, bookmarkIndex) in bookmarks"
                    :key="bookmark.id"
                    class="flex flex-wrap items-center gap-2"
                >
                    <Button size="sm" variant="outline" as-child>
                        <Link
                            :href="
                                create({ query: { bookmark_id: bookmark.id } })
                            "
                        >
                            Use
                        </Link>
                    </Button>
                    <Input
                        v-model="renameDrafts[bookmark.id]"
                        class="h-8 max-w-48"
                    />
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        @click="renameBookmark(bookmark)"
                    >
                        Rename
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        :disabled="bookmarkIndex === 0"
                        @click="moveBookmark(bookmark, 'up')"
                    >
                        <ArrowUp />
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        :disabled="bookmarkIndex === bookmarks.length - 1"
                        @click="moveBookmark(bookmark, 'down')"
                    >
                        <ArrowDown />
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        @click="deleteBookmark(bookmark)"
                    >
                        <Trash2 />
                    </Button>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                No bookmarks yet. Save the current transaction below to reuse it
                later.
            </p>

            <div class="flex flex-wrap items-end gap-2">
                <div class="grid gap-2">
                    <Label for="bookmark_name">Save as bookmark</Label>
                    <Input
                        id="bookmark_name"
                        v-model="bookmarkName"
                        class="h-9 max-w-48"
                        placeholder="Bookmark name"
                    />
                </div>
                <Button type="button" variant="outline" @click="saveBookmark">
                    Save as bookmark
                </Button>
            </div>
        </fieldset>

        <div class="flex items-center gap-3">
            <Button :disabled="processing">Save transaction</Button>
            <Button
                type="button"
                variant="outline"
                :disabled="processing"
                @click="saveDraft"
            >
                {{ draftId ? 'Update draft' : 'Save draft' }}
            </Button>
            <Button variant="outline" as-child>
                <Link :href="index()">Cancel</Link>
            </Button>
        </div>
    </Form>
</template>
