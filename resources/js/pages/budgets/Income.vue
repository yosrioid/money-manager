<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, X } from '@lucide/vue';
import { computed } from 'vue';
import BudgetController from '@/actions/App/Http/Controllers/BudgetController';
import BudgetViewNav from '@/components/BudgetViewNav.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { usePeriodNavigation } from '@/composables/usePeriodNavigation';
import { income, index } from '@/routes/budgets';

interface BudgetRow {
    category_id: number;
    name: string;
    default_budget: number | null;
    override: number | null;
    carryover: number | null;
    budget: number | null;
    pace: number | null;
    actual: number;
    currency: string | null;
}

const props = defineProps<{
    month: string;
    periodStart: string;
    budgets: BudgetRow[];
    previousMonth: string;
    nextMonth: string;
    navigationShortcutsEnabled: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Budgets', href: index() },
            { title: 'Income', href: income() },
        ],
    },
});

const monthFormatter = new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: 'long',
});

const monthLabel = computed(() =>
    monthFormatter.format(new Date(`${props.month}T00:00:00`)),
);

const remaining = (row: BudgetRow): number | null =>
    row.budget === null ? null : row.budget - row.actual;

const isBehindPace = (row: BudgetRow): boolean =>
    row.pace !== null && row.actual < row.pace;

usePeriodNavigation({
    enabled: () => props.navigationShortcutsEnabled,
    previousHref: () => income({ query: { month: props.previousMonth } }).url,
    nextHref: () => income({ query: { month: props.nextMonth } }).url,
});
</script>

<template>
    <Head title="Income Budgets" />

    <div class="flex flex-col gap-8 p-4">
        <Heading
            title="Budgets"
            description="Compare actual income against your planned monthly income"
        />

        <BudgetViewNav current="income" />

        <div class="flex items-center justify-between gap-4">
            <Button variant="outline" size="sm" as-child>
                <Link :href="income({ query: { month: previousMonth } })"
                    ><ChevronLeft /> Previous</Link
                >
            </Button>
            <Heading variant="small" :title="monthLabel" />
            <Button variant="outline" size="sm" as-child>
                <Link :href="income({ query: { month: nextMonth } })"
                    >Next <ChevronRight
                /></Link>
            </Button>
        </div>

        <Card>
            <CardContent class="p-4">
                <div v-if="budgets.length" class="grid gap-3">
                    <div
                        v-for="row in budgets"
                        :key="row.category_id"
                        class="grid gap-3 rounded-lg border p-4 md:grid-cols-[1fr_10rem_10rem_10rem_10rem_auto]"
                    >
                        <span class="self-center font-medium text-foreground">{{
                            row.name
                        }}</span>

                        <Form
                            v-bind="
                                BudgetController.updateOverride.form(
                                    row.category_id,
                                )
                            "
                            class="flex items-start gap-2"
                            v-slot="{ errors, processing }"
                        >
                            <input
                                type="hidden"
                                name="period"
                                :value="periodStart"
                            />
                            <div class="grid gap-1">
                                <Input
                                    name="amount"
                                    type="number"
                                    min="0"
                                    step="1"
                                    :default-value="
                                        row.override ?? row.default_budget ?? ''
                                    "
                                    :placeholder="
                                        row.default_budget !== null
                                            ? `Default: ${row.default_budget}`
                                            : 'No target'
                                    "
                                    class="w-full"
                                />
                                <InputError :message="errors.amount" />
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="processing"
                                >Save</Button
                            >
                        </Form>

                        <span class="self-center text-muted-foreground">
                            Received: {{ row.actual }}
                            {{ row.currency ?? '' }}
                            <template v-if="row.carryover !== null">
                                <br />
                                <span class="text-xs">
                                    Carryover: {{ row.carryover >= 0 ? '+' : ''
                                    }}{{ row.carryover }}
                                    {{ row.currency ?? '' }}
                                </span>
                            </template>
                        </span>

                        <span
                            v-if="row.pace !== null"
                            class="self-center"
                            :class="
                                isBehindPace(row)
                                    ? 'text-amber-600 dark:text-amber-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            On pace: {{ row.pace }}
                            {{ row.currency ?? '' }}
                        </span>
                        <span v-else class="self-center text-muted-foreground"
                            >&mdash;</span
                        >

                        <span
                            v-if="remaining(row) !== null"
                            class="self-center"
                            :class="
                                (remaining(row) as number) <= 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-red-600 dark:text-red-400'
                            "
                        >
                            Remaining: {{ remaining(row) }}
                            {{ row.currency ?? '' }}
                        </span>
                        <span v-else class="self-center text-muted-foreground"
                            >No target set</span
                        >

                        <Button
                            v-if="row.override !== null"
                            variant="ghost"
                            size="sm"
                            title="Reset to default planned income"
                            as-child
                        >
                            <Link
                                :href="
                                    BudgetController.destroyOverride(
                                        row.category_id,
                                        { query: { period: periodStart } },
                                    )
                                "
                            >
                                <X /> Reset
                            </Link>
                        </Button>
                    </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    No income categories have a planned amount yet. Set a
                    planned monthly income on a category to see it here.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
