import { Form, Head } from '@inertiajs/react';
import { edit, update } from '@/actions/App/Http/Controllers/Admin/RankingConfigurationController';
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
    win_weight: number;
    loss_weight: number;
    game_count_weight: number;
    frequency_weight: number;
};

type Props = {
    config: Config;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ranking Configuration', href: edit().url },
];

export default function RankingEdit({ config }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ranking Configuration" />

            <div className="flex flex-col gap-6 p-6 max-w-2xl">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Ranking Configuration
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Adjust the weights used to calculate player scores. A
                        new configuration is saved on each update and
                        recalculation is queued automatically.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Score Formula</CardTitle>
                        <CardDescription>
                            score = (wins × win_weight) + (losses ×
                            loss_weight) + (total_games × game_count_weight) +
                            (recent_30d_games × frequency_weight)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form {...update.form()} className="flex flex-col gap-4">
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="win_weight">
                                            Win Weight
                                        </Label>
                                        <Input
                                            id="win_weight"
                                            name="win_weight"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            defaultValue={config.win_weight}
                                            required
                                        />
                                        <InputError
                                            message={errors.win_weight}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="loss_weight">
                                            Loss Weight
                                        </Label>
                                        <Input
                                            id="loss_weight"
                                            name="loss_weight"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            defaultValue={config.loss_weight}
                                            required
                                        />
                                        <InputError
                                            message={errors.loss_weight}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="game_count_weight">
                                            Game Count Weight
                                        </Label>
                                        <Input
                                            id="game_count_weight"
                                            name="game_count_weight"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            defaultValue={
                                                config.game_count_weight
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.game_count_weight}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="frequency_weight">
                                            Frequency Weight (last 30 days)
                                        </Label>
                                        <Input
                                            id="frequency_weight"
                                            name="frequency_weight"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            defaultValue={
                                                config.frequency_weight
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.frequency_weight}
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
