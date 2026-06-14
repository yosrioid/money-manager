<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    Archive,
    ArrowDown,
    ArrowUp,
    EyeOff,
    Pencil,
    Plus,
    Star,
} from '@lucide/vue';
import AccountController from '@/actions/App/Http/Controllers/AccountController';
import AccountGroupController from '@/actions/App/Http/Controllers/AccountGroupController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/accounts';
import { create as createTransaction } from '@/routes/transactions';

interface AccountGroup {
    id: number;
    name: string;
    is_visible: boolean;
    accounts_count: number;
}

interface Account {
    id: number;
    name: string;
    type: string;
    currency_code: string;
    credit_limit: number | null;
    balance: number;
    statement_outstanding?: number;
    statement_closing_date?: string;
    payment_due_date?: string;
    is_visible: boolean;
    is_favorite: boolean;
    include_in_total: boolean;
    account_group: { id: number; name: string } | null;
}

defineProps<{
    accountGroups: AccountGroup[];
    accounts: Account[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Accounts', href: index() }],
    },
});

const typeLabel = (value: string): string =>
    value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');

const outstandingBalance = (balance: number): number =>
    balance < 0 ? -balance : 0;

const availableCredit = (account: Account): number | null =>
    account.credit_limit === null
        ? null
        : account.credit_limit - outstandingBalance(account.balance);
</script>

