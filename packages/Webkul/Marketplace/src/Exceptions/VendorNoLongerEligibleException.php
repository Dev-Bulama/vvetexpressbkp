<?php

namespace Webkul\Marketplace\Exceptions;

/**
 * Thrown when the vendor the customer selected earlier in checkout no
 * longer has the complete cart at the moment the order is actually about
 * to be created (stock moved, the vendor was suspended, etc). Rendered as
 * a clean customer-facing message in bootstrap/app.php rather than a raw
 * server error.
 */
class VendorNoLongerEligibleException extends \Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? 'The selected vendor can no longer fulfil all the products and quantities in your cart. Please allow the system to find another complete vendor or adjust your cart.');
    }
}
