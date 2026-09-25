<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import UserStatusBadges from '@/components/admin/UserStatusBadges.vue';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { getInitials } from '@/composables/useInitials';
import { formatDate, formatDateTime } from '@/lib/dates';
import { index, show } from '@/routes/admin/users';
import type { AdminUser, Paginated } from '@/types';

const props = defineProps<{
    users: Paginated<AdminUser>;
    filters: { search: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Users', href: index() }],
    },
});

const search = ref(props.filters.search);

// Laravel's paginator labels contain HTML entities for the arrows.
const linkLabel = (label: string): string =>
    label.replace('&laquo;', '«').replace('&raquo;', '»');

watchDebounced(
    search,
    (value) => {
        router.get(index.url(), value ? { search: value } : {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    },
    { debounce: 300 },
);
</script>

<template>
    <Head title="Users" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <Heading
                title="Users"
                description="Manage who can sign in and who has admin access"
            />

            <div class="relative w-full sm:max-w-xs">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search by name or email"
                    aria-label="Search users"
                    class="pl-9"
                />
            </div>
        </div>

        <div
            class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-left text-sm">
                <thead
                    class="border-b bg-muted/50 text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">User</th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Status
                        </th>
                        <th
                            scope="col"
                            class="hidden px-4 py-3 font-medium md:table-cell"
                        >
                            Last sign-in
                        </th>
                        <th
                            scope="col"
                            class="hidden px-4 py-3 font-medium md:table-cell"
                        >
                            Joined
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        class="border-b last:border-b-0 hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="show(user.id)"
                                class="flex items-center gap-3"
                            >
                                <Avatar
                                    class="size-8 overflow-hidden rounded-lg"
                                >
                                    <AvatarImage
                                        v-if="user.avatar"
                                        :src="user.avatar"
                                        :alt="user.name"
                                    />
                                    <AvatarFallback class="rounded-lg">
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <span class="grid">
                                    <span class="font-medium">{{
                                        user.name
                                    }}</span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ user.email }}
                                    </span>
                                </span>
                            </Link>
                        </td>
                        <td class="px-4 py-3">
                            <UserStatusBadges :user="user" />
                        </td>
                        <td
                            class="hidden px-4 py-3 text-muted-foreground md:table-cell"
                        >
                            {{ formatDateTime(user.last_login_at) }}
                        </td>
                        <td
                            class="hidden px-4 py-3 text-muted-foreground md:table-cell"
                        >
                            {{ formatDate(user.created_at) }}
                        </td>
                    </tr>
                    <tr v-if="users.data.length === 0">
                        <td
                            colspan="4"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No users found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav
            v-if="users.last_page > 1"
            class="flex flex-col items-center justify-between gap-3 text-sm sm:flex-row"
            aria-label="Pagination"
        >
            <p class="text-muted-foreground">
                Showing {{ users.from }}–{{ users.to }} of {{ users.total }}
            </p>
            <div class="flex flex-wrap gap-1">
                <template v-for="link in users.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-scroll
                        class="rounded-md border px-3 py-1.5"
                        :class="
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'hover:bg-muted'
                        "
                        >{{ linkLabel(link.label) }}</Link
                    >
                    <span
                        v-else
                        class="rounded-md border px-3 py-1.5 text-muted-foreground opacity-50"
                        >{{ linkLabel(link.label) }}</span
                    >
                </template>
            </div>
        </nav>
    </div>
</template>
