<?php

namespace Webkul\Marketplace\Carriers;

use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Shipping\Carriers\AbstractShipping;

/**
 * Bridges our own "Choose a Delivery Service" checkout step into Bagisto's
 * real shipping-method pipeline, so the fee the customer picked flows
 * through the same battle-tested cart total/tax/order-creation code every
 * other carrier uses instead of a parallel total-calculation path. It has
 * exactly one rate: whatever real quote(s) the customer already confirmed
 * in session - it never invents a price of its own.
 */
class MarketplaceLogistics extends AbstractShipping
{
    protected $code = 'marketplace_logistics';

    protected $method = 'marketplace_logistics_marketplace_logistics';

    /**
     * Only offered once the customer has actually been through the
     * delivery-service step for the cart currently being checked out -
     * never available by default the way flatrate/free are.
     */
    public function isAvailable()
    {
        if (! parent::isAvailable()) {
            return false;
        }

        return ! empty(session('marketplace.delivery_selection'));
    }

    public function calculate()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        $selection = session('marketplace.delivery_selection', []);

        $totalFeeMinor = collect($selection)->sum('fee_minor');
        $serviceNames = collect($selection)->pluck('service_type_name')->unique()->implode(', ');

        $price = $totalFeeMinor / 100;

        $cartShippingRate = new CartShippingRate;
        $cartShippingRate->carrier = $this->getCode();
        $cartShippingRate->carrier_title = $this->getConfigData('title');
        $cartShippingRate->method = $this->getMethod();
        $cartShippingRate->method_title = $serviceNames ?: $this->getConfigData('title');
        $cartShippingRate->method_description = $this->getConfigData('description');
        $cartShippingRate->price = core()->convertPrice($price);
        $cartShippingRate->base_price = $price;

        return $cartShippingRate;
    }
}
