<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp } from '@lucide/vue';
import { ref } from 'vue';
import WorkspaceController from '@/actions/App/Http/Controllers/Settings/WorkspaceController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/workspace';

interface Currency {
    code: string;
    name: string;
    symbol: string;
}

interface ExchangeRate {
    currency_code: string;
    rate_to_base: string;
}

interface WorkspaceData {
    id: number;
    name: string;
    default_currency: string;
    timezone: string;
    locale: string;
    number_format: string;
    first_day_of_week: number;
    month_start_day: number;
    adjust_month_for_weekend: boolean;
    application_lock_minutes: number;
    navigation_shortcuts_enabled: boolean;
    net_asset_target: number | null;
}

const props = defineProps<{
    workspace: WorkspaceData;
    currencies: Currency[];
    entryFormFields: string[];
    reportWidgets: string[];
    exchangeRates: ExchangeRate[];
    accountCurrencies: string[];
}>();

const rateFor = (currencyCode: string): string =>
    props.exchangeRates.find((rate) => rate.currency_code === currencyCode)
        ?.rate_to_base ?? '';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Workspace settings',
                href: edit(),
            },
        ],
    },
});

const localeOptions = [
    { value: 'id', label: 'Indonesian (id)' },
    { value: 'en', label: 'English (en)' },
];

const numberFormatOptions = [
    { value: 'id-ID', label: '1.000.000,00 (id-ID)' },
    { value: 'en-US', label: '1,000,000.00 (en-US)' },
    { value: 'en-GB', label: '1,000,000.00 (en-GB)' },
];

const weekdayOptions = [
    { value: 0, label: 'Sunday' },
    { value: 1, label: 'Monday' },
];

const applicationLockOptions = [
    { value: 0, label: 'Disabled' },
    { value: 5, label: 'After 5 minutes' },
    { value: 15, label: 'After 15 minutes' },
    { value: 30, label: 'After 30 minutes' },
    { value: 60, label: 'After 1 hour' },
];

const entryFormFieldLabels: Record<string, string> = {
    merchant: 'Merchant or recipient',
    memo: 'Memo and notes',
    tags: 'Tags',
};

const allEntryFormFieldKeys = ['merchant', 'memo', 'tags'];

const entryFormFieldRows = ref(
    allEntryFormFieldKeys
        .slice()
        .sort((a, b) => {
            const indexA = props.entryFormFields.indexOf(a);
            const indexB = props.entryFormFields.indexOf(b);

            if (indexA === -1 && indexB === -1) {
                return 0;
            }

            if (indexA === -1) {
                return 1;
            }

            if (indexB === -1) {
                return -1;
            }

            return indexA - indexB;
        })
        .map((key) => ({
            key,
            visible: props.entryFormFields.includes(key),
        })),
);

const visibleEntryFormFields = () =>
    entryFormFieldRows.value.filter((row) => row.visible).map((row) => row.key);

const reportWidgetLabels: Record<string, string> = {
    summary: 'Income, expense, and net summary',
    comparison: 'Comparison with previous period',
    categoryBreakdown: 'Expense and income by category',
    merchantBreakdown: 'Spending by merchant',
    accountActivity: 'Account activity',
    netWorth: 'Assets, liabilities, and net worth',
    netWorthTrend: 'Net worth trend',
};

const allReportWidgetKeys = [
    'summary',
    'comparison',
    'categoryBreakdown',
    'merchantBreakdown',
    'accountActivity',
    'netWorth',
    'netWorthTrend',
];

const reportWidgetRows = ref(
    allReportWidgetKeys.map((key) => ({
        key,
        visible: props.reportWidgets.includes(key),
    })),
);

const visibleReportWidgets = () =>
    reportWidgetRows.value.filter((row) => row.visible).map((row) => row.key);

const moveEntryFormField = (index: number, direction: 'up' | 'down') => {
    const targetIndex = direction === 'up' ? index - 1 : index + 1;

    if (targetIndex < 0 || targetIndex >= entryFormFieldRows.value.length) {
        return;
    }

    const rows = entryFormFieldRows.value;
    [rows[index], rows[targetIndex]] = [rows[targetIndex], rows[index]];
};
</script>

