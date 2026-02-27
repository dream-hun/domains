import { Form, Head } from '@inertiajs/react';
import Player from '@vimeo/player';
import { useEffect, useRef } from 'react';
import {
    index,
    update,
} from '@/actions/App/Http/Controllers/Admin/ModerationController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
};

const statusOptions = [
    { value: 'approved', label: 'Approve' },
    { value: 'rejected', label: 'Reject' },
    { value: 'flagged', label: 'Flag' },
];

function extractVimeoId(uri: string): string | null {
    return uri.match(/\/(\d+)$/)?.[1] ?? null;
}

function VimeoPlayer({ vimeoUri, subtitle }: { vimeoUri: string | null; subtitle: string }) {
    const containerRef = useRef<HTMLDivElement>(null);
    const vimeoId = vimeoUri ? extractVimeoId(vimeoUri) : null;

    useEffect(() => {
        if (!containerRef.current || !vimeoId) return;

        const player = new Player(containerRef.current, {
            id: Number(vimeoId),
            badge: 0,
            byline: false,
            portrait: false,
            title: false,
            share: false,
            watch_later: false,
            like: false,
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
                <p className="text-sm text-muted-foreground">No video available</p>
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-2">
            <div ref={containerRef} className="w-full overflow-hidden rounded-lg" />
            <p className="text-base text-muted-foreground">{subtitle}</p>
        </div>
    );
}

export default function ModerationShow({ game }: { game: Game }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Moderation Queue', href: index().url },
        { title: game.title, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Review: ${game.title}`} />

            <div className="grid gap-6 p-6 lg:grid-cols-3">
                {/* Video — spans 2 columns */}
                <div className="lg:col-span-2">
                    <VimeoPlayer vimeoUri={game.vimeo_uri} subtitle={game.title} />
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
                                <span className="text-muted-foreground">Title</span>
                                <span className="font-medium">{game.title}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Player</span>
                                <span>{game.player?.name ?? '—'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Format</span>
                                <span>{game.format}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Court</span>
                                <span>{game.court?.name ?? '—'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Played</span>
                                <span>
                                    {new Date(game.played_at).toLocaleDateString()}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Review form */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Review Decision</CardTitle>
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
                                                    {statusOptions.map(
                                                        (opt) => (
                                                            <SelectItem
                                                                key={opt.value}
                                                                value={opt.value}
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
                                                placeholder="Provide a reason for this decision..."
                                                rows={4}
                                                required
                                                className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive flex w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                            />
                                            <InputError
                                                message={errors.reason}
                                            />
                                        </div>

                                        <Button
                                            disabled={processing}
                                            className="w-full"
                                            asChild
                                        >
                                            <button type="submit">
                                                Submit Decision
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
