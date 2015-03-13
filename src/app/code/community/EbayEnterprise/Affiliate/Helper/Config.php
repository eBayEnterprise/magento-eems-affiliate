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
 * @codeCoverageIgnore
 */
class EbayEnterprise_Affiliate_Helper_Config
{
	const BEACON_URL_PATH = 'marketing_solutions/eems_affiliate/beacon_url';
	const ENABLED_PATH = 'marketing_solutions/eems_affiliate/active';
	const INT_PATH = 'marketing_solutions/eems_affiliate/int';
	const ITEMIZED_ORDERS_PATH = 'marketing_solutions/eems_affiliate/itemized_orders';
	const PROGRAM_ID_PATH = 'marketing_solutions/eems_affiliate/program_id';
	const TRANSACTION_TYPE_PATH = 'marketing_solutions/eems_affiliate/transaction_type';
	const EXPORT_FILE_PATH_CONFIG_PATH = 'marketing_solutions/eems_affiliate/export_path';
	const CALLBACK_MAPPINGS_PATH = 'marketing_solutions/eems_affiliate/feeds/callback_mappings';
	const PRODUCT_FEED_MAPPING_PATH = 'marketing_solutions/eems_affiliate_product_attribute_map';
	const PRODUCT_FEED_FILENAME_FORMAT_PATH = 'marketing_solutions/eems_affiliate/feeds/product/file_name_format';
	const ITEMIZED_ORDER_FEED_MAPPING_PATH = 'marketing_solutions/eems_affiliate/feeds/order_itemized/fields';
	const BASIC_ORDER_FEED_MAPPING_PATH = 'marketing_solutions/eems_affiliate/feeds/order_basic/fields';
	const ITEMIZED_ORDER_FEED_FILE_FORMAT_PATH = 'marketing_solutions/eems_affiliate/feeds/order_itemized/file_name_format';
	const BASIC_ORDER_FEED_FILE_FORMAT_PATH = 'marketing_solutions/eems_affiliate/feeds/order_basic/file_name_format';
	const ORDER_LAST_RUN_PATH = 'marketing_solutions/eems_affiliate/feed/last_run_time';
	const JS_FILES = 'marketing_solutions/eems_affiliate/js_files';
    const CONDITIONAL_PIXEL_ENABLED = 'marketing_solutions/eems_affiliate/conditional_pixel_enabled';
    const SOURCE_KEY_NAME = 'marketing_solutions/eems_affiliate/source_key_name';
	const TRANSACTION_TYPE_SALE = '1';
	const TRANSACTION_TYPE_LEAD = '2';

