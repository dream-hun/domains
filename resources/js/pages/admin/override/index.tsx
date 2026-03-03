import { Head, Link } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import { index, show } from '@/routes/admin/override';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type User = { id: number; name: string };
type Court = { id: number; name: string };

type GameModeration = {
    id: number;
    status: string;
    reason: string;
    is_override: boolean;
    created_at: string;
    moderator: User | null;
};

type Game = {
    id: number;
    uuid: string;
    title: string;
    format: string;
    court_id: number | null;
    player_id: number;
    played_at: string;
    vimeo_uri: string | null;
    vimeo_status: string | null;
    status: string;
    court: Court | null;
    player: User | null;
    moderation: GameModeration[];
};

type PaginationLink = { url: string | null; label: string; active: boolean };

type PaginatedGames = {
    data: Game[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Flagged Games', href: index().url },
];

export default function OverrideIndex({ games }: { games: PaginatedGames }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Flagged Games" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Flagged Games</h1>
                    <p className="text-sm text-muted-foreground">
                        {games.total} game{games.total !== 1 ? 's' : ''}{' '}
                        flagged for review
                    </p>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Title</TableHead>
                                <TableHead>Player</TableHead>
                                <TableHead>Format</TableHead>
                                <TableHead>Court</TableHead>
                                <TableHead>Flagged By</TableHead>
                                <TableHead>Flag Reason</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {games.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="py-8 text-center text-muted-foreground"
                                    >
                                        No flagged games.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                games.data.map((game) => {
                                    const latestModeration =
                                        game.moderation[
                                            game.moderation.length - 1
                                        ] ?? null;
                                    return (
                                        <TableRow key={game.id}>
                                            <TableCell className="font-medium">
                                                {game.title}
                                            </TableCell>
                                            <TableCell>
                                                {game.player?.name ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {game.format}
                                            </TableCell>
                                            <TableCell>
                                                {game.court?.name ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {latestModeration?.moderator
                                                    ?.name ?? '—'}
                                            </TableCell>
                                            <TableCell className="max-w-xs truncate">
                                                {latestModeration?.reason ??
                                                    '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger
                                                        asChild
                                                    >
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                        >
                                                            <MoreHorizontal className="size-4" />
                                                            <span className="sr-only">
                                                                Actions
                                                            </span>
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem
                                                            asChild
                                                        >
                                                            <Link
                                                                href={
                                                                    show(
                                                                        game.uuid,
                                                                    ).url
                                                                }
                                                            >
                                                                Review
                                                            </Link>
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })
                            )}
                        </TableBody>
                    </Table>
                </div>

                {games.last_page > 1 && (
                    <div className="flex items-center justify-center gap-1">
                        {games.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={link.url === null}
                                asChild={link.url !== null}
                            >
                                {link.url !== null ? (
                                    <Link
                                        href={link.url}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : (
                                    <span
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                )}
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
