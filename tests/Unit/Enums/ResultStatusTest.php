<?php

declare(strict_types=1);

use App\Enums\ResultStatus;

test('result status label returns correct string for each case', function (ResultStatus $status, string $expectedLabel): void {
    expect($status->label())->toBe($expectedLabel);
})->with([
    [ResultStatus::WIN, 'Win'],
    [ResultStatus::LOST, 'Lost'],
]);

test('result status color returns correct value for each case', function (ResultStatus $status, string $expectedColor): void {
    expect($status->color())->toBe($expectedColor);
})->with([
    [ResultStatus::WIN, 'green'],
    [ResultStatus::LOST, 'red'],
]);
