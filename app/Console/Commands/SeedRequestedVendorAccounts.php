<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Webkul\Marketplace\Models\Seller;

/**
 * One-off (but safely re-runnable) setup for the specific vendor accounts
 * requested, each pinned to the exact geo-coordinates given rather than a
 * guessed address - city/address are left blank for the vendor or admin to
 * fill in later via the seller profile page, since we were only given
 * coordinates, not a verified street address.
 *
 * Re-running this is safe: an existing account (matched by email) is left
 * untouched rather than having its password silently reset, so this can't
 * accidentally lock someone out after they've already changed it.
 */
class SeedRequestedVendorAccounts extends Command
{
    protected $signature = 'vetexpress:seed-requested-vendors';

    protected $description = 'Create the specific vendor accounts requested (Elite Veterinary Hub, Nana Agric Hub, etc.) with their given coordinates and print login credentials.';

    protected array $vendors = [
        ['shop_name' => 'Elite Veterinary Hub', 'email' => 'elite-veterinary-hub@vetexpress-vendors.local', 'latitude' => 4.8084300, 'longitude' => 7.0623770],
        ['shop_name' => 'Nana Agric Hub', 'email' => 'nana-agric-hub@vetexpress-vendors.local', 'latitude' => 9.154451, 'longitude' => 7.308253],
        ['shop_name' => 'Animora Veterinary Services', 'email' => 'animora-veterinary-services@vetexpress-vendors.local', 'latitude' => 12.025956, 'longitude' => 8.541524],
        ['shop_name' => 'Vetcrest Hub', 'email' => 'vetcrest-hub@vetexpress-vendors.local', 'latitude' => 6.6352258, 'longitude' => 3.3194234],
        ['shop_name' => 'Ayorinde Veterinary Services', 'email' => 'ayorinde-veterinary-services@vetexpress-vendors.local', 'latitude' => 7.3655589, 'longitude' => 3.8297834],
        ['shop_name' => 'Animora Veterinary Services (2)', 'email' => 'animora-veterinary-services-2@vetexpress-vendors.local', 'latitude' => 9.284334, 'longitude' => 12.424138],
    ];

    public function handle(): int
    {
        $rows = [];

        foreach ($this->vendors as $vendor) {
            $existing = Seller::where('email', $vendor['email'])->first();

            if ($existing) {
                $this->warn("Skipped {$vendor['shop_name']} - an account with {$vendor['email']} already exists (password left unchanged).");

                $rows[] = [$vendor['shop_name'], $vendor['email'], '(already exists - unchanged)'];

                continue;
            }

            $password = Str::password(12);

            Seller::create([
                'name' => $vendor['shop_name'],
                'shop_name' => $vendor['shop_name'],
                'email' => $vendor['email'],
                'password' => $password,
                'latitude' => $vendor['latitude'],
                'longitude' => $vendor['longitude'],
                'status' => Seller::STATUS_APPROVED,
                'rating' => 0,
            ]);

            $rows[] = [$vendor['shop_name'], $vendor['email'], $password];
        }

        $this->newLine();
        $this->table(['Shop', 'Email (login)', 'Password'], $rows);
        $this->newLine();
        $this->info('Login at /seller/login. Each account can update its shop name, address, logo, email, and password from Profile after logging in.');

        return self::SUCCESS;
    }
}
