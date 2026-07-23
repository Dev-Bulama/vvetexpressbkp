<?php

namespace Webkul\Marketplace\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Core\Models\Channel;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Marketplace\Models\DeliveryAgent;
use Webkul\Marketplace\Models\Seller;
use Webkul\User\Models\Admin;

/**
 * One known-credential login per role, purely for manually testing this
 * marketplace end to end (admin back-office, seller dashboard, customer
 * checkout, delivery-agent app) in a non-production environment. Never run
 * this against a real deployment: it overwrites the password of whichever
 * admin account already exists to a fixed, publicly-known value.
 *
 * Seller and delivery-agent test logins already exist from
 * VetMarketplaceDemoSeeder (vendor1@vetexpress.demo / password123, and
 * tunde.rider@vetexpress.ng / TestRider123! respectively) - this seeder only
 * adds what that one doesn't: a known admin password and a dedicated test
 * customer account.
 */
class TestAccountsSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@example.com';

    public const ADMIN_PASSWORD = 'VetExpressAdmin123!';

    public const CUSTOMER_EMAIL = 'customer@vetexpress.demo';

    public const CUSTOMER_PASSWORD = 'CustomerDemo123!';

    public const SELLER_EMAIL = 'vendor1@vetexpress.demo';

    public const SELLER_PASSWORD = 'password123';

    public const AGENT_EMAIL = 'tunde.rider@vetexpress.ng';

    public const AGENT_PASSWORD = 'TestRider123!';

    public function run(): void
    {
        $channel = Channel::first();

        if (! $channel) {
            $this->command?->error('No channel found - run the essential seeder first.');

            return;
        }

        $this->seedAdmin();

        $this->seedCustomer($channel);

        $this->reportCredentials();
    }

    /**
     * Sets a known password on whichever admin account already exists
     * (normally just the installer's default one) rather than creating a
     * second admin, since Bagisto only ever needs one for this purpose.
     */
    protected function seedAdmin(): void
    {
        $admin = Admin::first();

        if (! $admin) {
            $this->command?->error('No admin account found - run the installer first.');

            return;
        }

        $admin->email = static::ADMIN_EMAIL;
        $admin->password = bcrypt(static::ADMIN_PASSWORD);
        $admin->status = 1;
        $admin->save();
    }

    protected function seedCustomer(Channel $channel): void
    {
        $repository = app(CustomerRepository::class);

        $existing = Customer::where('email', static::CUSTOMER_EMAIL)->first();

        if ($existing) {
            $existing->password = bcrypt(static::CUSTOMER_PASSWORD);
            $existing->is_verified = 1;
            $existing->status = 1;
            $existing->save();

            return;
        }

        $repository->create([
            'first_name' => 'Demo',
            'last_name' => 'Customer',
            'email' => static::CUSTOMER_EMAIL,
            'password' => bcrypt(static::CUSTOMER_PASSWORD),
            'password_confirmation' => static::CUSTOMER_PASSWORD,
            'customer_group_id' => 2,
            'channel_id' => $channel->id,
            'is_verified' => 1,
            'status' => 1,
        ]);
    }

    protected function reportCredentials(): void
    {
        $sellerExists = Seller::where('email', static::SELLER_EMAIL)->exists();
        $agentExists = DeliveryAgent::where('email', static::AGENT_EMAIL)->exists();

        $this->command?->warn('These are DEV/DEMO credentials only - never use them in production.');
        $this->command?->info('Admin:    '.static::ADMIN_EMAIL.' / '.static::ADMIN_PASSWORD.' - /admin/login');
        $this->command?->info('Customer: '.static::CUSTOMER_EMAIL.' / '.static::CUSTOMER_PASSWORD.' - /customer/login');
        $this->command?->info('Seller:   '.static::SELLER_EMAIL.' / '.static::SELLER_PASSWORD.' - /seller/login'.($sellerExists ? '' : ' (run VetMarketplaceDemoSeeder first)'));
        $this->command?->info('Agent:    '.static::AGENT_EMAIL.' / '.static::AGENT_PASSWORD.' - /delivery-agent/login'.($agentExists ? '' : ' (run VetMarketplaceDemoSeeder first)'));
    }
}
