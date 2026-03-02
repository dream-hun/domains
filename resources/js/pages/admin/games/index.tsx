import { Form, Head, Link, router } from '@inertiajs/react';
import {
    CalendarIcon,
    CirclePlusIcon,
    MoreHorizontal,
    Search,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import GameController, {
    index,
} from '@/actions/App/Http/Controllers/Admin/GameController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
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
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type User = { id: number; name: string };
type Court = { id: number; name: string };

type Game = {
    id: number;
    uuid: string;
    title: string;
    format: string;
    court_id: number | null;
    player_id: number;
    played_at: string;
    status: string;
    result: 'win' | 'lost' | null;
    points: number | null;
    comments: string | null;
    vimeo_status: string | null;
    court: Court | null;
    player: User | null;
};

type PaginationLink = { url: string | null; label: string; active: boolean };

type PaginatedGames = {
    data: Game[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
};

const formats = ['1v1', '2v2', '3v3', '4v4', '5v5'];

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Games', href: index().url }];

function statusBadge(status: string) {
    const colors: Record<string, string> = {
        pending: 'bg-yellow-500',
        approved: 'bg-green-500',
        rejected: 'bg-red-500',
        flagged: 'bg-orange-500',
    };
    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white ${colors[status] ?? 'bg-gray-400'}`}
        >
            {status}
        </span>
    );
}

function vimeoStatusBadge(status: string | null) {
    if (!status) {
        return <span className="text-xs text-muted-foreground">No video</span>;
    }
    const colors: Record<string, string> = {
        pending: 'bg-yellow-500',
        complete: 'bg-green-500',
    };
    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white ${colors[status] ?? 'bg-gray-400'}`}
        >
            {status}
        </span>
    );
}

