<?php

declare (strict_types=1);
namespace AcVendor\Brick\Money;

use AcVendor\Brick\Money\Exception\CurrencyConversionException;
use AcVendor\Brick\Math\BigNumber;
/**
 * Interface for exchange rate providers.
 */
interface ExchangeRateProvider
{
    /**
     * @param string $sourceCurrencyCode The source currency code.
     * @param string $targetCurrencyCode The target currency code.
     *
     * @return BigNumber|int|float|string The exchange rate.
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     */
    public function getExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode);
}
