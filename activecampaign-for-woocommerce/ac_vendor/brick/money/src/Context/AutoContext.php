<?php

declare (strict_types=1);
namespace AcVendor\Brick\Money\Context;

use AcVendor\Brick\Money\Context;
use AcVendor\Brick\Money\Currency;
use AcVendor\Brick\Math\BigDecimal;
use AcVendor\Brick\Math\BigNumber;
use AcVendor\Brick\Math\RoundingMode;
/**
 * Automatically adjusts the scale of a number to the strict minimum.
 */
final class AutoContext implements Context
{
    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency, int $roundingMode) : BigDecimal
    {
        if ($roundingMode !== RoundingMode::UNNECESSARY) {
            throw new \InvalidArgumentException('AutoContext only supports RoundingMode::UNNECESSARY');
        }
        return $amount->toBigDecimal()->stripTrailingZeros();
    }
    /**
     * {@inheritdoc}
     */
    public function getStep() : int
    {
        return 1;
    }
    /**
     * {@inheritdoc}
     */
    public function isFixedScale() : bool
    {
        return \false;
    }
}
