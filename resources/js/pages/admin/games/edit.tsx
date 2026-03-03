import { Form, Head } from '@inertiajs/react';
import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';
import GameController, {
    index,
} from '@/actions/App/Http/Controllers/Admin/GameController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type Court = { id: number; name: string };

type Game = {
    id: number;
    uuid: string;
    title: string;
    format: string;
    court_id: number | null;
    played_at: string;
    result: 'win' | 'lost' | null;
    points: number | null;
    comments: string | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Games',
        href: index().url,
    },
    {
        title: 'Edit Game',
        href: '#',
    },
];

const formats = ['1v1', '2v2', '3v3', '4v4', '5v5'];

export default function EditGame({ game, courts }: { game: Game; courts: Court[] }) {
    const initialDate = game.played_at ? new Date(game.played_at) : undefined;
    const [date, setDate] = useState<Date | undefined>(initialDate);
    const [calendarOpen, setCalendarOpen] = useState(false);

    const playedAtValue = date
        ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
        : '';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Game" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Edit Game</h1>
                    <p className="text-sm text-muted-foreground">
                        Update the game record.
                    </p>
                </div>

                <Form
                    {...GameController.update.patch(game)}
                    className="max-w-lg space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    placeholder="Game title"
                                    defaultValue={game.title}
                                    required
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="format">Format</Label>
                                <Select
                                    name="format"
                                    defaultValue={game.format}
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
                                    defaultValue={game.court_id ? String(game.court_id) : ''}
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
                                <Popover
                                    open={calendarOpen}
                                    onOpenChange={setCalendarOpen}
                                >
                                    <PopoverTrigger asChild>
                                        <Button
                                            variant="outline"
                                            type="button"
                                            className={cn(
                                                'justify-start font-normal',
                                                !date &&
                                                    'text-muted-foreground',
                                            )}
                                        >
                                            <CalendarIcon className="mr-2 size-4" />
                                            {date
                                                ? date.toLocaleDateString(
                                                      'default',
                                                      {
                                                          year: 'numeric',
                                                          month: 'long',
                                                          day: 'numeric',
                                                      },
                                                  )
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
                                <input
                                    type="hidden"
                                    name="played_at"
                                    value={playedAtValue}
                                />
                                <InputError message={errors.played_at} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="result">Result</Label>
                                <Select
                                    name="result"
                                    defaultValue={game.result ?? ''}
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
                                    defaultValue={game.points ?? ''}
                                    placeholder="Points scored (optional)"
                                />
                                <InputError message={errors.points} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="comments">Comments</Label>
                                <Textarea
                                    id="comments"
                                    name="comments"
                                    defaultValue={game.comments ?? ''}
                                    placeholder="Comments (optional)"
                                />
                                <InputError message={errors.comments} />
                            </div>

                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => window.history.back()}
                                >
                                    Cancel
                                </Button>
                                <Button disabled={processing} asChild>
                                    <button type="submit">Update Game</button>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
