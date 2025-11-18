<?php

declare(strict_types=1);

namespace App\Actions\Hosting\Catgeories;

use App\Models\HostingCategory;

final readonly class UpdateCategoryAction
{
    /**
     * Handle the update of a hosting category
     *
     * @param  HostingCategory  $category  The category to update
     * @param  array<string, mixed>  $validatedData  The validated category data
     */
    public function handle(HostingCategory $category, array $validatedData): HostingCategory
    {
        $category->update($validatedData);

        return $category->fresh();
    }
}
