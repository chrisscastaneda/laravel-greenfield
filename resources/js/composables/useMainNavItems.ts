import { usePage } from '@inertiajs/vue3';
import { LayoutGrid, Users } from '@lucide/vue';
import { computed } from 'vue';
import type { ComputedRef } from 'vue';
import { dashboard } from '@/routes';
import { index as adminUsers } from '@/routes/admin/users';
import type { NavItem } from '@/types';

/**
 * Primary navigation shared by the sidebar and header layouts.
 */
export function useMainNavItems(): ComputedRef<NavItem[]> {
    const page = usePage();

    return computed(() => [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        ...(page.props.auth.user?.is_admin
            ? [{ title: 'Users', href: adminUsers(), icon: Users }]
            : []),
    ]);
}
