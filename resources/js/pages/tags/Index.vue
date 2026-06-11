<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Archive } from '@lucide/vue';
import TagController from '@/actions/App/Http/Controllers/TagController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/tags';

interface Tag {
    id: number;
    name: string;
    color: string | null;
}

defineProps<{ tags: Tag[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Tags', href: index() }] },
});
</script>

<template>
    <Head title="Tags" />
    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Tags"
            description="Create reusable labels for financial transactions"
        />

        <Card>
            <CardHeader><CardTitle>Add tag</CardTitle></CardHeader>
            <CardContent>
                <Form
                    v-bind="TagController.store.form()"
                    reset-on-success
                    class="grid gap-4 sm:grid-cols-[1fr_auto_auto]"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="tag-name">Name</Label>
                        <Input id="tag-name" name="name" required />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="tag-color">Color</Label>
                        <input
                            id="tag-color"
                            name="color"
                            type="color"
                            value="#3366ff"
                            class="h-9 w-16 rounded-md border border-input bg-background p-1"
                        />
                        <InputError :message="errors.color" />
                    </div>
                    <Button class="self-end" :disabled="processing">Add</Button>
                </Form>
            </CardContent>
        </Card>

        <div class="grid gap-3">
            <Form
                v-for="tag in tags"
                :key="tag.id"
                v-bind="TagController.update.form(tag.id)"
                class="grid gap-3 rounded-lg border p-4 sm:grid-cols-[1fr_auto_auto_auto]"
                v-slot="{ errors, processing }"
            >
                <Input name="name" :default-value="tag.name" required />
                <input
                    name="color"
                    type="color"
                    :value="tag.color ?? '#3366ff'"
                    class="h-9 w-16 rounded-md border border-input bg-background p-1"
                />
                <Button variant="outline" :disabled="processing">Save</Button>
                <Button variant="ghost" title="Archive tag" as-child>
                    <Link :href="TagController.destroy(tag.id)">
                        <Archive />
                    </Link>
                </Button>
                <InputError :message="errors.name" class="sm:col-span-4" />
            </Form>
            <p v-if="!tags.length" class="text-sm text-muted-foreground">
                No tags yet.
            </p>
        </div>
    </div>
</template>
