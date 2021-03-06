<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    /**
     * Performs a checkout operation
     *
     * @param int|string $address_id
     * @param int|string $billing_address_id
     * @param string|null $additional_information
     * @param string $payment_mode
     * @param App\Models\Cart $cart
     * @return array
     */
    public function checkout(
        int|string $address_id,
        int|string $billing_address_id,
        string|null $additional_information,
        string $payment_mode,
        Cart $cart
    ): array {
        /** @var \App\Models\User */
        $authUser = auth()->user();
        $authUserAddress = $authUser->addresses()->findOrFail($address_id);
        $authUserBillingAddress = $billing_address_id
            ? $authUser->addresses()->findOrFail($billing_address_id)
            : $authUserAddress;

        try {
            DB::beginTransaction();

            $deliveryPrice = $authUserAddress->city === 'casablanca' ? 0 : (float)setting("cart.delivery_price");

            $order = $authUser->orders()->create([
                'address_name' => $authUserAddress->name,
                "address_phone" => $authUserAddress->phone,
                'address_email' => $authUserAddress->email,
                "address_city" => $authUserAddress->city,
                "address_zip" => $authUserAddress->zip,
                "address_line" => $authUserAddress->line,
                'billing_address_name' => $authUserBillingAddress->name,
                "billing_address_phone" => $authUserBillingAddress->phone,
                'billing_address_email' => $authUserBillingAddress->email,
                "billing_address_city" => $authUserBillingAddress->city,
                "billing_address_zip" => $authUserBillingAddress->zip,
                "billing_address_line" => $authUserBillingAddress->line,
                "subtotal" => $cart->subtotal,
                "discount_price" => $cart->discount_price,
                "dealer_discount_price" => $cart->getDealerDiscountPrice(),
                "coupon_code" => $cart->coupon_code,
                "delivery_price" => $deliveryPrice,
                "tax_price" => $cart->getTaxPrice($deliveryPrice),
                "total" => $cart->getTotal($deliveryPrice),
                "payment_mode" => $payment_mode,
                "additional_information" => $additional_information
            ]);

            foreach ($cart->items as $cartItem) {
                $orderItemData = $cartItem->replicate(["cart_id"])->withoutRelations()->toArray();
                $orderItem = $order->items()->create($orderItemData);

                foreach ($cartItem->media as $mediaItem) {
                    $mediaItem->copyToModel($orderItem);
                }

                $cartItem->delete();
            }

            $cart->delete();

            if ($payment_mode == "credit_card") {
                // TODO: perform some payment logic in the future
            }

            DB::commit();

            OrderPlaced::dispatch($order);

            return ["msg_type" => "success_message", "msg_content" => __("Thank you for your order")];
        } catch (\Exception $ex) {
            DB::rollback();

            Log::error("Exception Happened in CheckoutService@checkout : ",  [
                'exception message' => $ex->getMessage()
            ]);

            return ["msg_type" => "error_message", "msg_content" => __("Unknown error occurred. our team has been notified. try again !")];
        }
    }
}
