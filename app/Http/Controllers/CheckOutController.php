<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Coupon\CouponService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

final class CheckOutController extends Controller
{
    public function index(): Factory|View
    {
        return view('checkout.index');
    }

    /**
     * @throws Exception
     */
    public function applyCoupon($couponCode)
    {
        try {
            $couponService = new CouponService();
            $coupon = $couponService->validateCoupon($couponCode);

            // Calculate cart total
            $cartItems = Cart::getContent();
            $amount = $cartItems->sum(function ($item) {
                return $item->price * $item->quantity;
            });

            // Calculate discount amount
            $discountAmount = 0;
            if ($coupon->type->value === 'fixed') {
                $discountAmount = min($coupon->value, $amount); // Don't exceed cart total
            } elseif ($coupon->type->value === 'percentage') {
                $discountAmount = $amount * ($coupon->value / 100);
            }

            return response()->json([
                'success' => true,
                'discount' => $discountAmount,
                'total' => $amount - $discountAmount,
                'coupon' => $coupon->code,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function proceed(): JsonResponse
    {
        try {
            $request = request();
            $cartItems = Cart::getContent();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your cart is empty',
                ], 400);
            }

            // Validate required checkout data
            $paymentMethod = $request->input('payment_method');
            $total = $request->input('total');

            if (! $paymentMethod || ! $total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required checkout information',
                ], 400);
            }

            // Store checkout data in session
            session([
                'checkout' => [
                    'payment_method' => $paymentMethod,
                    'coupon_code' => $request->input('coupon_code'),
                    'discount' => $request->input('discount', 0),
                    'total' => $total,
                    'cart_items' => $cartItems->toArray(),
                    'created_at' => now()->toISOString(),
                ],
            ]);

            return response()->json([
                'success' => true,
                'redirect_url' => route('payment.index'),
                'message' => 'Proceeding to payment...',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to proceed to payment: '.$e->getMessage(),
            ], 500);
        }
    }
}
