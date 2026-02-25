<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Models\Game;
use Vimeo\Laravel\Facades\Vimeo;

final class InitiateVimeoUploadAction
{
    /** @return array{upload_link: string} */
    public function handle(Game $game, int $fileSize): array
    {
        // @phpstan-ignore staticMethod.notFound
        $response = Vimeo::request('/me/videos', [
            'upload' => [
                'approach' => 'tus',
                'size' => $fileSize,
            ],
            'name' => $game->title,
        ], 'POST');

        /** @var array{body: array{uri: string, upload: array{upload_link: string}}} $response */
        $body = $response['body'];

        $game->update([
            'vimeo_uri' => $body['uri'],
            'vimeo_status' => 'pending',
        ]);

        return ['upload_link' => $body['upload']['upload_link']];
    }
}
