<?php

use Illuminate\Support\Facades\Event;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\Cart as CartModel;
use Webkul\Checkout\Models\CartItem;
use Webkul\Customer\Contracts\Customer as CustomerContract;
use Webkul\Marketplace\Exceptions\VendorNoLongerEligibleException;
use Webkul\Marketplace\Listeners\RevalidateVendorBeforeOrderPlaced;
use Webkul\Marketplace\Models\FailedCartMatch;
use Webkul\Product\Contracts\Product as ProductContract;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

function createActiveCartWithProduct(CustomerContract $customer, ProductContract $product, int $quantity = 1): CartModel
{
    $cart = CartModel::factory()->create([
        'customer_id' => $customer->id,
        'is_active' => true,
    ]);

    CartItem::factory()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'sku' => $product->sku,
        'type' => 'simple',
        'name' => $product->name,
        'quantity' => $quantity,
        'price' => $product->price,
        'base_price' => $product->price,
        'total' => $product->price * $quantity,
        'base_total' => $product->price * $quantity,
    ]);

    return CartModel::find($cart->id);
}

it('shows only fully eligible vendors at the checkout vendor step, never an incomplete one', function () {
    $product = $this->makeTestProduct();

    $completeVendor = $this->makeTestSeller(['shop_name' => 'Complete Vendor Shop']);
    $this->makeTestOffer($completeVendor, $product, quantity: 10);

    $incompleteVendor = $this->makeTestSeller(['shop_name' => 'Incomplete Vendor Shop']);
    $this->makeTestOffer($incompleteVendor, $product, quantity: 0);

    $customer = $this->loginAsCustomer();
    createActiveCartWithProduct($customer, $product);

    $response = get(route('marketplace.checkout.vendor.index'));

    // Note: this page's layout embeds heavy inline <script> blocks
    // containing raw `<`/`>` operators, which confuses strip_tags()
    // (used internally by assertSeeText/assertDontSeeText) into eating
    // huge unrelated chunks of the body. assertSee/assertDontSee check
    // the raw response content instead, so they aren't affected.
    $response->assertOk();
    $response->assertSee('Complete Vendor Shop');
    $response->assertDontSee('Incomplete Vendor Shop');
});

it('rejects a manipulated seller_id that is not actually eligible', function () {
    $product = $this->makeTestProduct();

    $ineligibleVendor = $this->makeTestSeller();
    $this->makeTestOffer($ineligibleVendor, $product, quantity: 0);

    $customer = $this->loginAsCustomer();
    createActiveCartWithProduct($customer, $product);

    $response = post(route('marketplace.checkout.vendor.store'), [
        'seller_id' => $ineligibleVendor->id,
    ]);

    $response->assertRedirect(route('marketplace.checkout.vendor.index'));
    expect(session('marketplace.vendor_selection'))->toBeNull();
});

it('accepts selecting a genuinely eligible vendor', function () {
    $product = $this->makeTestProduct();

    $eligibleVendor = $this->makeTestSeller();
    $this->makeTestOffer($eligibleVendor, $product, quantity: 10);

    $customer = $this->loginAsCustomer();
    createActiveCartWithProduct($customer, $product);

    $response = post(route('marketplace.checkout.vendor.store'), [
        'seller_id' => $eligibleVendor->id,
    ]);

    $response->assertRedirect(route('marketplace.checkout.delivery.index'));
    expect((int) session('marketplace.vendor_selection'))->toBe($eligibleVendor->id);
});

it('records a failed cart match when zero vendors can fulfil the complete cart', function () {
    $product = $this->makeTestProduct();

    // No seller stocks this product at all.
    $customer = $this->loginAsCustomer();
    createActiveCartWithProduct($customer, $product);

    $countBefore = FailedCartMatch::count();

    get(route('marketplace.checkout.vendor.index'))->assertOk();

    expect(FailedCartMatch::count())->toBe($countBefore + 1);

    $match = FailedCartMatch::latest()->first();
    expect($match->customer_id)->toBe($customer->id);
    expect(collect($match->cart_snapshot)->pluck('product_id'))->toContain($product->id);
});

it('blocks order placement via checkout.order.save.before when the selected vendor lost eligibility', function () {
    $product = $this->makeTestProduct();
    $seller = $this->makeTestSeller();

    // Seller only has 1 unit, but we'll ask for more than that below.
    $this->makeTestOffer($seller, $product, quantity: 1);

    $cart = $this->makeTestCart([[$product, 5]]);

    Cart::setCart($cart);
    session(['marketplace.vendor_selection' => $seller->id]);

    $thrown = null;

    try {
        Event::dispatch('checkout.order.save.before', [[]]);
    } catch (VendorNoLongerEligibleException $e) {
        $thrown = $e;
    }

    expect($thrown)->not->toBeNull();
    expect($thrown->getMessage())->toContain('can no longer fulfil all the products');
});

it('does not interfere with checkout when no marketplace vendor is selected', function () {
    // A non-marketplace checkout (or one where the vendor step hasn't
    // run yet) must never be blocked by this listener.
    session()->forget('marketplace.vendor_selection');

    $listener = app(RevalidateVendorBeforeOrderPlaced::class);

    // Should return silently, not throw.
    $listener->handle([]);

    expect(true)->toBeTrue();
});
