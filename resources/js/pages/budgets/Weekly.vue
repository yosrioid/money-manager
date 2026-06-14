<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import BudgetViewNav from '@/components/BudgetViewNav.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { usePeriodNavigation } from '@/composables/usePeriodNavigation';
import { index, weekly } from '@/routes/budgets';

interface BudgetRow {
    category_id: number;
    name: string;
    budget: number | null;
    actual: number;
    currency: string | null;
}

const props = defineProps<{
    weekStart: string;
    budgets: BudgetRow[];
    previousWeek: string;
    nextWeek: string;
    navigationShortcutsEnabled: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Budgets', href: index() },
            { title: 'Weekly', href: weekly() },
        ],
    },
});

const weekEndLabel = computed(() => {
    const start = new Date(`${props.weekStart}T00:00:00`);
    const end = new Date(start);
    end.setDate(end.getDate() + 6);

    return end.toISOString().slice(0, 10);
});

const remaining = (row: BudgetRow): number | null =>
    row.budget === null ? null : row.budget - row.actual;

usePeriodNavigation({
    enabled: () => props.navigationShortcutsEnabled,
    previousHref: () => weekly({ query: { week: props.previousWeek } }).url,
    nextHref: () => weekly({ query: { week: props.nextWeek } }).url,
});
</script>

<template>
    <Head title="Weekly Budgets" />

    <div class="flex flex-col gap-8 p-4">
        <Heading
            title="Budgets"
            description="Compare actual spending against your prorated weekly budget"
        />

        <BudgetViewNav current="weekly" />

        <div class="flex items-center justify-between gap-4">
            <Button variant="outline" size="sm" as-child>
                <Link :href="weekly({ query: { week: previousWeek } })"
                    ><ChevronLeft /> Previous</Link
                >
            </Button>
            <Heading
                variant="small"
                :title="`${weekStart} - ${weekEndLabel}`"
            />
            <Button variant="outline" size="sm" as-child>
                <Link :href="weekly({ query: { week: nextWeek } })"
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
                        class="grid gap-3 rounded-lg border p-4 md:grid-cols-[1fr_10rem_10rem_10rem]"
                    >
                        <span class="self-center font-medium text-foreground">{{
                            row.name
                        }}</span>

                        <span class="self-center text-muted-foreground">
                            Budget:
                            {{ row.budget ?? 'None' }}
                            {{
                                row.budget !== null ? (row.currency ?? '') : ''
                            }}
                        </span>

                        <span class="self-center text-muted-foreground">
                            Actual: {{ row.actual }}
                            {{ row.currency ?? '' }}
                        </span>

                        <span
                            v-if="remaining(row) !== null"
                            class="self-center"
                            :class="
                                (remaining(row) as number) >= 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-red-600 dark:text-red-400'
                            "
                        >
                            Remaining: {{ remaining(row) }}
                            {{ row.currency ?? '' }}
                        </span>
                        <span v-else class="self-center text-muted-foreground"
                            >No budget set</span
                        >
                    </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    No expense categories have a budget yet. Set a default
                    monthly budget on a category to see it here.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
