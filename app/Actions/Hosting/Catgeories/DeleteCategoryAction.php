<?php

declare(strict_types=1);

namespace App\Actions\Hosting\Catgeories;

use App\Models\HostingCategory;

final readonly class DeleteCategoryAction
{
    /**
     * Handle the deletion of a hosting category
     *
     * @param  HostingCategory  $category  The category to delete
     */
    public function handle(HostingCategory $category): void
    {
        $category->delete();
    }
}
