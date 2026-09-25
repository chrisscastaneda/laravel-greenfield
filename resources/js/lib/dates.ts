const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

const dateTimeFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
});

export function formatDate(value: string | null, fallback = '—'): string {
    return value ? dateFormatter.format(new Date(value)) : fallback;
}

export function formatDateTime(
    value: string | null,
    fallback = 'Never',
): string {
    return value ? dateTimeFormatter.format(new Date(value)) : fallback;
}
