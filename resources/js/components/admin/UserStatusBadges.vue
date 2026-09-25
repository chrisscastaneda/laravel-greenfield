<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { AdminUser } from '@/types';

defineProps<{
    user: AdminUser;
}>();
</script>

<template>
    <div class="flex flex-wrap gap-1">
        <Badge v-if="user.is_admin">Admin</Badge>
        <Badge v-if="user.deactivated_at" variant="destructive"
            >Deactivated</Badge
        >
        <Badge v-else variant="outline">Active</Badge>
    </div>
</template>
