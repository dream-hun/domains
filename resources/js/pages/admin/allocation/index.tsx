import { Head, router } from '@inertiajs/react';
import { DownloadIcon } from 'lucide-react';
import { useState } from 'react';
import {
    exportMethod,
    index,
} from '@/actions/App/Http/Controllers/Admin/AllocationController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Summary = {
    total: number;
    insurance: number;
    savings: number;
    pathway: number;
    administration: number;
    count: number;
};

type Filters = {
    from?: string;
    to?: string;
    format?: string;
    player_id?: number;
};

type Props = {
    summary: Summary;
    filters: Filters;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Allocation Summary', href: index().url },
];

function StatCard({
    title,
    value,
    subtitle,
}: {
    title: string;
    value: string;
    subtitle?: string;
}) {
    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {title}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <p className="text-2xl font-bold">{value}</p>
                {subtitle && (
                    <p className="text-xs text-muted-foreground">{subtitle}</p>
                )}
            </CardContent>
        </Card>
    );
}

export default function AllocationIndex({ summary, filters }: Props) {
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');
    const [format, setFormat] = useState(filters.format ?? '');
    const [playerId, setPlayerId] = useState(
        filters.player_id ? String(filters.player_id) : '',
    );

    function applyFilters() {
        const params: Record<string, string> = {};
        if (from) params.from = from;
        if (to) params.to = to;
        if (format) params.format = format;
        if (playerId) params.player_id = playerId;

        router.get(index().url, params, { preserveState: true });
    }

    function clearFilters() {
        setFrom('');
        setTo('');
        setFormat('');
        setPlayerId('');
        router.get(index().url, {}, { preserveState: true });
    }

    const exportUrl = (() => {
        const params = new URLSearchParams();
        if (filters.from) params.set('from', filters.from);
        if (filters.to) params.set('to', filters.to);
        if (filters.format) params.set('format', filters.format);
        if (filters.player_id)
            params.set('player_id', String(filters.player_id));
        const qs = params.toString();
        return exportMethod.url() + (qs ? `?${qs}` : '');
    })();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Allocation Summary" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Allocation Summary
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            $1 per approved game, split across four categories.
                        </p>
                    </div>
                    <Button asChild variant="outline" size="sm">
                        <a href={exportUrl}>
                            <DownloadIcon className="mr-2 h-4 w-4" />
                            Export CSV
                        </a>
                    </Button>
                </div>

                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    <StatCard
                        title="Total Allocated"
                        value={`$${summary.total.toFixed(2)}`}
                        subtitle={`${summary.count} games`}
                    />
                    <StatCard
                        title="Insurance"
                        value={`$${summary.insurance.toFixed(4)}`}
                    />
                    <StatCard
                        title="Savings"
                        value={`$${summary.savings.toFixed(4)}`}
                    />
                    <StatCard
                        title="Pathway"
                        value={`$${summary.pathway.toFixed(4)}`}
                    />
                    <StatCard
                        title="Administration"
                        value={`$${summary.administration.toFixed(4)}`}
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div className="grid gap-1.5">
                                <Label htmlFor="from">From</Label>
                                <Input
                                    id="from"
                                    type="date"
                                    value={from}
                                    onChange={(e) => setFrom(e.target.value)}
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="to">To</Label>
                                <Input
                                    id="to"
                                    type="date"
                                    value={to}
                                    onChange={(e) => setTo(e.target.value)}
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="format">Format</Label>
                                <Input
                                    id="format"
                                    placeholder="e.g. singles"
                                    value={format}
                                    onChange={(e) => setFormat(e.target.value)}
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="player_id">Player ID</Label>
                                <Input
                                    id="player_id"
                                    type="number"
                                    placeholder="Player ID"
                                    value={playerId}
                                    onChange={(e) =>
                                        setPlayerId(e.target.value)
                                    }
                                />
                            </div>
                        </div>
                        <div className="mt-4 flex gap-2">
                            <Button size="sm" onClick={applyFilters}>
                                Apply
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={clearFilters}
                            >
                                Clear
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
