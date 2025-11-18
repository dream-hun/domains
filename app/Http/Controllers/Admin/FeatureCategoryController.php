<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\FeatureCategory\CreateFeatureCategoryAction;
use App\Actions\FeatureCategory\DeleteFeatureCategoryAction;
use App\Actions\FeatureCategory\ListFeatureCategoryAction;
use App\Actions\FeatureCategory\UpdateFeatureCategoryAction;
use App\Enums\Hosting\CategoryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFeatureCategoryRequest;
use App\Http\Requests\Admin\UpdateFeatureCategoryRequest;
use App\Models\FeatureCategory;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class FeatureCategoryController extends Controller
{
    public function index(ListFeatureCategoryAction $action): View|Factory
    {
        abort_if(Gate::denies('feature_category_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $featureCategories = $action->handle();

        return view('admin.feature-categories.index', ['featureCategories' => $featureCategories]);
    }

    public function create(): View|Factory
    {
        abort_if(Gate::denies('feature_category_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.feature-categories.create', [
            'statuses' => CategoryStatus::cases(),
        ]);
    }

    public function store(StoreFeatureCategoryRequest $request, CreateFeatureCategoryAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('admin.feature-categories.index')->with('success', 'Feature category created successfully.');
    }

    public function edit(FeatureCategory $featureCategory): View|Factory
    {
        abort_if(Gate::denies('feature_category_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.feature-categories.edit', [
            'featureCategory' => $featureCategory,
            'statuses' => CategoryStatus::cases(),
        ]);
    }

    public function update(UpdateFeatureCategoryRequest $request, FeatureCategory $featureCategory, UpdateFeatureCategoryAction $action): RedirectResponse
    {
        $action->handle($featureCategory, $request->validated());

        return to_route('admin.feature-categories.index')->with('success', 'Feature category updated successfully.');
    }

    public function destroy(FeatureCategory $featureCategory, DeleteFeatureCategoryAction $action): RedirectResponse
    {
        abort_if(Gate::denies('feature_category_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        try {
            $action->handle($featureCategory);

            return to_route('admin.feature-categories.index')->with('success', 'Feature category deleted successfully.');
        } catch (Exception $exception) {
            return to_route('admin.feature-categories.index')->with('error', $exception->getMessage());
        }
    }
}
