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


class EbayEnterprise_Affiliate_Model_Feed_Order_Itemized
	extends EbayEnterprise_Affiliate_Model_Feed_Order_Abstract
{
	/**
	 * @see parent::_getItems
	 */
	protected function _getItems()
	{
		$lastRunTime = date(static::SELECT_TIME_FORMAT, Mage::helper('eems_affiliate/config')->getOrderLastRunTime() ?: 0);
		$startTime = date(static::SELECT_TIME_FORMAT, $this->_startTime);
		$storeIds = $this->_getStoreIdsToInclude();

		$collection = Mage::getResourceModel('sales/order_item_collection');
		$select = $collection->getSelect();
		$select
			->joinLeft(
				array('o' => $collection->getTable('sales/order')),
				'main_table.order_id = o.entity_id',
				array('o.increment_id', 'o.original_increment_id')
			)
			->joinLeft(
				array('cmo' => $collection->getTable('sales/creditmemo')),
				'main_table.order_id = cmo.order_id',
				array()
			)
			// this is far more pure SQL than should be here but I don't see a way to
			// get the proper groupings of where clauses without doing this
			->where(
				// get only items within the correct store scope and filter out any
				// configurable used simple products
				'main_table.store_id IN (?) AND NOT (main_table.product_type="simple" AND main_table.parent_item_id IS NOT NULL AND main_table.row_total=0)', $storeIds
			)
			->where(
				"(o.original_increment_id IS NOT NULL AND o.created_at >= :lastRunTime AND o.created_at < :startTime) OR " .
				"(cmo.created_at IS NOT NULL AND cmo.created_at >= :lastRunTime AND cmo.created_at < :startTime) OR " .
				"(o.state = 'canceled' AND o.updated_at >= :lastRunTime AND o.updated_at < :startTime AND o.relation_child_id IS NULL)"
			);

		$collection->addBindParam(':lastRunTime', $lastRunTime)
			->addBindParam(':startTime', $startTime);

		return $collection;
	}
	/**
	 * @see parent::_getFeedFields
	 */
	protected function _getFeedFields()
	{
		return explode(',', Mage::helper('eems_affiliate/config')->getItemizedOrderFeedFields());
	}
	/**
	 * Get the file name format from config. Doesn't pass store context as the
	 * file name format should only ever exist at the global level.
	 * @see  parent::_getFileNameFormat
	 * @codeCoverageIgnore
	 */
	protected function _getFileNameFormat()
	{
		return Mage::helper('eems_affiliate/config')->getItemizedOrderFeedFileFormat();
	}
}
