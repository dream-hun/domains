<?php

declare(strict_types=1);

namespace App\Actions\FeatureCategory;

use App\Models\FeatureCategory;
use Exception;
use Illuminate\Support\Facades\Log;

final class DeleteFeatureCategoryAction
{
    public function handle(FeatureCategory $featureCategory): void
    {
        // Check if category has associated features
        throw_if($featureCategory->hostingFeatures()->count() > 0, Exception::class, 'Cannot delete category with associated hosting features. Please remove or reassign features first.');

        $categoryId = $featureCategory->id;
        $categoryName = $featureCategory->name;

        $featureCategory->delete();

        Log::info('Feature category deleted successfully', [
            'category_id' => $categoryId,
            'category_name' => $categoryName,
        ]);
    }
}
