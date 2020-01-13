<?php
namespace Magecomp\Customshipping\Model\Config;

class Allowfor implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Admin')],
            ['value' => 1, 'label' => __('Customers')],
            ['value' => 2, 'label' => __('Both')],
        ];
    }
}