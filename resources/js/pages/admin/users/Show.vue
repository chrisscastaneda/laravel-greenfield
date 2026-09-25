<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import UserAdminController from '@/actions/App/Http/Controllers/Admin/UserAdminController';
import UserDeactivationController from '@/actions/App/Http/Controllers/Admin/UserDeactivationController';
import UserStatusBadges from '@/components/admin/UserStatusBadges.vue';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { getInitials } from '@/composables/useInitials';
import { formatDate, formatDateTime } from '@/lib/dates';
import { index } from '@/routes/admin/users';
import type { AbilityCheck, AdminUserDetail } from '@/types';

defineProps<{
    user: AdminUserDetail;
    can: Record<
        'grantAdmin' | 'revokeAdmin' | 'deactivate' | 'reactivate',
        AbilityCheck
    >;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Users', href: index() }],
    },
});
</script>

<template>
    <Head :title="user.name" />

    <div class="flex max-w-3xl flex-1 flex-col gap-8 p-4">
        <Link
            :href="index()"
            class="flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="size-4" /> All users
        </Link>

        <div class="flex items-center gap-4">
            <Avatar class="size-14 overflow-hidden rounded-xl">
                <AvatarImage
                    v-if="user.avatar"
                    :src="user.avatar"
                    :alt="user.name"
                />
                <AvatarFallback class="rounded-xl text-lg">
                    {{ getInitials(user.name) }}
                </AvatarFallback>
            </Avatar>
            <div class="grid gap-1">
                <h1 class="text-xl font-semibold tracking-tight">
                    {{ user.name }}
                </h1>
                <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                <UserStatusBadges :user="user" />
            </div>
        </div>

        <dl
            class="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 text-sm sm:grid-cols-2 dark:border-sidebar-border"
        >
            <div>
                <dt class="text-muted-foreground">Joined</dt>
                <dd class="font-medium">{{ formatDate(user.created_at) }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Last sign-in</dt>
                <dd class="font-medium">
                    {{ formatDateTime(user.last_login_at) }}
                </dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Email verified</dt>
                <dd class="font-medium">
                    {{ formatDate(user.email_verified_at, 'No') }}
                </dd>
            </div>
            <div v-if="user.deactivated_at">
                <dt class="text-muted-foreground">Deactivated</dt>
                <dd class="font-medium">
                    {{ formatDateTime(user.deactivated_at) }}
                </dd>
            </div>
        </dl>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Admin access"
                :description="
                    user.is_environment_admin
                        ? 'This user is listed in ADMIN_EMAILS, so their admin access is managed by the environment.'
                        : 'Admins can manage users from this area.'
                "
            />

            <Form
                v-if="!user.is_admin"
                v-bind="UserAdminController.store.form(user.id)"
                v-slot="{ processing }"
            >
                <Button
                    type="submit"
                    :disabled="processing || !can.grantAdmin.allowed"
                    data-test="grant-admin-button"
                >
                    Make admin
                </Button>
                <p
                    v-if="!can.grantAdmin.allowed && can.grantAdmin.reason"
                    class="mt-2 text-sm text-muted-foreground"
                >
                    {{ can.grantAdmin.reason }}
                </p>
            </Form>

            <Form
                v-else
                v-bind="UserAdminController.destroy.form(user.id)"
                v-slot="{ processing }"
            >
                <Button
                    type="submit"
                    variant="secondary"
                    :disabled="processing || !can.revokeAdmin.allowed"
                    data-test="revoke-admin-button"
                >
                    Revoke admin access
                </Button>
                <p
                    v-if="!can.revokeAdmin.allowed && can.revokeAdmin.reason"
                    class="mt-2 text-sm text-muted-foreground"
                >
                    {{ can.revokeAdmin.reason }}
                </p>
            </Form>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Account status"
                description="Deactivated users are signed out and cannot sign in again until they are reactivated."
            />

            <Form
                v-if="user.deactivated_at"
                v-bind="UserDeactivationController.destroy.form(user.id)"
                v-slot="{ processing }"
            >
                <Button
                    type="submit"
                    :disabled="processing || !can.reactivate.allowed"
                    data-test="reactivate-user-button"
                >
                    Reactivate user
                </Button>
            </Form>

            <template v-else>
                <Dialog>
                    <DialogTrigger as-child>
                        <Button
                            variant="destructive"
                            :disabled="!can.deactivate.allowed"
                            data-test="deactivate-user-button"
                        >
                            Deactivate user
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <Form
                            v-bind="
                                UserDeactivationController.store.form(user.id)
                            "
                            class="space-y-6"
                            v-slot="{ processing }"
                        >
                            <DialogHeader class="space-y-3">
                                <DialogTitle>
                                    Deactivate {{ user.name }}?
                                </DialogTitle>
                                <DialogDescription>
                                    They will be signed out immediately and will
                                    not be able to sign in again. You can
                                    reactivate them at any time.
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter class="gap-2">
                                <DialogClose as-child>
                                    <Button variant="secondary">Cancel</Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    :disabled="processing"
                                    data-test="confirm-deactivate-user-button"
                                >
                                    Deactivate
                                </Button>
                            </DialogFooter>
                        </Form>
                    </DialogContent>
                </Dialog>
                <p
                    v-if="!can.deactivate.allowed && can.deactivate.reason"
                    class="text-sm text-muted-foreground"
                >
                    {{ can.deactivate.reason }}
                </p>
            </template>
        </section>
    </div>
</template>
