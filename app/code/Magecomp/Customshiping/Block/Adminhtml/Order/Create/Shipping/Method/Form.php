<?php
namespace Magecomp\Customshipping\Block\Adminhtml\Order\Create\Shipping\Method;

use Magecomp\Customshipping\Model\Carrier;

class Form extends \Magento\Sales\Block\Adminhtml\Order\Create\Shipping\Method\Form
{
    protected $activeMethodRate;

    public function getActiveCustomshippingMethod()
    {
        $rate = $this->getActiveMethodRate();
        return $rate && $rate->getCarrier() == Carrier::CODE ? $rate->getMethod() : '';
    }

    public function getActiveCustomshippingPrice()
    {
        $rate = $this->getActiveMethodRate();
        return $this->getActiveCustomshippingMethod() && $rate->getPrice() ? $rate->getPrice() * 1 : '';
    }

    public function isCustomshippingActive()
    {
        if (empty($this->activeMethodRate)) {
            $this->activeMethodRate = $this->getActiveMethodRate();
        }

        return $this->activeMethodRate && $this->activeMethodRate->getCarrier() == Carrier::CODE ? true : false;
    }

    public function getGroupShippingRates()
    {
        $rates = $this->getShippingRates();

        if (array_key_exists(Carrier::CODE, $rates)) {
            if (!$this->isCustomshippingActive()) {
                unset($rates[Carrier::CODE]);
            } else {
                $activeRateMethod = $this->getActiveCustomshippingMethod();
                foreach ($rates[Carrier::CODE] as $key => $rate) {
                    if ($rate->getMethod() != $activeRateMethod) {
                        unset($rates[Carrier::CODE][$key]);
                    }
                }
            }
        }

        return $rates;
    }
}
