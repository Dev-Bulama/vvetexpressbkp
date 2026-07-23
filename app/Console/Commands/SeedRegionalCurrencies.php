<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Core\Models\Channel;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\ExchangeRateRepository;

/**
 * Idempotent, re-runnable setup for the storefront's currency switcher:
 * creates/updates the currencies VetXpress's target countries need and
 * attaches them to every channel so Bagisto's built-in currency switcher
 * (already fully wired in the theme's header) has them to list. Exchange
 * rates seeded here are starting placeholders only - the whole point of
 * using Bagisto's built-in exchange rate mechanism is that an admin can
 * edit them any time from Settings > Exchange Rates.
 */
class SeedRegionalCurrencies extends Command
{
    protected $signature = 'vetexpress:seed-regional-currencies';

    protected $description = 'Create/update the Ghana, Liberia, Cameroon, Ivory Coast, and Sierra Leone currencies, seed placeholder exchange rates against the store base currency, and attach them to every channel.';

    /**
     * code => [name, symbol, decimal, rate against 1 unit of the store's
     * base currency (NGN) - placeholders for the admin to verify/adjust].
     */
    protected array $currencies = [
        'USD' => ['name' => 'United States Dollar', 'symbol' => '$', 'decimal' => 2, 'rate' => 0.00065],
        'GHS' => ['name' => 'Ghanaian Cedi', 'symbol' => 'GH₵', 'decimal' => 2, 'rate' => 0.0101],
        'XOF' => ['name' => 'CFA Franc (Cameroon & Ivory Coast)', 'symbol' => 'CFA', 'decimal' => 0, 'rate' => 0.39],
        'SLE' => ['name' => 'Sierra Leonean Leone', 'symbol' => 'Le', 'decimal' => 2, 'rate' => 0.0146],
    ];

    public function handle(CurrencyRepository $currencyRepository, ExchangeRateRepository $exchangeRateRepository): int
    {
        foreach ($this->currencies as $code => $data) {
            $currency = $currencyRepository->findOneByField('code', $code);

            if ($currency) {
                $currencyRepository->update([
                    'name' => $data['name'],
                    'symbol' => $data['symbol'],
                    'decimal' => $data['decimal'],
                    'group_separator' => ',',
                    'decimal_separator' => '.',
                    'currency_position' => 'left',
                ], $currency->id);
            } else {
                $currency = $currencyRepository->create([
                    'code' => $code,
                    'name' => $data['name'],
                    'symbol' => $data['symbol'],
                    'decimal' => $data['decimal'],
                    'group_separator' => ',',
                    'decimal_separator' => '.',
                    'currency_position' => 'left',
                ]);
            }

            $exchangeRate = $exchangeRateRepository->findOneWhere(['target_currency' => $currency->id]);

            if ($exchangeRate) {
                $exchangeRateRepository->update(['rate' => $data['rate']], $exchangeRate->id);
            } else {
                $exchangeRateRepository->create([
                    'target_currency' => $currency->id,
                    'rate' => $data['rate'],
                ]);
            }

            $this->info("Currency {$code} ready (rate {$data['rate']} per base currency unit - adjust in Settings > Exchange Rates).");
        }

        $currencyIds = $currencyRepository->findWhereIn('code', array_keys($this->currencies))->pluck('id');

        Channel::all()->each(function (Channel $channel) use ($currencyIds) {
            $channel->currencies()->syncWithoutDetaching($currencyIds);
        });

        $this->info('Attached all seeded currencies to every channel.');

        if (config('responsecache.enabled')) {
            $this->call('responsecache:clear');
        }

        return self::SUCCESS;
    }
}
