<?php
namespace Magecomp\Customshipping\Plugin\Model\Quote;

use Magento\Quote\Api\Data\AddressInterface;

class AddressPlugin
{

    public function aroundCollectShippingRates(AddressInterface $subject, callable $proceed)
    {
        $price = null;
        $description = null;

        foreach ($subject->getAllShippingRates() as $rate) {
            if ($rate->getCode() == $subject->getShippingMethod()) {
                $price = $rate->getPrice();
                $description = $rate->getMethodTitle();
                break;
            }
        }

        $return = $proceed();

        if ($price !== null) {
            foreach ($subject->getAllShippingRates() as $rate) {
                if ($rate->getCode() == $subject->getShippingMethod()) {
                    $rate->setPrice($price);
                    $rate->setCost($price);
                    $rate->setMethodTitle($description);
                    break;
                }
            }
        }

        return $return;
    }
}