import { Form, Head } from '@inertiajs/react';
import GameController, { index } from '@/actions/App/Http/Controllers/Admin/GameController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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

type User = {
    id: number;
    name: string;
};

type Court = {
    id: number;
    name: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Games',
        href: index().url,
    },
    {
        title: 'Create Game',
        href: GameController.create().url,
    },
];

const formats = ['1v1', '2v2', '3v3', '4v4', '5v5'];

export default function CreateGame({
    users,
    courts,
}: {
    users: User[];
    courts: Court[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Game" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Create Game</h1>
                    <p className="text-muted-foreground text-sm">Add a new game record.</p>
                </div>

                <Form {...GameController.store.form()} className="max-w-lg space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    placeholder="Game title"
                                    required
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="format">Format</Label>
                                <Select name="format" defaultValue="5v5" required>
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
                                <Select name="court_id">
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
                                <Label htmlFor="player_id">Player</Label>
                                <Select name="player_id" required>
                                    <SelectTrigger id="player_id">
                                        <SelectValue placeholder="Select a player" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {users.map((user) => (
                                            <SelectItem key={user.id} value={String(user.id)}>
                                                {user.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.player_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="played_at">Played At</Label>
                                <Input
                                    id="played_at"
                                    name="played_at"
                                    type="datetime-local"
                                    required
                                />
                                <InputError message={errors.played_at} />
                            </div>

                            <div className="flex gap-2">
                                <Button type="button" variant="secondary" onClick={() => window.history.back()}>
                                    Cancel
                                </Button>
                                <Button disabled={processing} asChild>
                                    <button type="submit">Create Game</button>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
