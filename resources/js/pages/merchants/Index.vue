<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Archive } from '@lucide/vue';
import MerchantController from '@/actions/App/Http/Controllers/MerchantController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/merchants';

interface Category {
    id: number;
    name: string;
    type: string;
}

interface Merchant {
    id: number;
    name: string;
    default_category_id: number | null;
    default_category: Category | null;
}

defineProps<{ merchants: Merchant[]; categories: Category[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Merchants', href: index() }] },
});
</script>

<template>
    <Head title="Merchants" />
    <div class="flex flex-col gap-6">
        <Heading
            title="Merchants and recipients"
            description="Save frequently used payees and their default categories"
        />

        <Card>
            <CardHeader><CardTitle>Add merchant</CardTitle></CardHeader>
            <CardContent>
                <Form
                    v-bind="MerchantController.store.form()"
                    reset-on-success
                    class="grid gap-4 md:grid-cols-[1fr_1fr_auto]"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="merchant-name">Name</Label>
                        <Input id="merchant-name" name="name" required />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="merchant-category">Default category</Label>
                        <select
                            id="merchant-category"
                            name="default_category_id"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">No default category</option>
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }} ({{ category.type }})
                            </option>
                        </select>
                        <InputError :message="errors.default_category_id" />
                    </div>
                    <Button class="self-end" :disabled="processing">Add</Button>
                </Form>
            </CardContent>
        </Card>

        <div class="grid gap-3">
            <Form
                v-for="merchant in merchants"
                :key="merchant.id"
                v-bind="MerchantController.update.form(merchant.id)"
                class="grid gap-3 rounded-lg border p-4 md:grid-cols-[1fr_1fr_auto_auto]"
                v-slot="{ errors, processing }"
            >
                <Input name="name" :default-value="merchant.name" required />
                <select
                    name="default_category_id"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option value="">No default category</option>
                    <option
                        v-for="category in categories"
                        :key="category.id"
                        :value="category.id"
                        :selected="category.id === merchant.default_category_id"
                    >
                        {{ category.name }} ({{ category.type }})
                    </option>
                </select>
                <Button variant="outline" :disabled="processing">Save</Button>
                <Button variant="ghost" title="Archive merchant" as-child>
                    <Link :href="MerchantController.destroy(merchant.id)">
                        <Archive />
                    </Link>
                </Button>
                <InputError :message="errors.name" class="md:col-span-4" />
            </Form>
            <p v-if="!merchants.length" class="text-sm text-muted-foreground">
                No merchants or recipients yet.
            </p>
        </div>
    </div>
</template>
