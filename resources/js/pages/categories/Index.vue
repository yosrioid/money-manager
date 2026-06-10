<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Archive, ArrowDown, ArrowUp } from '@lucide/vue';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/categories';

interface Category {
    id: number;
    name: string;
    type: 'income' | 'expense';
    color: string | null;
    icon: string | null;
    is_visible: boolean;
    subcategories: Category[];
}

const props = defineProps<{
    incomeCategories: Category[];
    expenseCategories: Category[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Categories', href: index() }] },
});

const allParents = (): Category[] => [
    ...props.incomeCategories,
    ...props.expenseCategories,
];
</script>

<template>
    <Head title="Categories" />
    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Categories"
            description="Manage income, expense, and one-level subcategories"
        />

        <Card>
            <CardHeader><CardTitle>Add category</CardTitle></CardHeader>
            <CardContent>
                <Form
                    v-bind="CategoryController.store.form()"
                    reset-on-success
                    class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="category-name">Name</Label>
                        <Input id="category-name" name="name" required />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="category-type">Type</Label>
                        <select
                            id="category-type"
                            name="type"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                        </select>
                        <InputError :message="errors.type" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="category-parent">Parent</Label>
                        <select
                            id="category-parent"
                            name="parent_id"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">Top-level category</option>
                            <option
                                v-for="parent in allParents()"
                                :key="parent.id"
                                :value="parent.id"
                            >
                                {{ parent.name }} ({{ parent.type }})
                            </option>
                        </select>
                        <InputError :message="errors.parent_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="category-color">Color</Label>
                        <input
                            id="category-color"
                            name="color"
                            type="color"
                            value="#3366ff"
                            class="h-9 w-16 rounded-md border border-input bg-background p-1"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="category-icon">Icon name</Label>
                        <Input
                            id="category-icon"
                            name="icon"
                            placeholder="coffee"
                        />
                    </div>
                    <label
                        class="flex items-center gap-2 self-end pb-2 text-sm"
                    >
                        <input type="hidden" name="is_visible" value="0" />
                        <input
                            name="is_visible"
                            type="checkbox"
                            value="1"
                            checked
                            class="size-4 rounded border-input"
                        />
                        Visible
                    </label>
                    <Button class="self-end" :disabled="processing"
                        >Add category</Button
                    >
                </Form>
            </CardContent>
        </Card>

        <section
            v-for="section in [
                { title: 'Expense categories', items: expenseCategories },
                { title: 'Income categories', items: incomeCategories },
            ]"
            :key="section.title"
            class="space-y-3"
        >
            <Heading variant="small" :title="section.title" />
            <div class="grid gap-3">
                <template v-for="category in section.items" :key="category.id">
                    <Form
                        v-bind="CategoryController.update.form(category.id)"
                        class="grid gap-3 rounded-lg border p-4 md:grid-cols-2 xl:grid-cols-[1fr_10rem_auto_auto_auto]"
                        v-slot="{ errors, processing }"
                    >
                        <Input
                            name="name"
                            :default-value="category.name"
                            required
                        />
                        <input
                            type="hidden"
                            name="type"
                            :value="category.type"
                        />
                        <input type="hidden" name="parent_id" value="" />
                        <input
                            name="color"
                            type="color"
                            :value="category.color ?? '#3366ff'"
                            class="h-9 w-16 rounded-md border border-input bg-background p-1"
                        />
                        <Input
                            name="icon"
                            :default-value="category.icon ?? ''"
                            placeholder="Icon name"
                        />
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_visible" value="0" />
                            <input
                                name="is_visible"
                                type="checkbox"
                                value="1"
                                :checked="category.is_visible"
                                class="size-4 rounded border-input"
                            />
                            Visible
                        </label>
                        <div class="flex gap-2">
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                title="Move category up"
                                as-child
                            >
                                <Link
                                    :href="
                                        CategoryController.move(category.id, {
                                            query: { direction: 'up' },
                                        })
                                    "
                                    aria-label="Move category up"
                                    preserve-scroll
                                >
                                    <ArrowUp />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                title="Move category down"
                                as-child
                            >
                                <Link
                                    :href="
                                        CategoryController.move(category.id, {
                                            query: { direction: 'down' },
                                        })
                                    "
                                    aria-label="Move category down"
                                    preserve-scroll
                                >
                                    <ArrowDown />
                                </Link>
                            </Button>
                            <Button variant="outline" :disabled="processing"
                                >Save</Button
                            >
                            <Button
                                v-if="!category.subcategories.length"
                                variant="ghost"
                                title="Archive category"
                                as-child
                            >
                                <Link
                                    :href="
                                        CategoryController.destroy(category.id)
                                    "
                                >
                                    <Archive />
                                </Link>
                            </Button>
                        </div>
                        <InputError
                            :message="errors.name"
                            class="md:col-span-2 xl:col-span-5"
                        />
                    </Form>

                    <Form
                        v-for="child in category.subcategories"
                        :key="child.id"
                        v-bind="CategoryController.update.form(child.id)"
                        class="ml-6 grid gap-3 rounded-lg border border-dashed p-4 md:grid-cols-2 xl:grid-cols-[1fr_10rem_auto_auto_auto_auto]"
                        v-slot="{ errors, processing }"
                    >
                        <Input
                            name="name"
                            :default-value="child.name"
                            required
                        />
                        <input type="hidden" name="type" :value="child.type" />
                        <input
                            type="hidden"
                            name="parent_id"
                            :value="category.id"
                        />
                        <input
                            name="color"
                            type="color"
                            :value="child.color ?? '#3366ff'"
                            class="h-9 w-16 rounded-md border border-input bg-background p-1"
                        />
                        <Input
                            name="icon"
                            :default-value="child.icon ?? ''"
                            placeholder="Icon name"
                        />
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_visible" value="0" />
                            <input
                                name="is_visible"
                                type="checkbox"
                                value="1"
                                :checked="child.is_visible"
                                class="size-4 rounded border-input"
                            />
                            Visible
                        </label>
                        <div class="flex gap-2">
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                title="Move subcategory up"
                                as-child
                            >
                                <Link
                                    :href="
                                        CategoryController.move(child.id, {
                                            query: { direction: 'up' },
                                        })
                                    "
                                    aria-label="Move subcategory up"
                                    preserve-scroll
                                >
                                    <ArrowUp />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                title="Move subcategory down"
                                as-child
                            >
                                <Link
                                    :href="
                                        CategoryController.move(child.id, {
                                            query: { direction: 'down' },
                                        })
                                    "
                                    aria-label="Move subcategory down"
                                    preserve-scroll
                                >
                                    <ArrowDown />
                                </Link>
                            </Button>
                        </div>
                        <Button variant="outline" :disabled="processing"
                            >Save</Button
                        >
                        <Button
                            variant="ghost"
                            title="Archive subcategory"
                            as-child
                        >
                            <Link :href="CategoryController.destroy(child.id)">
                                <Archive />
                            </Link>
                        </Button>
                        <InputError
                            :message="errors.name"
                            class="md:col-span-2 xl:col-span-6"
                        />
                        <InputError
                            :message="errors.parent_id"
                            class="md:col-span-2 xl:col-span-6"
                        />
                    </Form>
                </template>
                <p
                    v-if="!section.items.length"
                    class="text-sm text-muted-foreground"
                >
                    No categories yet.
                </p>
            </div>
        </section>
    </div>
</template>
