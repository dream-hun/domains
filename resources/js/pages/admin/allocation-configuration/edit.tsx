import { Form, Head } from '@inertiajs/react';
import {
    edit,
    update,
} from '@/actions/App/Http/Controllers/Admin/AllocationConfigurationController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Config = {
    id: number;
    insurance_percentage: number;
    savings_percentage: number;
    pathway_percentage: number;
    administration_percentage: number;
};

type Props = {
    config: Config;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Allocation Configuration', href: edit().url },
];

export default function AllocationConfigurationEdit({ config }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Allocation Configuration" />

            <div className="flex max-w-2xl flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Allocation Configuration
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Set the percentage split for the $1 per game allocation.
                        Percentages must sum to 100. A new configuration is
                        saved on each update; existing allocations are
                        unaffected.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Percentage Split</CardTitle>
                        <CardDescription>
                            Each approved game allocates $1 split across these
                            four categories.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...update.form()}
                            className="flex flex-col gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="insurance_percentage">
                                            Insurance (%)
                                        </Label>
                                        <Input
                                            id="insurance_percentage"
                                            name="insurance_percentage"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            defaultValue={
                                                config.insurance_percentage
                                            }
                                            required
                                        />
                                        <InputError
                                            message={
                                                errors.insurance_percentage
                                            }
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="savings_percentage">
                                            Savings (%)
                                        </Label>
                                        <Input
                                            id="savings_percentage"
                                            name="savings_percentage"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            defaultValue={
                                                config.savings_percentage
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.savings_percentage}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="pathway_percentage">
                                            Pathway (%)
                                        </Label>
                                        <Input
                                            id="pathway_percentage"
                                            name="pathway_percentage"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            defaultValue={
                                                config.pathway_percentage
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.pathway_percentage}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="administration_percentage">
                                            Administration (%)
                                        </Label>
                                        <Input
                                            id="administration_percentage"
                                            name="administration_percentage"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            defaultValue={
                                                config.administration_percentage
                                            }
                                            required
                                        />
                                        <InputError
                                            message={
                                                errors.administration_percentage
                                            }
                                        />
                                    </div>

                                    <Button
                                        disabled={processing}
                                        asChild
                                        className="w-fit"
                                    >
                                        <button type="submit">
                                            Save Configuration
                                        </button>
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
