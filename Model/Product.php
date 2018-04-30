<?php
/**
 * Created by PhpStorm.
 * User: ahmed
 * Date: 14/02/18
 * Time: 12:04 ص
 */

namespace DPDFrance\Shipping\Model;


use Magento\Framework\App\ObjectManager;

class Product
{
    private $parent_cart_item;
    private $cart_item;
    private $cart_product;
    private $loaded_product;
    private $quantity;

    public function __construct($cart_item, $parent_cart_item) {
        $this->cart_item = $cart_item;
        $this->cart_product = $cart_item->getProduct();
        $this->parent_cart_item = $parent_cart_item;
        $this->quantity = isset($parent_cart_item) ? $parent_cart_item->getQty() : $cart_item->getQty();
    }

    public function getOption($option_name, $get_by_id=false) {
        $value = null;
        $product = $this->cart_product;
        foreach ($product->getOptions() as $option) {
            if ($option->getTitle()==$option_name) {
                $custom_option = $product->getCustomOption('option_'.$option->getId());
                if ($custom_option) {
                    $value = $custom_option->getValue();
                    if ($option->getType()=='drop_down' && !$get_by_id) {
                        $option_value = $option->getValueById($value);
                        if ($option_value) $value = $option_value->getTitle();
                    }
                }
                break;
            }
        }
        return $value;
    }

    public function getAttribute($attribute_name, $get_by_id=false) {
        $value = null;
        $product = $this->_getLoadedProduct();
        $attribute = $product->getResource()->getAttribute($attribute_name);
        if ($attribute) {
            $input_type = $attribute->getFrontend()->getInputType();
            switch ($input_type) {
                case 'select' :
                    $value = $get_by_id ? $product->getData($attribute_name) : $product->getAttributeText($attribute_name);
                    break;
                default :
                    $value = $product->getData($attribute_name);
                    break;
            }
        }
        return $value;
    }

    private function _getLoadedProduct() {
        $objectManager = ObjectManager::getInstance();
        if (!isset($this->loaded_product)) $this->loaded_product = $objectManager->create('Magento\Catalog\Model\Product')->load($this->cart_product->getId());
        return $this->loaded_product;
    }

    public function getQuantity() {
        return $this->quantity;
    }

    public function getName() {
        return $this->cart_product->getName();
    }

    public function getSku() {
        return $this->cart_product->getSku();
    }

    public function getStockData($key) {
        $stock = $this->cart_product->getStockItem();
        switch ($key) {
            case 'is_in_stock':
                return (bool)$stock->getIsInStock();
            case 'quantity':
                $quantity = $stock->getQty();
                return $stock->getIsQtyDecimal() ? (float)$quantity : (int)$quantity;
        }
        return null;
    }
}