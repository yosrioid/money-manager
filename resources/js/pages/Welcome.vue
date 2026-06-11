<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    LayoutDashboard,
    ShieldCheck,
    Tags,
    Wallet,
} from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard, login, register } from '@/routes';

const features = [
    {
        icon: Wallet,
        title: 'Accounts & balances',
        description:
            'Organize cash, bank, e-wallet, and card accounts into groups with a clear picture of every balance.',
    },
    {
        icon: LayoutDashboard,
        title: 'Categories & tags',
        description:
            'Track income and expenses with customizable categories, subcategories, merchants, and tags.',
    },
    {
        icon: ShieldCheck,
        title: 'Private workspaces',
        description:
            'Each account gets an isolated workspace with session lock and security controls built in.',
    },
    {
        icon: Tags,
        title: 'Built for clarity',
        description:
            'A focused, distraction-free interface that keeps your financial structure easy to manage.',
    },
];
</script>

<template>
    <Head title="Welcome" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header
            class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6"
        >
            <div class="flex items-center gap-2">
                <div
                    class="flex aspect-square size-9 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
                >
                    <AppLogoIcon
                        class="size-5 fill-current text-white dark:text-black"
                    />
                </div>
                <span class="text-lg font-semibold">Money Manager</span>
            </div>
            <nav class="flex items-center gap-2">
                <Button v-if="$page.props.auth.user" as-child>
                    <Link :href="dashboard()">Dashboard</Link>
                </Button>
                <template v-else>
                    <Button variant="ghost" as-child>
                        <Link :href="login()">Log in</Link>
                    </Button>
                    <Button as-child>
                        <Link :href="register()">Get started</Link>
                    </Button>
                </template>
            </nav>
        </header>

        <main class="flex-1">
            <section class="mx-auto max-w-6xl px-6 py-16 text-center sm:py-24">
                <h1
                    class="mx-auto max-w-2xl text-4xl font-bold tracking-tight sm:text-5xl"
                >
                    Manage your money with confidence
                </h1>
                <p
                    class="mx-auto mt-4 max-w-xl text-muted-foreground sm:text-lg"
                >
                    Track accounts, categorize spending, and keep your financial
                    structure organized in one private workspace.
                </p>
                <div class="mt-8 flex items-center justify-center gap-3">
                    <Button v-if="$page.props.auth.user" size="lg" as-child>
                        <Link :href="dashboard()">
                            Go to dashboard
                            <ArrowRight />
                        </Link>
                    </Button>
                    <template v-else>
                        <Button size="lg" as-child>
                            <Link :href="register()">
                                Create your workspace
                                <ArrowRight />
                            </Link>
                        </Button>
                        <Button size="lg" variant="outline" as-child>
                            <Link :href="login()">Log in</Link>
                        </Button>
                    </template>
                </div>
            </section>

            <section class="mx-auto max-w-6xl px-6 pb-16 sm:pb-24">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card v-for="feature in features" :key="feature.title">
                        <CardHeader>
                            <component
                                :is="feature.icon"
                                class="size-6 text-primary"
                            />
                            <CardTitle class="mt-2">
                                {{ feature.title }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                {{ feature.description }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </main>

        <footer class="border-t py-6">
            <div
                class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-6 text-sm text-muted-foreground sm:flex-row"
            >
                <span>&copy; {{ new Date().getFullYear() }} Money Manager</span>
                <span>Built with Laravel and Vue</span>
            </div>
        </footer>
    </div>
</template>