	/**
	 * retrieve the program id from store config
	 * @param mixed $store
	 * @return string
	 */
	public function getProgramId($store=null)
	{
		return Mage::getStoreConfig(static::PROGRAM_ID_PATH, $store);
	}
	/**
	 * retrieve the transaction type from store config
	 * @param mixed $store
	 * @return string
	 */
	public function getTransactionType($store=null)
	{
		return Mage::getStoreConfig(static::TRANSACTION_TYPE_PATH, $store);
	}
	/**
	 * retrieve the itemized orders from store config
	 * @param mixed $store
	 * @return bool
	 */
	public function isItemizedOrders($store=null)
	{
		return Mage::getStoreConfigFlag(static::ITEMIZED_ORDERS_PATH, $store);
	}
	/**
	 * check if beacon pixel is enable in the store config
	 * @param mixed $store
	 * @return bool
	 */
	public function isEnabled($store=null)
	{
		return Mage::getStoreConfigFlag(static::ENABLED_PATH, $store);
	}
	/**
	 * retrieve the int from store config
	 * @param mixed $store
	 * @return string
	 */
	public function getInt($store=null)
	{
		return Mage::getStoreConfig(static::INT_PATH, $store);
	}
	/**
	 * retrieve the base url of the beacon from store config
	 * @param mixed $store
	 * @return string
	 */
	public function getBeaconBaseUrl($store=null)
	{
		return Mage::getStoreConfig(static::BEACON_URL_PATH, $store);
	}
	/**
	 * Get the configured export file path.
	 * @param  mixed $store
	 * @return string
	 */
	public function getExportFilePath($store=null)
	{
		return Mage::getStoreConfig(static::EXPORT_FILE_PATH_CONFIG_PATH, $store);
	}
	/**
	 * Get the callback mappings from the config
	 * @param  mixed $store
	 * @return array
	 */
	public function getCallbackMappings($store=null)
	{
		return Mage::getStoreConfig(static::CALLBACK_MAPPINGS_PATH, $store);
	}
	/**
	 * Get the configured feed mapping for the product feed.
	 * @param  mixed $store
	 * @return array
	 */
	public function getProductFeedFields($store=null)
	{
		return array_filter(Mage::getStoreConfig(static::PRODUCT_FEED_MAPPING_PATH, $store));
	}
	/**
	 * Get the configured product feed file name format
	 * @param  mixed $store
	 * @return string
	 */
	public function getProductFeedFilenameFormat($store=null)
	{
		return Mage::getStoreConfig(static::PRODUCT_FEED_FILENAME_FORMAT_PATH, $store);
	}
	/**
	 * Get the configured feed mapping for the itemized orders feed.
	 * @param  mixed $store
	 * @return array
	 */
	public function getItemizedOrderFeedFields($store=null)
	{
		return Mage::getStoreConfig(static::ITEMIZED_ORDER_FEED_MAPPING_PATH, $store);
	}
	/**
	 * Get the configured feed mapping for the basic orders feed.
	 * @param  mixed $store
	 * @return array
	 */
	public function getBasicOrderFeedFields($store=null)
	{
		return Mage::getStoreConfig(static::BASIC_ORDER_FEED_MAPPING_PATH, $store);
	}
	/**
	 * Get the configured itemized order feed file format
	 * @param  mixed $store
	 * @return string
	 */
	public function getItemizedOrderFeedFileFormat($store=null)
	{
		return Mage::getStoreConfig(static::ITEMIZED_ORDER_FEED_FILE_FORMAT_PATH, $store);
	}
	/**
	 * Get the configured basic order feed file format
	 * @param  mixed $store
	 * @return string
	 */
	public function getBasicOrderFeedFileFormat($store=null)
	{
		return Mage::getStoreConfig(static::BASIC_ORDER_FEED_FILE_FORMAT_PATH, $store);
	}
	/**
	 * Update the last run time of the order create feed to the specified time,
	 * or the current time it no time is given. Always set globally so no need to
	 * ever be given a store context.
	 * @param  string $time
	 * @return self
	 */
	public function updateOrderLastRunTime($time=null)
	{
		Mage::getConfig()->saveConfig(self::ORDER_LAST_RUN_PATH, $time ?: time());
		Mage::app()->getStore()->resetConfig();
		return $this;
	}
	/**
	 * Get the last time the order corrections feed was run. Returns the string
	 * value saved in config. Always set globally so no need for a store context.
	 * @return string
	 */
	public function getOrderLastRunTime()
	{
		return Mage::getStoreConfig(self::ORDER_LAST_RUN_PATH);
	}

	/**
	 * Enable/disable conditional pixel logic
	 *
	 * @param null $store
	 * @return bool
	 */
	public function isConditionalPixelEnabled($store=null)
	{
	    return Mage::getStoreConfig(self::CONDITIONAL_PIXEL_ENABLED, $store);
	}

	/**
	 * Name of the affiliate source
	 *
	 * If conditional pixel logic is enabled then only display the pixel
	 * if the query string contains a key with this name
	 *
	 * @param null $store
	 * @return string
	 */
	public function getSourceKeyName($store=null)
	{
	    return Mage::getStoreConfig(self::SOURCE_KEY_NAME, $store);
	}
}
