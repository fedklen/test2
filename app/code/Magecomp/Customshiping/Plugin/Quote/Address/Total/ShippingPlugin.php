<?php
namespace Magecomp\Customshipping\Plugin\Quote\Address\Total;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\Shipping;
use Magecomp\Customshipping\Helper\Data;
use Magecomp\Customshipping\Model\Carrier;

class ShippingPlugin
{
    protected $customshippinghelper;

    public function __construct(Data $customshippinghelper) {
        $this->customshippinghelper = $customshippinghelper;
    }

    public function aroundCollect(
        Shipping $subject,
        callable $proceed,
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        $shipping = $shippingAssignment->getShipping();
        $address = $shipping->getAddress();
        $method = $address->getShippingMethod();

        if (!$this->customshippinghelper->IsCustomShippingAllowedForAdmin()
            || $address->getAddressType() != Address::ADDRESS_TYPE_SHIPPING
            || strpos($method, Carrier::CODE) === false
        ) {
            return $proceed($quote, $shippingAssignment, $total);
        }

        $customShippingOption = $this->getCustomShippingJsonToArray($method, $address);

        if ($customShippingOption && strpos($method, $customShippingOption['code']) !== false) {
            $shipping->setMethod($customShippingOption['code']);
            $address->setShippingMethod($customShippingOption['code']);
            $this->updateCustomRate($address, $customShippingOption);
        }

        return $proceed($quote, $shippingAssignment, $total);
    }

    protected function updateCustomRate($address, $customShippingOption)
    {
        if ($selectedRate = $this->getSelectedShippingRate($address, $customShippingOption['code'])) {
            $cost = (float) $customShippingOption['rate'];
            $description = trim($customShippingOption['description']);

            $selectedRate->setPrice($cost);
            $selectedRate->setCost($cost);

            if (!empty($description) || strlen($description) > 2) {
                $selectedRate->setMethodTitle($description);
            }
        }
    }

    private function getCustomShippingJsonToArray($json, $address)
    {
        $isJson = $this->customshippinghelper->IsCustomShippingJson($json);

        if ($json && !$isJson) {
            $rate = 0;
            if ($selectedRate = $this->getSelectedShippingRate($address, $json)) {
                $rate = $selectedRate->getPrice();
            }

            $jsonToArray = [
                'code' => $json,
                'type' => $this->customshippinghelper->getShippingCodeFromMethod($json),
                'rate' => $rate
            ];

            return $this->formatShippingArray($jsonToArray);
        }

        $jsonToArray = (array)json_decode($json, true);

        if (is_array($jsonToArray) && count($jsonToArray) == 4) {
            return $this->formatShippingArray($jsonToArray);
        }

        return false;
    }

    protected function getSelectedShippingRate($address, $code)
    {
        $selectedRate = null;

        if ($code) {
            foreach ($address->getAllShippingRates() as $rate) {
                if ($rate->getCode() == $code) {
                    $selectedRate = $rate;
                    break;
                }
            }
        }

        return $selectedRate;
    }

    protected function formatShippingArray($jsonToArray)
    {
        $customShippingOption = [
            'code' => '',
            'rate' => 0,
            'type' => '',
            'description' => ''
        ];

        foreach ((array) $jsonToArray as $key => $value) {
            $customShippingOption[$key] = $value;
        }

        return $customShippingOption;
    }
}