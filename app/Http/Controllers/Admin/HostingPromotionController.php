<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Hosting\Promotions\DeleteHostingPromotionAction;
use App\Actions\Hosting\Promotions\ListHostingPromotionAction;
use App\Actions\Hosting\Promotions\StoreHostingPromotionAction;
use App\Actions\Hosting\Promotions\UpdateHostingPromotionAction;
use App\Enums\Hosting\BillingCycle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHostingPromotionRequest;
use App\Http\Requests\Admin\UpdateHostingPromotionRequest;
use App\Models\HostingCategory;
use App\Models\HostingPlan;
use App\Models\HostingPromotion;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class HostingPromotionController extends Controller
{
    public function index(ListHostingPromotionAction $action): View|Factory
    {
        abort_if(Gate::denies('hosting_promotion_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $selectedCategoryId = request()->filled('category_id') ? (int) request()->input('category_id') : null;
        $selectedPlanId = request()->filled('plan_id') ? (int) request()->input('plan_id') : null;
        $promotions = $action->handle(10, $selectedCategoryId, $selectedPlanId);
        $categories = $this->categories();
        $plans = $this->plans();

        return view('admin.hosting-promotions.index', [
            'promotions' => $promotions,
            'categories' => $categories,
            'plans' => $plans,
            'filters' => [
                'category_id' => $selectedCategoryId,
                'plan_id' => $selectedPlanId,
            ],
        ]);
    }

    public function create(): View|Factory
    {
        abort_if(Gate::denies('hosting_promotion_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.hosting-promotions.create', [
            'plans' => $this->plans(),
            'billingCycles' => BillingCycle::cases(),
        ]);
    }

    public function store(StoreHostingPromotionRequest $request, StoreHostingPromotionAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('admin.hosting-promotions.index')
            ->with('success', 'Hosting promotion created successfully.');
    }

    public function edit(HostingPromotion $hostingPromotion): View|Factory
    {
        abort_if(Gate::denies('hosting_promotion_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $hostingPromotion->load('plan.category');

        return view('admin.hosting-promotions.edit', [
            'promotion' => $hostingPromotion,
            'plans' => $this->plans(),
            'billingCycles' => BillingCycle::cases(),
        ]);
    }

    public function update(UpdateHostingPromotionRequest $request, HostingPromotion $hostingPromotion, UpdateHostingPromotionAction $action): RedirectResponse
    {
        $action->handle($hostingPromotion->uuid, $request->validated());

        return to_route('admin.hosting-promotions.index')
            ->with('success', 'Hosting promotion updated successfully.');
    }

    public function destroy(HostingPromotion $hostingPromotion, DeleteHostingPromotionAction $action): RedirectResponse
    {
        abort_if(Gate::denies('hosting_promotion_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $action->handle($hostingPromotion->uuid);

        return to_route('admin.hosting-promotions.index')
            ->with('success', 'Hosting promotion deleted successfully.');
    }

    /**
     * @phpstan-return Collection<int, array{id:int,name:string,category:string|null}>
     */
    private function plans(): Collection
    {
        return HostingPlan::query()
            ->with('category:id,name')
            ->select(['id', 'name', 'category_id'])
            ->orderBy('name')
            ->get()
            ->map(fn (HostingPlan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'category' => $plan->category?->name,
            ]);
    }

    private function categories(): Collection
    {
        return HostingCategory::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }
}
