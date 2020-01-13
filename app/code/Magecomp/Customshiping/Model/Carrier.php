<?php
namespace Magecomp\Customshipping\Model;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Helper\Carrier as ShippingCarrierHelper;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magecomp\Customshipping\Helper\Data;
use Psr\Log\LoggerInterface;

class Carrier extends AbstractCarrier implements CarrierInterface
{
    const CODE = 'customshipping';

    protected $_code = self::CODE;
    protected $_rateMethodFactory;
    protected $_carrierHelper;
    protected $_rateFactory;
    protected $_state;
    protected $_CustomshippingHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateFactory,
        ShippingCarrierHelper $carrierHelper,
        MethodFactory $rateMethodFactory,
        State $state,
        Data $CustomshippingHelper,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->_scopeConfig = $scopeConfig;
        $this->_rateErrorFactory = $rateErrorFactory;
        $this->_logger = $logger;
        $this->_rateFactory = $rateFactory;
        $this->_carrierHelper = $carrierHelper;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_state = $state;
        $this->_CustomshippingHelper = $CustomshippingHelper;
    }

    public function collectRates(RateRequest $request)
    {
        $result = $this->_rateFactory->create();

        if (!$this->getConfigFlag('active') || (!$this->isAdmin() && !$this->_CustomshippingHelper->IsCustomShippingAllowedForFrontend())) {
            return $result;
        }

        foreach ($this->_CustomshippingHelper->getShippingType() as $shippingType) {
            $rate = $this->_rateMethodFactory->create();
            $rate->setCarrier($this->_code);
            $rate->setCarrierTitle($this->getConfigData('title'));
            $rate->setMethod($shippingType['code']);
            $rate->setMethodTitle($shippingType['title']);
            $rate->setCost($shippingType['price']);
            $rate->setPrice($shippingType['price']);

            $result->append($rate);
        }

        return $result;
    }

    public function getAllowedMethods()
    {
        return [$this->getCarrierCode() => __($this->getConfigData('name'))];
    }

    public function isTrackingAvailable()
    {
        return false;
    }

    protected function isAdmin()
    {
        return $this->_state->getAreaCode() == FrontNameResolver::AREA_CODE;
    }
}