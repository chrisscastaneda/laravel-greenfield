<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
</script>

<template>
    <Head title="Account deactivated" />

    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div
            class="flex w-full max-w-sm flex-col items-center gap-4 text-center"
        >
            <AppLogoIcon class="size-9 fill-current text-foreground" />

            <h1 class="text-xl font-medium">
                Your account has been deactivated
            </h1>

            <p class="text-sm text-muted-foreground">
                You can no longer sign in to this application. If you think this
                is a mistake, please contact an administrator.
            </p>

            <Link
                :href="home()"
                class="text-sm underline underline-offset-4 hover:text-muted-foreground"
            >
                Return home
            </Link>
        </div>
    </div>
</template>
