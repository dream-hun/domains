import { Form, Head } from '@inertiajs/react';
import Player from '@vimeo/player';
import { ShieldAlert } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { index, update } from '@/routes/admin/override';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

const overrideStatusOptions = [
    { value: 'approved', label: 'Approve' },
    { value: 'rejected', label: 'Reject' },
];

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    approved: 'default',
    rejected: 'destructive',
    flagged: 'secondary',
    pending: 'outline',
};

function extractVimeoId(uri: string): string | null {
    return uri.match(/\/(\d+)$/)?.[1] ?? null;
}

function VimeoPlayer({
    vimeoUri,
    subtitle,
}: {
    vimeoUri: string | null;
    subtitle: string;
}) {
    const containerRef = useRef<HTMLDivElement>(null);
    const vimeoId = vimeoUri ? extractVimeoId(vimeoUri) : null;

    useEffect(() => {
        if (!containerRef.current || !vimeoId) return;

        const player = new Player(containerRef.current, {
            id: Number(vimeoId),
            byline: false,
            portrait: false,
            title: false,
            dnt: true,
            responsive: true,
        });

        return () => {
            player.destroy().catch(() => {});
        };
    }, [vimeoId]);

    if (!vimeoId) {
        return (
            <div className="flex aspect-video w-full items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/30">
                <p className="text-sm text-muted-foreground">
                    No video available
                </p>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-2">
            <div
                ref={containerRef}
                className="w-full overflow-hidden rounded-lg"
            />
            <p className="text-base text-muted-foreground">{subtitle}</p>
        </div>
    );
}

export default function OverrideShow({ game }: { game: Game }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Flagged Games', href: index().url },
        { title: game.title, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Override: ${game.title}`} />

            <div className="grid gap-6 p-6 lg:grid-cols-3">
                {/* Video — spans 2 columns */}
                <div className="lg:col-span-2">
                    <VimeoPlayer
                        vimeoUri={game.vimeo_uri}
                        subtitle={game.title}
                    />
                </div>

                {/* Sidebar */}
                <div className="flex flex-col gap-6">
                    {/* Game info */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Game Details</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Title
                                </span>
                                <span className="font-medium">
                                    {game.title}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Player
                                </span>
                                <span>{game.player?.name ?? '—'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Format
                                </span>
                                <span>{game.format}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Court
                                </span>
                                <span>{game.court?.name ?? '—'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Played
                                </span>
                                <span>
                                    {new Date(
                                        game.played_at,
                                    ).toLocaleDateString()}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Moderation history */}
                    {game.moderation.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Moderation History</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                {game.moderation.map((record) => (
                                    <div
                                        key={record.id}
                                        className="flex flex-col gap-1 text-sm"
                                    >
                                        <div className="flex items-center gap-2">
                                            <Badge
                                                variant={
                                                    statusVariant[
                                                        record.status
                                                    ] ?? 'outline'
                                                }
                                            >
                                                {record.status}
                                            </Badge>
                                            {record.is_override && (
                                                <Badge
                                                    variant="outline"
                                                    className="gap-1 border-orange-400 text-orange-600"
                                                >
                                                    <ShieldAlert className="size-3" />
                                                    Override
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="text-muted-foreground">
                                            {record.moderator?.name ?? '—'} &middot;{' '}
                                            {new Date(
                                                record.created_at,
                                            ).toLocaleDateString()}
                                        </p>
                                        <p>{record.reason}</p>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}

                    {/* Admin override form */}
                    <Card className="border-orange-400">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-orange-600">
                                <ShieldAlert className="size-5" />
                                Admin Override
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...update.form(game.uuid)}
                                className="space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="status">
                                                Decision
                                            </Label>
                                            <Select name="status" required>
                                                <SelectTrigger id="status">
                                                    <SelectValue placeholder="Select a decision" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {overrideStatusOptions.map(
                                                        (opt) => (
                                                            <SelectItem
                                                                key={opt.value}
                                                                value={
                                                                    opt.value
                                                                }
                                                            >
                                                                {opt.label}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={errors.status}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="reason">
                                                Reason
                                            </Label>
                                            <textarea
                                                id="reason"
                                                name="reason"
                                                placeholder="Provide a reason for this override decision..."
                                                rows={4}
                                                required
                                                className="flex w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm dark:aria-invalid:ring-destructive/40"
                                            />
                                            <InputError
                                                message={errors.reason}
                                            />
                                        </div>

                                        <Button
                                            disabled={processing}
                                            variant="destructive"
                                            className="w-full"
                                            asChild
                                        >
                                            <button type="submit">
                                                Submit Override
                                            </button>
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
