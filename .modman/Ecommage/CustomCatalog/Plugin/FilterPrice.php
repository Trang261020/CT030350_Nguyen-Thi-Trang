<?php

namespace Ecommage\CustomCatalog\Plugin;

class FilterPrice extends FulltextCollection
{
    public function aroundRenderLabelDependOnPrice($subject, callable $proceed, float $fromPrice, float $toPrice)
    {
        $finalFilterPrice = $this->getFinalPriceFilter();
        if ($finalFilterPrice) {
            [$from, $to] = explode('_', $finalFilterPrice);
            if ($from == $fromPrice && $to = $toPrice) {
                $toPrice = 999999999;
            }
        }

        return $proceed($fromPrice, $toPrice);
    }
}