<template>
    <Head title="Accounts" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Accounts"
                description="Manage account groups, visibility, and financial accounts"
            />
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="createTransaction()"
                        ><Plus /> Add transaction</Link
                    >
                </Button>
                <Button as-child>
                    <Link :href="create()"><Plus /> Create account</Link>
                </Button>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Account groups</CardTitle>
                <CardDescription>
                    Group related accounts and control whether groups are
                    visible.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-5">
                <Form
                    v-bind="AccountGroupController.store.form()"
                    reset-on-success
                    class="flex flex-col gap-3 sm:flex-row"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid flex-1 gap-2">
                        <Label for="new-group-name">New group name</Label>
                        <Input
                            id="new-group-name"
                            name="name"
                            required
                            placeholder="Everyday accounts"
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <Button class="self-end" :disabled="processing">
                        Add group
                    </Button>
                </Form>

                <div v-if="accountGroups.length" class="grid gap-3">
                    <Form
                        v-for="group in accountGroups"
                        :key="group.id"
                        v-bind="AccountGroupController.update.form(group.id)"
                        class="grid gap-3 rounded-lg border p-4 md:grid-cols-[1fr_auto_auto]"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label :for="`group-${group.id}`">Group name</Label>
                            <Input
                                :id="`group-${group.id}`"
                                name="name"
                                :default-value="group.name"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <label
                            class="flex items-center gap-2 self-end pb-2 text-sm"
                        >
                            <input type="hidden" name="is_visible" value="0" />
                            <input
                                name="is_visible"
                                type="checkbox"
                                value="1"
                                :checked="group.is_visible"
                                class="size-4 rounded border-input"
                            />
                            Visible
                        </label>
                        <div class="flex items-end gap-2">
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                title="Move group up"
                                as-child
                            >
                                <Link
                                    :href="
                                        AccountGroupController.move(group.id, {
                                            query: { direction: 'up' },
                                        })
                                    "
                                    aria-label="Move group up"
                                    preserve-scroll
                                >
                                    <ArrowUp />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                title="Move group down"
                                as-child
                            >
                                <Link
                                    :href="
                                        AccountGroupController.move(group.id, {
                                            query: { direction: 'down' },
                                        })
                                    "
                                    aria-label="Move group down"
                                    preserve-scroll
                                >
                                    <ArrowDown />
                                </Link>
                            </Button>
                            <Button variant="outline" :disabled="processing">
                                Save
                            </Button>
                            <Button
                                variant="ghost"
                                title="Archive group"
                                as-child
                            >
                                <Link
                                    :href="
                                        AccountGroupController.destroy(group.id)
                                    "
                                >
                                    <Archive />
                                </Link>
                            </Button>
                        </div>
                        <p class="text-sm text-muted-foreground md:col-span-3">
                            {{ group.accounts_count }} accounts
                        </p>
                    </Form>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    No account groups yet. Accounts can also remain ungrouped.
                </p>
            </CardContent>
        </Card>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Financial accounts"
                description="Active accounts in the current workspace"
            />
            <div
                v-if="accounts.length"
                class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
            >
                <Card v-for="account in accounts" :key="account.id">
                    <CardHeader>
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <CardTitle class="flex items-center gap-2">
                                    {{ account.name }}
                                    <Star
                                        v-if="account.is_favorite"
                                        class="size-4 fill-current text-amber-500"
                                        aria-label="Favorite account"
                                    />
                                </CardTitle>
                                <CardDescription>
                                    {{
                                        account.account_group?.name ??
                                        'Ungrouped'
                                    }}
                                </CardDescription>
                            </div>
                            <EyeOff
                                v-if="!account.is_visible"
                                class="size-4 text-muted-foreground"
                                aria-label="Hidden account"
                            />
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex flex-wrap gap-2">
                            <Badge variant="secondary">
                                {{ typeLabel(account.type) }}
                            </Badge>
                            <Badge variant="outline">
                                {{ account.currency_code }}
                            </Badge>
                            <Badge
                                v-if="!account.include_in_total"
                                variant="outline"
                            >
                                Excluded from totals
                            </Badge>
                        </div>
                        <template v-if="account.type === 'credit_card'">
                            <p class="text-sm text-muted-foreground">
                                Outstanding balance:
                                <span class="font-medium text-foreground">
                                    {{ outstandingBalance(account.balance) }}
                                    {{ account.currency_code }}
                                </span>
                            </p>
                            <p
                                v-if="account.credit_limit !== null"
                                class="text-sm text-muted-foreground"
                            >
                                Available credit:
                                <span class="font-medium text-foreground">
                                    {{ availableCredit(account) }}
                                    {{ account.currency_code }}
                                </span>
                            </p>
                            <p
                                v-if="account.statement_outstanding !== undefined"
                                class="text-sm text-muted-foreground"
                            >
                                Statement outstanding (as of
                                {{ account.statement_closing_date }}):
                                <span class="font-medium text-foreground">
                                    {{ account.statement_outstanding }}
                                    {{ account.currency_code }}
                                </span>
                                <br />
                                Payment due {{ account.payment_due_date }}
                            </p>
                        </template>
                        <p v-else class="text-sm text-muted-foreground">
                            Ledger balance:
                            <span class="font-medium text-foreground">
                                {{ account.balance }}
                                {{ account.currency_code }}
                            </span>
                        </p>
                        <div class="flex gap-2">
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                title="Move account up"
                                as-child
                            >
                                <Link
                                    :href="
                                        AccountController.move(account.id, {
                                            query: { direction: 'up' },
                                        })
                                    "
                                    aria-label="Move account up"
                                    preserve-scroll
                                >
                                    <ArrowUp />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                title="Move account down"
                                as-child
                            >
                                <Link
                                    :href="
                                        AccountController.move(account.id, {
                                            query: { direction: 'down' },
                                        })
                                    "
                                    aria-label="Move account down"
                                    preserve-scroll
                                >
                                    <ArrowDown />
                                </Link>
                            </Button>
                            <Button variant="outline" size="sm" as-child>
                                <Link
                                    :href="AccountController.edit(account.id)"
                                >
                                    <Pencil /> Edit
                                </Link>
                            </Button>
                            <Form
                                v-bind="
                                    AccountController.destroy.form(account.id)
                                "
                                v-slot="{ processing }"
                            >
                                <Button
                                    type="submit"
                                    variant="ghost"
                                    size="sm"
                                    :disabled="processing"
                                >
                                    <Archive /> Archive
                                </Button>
                            </Form>
                        </div>
                    </CardContent>
                </Card>
            </div>
            <Card v-else>
                <CardContent class="py-8 text-center">
                    <p class="text-sm text-muted-foreground">
                        No accounts yet. Create the first account for this
                        workspace.
                    </p>
                </CardContent>
            </Card>
        </section>
    </div>
</template>