<template>
    <Head title="Workspace settings" />

    <h1 class="sr-only">Workspace settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Workspace"
            description="Configure your workspace name and financial preferences"
        />

        <Form
            v-bind="WorkspaceController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Workspace name</Label>
                <Input
                    id="name"
                    name="name"
                    class="mt-1 block w-full"
                    :default-value="workspace.name"
                    required
                    placeholder="My Workspace"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="default_currency">Default currency</Label>
                <select
                    id="default_currency"
                    name="default_currency"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                >
                    <option
                        v-for="currency in currencies"
                        :key="currency.code"
                        :value="currency.code"
                        :selected="currency.code === workspace.default_currency"
                    >
                        {{ currency.code }} — {{ currency.name }} ({{
                            currency.symbol
                        }})
                    </option>
                </select>
                <InputError class="mt-2" :message="errors.default_currency" />
            </div>

            <div class="grid gap-2">
                <Label for="timezone">Timezone</Label>
                <Input
                    id="timezone"
                    name="timezone"
                    class="mt-1 block w-full"
                    :default-value="workspace.timezone"
                    required
                    placeholder="Asia/Jakarta"
                />
                <InputError class="mt-2" :message="errors.timezone" />
            </div>

            <div class="grid gap-2">
                <Label for="locale">Locale</Label>
                <select
                    id="locale"
                    name="locale"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                >
                    <option
                        v-for="option in localeOptions"
                        :key="option.value"
                        :value="option.value"
                        :selected="option.value === workspace.locale"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <InputError class="mt-2" :message="errors.locale" />
            </div>

            <div class="grid gap-2">
                <Label for="number_format">Number format</Label>
                <select
                    id="number_format"
                    name="number_format"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                >
                    <option
                        v-for="option in numberFormatOptions"
                        :key="option.value"
                        :value="option.value"
                        :selected="option.value === workspace.number_format"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <InputError class="mt-2" :message="errors.number_format" />
            </div>

            <div class="grid gap-2">
                <Label for="first_day_of_week">First day of week</Label>
                <select
                    id="first_day_of_week"
                    name="first_day_of_week"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                >
                    <option
                        v-for="option in weekdayOptions"
                        :key="option.value"
                        :value="option.value"
                        :selected="option.value === workspace.first_day_of_week"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <InputError class="mt-2" :message="errors.first_day_of_week" />
            </div>

            <div class="grid gap-2">
                <Label for="month_start_day">Month start day</Label>
                <Input
                    id="month_start_day"
                    name="month_start_day"
                    type="number"
                    class="mt-1 block w-full"
                    :default-value="workspace.month_start_day"
                    min="1"
                    max="28"
                    required
                />
                <InputError class="mt-2" :message="errors.month_start_day" />
            </div>

            <div class="flex items-center gap-2">
                <input
                    id="adjust_month_for_weekend"
                    name="adjust_month_for_weekend"
                    type="checkbox"
                    :checked="workspace.adjust_month_for_weekend"
                    value="1"
                    class="h-4 w-4 rounded border-gray-300"
                />
                <Label for="adjust_month_for_weekend"
                    >Adjust month start for weekends</Label
                >
                <InputError
                    class="mt-2"
                    :message="errors.adjust_month_for_weekend"
                />
            </div>

            <div class="grid gap-2">
                <Label for="application_lock_minutes">Application lock</Label>
                <select
                    id="application_lock_minutes"
                    name="application_lock_minutes"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:outline-none"
                >
                    <option
                        v-for="option in applicationLockOptions"
                        :key="option.value"
                        :value="option.value"
                        :selected="
                            option.value === workspace.application_lock_minutes
                        "
                    >
                        {{ option.label }}
                    </option>
                </select>
                <p class="text-sm text-muted-foreground">
                    Require password confirmation after this workspace is
                    inactive.
                </p>
                <InputError
                    class="mt-2"
                    :message="errors.application_lock_minutes"
                />
            </div>

            <div class="grid gap-3">
                <Label>Transaction entry form fields</Label>
                <p class="text-sm text-muted-foreground">
                    Choose which optional fields appear on the transaction entry
                    form and in what order.
                </p>
                <div class="grid gap-2">
                    <div
                        v-for="(row, index) in entryFormFieldRows"
                        :key="row.key"
                        class="flex items-center gap-3 rounded-md border p-3"
                    >
                        <label class="flex flex-1 items-center gap-2 text-sm">
                            <input
                                v-model="row.visible"
                                type="checkbox"
                                class="size-4 rounded border-input"
                            />
                            {{ entryFormFieldLabels[row.key] }}
                        </label>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            title="Move up"
                            :disabled="index === 0"
                            @click="moveEntryFormField(index, 'up')"
                        >
                            <ArrowUp />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            title="Move down"
                            :disabled="index === entryFormFieldRows.length - 1"
                            @click="moveEntryFormField(index, 'down')"
                        >
                            <ArrowDown />
                        </Button>
                    </div>
                </div>
                <input
                    v-for="field in visibleEntryFormFields()"
                    :key="field"
                    type="hidden"
                    name="entry_form_fields[]"
                    :value="field"
                />
                <InputError class="mt-2" :message="errors.entry_form_fields" />
            </div>

            <div class="grid gap-3">
                <Label>Report cards and charts</Label>
                <p class="text-sm text-muted-foreground">
                    Choose which summary cards and charts appear on the Reports
                    page.
                </p>
                <div class="grid gap-2">
                    <label
                        v-for="row in reportWidgetRows"
                        :key="row.key"
                        class="flex items-center gap-2 rounded-md border p-3 text-sm"
                    >
                        <input
                            v-model="row.visible"
                            type="checkbox"
                            class="size-4 rounded border-input"
                        />
                        {{ reportWidgetLabels[row.key] }}
                    </label>
                </div>
                <input
                    v-for="widget in visibleReportWidgets()"
                    :key="widget"
                    type="hidden"
                    name="report_widgets[]"
                    :value="widget"
                />
                <InputError class="mt-2" :message="errors.report_widgets" />
            </div>

            <div class="flex items-center gap-2">
                <input
                    type="hidden"
                    name="navigation_shortcuts_enabled"
                    value="0"
                />
                <input
                    id="navigation_shortcuts_enabled"
                    name="navigation_shortcuts_enabled"
                    type="checkbox"
                    :checked="workspace.navigation_shortcuts_enabled"
                    value="1"
                    class="h-4 w-4 rounded border-gray-300"
                />
                <Label for="navigation_shortcuts_enabled"
                    >Enable swipe and arrow-key navigation between
                    periods</Label
                >
                <InputError
                    class="mt-2"
                    :message="errors.navigation_shortcuts_enabled"
                />
            </div>

            <div class="grid gap-2">
                <Label for="net_asset_target">Net asset target</Label>
                <Input
                    id="net_asset_target"
                    name="net_asset_target"
                    type="number"
                    min="0"
                    step="1"
                    class="mt-1 block w-full"
                    :default-value="workspace.net_asset_target ?? ''"
                    placeholder="Optional"
                />
                <p class="text-sm text-muted-foreground">
                    Set a target total balance, in your default currency, to
                    monitor on the summary page.
                </p>
                <InputError class="mt-2" :message="errors.net_asset_target" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">Save workspace settings</Button>
            </div>
        </Form>

        <Heading
            v-if="accountCurrencies.length"
            variant="small"
            title="Exchange rates"
            description="Set how many units of your default currency equal one unit of each other currency used by your accounts, so net asset totals can be converted consistently."
        />

        <Form
            v-if="accountCurrencies.length"
            v-bind="WorkspaceController.updateExchangeRates.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div
                v-for="(currencyCode, index) in accountCurrencies"
                :key="currencyCode"
                class="grid gap-2"
            >
                <Label :for="`exchange_rate_${currencyCode}`">
                    1 {{ currencyCode }} = ? {{ workspace.default_currency }}
                </Label>
                <input
                    type="hidden"
                    :name="`exchange_rates[${index}][currency_code]`"
                    :value="currencyCode"
                />
                <Input
                    :id="`exchange_rate_${currencyCode}`"
                    :name="`exchange_rates[${index}][rate_to_base]`"
                    type="text"
                    inputmode="decimal"
                    placeholder="1.0000000000"
                    class="mt-1 block w-full"
                    :default-value="rateFor(currencyCode)"
                />
                <InputError
                    class="mt-2"
                    :message="errors[`exchange_rates.${index}.rate_to_base`]"
                />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">Save exchange rates</Button>
            </div>
        </Form>
    </div>
</template>
