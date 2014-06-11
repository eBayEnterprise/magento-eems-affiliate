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


abstract class EbayEnterprise_Affiliate_Model_Feed_Order_Abstract
	extends EbayEnterprise_Affiliate_Model_Feed_Abstract
{
	// Time format to use for the SQL statements
	const SELECT_TIME_FORMAT = 'Y-m-d H:i:s';
	const FILENAME_TIME_FORMAT = 'YmdHis';
	/**
	 * Time stamp considered to be the time at which the feed runs - used for
	 * generating the file name and the cutoff time for capturing order changes.
	 * @var int
	 */
	protected $_startTime;
	/**
	 * Allow for the "start" time of the feed to be passed in the constructor
	 * $args array. If given, this should be the time at which the feed is run.
	 * @param array $args
	 */
	public function __construct($args=array())
	{
		parent::__construct($args);
		$this->_startTime = isset($args['start_time']) ? $args['start_time'] : time();
	}
	/**
	 * Get the ids of all stores that should be included in the feed. Only orders
	 * placed for these stores will be included in the corrected feed.
	 * @return array
	 */
	protected function _getStoreIdsToInclude()
	{
		return array_map(
			function ($store) { return $store->getId(); },
			Mage::helper('eems_affiliate')->getAllStoresForProgramId(
				Mage::helper('eems_affiliate/config')->getProgramId($this->_store)
			)
		);
	}
	/**
	 * @see parent::_getFileName
	 */
	protected function _getFileName()
	{
		return sprintf(
			$this->_getFileNameFormat(),
			Mage::helper('eems_affiliate/config')->getProgramId($this->getStore()),
			date(static::FILENAME_TIME_FORMAT, $this->_startTime)
		);
	}
	/**
	 * Get the format string used to build the feed file name.
	 * @return string
	 */
	abstract protected function _getFileNameFormat();
}