function GameFormFields({
    game,
    courts,
    errors,
}: {
    game?: Game;
    courts: Court[];
    errors: Record<string, string>;
}) {
    const [date, setDate] = useState<Date | undefined>(
        game?.played_at ? new Date(game.played_at) : undefined,
    );
    const [calendarOpen, setCalendarOpen] = useState(false);

    const playedAtValue = date
        ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
        : '';

    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="title">Title</Label>
                <Input
                    id="title"
                    name="title"
                    defaultValue={game?.title}
                    placeholder="Game title"
                    required
                />
                <InputError message={errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="format">Format</Label>
                <Select
                    name="format"
                    defaultValue={game?.format ?? '5v5'}
                    required
                >
                    <SelectTrigger id="format">
                        <SelectValue placeholder="Select format" />
                    </SelectTrigger>
                    <SelectContent>
                        {formats.map((f) => (
                            <SelectItem key={f} value={f}>
                                {f}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.format} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="court_id">Court</Label>
                <Select
                    name="court_id"
                    defaultValue={game?.court_id ? String(game.court_id) : ''}
                >
                    <SelectTrigger id="court_id">
                        <SelectValue placeholder="Select a court (optional)" />
                    </SelectTrigger>
                    <SelectContent>
                        {courts.map((court) => (
                            <SelectItem key={court.id} value={String(court.id)}>
                                {court.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.court_id} />
            </div>

            <div className="grid gap-2">
                <Label>Played At</Label>
                <Popover open={calendarOpen} onOpenChange={setCalendarOpen}>
                    <PopoverTrigger asChild>
                        <Button
                            variant="outline"
                            type="button"
                            className={cn(
                                'justify-start font-normal',
                                !date && 'text-muted-foreground',
                            )}
                        >
                            <CalendarIcon className="mr-2 size-4" />
                            {date
                                ? date.toLocaleDateString('default', {
                                      year: 'numeric',
                                      month: 'long',
                                      day: 'numeric',
                                  })
                                : 'Pick a date'}
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent align="start">
                        <Calendar
                            mode="single"
                            selected={date}
                            onSelect={(d) => {
                                setDate(d);
                                setCalendarOpen(false);
                            }}
                        />
                    </PopoverContent>
                </Popover>
                <input type="hidden" name="played_at" value={playedAtValue} />
                <InputError message={errors.played_at} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="result">Result</Label>
                <Select
                    name="result"
                    defaultValue={game?.result ?? ''}
                >
                    <SelectTrigger id="result">
                        <SelectValue placeholder="Select result (optional)" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="win">Win</SelectItem>
                        <SelectItem value="lost">Lost</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={errors.result} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="points">Points</Label>
                <Input
                    id="points"
                    name="points"
                    type="number"
                    min={0}
                    defaultValue={game?.points ?? ''}
                    placeholder="Points scored (optional)"
                />
                <InputError message={errors.points} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="comments">Comments</Label>
                <Input
                    id="comments"
                    name="comments"
                    defaultValue={game?.comments ?? ''}
                    placeholder="Comments (optional)"
                />
                <InputError message={errors.comments} />
            </div>
        </>
    );
}

export default function GamesIndex({
    games,
    filters,
    courts,
}: {
    games: PaginatedGames;
    filters: { search: string | null };
    courts: Court[];
}) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editGame, setEditGame] = useState<Game | null>(null);
    const [deleteGame, setDeleteGame] = useState<Game | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                index().url,
                { search: search || undefined },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Games" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Games</h1>
                        <p className="text-sm text-muted-foreground">
                            Manage all games ({games.total} total)
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <div className="relative w-64">
                            <Search className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Search games..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-8"
                            />
                        </div>
                        <Button onClick={() => setCreateOpen(true)}>
                            <CirclePlusIcon></CirclePlusIcon>
                            Add Game
                        </Button>
                    </div>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Title</TableHead>
                                <TableHead>Player</TableHead>
                                <TableHead>Format</TableHead>
                                <TableHead>Played At</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Result</TableHead>
                                <TableHead>Points</TableHead>
                                <TableHead>Video</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {games.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={9}
                                        className="py-8 text-center text-muted-foreground"
                                    >
                                        No games found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                games.data.map((game) => (
                                    <TableRow key={game.id}>
                                        <TableCell className="font-medium">
                                            {game.title}
                                        </TableCell>
                                        <TableCell>
                                            {game.player?.name ?? '—'}
                                        </TableCell>
                                        <TableCell>{game.format}</TableCell>
                                        <TableCell>
                                            {new Date(
                                                game.played_at,
                                            ).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell>
                                            {statusBadge(game.status)}
                                        </TableCell>
                                        <TableCell>
                                            {game.result ? (
                                                <span
                                                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white ${game.result === 'win' ? 'bg-green-500' : 'bg-red-500'}`}
                                                >
                                                    {game.result === 'win' ? 'Win' : 'Lost'}
                                                </span>
                                            ) : (
                                                <span className="text-xs text-muted-foreground">—</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {game.points ?? <span className="text-xs text-muted-foreground">—</span>}
                                        </TableCell>
                                        <TableCell>
                                            {vimeoStatusBadge(
                                                game.vimeo_status,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
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
                                                        onClick={() =>
                                                            setEditGame(game)
                                                        }
                                                    >
                                                        Edit
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link
                                                            href={
                                                                GameController.showUpload(
                                                                    game.uuid,
                                                                ).url
                                                            }
                                                        >
                                                            Upload Video
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        onClick={() =>
                                                            setDeleteGame(game)
                                                        }
                                                    >
                                                        Delete
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))
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

            {/* Create Game Modal */}
            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Create Game</DialogTitle>
                        <DialogDescription>
                            Add a new game record.
                        </DialogDescription>
                    </DialogHeader>

                    <Form
                        {...GameController.store.form()}
                        key={createOpen ? 'open' : 'closed'}
                        resetOnSuccess
                        onSuccess={() => setCreateOpen(false)}
                        className="space-y-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <GameFormFields
                                    courts={courts}
                                    errors={errors}
                                />

                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button variant="secondary">
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button disabled={processing} asChild>
                                        <button type="submit">
                                            Create Game
                                        </button>
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>

            {/* Edit Game Modal */}
            <Dialog
                open={editGame !== null}
                onOpenChange={(open) => {
                    if (!open) setEditGame(null);
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Game</DialogTitle>
                        <DialogDescription>
                            Update game details.
                        </DialogDescription>
                    </DialogHeader>

                    {editGame && (
                        <Form
                            {...GameController.update.form(editGame.uuid)}
                            key={editGame.id}
                            onSuccess={() => setEditGame(null)}
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <GameFormFields
                                        game={editGame}
                                        courts={courts}
                                        errors={errors}
                                    />

                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button variant="secondary">
                                                Cancel
                                            </Button>
                                        </DialogClose>
                                        <Button disabled={processing} asChild>
                                            <button type="submit">
                                                Update Game
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>

            {/* Delete Game Modal */}
            <Dialog
                open={deleteGame !== null}
                onOpenChange={(open) => {
                    if (!open) setDeleteGame(null);
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Game</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete{' '}
                            <span className="font-medium">
                                {deleteGame?.title}
                            </span>
                            ? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>

                    {deleteGame && (
                        <Form
                            {...GameController.destroy.form(deleteGame.uuid)}
                            onSuccess={() => setDeleteGame(null)}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <InputError message={errors.game} />

                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button variant="secondary">
                                                Cancel
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            variant="destructive"
                                            disabled={processing}
                                            asChild
                                        >
                                            <button type="submit">
                                                Delete
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
