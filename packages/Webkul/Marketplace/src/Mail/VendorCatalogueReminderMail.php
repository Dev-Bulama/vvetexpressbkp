<?php

namespace Webkul\Marketplace\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;
use Webkul\Admin\Mail\Mailable;
use Webkul\Marketplace\Models\Seller;

/**
 * "Action Required: Complete Your VetExpress Product Catalogue" - sent to a
 * vendor whose catalogue coverage triggered either an automated threshold
 * check or a manual admin send. Carries real, computed numbers only
 * (coverage %, actual missing/out-of-stock/low-stock products, real
 * failed-cart-match counts) - never fabricated demand or sales figures.
 */
class VendorCatalogueReminderMail extends Mailable
{
    public function __construct(
        public Seller $seller,
        public object $coverage,
        public Collection $missingProducts,
        public Collection $outOfStockProducts,
        public Collection $lowStockProducts,
        public bool $isUrgent = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->seller->email, $this->seller->name)],
            subject: $this->isUrgent
                ? 'Urgent: Action Required to Complete Your VetExpress Product Catalogue'
                : 'Action Required: Complete Your VetExpress Product Catalogue',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'marketplace::emails.vendor-catalogue-reminder',
        );
    }
}
