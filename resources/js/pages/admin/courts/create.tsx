import { Form, Head } from '@inertiajs/react';
import CourtController, {
    index,
} from '@/actions/App/Http/Controllers/Admin/CourtController';
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

type StatusOption = {
    value: string;
    label: string;
    color: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Courts',
        href: index().url,
    },
    {
        title: 'Create Court',
        href: CourtController.create().url,
    },
];

export default function CreateCourt({
    statuses,
}: {
    statuses: StatusOption[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Court" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Create Court</h1>
                    <p className="text-sm text-muted-foreground">
                        Add a new court to the system.
                    </p>
                </div>

                <Form
                    {...CourtController.store.form()}
                    className="max-w-lg space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
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
                                        placeholder="e.g. -0.1278"
                                    />
                                    <InputError message={errors.longitude} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <Select name="status" required>
                                    <SelectTrigger id="status">
                                        <SelectValue placeholder="Select a status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((status) => (
                                            <SelectItem
                                                key={status.value}
                                                value={status.value}
                                            >
                                                {status.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
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
                                    <button type="submit">Create Court</button>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
