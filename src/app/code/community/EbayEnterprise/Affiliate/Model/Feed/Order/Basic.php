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


class EbayEnterprise_Affiliate_Model_Feed_Order_Basic
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

		$collection = Mage::getResourceModel('sales/order_collection');
		$select = $collection->getSelect();
		$select
			->joinLeft(
				array('cmo' => $collection->getTable('sales/creditmemo')),
				'main_table.entity_id = cmo.order_id',
				array()
			)
			// this is far more pure SQL than should be here but I don't see a way to
			// get the proper groupings of where clauses without doing this
			->where(
				'main_table.store_id IN (?)', $storeIds
			)
			->where(
				"(main_table.original_increment_id IS NOT NULL AND main_table.created_at >= :lastRunTime AND main_table.created_at < :startTime) OR" .
				"(cmo.created_at IS NOT NULL AND cmo.created_at >= :lastRunTime AND cmo.created_at < :startTime) OR" .
				"(main_table.state = 'canceled' AND main_table.updated_at >= :lastRunTime AND main_table.updated_at < :startTime AND main_table.relation_child_id IS NULL)"
			);

		$collection->addBindParam(':lastRunTime', $lastRunTime)
			->addBindParam(':startTime', $startTime);

		return $collection;
	}
	/**
	 * Get an array of callback mappings for the feed. Should result in an array
	 * with keys for the field in the CSV and a value of an array used to
	 * represent a mapping callback.
	 * @see parent::_invokeCallback
	 */
	protected function _getFeedFields()
	{
		return explode(',', Mage::helper('eems_affiliate/config')->getBasicOrderFeedFields());
	}
	/**
	 * Get the file name format from config. Doesn't pass store context as the
	 * file name format should only ever exist at the global level.
	 * @see  parent::_getFileNameFormat
	 * @codeCoverageIgnore
	 */
	protected function _getFileNameFormat()
	{
		return Mage::helper('eems_affiliate/config')->getBasicOrderFeedFileFormat();
	}
}
