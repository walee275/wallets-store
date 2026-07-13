import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const currencySymbols: Record<string, string> = {
    PKR: 'Rs',
    USD: '$',
    EUR: '€',
    GBP: '£',
};

export function useFormatMoney() {
    const page = usePage();

    const currency = computed(() => {
        const store = page.props.store as { currency?: string } | undefined;
        return store?.currency ?? 'PKR';
    });

    function formatMoney(cents: number | null | undefined): string {
        const amount = ((cents ?? 0) / 100).toFixed(2);
        const symbol = currencySymbols[currency.value] ?? currency.value;
        return `${symbol} ${amount}`;
    }

    function formatMajor(amount: number | null | undefined): string {
        const symbol = currencySymbols[currency.value] ?? currency.value;
        return `${symbol} ${(amount ?? 0).toFixed(2)}`;
    }

    return { formatMoney, formatMajor, currency };
}
