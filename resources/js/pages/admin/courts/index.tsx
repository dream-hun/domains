import { Form, Head, router } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import { useEffect, useState } from 'react';
import CourtController, { index } from '@/actions/App/Http/Controllers/Admin/CourtController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import type { BreadcrumbItem } from '@/types';

type StatusOption = {
    value: string;
    label: string;
    color: string;
};

type CourtUser = {
    id: number;
    name: string;
};

type Court = {
    id: number;
    uuid: string;
    name: string;
    country: string;
    city: string;
    latitude: number | null;
    longitude: number | null;
    status: string;
    created_by: number;
    created_at: string;
    updated_at: string;
    created_by_user: CourtUser | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedCourts = {
    data: Court[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Courts',
        href: index().url,
    },
];

function CourtFormFields({
    court,
    statuses,
    errors,
}: {
    court?: Court;
    statuses: StatusOption[];
    errors: Record<string, string>;
}) {
    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    defaultValue={court?.name}
                    placeholder="Court name"
                    required
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="country">Country</Label>
                <Input
                    id="country"
                    name="country"
                    defaultValue={court?.country}
                    placeholder="Country"
                    required
                />
                <InputError message={errors.country} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="city">City</Label>
                <Input
                    id="city"
                    name="city"
                    defaultValue={court?.city}
                    placeholder="City"
                    required
                />
                <InputError message={errors.city} />
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="latitude">Latitude</Label>
                    <Input
                        id="latitude"
                        name="latitude"
                        type="number"
                        step="any"
                        defaultValue={court?.latitude ?? ''}
                        placeholder="e.g. 51.5074"
                    />
                    <InputError message={errors.latitude} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="longitude">Longitude</Label>
                    <Input
                        id="longitude"
                        name="longitude"
                        type="number"
                        step="any"
                        defaultValue={court?.longitude ?? ''}
                        placeholder="e.g. -0.1278"
                    />
                    <InputError message={errors.longitude} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="status">Status</Label>
                <Select name="status" defaultValue={court?.status} required>
                    <SelectTrigger id="status">
                        <SelectValue placeholder="Select a status" />
                    </SelectTrigger>
                    <SelectContent>
                        {statuses.map((status) => (
                            <SelectItem key={status.value} value={status.value}>
                                {status.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.status} />
            </div>
        </>
    );
}

export default function CourtsIndex({
    courts,
    statuses,
    filters,
}: {
    courts: PaginatedCourts;
    statuses: StatusOption[];
    filters: { search: string | null };
}) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editCourt, setEditCourt] = useState<Court | null>(null);
    const [deleteCourt, setDeleteCourt] = useState<Court | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    const statusMap = Object.fromEntries(statuses.map((s) => [s.value, s]));

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
            <Head title="Courts" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Courts</h1>
                        <p className="text-muted-foreground text-sm">
                            Manage all courts ({courts.total} total)
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Input
                            placeholder="Search courts..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-64"
                        />
                        <Button onClick={() => setCreateOpen(true)}>Add Court</Button>
                    </div>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Country</TableHead>
                                <TableHead>City</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Created By</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {courts.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        No courts found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                courts.data.map((court) => {
                                    const status = statusMap[court.status];

                                    return (
                                        <TableRow key={court.id}>
                                            <TableCell className="font-medium">
                                                {court.name}
                                            </TableCell>
                                            <TableCell>{court.country}</TableCell>
                                            <TableCell>{court.city}</TableCell>
                                            <TableCell>
                                                {status && (
                                                    <span
                                                        className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white ${status.color}`}
                                                    >
                                                        {status.label}
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {court.created_by_user?.name ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="icon">
                                                            <MoreHorizontal className="size-4" />
                                                            <span className="sr-only">Actions</span>
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem
                                                            onClick={() => setEditCourt(court)}
                                                        >
                                                            Edit
                                                        </DropdownMenuItem>
                                                        <DropdownMenuSeparator />
                                                        <DropdownMenuItem
                                                            variant="destructive"
                                                            onClick={() => setDeleteCourt(court)}
                                                        >
                                                            Delete
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

                {courts.last_page > 1 && (
                    <div className="flex items-center justify-center gap-1">
                        {courts.links.map((link, i) => (
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
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                )}
                            </Button>
                        ))}
                    </div>
                )}
            </div>

            {/* Create Court Modal */}
            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Create Court</DialogTitle>
                        <DialogDescription>Add a new court to the system.</DialogDescription>
                    </DialogHeader>

                    <Form
                        {...CourtController.store.form()}
                        key={createOpen ? 'open' : 'closed'}
                        resetOnSuccess
                        onSuccess={() => setCreateOpen(false)}
                        className="space-y-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <CourtFormFields statuses={statuses} errors={errors} />

                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button variant="secondary">Cancel</Button>
                                    </DialogClose>

                                    <Button disabled={processing} asChild>
                                        <button type="submit">Create Court</button>
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>

            {/* Edit Court Modal */}
            <Dialog
                open={editCourt !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditCourt(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Court</DialogTitle>
                        <DialogDescription>Update court details.</DialogDescription>
                    </DialogHeader>

                    {editCourt && (
                        <Form
                            {...CourtController.update.form(editCourt.uuid)}
                            key={editCourt.id}
                            onSuccess={() => setEditCourt(null)}
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <CourtFormFields
                                        court={editCourt}
                                        statuses={statuses}
                                        errors={errors}
                                    />

                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button variant="secondary">Cancel</Button>
                                        </DialogClose>

                                        <Button disabled={processing} asChild>
                                            <button type="submit">Update Court</button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>

            {/* Delete Court Modal */}
            <Dialog
                open={deleteCourt !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleteCourt(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Court</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete{' '}
                            <span className="font-medium">{deleteCourt?.name}</span>? This action
                            cannot be undone.
                        </DialogDescription>
                    </DialogHeader>

                    {deleteCourt && (
                        <Form
                            {...CourtController.destroy.form(deleteCourt.uuid)}
                            onSuccess={() => setDeleteCourt(null)}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <InputError message={errors.court} />

                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button variant="secondary">Cancel</Button>
                                        </DialogClose>

                                        <Button variant="destructive" disabled={processing} asChild>
                                            <button type="submit">Delete</button>
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
