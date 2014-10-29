<?php
/**
 * Copyright (c) 2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the eBay Enterprise
 * Magento Extensions End User License Agreement
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://www.ebayenterprise.com/files/pdf/Magento_Connect_Extensions_EULA_050714.pdf
 *
 * @copyright   Copyright (c) 2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://www.ebayenterprise.com/files/pdf/Magento_Connect_Extensions_EULA_050714.pdf  eBay Enterprise Magento Extensions End User License Agreement
 *
 */

class EbayEnterprise_Affiliate_Block_Beacon extends Mage_Core_Block_Template
{
	/**
	 * The 'PID' beacon URL querystring key
	 */
	const KEY_PID = 'PID';
	/**
	 * The 'OID' beacon URL querystring key
	 */
	const KEY_OID = 'OID';
	/**
	 * The 'AMOUNT' beacon URL querystring key
	 */
	const KEY_AMOUNT = 'AMOUNT';
	/**
	 * The 'TYPE' beacon URL querystring key
	 */
	const KEY_TYPE = 'TYPE';
	/**
	 * The 'QTY' beacon URL querystring key
	 */
	const KEY_QTY = 'QTY';
	/**
	 * The 'TOTALAMOUNT' beacon URL querystring key
	 */
	const KEY_TOTALAMOUNT = 'TOTALAMOUNT';
	/**
	 * The 'INT' beacon URL querystring key
	 */
	const KEY_INT = 'INT';
	/**
	 * The 'ITEM' beacon URL querystring key
	 */
	const KEY_ITEM = 'ITEM';
	/**
	 * The 'PROMOCODE' beacon URL querystring key
	 */
	const KEY_PROMOCODE = 'PROMOCODE';
	/**
	 * @var Mage_Sales_Model_Order
	 * @see self::_getOrder
	 */
	protected $_order;
    /** @var  EbayEnterprise_Affiliate_Helper_Data */
    protected $_helper = null;
    /** @var  EbayEnterprise_Affiliate_Helper_Config */
    protected $_configHelper = null;

    /**
     * @return EbayEnterprise_Affiliate_Helper_Data
     */
    protected function _getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('eems_affiliate');
        }

        return $this->_helper;
    }

    /**
     * @return EbayEnterprise_Affiliate_Helper_Config
     */
    protected function _getConfigHelper()
    {
        if (!$this->_configHelper) {
            $this->_configHelper = Mage::helper('eems_affiliate/config');
        }

        return $this->_configHelper;
    }

	/**
	 * Get the last order.
	 * @return Mage_Sales_Model_Order | null
	 */
	protected function _getOrder()
	{
		if (!($this->_order instanceof Mage_Sales_Model_Order)) {
			$orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
			if ($orderId) {
				$this->_order = Mage::getModel('sales/order')->load($orderId);
			}
		}
		return $this->_order;
	}
	/**
	 * Get the beacon URL.
	 * @return string | null
	 */
	public function getBeaconUrl()
	{
		$order = $this->_getOrder();
		return ($order instanceof Mage_Sales_Model_Order) ?
			Mage::helper('eems_affiliate')->buildBeaconUrl(
				Mage::helper('eems_affiliate/config')->isItemizedOrders() ?
					$this->_buildItemizedParams($order) : $this->_buildBasicParams($order)
			) : null;
	}
	/**
	 * build common params array
	 * @param Mage_Sales_Model_Order $order
	 * @return array
	 */
	protected function _buildCommonParams(Mage_Sales_Model_Order $order)
	{
		$params = array(
			static::KEY_PID => Mage::helper('eems_affiliate/config')->getProgramId(),
			static::KEY_OID => $order->getIncrementId(),
		);
		$couponCode = trim($order->getCouponCode());
		return ($couponCode !== '')?
			array_merge($params, array(static::KEY_PROMOCODE => $couponCode)) : $params;
	}
	/**
	 * build basic params array for non itemized beacon URL
	 * @param Mage_Sales_Model_Order $order
	 * @return array
	 */
	protected function _buildBasicParams(Mage_Sales_Model_Order $order)
	{
		return array_merge($this->_buildCommonParams($order), array(
			static::KEY_AMOUNT => number_format($order->getSubtotal() + $order->getDiscountAmount() + $order->getShippingDiscountAmount(), 2, '.', ''),
			static::KEY_TYPE => Mage::helper('eems_affiliate/config')->getTransactionType()
		));
	}
	/**
	 * build itemized order params array for itemized beacon URL
	 * @param Mage_Sales_Model_Order $order
	 * @return array
	 */
	protected function _buildItemizedParams(Mage_Sales_Model_Order $order)
	{
		$params = array(static::KEY_INT => Mage::helper('eems_affiliate/config')->getInt());
		$increment = 1; // incrementer for the unique item keys
		foreach ($order->getAllItems() as $item) {
			// need to ignore the bundle parent as it will contain collected total
			// for children but not discounts
			$position = $this->_getDupePosition($params, $item);
			// ignore the parent configurable quantity - quantity of config products
			// will come from the simple used product with the same SKU
			$quantity = $item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE ?
				0 : (int) $item->getQtyOrdered();
			// consider parent bundle products to be 0.00 total - total of the bundle
			// is the sum of all child products which are also included in the beacon
			// so including both totals would effectively double the price of the bundle
			$total = $item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE ?
				0.00 : $item->getRowTotal() - $item->getDiscountAmount();
			if ($position) {
				// we detected that the current item already exist in the params array
				// and have the key increment position let's simply adjust
				// the qty and total amount
				$params[static::KEY_QTY . $position] += $quantity;
				$amtKey = static::KEY_TOTALAMOUNT . $position;
				$params[$amtKey] = number_format($params[$amtKey] + $total, 2, '.', '');
			} else {
				$params = array_merge($params, array(
					static::KEY_ITEM . $increment => $item->getSku(),
					static::KEY_QTY . $increment => $quantity,
					static::KEY_TOTALAMOUNT . $increment => number_format($total, 2, '.', ''),
				));
				$increment++; // only get incremented when a unique key have been appended
			}
		}
		return array_merge($this->_buildCommonParams($order), $params);
	}
	/**
	 * check if the current sku already exists in the params data if so return
	 * the position it is found in
	 * @param array $params the given array of keys needed to build the beacon URL querystring
	 * @param Mage_Sales_Model_Order_Item $item
	 * @return int the item position where dupe found otherwise zero
	 */
	protected function _getDupePosition(array $params, Mage_Sales_Model_Order_Item $item)
	{
		$key = array_search($item->getSku(), $params, true);
		return ($key !== false)?
			(int) str_replace(static::KEY_ITEM, '', $key) : 0;
	}
	/**
	 * Whether or not to display the beacon.
     *
     * Show the pixel only if tracking is enabled and we have a valid order
     * AND either conditional pixel logic is OFF or it is ON and we have
     * a valid cookie.
     *
	 * @return bool
	 */
	public function showBeacon()
    {
        $config = $this->_getConfigHelper();

        return (
            (
                $config->isEnabled() &&
                $this->_getOrder() instanceof Mage_Sales_Model_Order
            ) &&
            (
                $this->_getHelper()->isValidCookie() ||
                !$config->isConditionalPixelEnabled()
            )
        );
    }
}
