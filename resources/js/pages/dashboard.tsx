import { Head } from '@inertiajs/react';
import { CheckCircle2, Clock, Gamepad2, MapPin, Trophy } from 'lucide-react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

interface GameStats {
    total_games: number;
    total_courts: number;
    pending_games: number;
    approved_games: number;
}

interface RecentGame {
    id: number;
    uuid: string;
    title: string;
    status: string;
    played_at: string;
    court: { name: string } | null;
    player: { name: string };
}

interface MonthlyData {
    month: string;
    count: number;
}

interface PlayerRankingEntry {
    format: string;
    rank: number;
    score: number;
    wins: number;
    losses: number;
}

interface Props {
    stats: GameStats;
    recent_games: RecentGame[];
    games_per_month: MonthlyData[];
    player_rankings: Record<string, PlayerRankingEntry>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const chartConfig = {
    games: {
        label: 'Games',
        color: 'hsl(var(--chart-1))',
    },
};

function statusBadgeClass(status: string): string {
    switch (status) {
        case 'approved':
            return 'border-transparent bg-green-500 text-white';
        case 'pending':
            return 'border-transparent bg-yellow-500 text-white';
        case 'rejected':
            return 'border-transparent bg-red-500 text-white';
        case 'flagged':
            return 'border-transparent bg-orange-500 text-white';
        default:
            return '';
    }
}

function formatMonth(yearMonth: string): string {
    const [year, month] = yearMonth.split('-');
    const date = new Date(Number(year), Number(month) - 1, 1);
    return date.toLocaleString('default', { month: 'short' });
}

export default function Dashboard({
    stats,
    recent_games,
    games_per_month,
    player_rankings,
}: Props) {
    const chartData = games_per_month.map((item) => ({
        month: formatMonth(item.month),
        games: item.count,
    }));

    const rankingEntries = Object.values(player_rankings);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
                {/* Stat Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Games
                            </CardTitle>
                            <Gamepad2 className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.total_games}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Courts
                            </CardTitle>
                            <MapPin className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.total_courts}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Pending Review
                            </CardTitle>
                            <Clock className="h-4 w-4 text-yellow-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.pending_games}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Approved Games
                            </CardTitle>
                            <CheckCircle2 className="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.approved_games}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* My Rankings */}
                {rankingEntries.length > 0 && (
                    <div className="flex flex-col gap-2">
                        <h2 className="flex items-center gap-2 text-lg font-semibold">
                            <Trophy className="h-5 w-5 text-yellow-500" />
                            My Rankings
                        </h2>
                        <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                            {rankingEntries.map((entry) => (
                                <Card key={entry.format}>
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-sm font-medium uppercase text-muted-foreground">
                                            {entry.format}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="flex flex-col gap-1">
                                        <div className="text-3xl font-bold">
                                            #{entry.rank}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            Score:{' '}
                                            <span className="font-mono">
                                                {entry.score.toFixed(2)}
                                            </span>
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            <span className="text-green-600">
                                                {entry.wins}W
                                            </span>{' '}
                                            /{' '}
                                            <span className="text-red-500">
                                                {entry.losses}L
                                            </span>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                )}

                {/* Chart + Recent Games */}
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Games per Month Chart */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Games per Month</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ChartContainer
                                config={chartConfig}
                                className="h-64 w-full"
                            >
                                <BarChart data={chartData}>
                                    <CartesianGrid vertical={false} />
                                    <XAxis
                                        dataKey="month"
                                        tickLine={false}
                                        axisLine={false}
                                    />
                                    <YAxis
                                        tickLine={false}
                                        axisLine={false}
                                        allowDecimals={false}
                                    />
                                    <ChartTooltip
                                        content={<ChartTooltipContent />}
                                    />
                                    <Bar
                                        dataKey="games"
                                        fill="var(--color-games)"
                                        radius={4}
                                    />
                                </BarChart>
                            </ChartContainer>
                        </CardContent>
                    </Card>

                    {/* Recent Games */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Games</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recent_games.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No games have been recorded yet.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Title</TableHead>
                                            <TableHead>Court</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Date</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recent_games.map((game) => (
                                            <TableRow key={game.id}>
                                                <TableCell className="font-medium">
                                                    {game.title}
                                                </TableCell>
                                                <TableCell>
                                                    {game.court?.name ?? '—'}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        className={statusBadgeClass(
                                                            game.status,
                                                        )}
                                                    >
                                                        {game.status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {new Date(
                                                        game.played_at,
                                                    ).toLocaleDateString()}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
