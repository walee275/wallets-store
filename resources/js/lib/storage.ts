export function storageUrl(path: string | null | undefined): string {
    if (!path) {
        return '';
    }

    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    return `/storage/${path.replace(/^\//, '')}`;
}
