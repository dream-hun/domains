<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Hosting\Catgeories\DeleteCategoryAction;
use App\Actions\Hosting\Catgeories\ListCategoryAction;
use App\Actions\Hosting\Catgeories\StoreCategoryAction;
use App\Actions\Hosting\Catgeories\UpdateCategoryAction;
use App\Enums\Hosting\CategoryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHostingCategoryRequest;
use App\Http\Requests\UpdateHostingCategoryRequest;
use App\Models\HostingCategory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class HostingCategoryController extends Controller
{
    public function __construct(
        private readonly ListCategoryAction $listCategoryAction,
        private readonly StoreCategoryAction $storeCategoryAction,
        private readonly UpdateCategoryAction $updateCategoryAction,
        private readonly DeleteCategoryAction $deleteCategoryAction
    ) {}

    public function index(): Factory|View
    {
        abort_if(Gate::denies('hosting_category_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $categories = $this->listCategoryAction->handle();

        return view('admin.hosting.categories.index', ['categories' => $categories]);
    }

    public function create(): Factory|View
    {
        abort_if(Gate::denies('hosting_category_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.hosting.categories.create', [
            'statuses' => CategoryStatus::cases(),
        ]);
    }

    public function store(StoreHostingCategoryRequest $request): RedirectResponse
    {
        $this->storeCategoryAction->handle($request->validated());

        return to_route('admin.hosting-categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(HostingCategory $hostingCategory): Factory|View
    {
        abort_if(Gate::denies('hosting_category_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.hosting.categories.edit', [
            'category' => $hostingCategory,
            'statuses' => CategoryStatus::cases(),
        ]);
    }

    public function update(UpdateHostingCategoryRequest $request, HostingCategory $hostingCategory): RedirectResponse
    {
        $this->updateCategoryAction->handle($hostingCategory, $request->validated());

        return to_route('admin.hosting-categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(HostingCategory $hostingCategory): RedirectResponse
    {
        abort_if(Gate::denies('hosting_category_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $this->deleteCategoryAction->handle($hostingCategory);

        return to_route('admin.hosting-categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
