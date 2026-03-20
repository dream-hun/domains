<?php

declare(strict_types=1);

use App\Enums\Vps\VpsInstanceStatus;

test('VpsInstanceStatus returns correct labels/colors/icons', function (): void {
    expect(VpsInstanceStatus::Running->label())->toBe('Running');
    expect(VpsInstanceStatus::Running->color())->toBe('bg-success');
    expect(VpsInstanceStatus::Running->icon())->toBe('bi bi-play-circle');

    expect(VpsInstanceStatus::Stopped->label())->toBe('Stopped');
    expect(VpsInstanceStatus::Stopped->color())->toBe('bg-secondary');
    expect(VpsInstanceStatus::Stopped->icon())->toBe('bi bi-stop-circle');

    expect(VpsInstanceStatus::Error->label())->toBe('Error');
    expect(VpsInstanceStatus::Error->color())->toBe('bg-danger');
    expect(VpsInstanceStatus::Error->icon())->toBe('bi bi-exclamation-triangle');

    expect(VpsInstanceStatus::Installing->label())->toBe('Installing');
    expect(VpsInstanceStatus::Installing->color())->toBe('bg-warning');
    expect(VpsInstanceStatus::Installing->icon())->toBe('bi bi-gear');

    expect(VpsInstanceStatus::Unknown->label())->toBe('Unknown');
    expect(VpsInstanceStatus::Unknown->color())->toBe('bg-info');
    expect(VpsInstanceStatus::Unknown->icon())->toBe('bi bi-question-circle');
});
