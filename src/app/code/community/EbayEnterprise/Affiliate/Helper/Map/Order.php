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

/**
 * The public methods of this class are all expected to be used as callbacks
 * for building the Affiliate corrected orders feeds.
 * @see EbayEnterprise_Affiliate_Helper_Map
 */
class EbayEnterprise_Affiliate_Helper_Map_Order
{
	/**
	 * Get the order increment id from the order the item was created for. Expects
	 * the "item" to be a Mage_Sales_Model_Order_Item and the "format" to be a
	 * valid string format.
	 * @param  array $params
	 * @return string
	 */
	public function getItemOrderId($params)
	{
		$item = $params['item'];
		return sprintf(
			$params['format'], $item->getOriginalIncrementId() ?: $item->getIncrementId()
		);
	}
	/**
	 * Get the updated item quantity - original quantity less any refunded
	 * or canceled. Expects the "item" to be a Mage_Sales_Model_Order_Item.
	 * @param  array $params
	 * @return int
	 */
	public function getItemQuantity($params)
	{
		$item = $params['item'];
		// field limit doesn't allow this to go above 99
		return (int) ($item->getQtyOrdered() - $item->getQtyRefunded() - $item->getQtyCanceled());
	}
	/**
	 * Calculate a row total including discounts.
	 * @param  array $params
	 * @return float
	 */
	private function _calculateDiscountedRowTotal($params)
	{
		$item = $params['item'];
		// tread bundle items as 0.00 total as their total will be represented by
		// the total of their children products
		if ($item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
			return 0.00;
		}
		// don't allow negative amounts - could happen if a discounted item was cancelled
		return max(
			0,
			$item->getBasePrice() * $this->getItemQuantity($params) - ($item->getBaseDiscountAmount() - $item->getbaseDiscountRefunded())
		);
	}
	/**
	 * Get the corrected total for the row - price * corrected qty. Expects the
	 * "item" to be a Mage_Sales_Model_Order_Item, "format" to be a valid
	 * format string and "store" to be a Mage_Core_Model_Store or otherwise viable
	 * store identifier.
	 * @param  array $params
	 * @return string
	 */
	public function getRowTotal($params)
	{
		$config = Mage::helper('eems_affiliate/config');
		// transaction type of Lead should always just be "0"
		if ($config->getTransactionType($params['store']) === $config::TRANSACTION_TYPE_LEAD) {
			return 0;
		}
		return sprintf(
			$params['format'],
			$this->_calculateDiscountedRowTotal($params)
		);
	}
	/**
	 * Get the corrected amount of the order. Expects "item" to be a
	 * Mage_Sales_Model_Order, "store" to be a Mage_Core_Model_Store or otherwise
	 * valid store identifier, and "format" to be a valid format string.
	 * @param  array $params
	 * @return string
	 */
	public function getOrderAmount($params)
	{
		$config = Mage::helper('eems_affiliate/config');
		// transaction type of Lead should always just be "0"
		if ($config->getTransactionType($params['store']) === $config::TRANSACTION_TYPE_LEAD) {
			return 0;
		}
		$order = $params['item'];
		return sprintf(
			$params['format'],
			// prevent sub-zero amounts for canceled orders with discounts
			max(
				0,
				($order->getBaseSubtotal() + $order->getBaseDiscountAmount()) -
				($order->getBaseSubtotalRefunded() + $order->getBaseDiscountRefunded()) -
				($order->getBaseSubtotalCanceled() + $order->getBaseDiscountCanceled())
			)
		);
	}
	/**
	 * Get the transaction type configured for the store the order was received
	 * in. Expects "store" to be a Mage_Core_Model_Store or otherwise valid
	 * store identifier.
	 * @param  array $params
	 * @return int
	 */
	public function getTransactionType($params)
	{
		return (int) Mage::helper('eems_affiliate/config')->getTransactionType($params['store']);
	}
	/**
	 * Get the order item increment id. For orders that are the result of an edit,
	 * get the increment id of the original order. Expects "item" to be a
	 * Mage_Sales_Model_Oorder and "format" to be a valid format string.
	 * @param  array $params
	 * @return string
	 */
	public function getOrderId($params)
	{
		$order = $params['item'];
		return sprintf(
			$params['format'], $order->getOriginalIncrementId() ?: $order->getIncrementId()
		);
	}
	/**
	 * Get the SKU of the item with any prohibited characters in the SKU removed.
	 * Expects "format" to be a valid format string. As this method also passes
	 * through to EbayEnterprise_Affiliate_Helper_Map::getDataValue, `$params`
	 * must also adhere to the requirements of that method - "item" is a subclass
	 * of Varien_Object, have a "key" value set.
	 * @param  array $params
	 * @return string
	 */
	public function getItemId($params)
	{
		return sprintf(
			$params['format'],
			preg_replace('/[^a-zA-Z0-9\-_]/', '', Mage::helper('eems_affiliate/map')->getDataValue($params))
		);
	}
}
