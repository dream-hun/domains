import { Head, router } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { leaderboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type LeaderboardEntry = {
    rank: number;
    player_id: number;
    player_name: string;
    wins: number;
    losses: number;
    total_games: number;
    score: number;
};

type Filters = {
    format: string;
    geo: string;
};

type Props = {
    entries: LeaderboardEntry[];
    filters: Filters;
    formats: string[];
};

const geoOptions = [
    { value: 'local', label: 'Local' },
    { value: 'national', label: 'National' },
    { value: 'continental', label: 'Continental' },
];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Leaderboard', href: leaderboard().url },
];

export default function LeaderboardIndex({ entries, filters, formats }: Props) {
    function handleFilterChange(key: string, value: string) {
        router.get(
            leaderboard().url,
            { ...filters, [key]: value },
            { preserveState: true },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Leaderboard" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center gap-3">
                    <Trophy className="h-6 w-6 text-yellow-500" />
                    <div>
                        <h1 className="text-2xl font-semibold">Leaderboard</h1>
                        <p className="text-sm text-muted-foreground">
                            Rankings based on approved games
                        </p>
                    </div>
                </div>

                <div className="flex flex-wrap gap-4">
                    <div className="w-40">
                        <Select
                            value={filters.format}
                            onValueChange={(val) =>
                                handleFilterChange('format', val)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Format" />
                            </SelectTrigger>
                            <SelectContent>
                                {formats.map((fmt) => (
                                    <SelectItem key={fmt} value={fmt}>
                                        {fmt}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="w-44">
                        <Select
                            value={filters.geo}
                            onValueChange={(val) =>
                                handleFilterChange('geo', val)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Region" />
                            </SelectTrigger>
                            <SelectContent>
                                {geoOptions.map((opt) => (
                                    <SelectItem key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-16">Rank</TableHead>
                                <TableHead>Player</TableHead>
                                <TableHead className="text-right">W</TableHead>
                                <TableHead className="text-right">L</TableHead>
                                <TableHead className="text-right">
                                    Games
                                </TableHead>
                                <TableHead className="text-right">
                                    Score
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {entries.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="py-8 text-center text-muted-foreground"
                                    >
                                        No rankings available for this
                                        selection.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                entries.map((entry) => (
                                    <TableRow key={entry.player_id}>
                                        <TableCell className="font-bold">
                                            #{entry.rank}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {entry.player_name}
                                        </TableCell>
                                        <TableCell className="text-right text-green-600">
                                            {entry.wins}
                                        </TableCell>
                                        <TableCell className="text-right text-red-500">
                                            {entry.losses}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {entry.total_games}
                                        </TableCell>
                                        <TableCell className="text-right font-mono">
                                            {entry.score.toFixed(2)}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}
