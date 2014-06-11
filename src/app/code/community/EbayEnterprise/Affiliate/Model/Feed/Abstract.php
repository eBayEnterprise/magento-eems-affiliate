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


abstract class EbayEnterprise_Affiliate_Model_Feed_Abstract
{
	/**
	 * The store context the feed is generated for. May be set to any viable store
	 * identified.
	 * @var Mage_Core_Model_Store
	 */
	protected $_store;
	/**
	 * Get a collection of items to be included in the feed.
	 * @return Varien_Data_Collection
	 */
	abstract protected function _getItems();
	/**
	 * Get fields to include in the feed. Fields are expected to map to existing
	 * callbacks defined in the config.xml.
	 * @see self::_invokeCallback
	 * @return array
	 */
	abstract protected function _getFeedFields();
	/**
	 * Gets the filename format for the feed from config for this feed.
	 * @return  string
	 */
	abstract protected function _getFileName();
	/**
	 * Get the delimiter to use in the csv file
	 * @return string
	 * @codeCoverageIgnore
	 */
	protected function _getDelimiter()
	{
		return ',';
	}
	/**
	 * Get the encolsure to use in the csv file
	 * @return string
	 * @codeCoverageIgnore
	 */
	protected function _getEnclosure()
	{
		return '"';
	}
	/**
	 * Set up the store property.
	 */
	public function __construct($args=array())
	{
		// Set the store context to the given store or null which whill
		// result in the "current" store.
		$this->setStore(isset($args['store']) ? $args['store'] : null);
	}
	/**
	 * Set the store context for the feed, converting whatever viable store
	 * ID is passed in to an actual store instance.
	 * @see Mage_Core_Model_App::getStore for how various identifiers may be used to represent a store
	 * @param null|string|bool|int|Mage_Core_Model_Store
	 * @codeCoverageIgnore
	 */
	public function setStore($storeId)
	{
		$this->_store = Mage::app()->getStore($storeId);
		return $this;
	}
	/**
	 * Get the store instance the feed is being executed within.
	 * @return Mage_Core_Model_Store
	 * @codeCoverageIgnore
	 */
	public function getStore()
	{
		return $this->_store;
	}
	/**
	 * Create the feed file and drop it in the configured export directory.
	 * @return self
	 */
	public function generateFeed()
	{
		$this->_generateFile($this->_buildFeedData());
		return $this;
	}
	/**
	 * Create arrays of data that should be included in the feed file. Each array
	 * should included a value for every field that is expected to be in the feed.
	 * @return array
	 */
	protected function _buildFeedData()
	{
		$items = $this->_getItems();
		Mage::log(sprintf('building feed for %d items', $items->count()));
		// array_map must be on an array - $items is a collection so need to get the
		// underlying array to pass to array_map
		return array_map(array($this, '_applyMapping'), $items->getItems());
	}
	/**
	 * Use the callback mapping to create the data that represents the given item
	 * in the feed.
	 * @param  mixed $item Likely a Varien_Object but could really be anything.
	 * @return array
	 */
	protected function _applyMapping($item)
	{
		$fields = array();
		$mappings = Mage::helper('eems_affiliate/config')->getCallbackMappings();
		foreach ($this->_getFeedFields() as $feedField) {
			// If the mapping doesn't exist, supplying an empty array will eventually
			// result in an exception for being an invalid config mapping.
			// @see self::_validateCallbackConfig
			$callback = isset($mappings[$feedField]) ? $mappings[$feedField] : array();
			// exclude any mappings that have a type of "disabled"
			if (!isset($callback['type']) || $callback['type'] !== 'disabled') {
				$fields[] = $this->_invokeCallback($callback, $item);
			}
		}
		return $fields;
	}
	/**
	 * Given a set of callback configuration and an item, invoke the configured
	 * callback and return the value. The callback configuration must meet the
	 * following requirements:
	 * - May contain a "type" key indicating the type of factory to use. May be
	 *   one of:
	 *   - "disabled" - will not be included in the feed
	 *   - "helper" - will use the Mage::helper factory
	 *   - "model" - will use the Mage::getModel factory
	 *   - "singleton" - will use the Mage::getSingleton factory
	 *   - If not included, will default to "singleton"
	 * - When type is "disabled" no other key/value pairs are required.
	 * - If type is not "disabled" the following must be included:
	 *   - "class" must be a valid class alias for the configured factory type
	 *   - "method" must be a valid method on the configured class
	 * - A "params" key may also be included. If included, its value must be
	 *   an array of key/value pairs that will be included in the params array
	 *   passed to the callback method.
	 * Every callback will be called with an array of params. The array will
	 * contain any key/value pairs added in the config as well as an "item" key
	 * which will have the item being mapped as the value and a "store" key which
	 * will have the store instance representing the store view context the feed
	 * is being generated for.
	 *
	 * Example configuration:
	 * <code>
	 * // This callback configuration array:
	 * $cfg = array(
	 *   'type' => 'helper',
	 *   'class' => 'eems_affiliate/map',
	 *   'column_name' => 'OID',
	 *   'params' => array(
	 *     'key' => 'increment_id',
	 *   ),
	 * );
	 * // Will result in the following invocation:
	 * Mage::helper('eems_affiliate/map')->getDataValue(
	 *     array('key' => 'increment_id', 'item' => $item)
	 * );
	 * </code>
	 *
	 * @see src/app/code/community/EbayEnterprise/Affiliate/etc/config.xml marketing_solutions/eems_affiliate/feeds contains mappings used for the affiliate feeds
	 * @param  array $callbackConfig
	 * @param  mixed $item
	 * @return mixed
	 */
	protected function _invokeCallback($callbackConfig, $item)
	{
		$obj = $this->_getCallbackInstance($callbackConfig);
		$params = isset($callbackConfig['params']) ? $callbackConfig['params'] : array();
		$item->setStoreId($this->getStore()->getId());
		$params['item'] = $item;
		$params['store'] = $this->getStore();
		$method = $callbackConfig['method'];
		if (method_exists($obj, $method)) {
			return $obj->$method($params);
		} else {
			throw new EbayEnterprise_Affiliate_Exception_Configuration(
				sprintf(
					'Configured callback method %s::%s does not exist',
					get_class($obj),$method
				)
			);
		}
	}
	/**
	 * Get an instance of the configured callback.
	 * @param  array $callbackConfig
	 * @return mixed
	 */
	protected function _getCallbackInstance($callbackConfig)
	{
		$this->_validateCallbackConfig($callbackConfig);
		switch ($callbackConfig['type']) {
			// 'disabled' type callback mappings shouldn't pass through here under
			// "normal" circumstances (filtered out in apply mapping) but if they do,
			// do nothing and return null
			case 'disabled':
				return null;
			case 'helper':
				return Mage::helper($callbackConfig['class']);
			case 'model':
				return Mage::getModel($callbackConfig['class']);
			case 'singleton':
			default:
				return Mage::getSingleton($callbackConfig['class']);
		}
	}
	/**
	 * Make sure the callback configuration is valid. If it isn't throw an
	 * exception.
	 * @param  array $callbackConfig
	 * @return self
	 * @throws EbayEnterprise_Affiliate_Exception_Configuration If callback configuration is not valid
	 */
	protected function _validateCallbackConfig($callbackConfig)
	{
		if (empty($callbackConfig)) {
			throw new EbayEnterprise_Affiliate_Exception_Configuration('Callback configuration is empty or missing.');
		}
		// When the callback is "disabled" no other configuration is necessary.
		if (isset($callbackConfig['type']) && $callbackConfig['type'] === 'disabled') {
			return $this;
		}
		// If not disabled, must have a class and method - separate checks for
		// more simply providing more useful error messages.
		$missingFields = array_diff(array('class', 'method', 'column_name'), array_keys($callbackConfig));
		if ($missingFields) {
			throw new EbayEnterprise_Affiliate_Exception_Configuration(
				sprintf('Callback missing %s configuration.', implode(', ', $missingFields))
			);
		}
		return $this;
	}
	/**
	 * Create the file and drop it in the configured export directory.
	 * @param  array $feedData
	 * @return self
	 */
	protected function _generateFile($feedData)
	{
		if (empty($feedData)) {
			return $this;
		}
		$delimiter = $this->_getDelimiter();
		$enclosure = $this->_getEnclosure();

		$tmpFile = fopen('php://temp', 'r+');
		fputcsv($tmpFile, $this->_getHeaders(), $delimiter, $enclosure);
		foreach ($feedData as $row) {
			fputcsv($tmpFile, $row, $delimiter, $enclosure);
		}
		rewind($tmpFile);

		$targetPath = $this->_generateFilePath();
		$this->_checkAndCreateFolder(dirname($targetPath));

		// send the contents of the temp stream to the actual file
		file_put_contents(
			$targetPath,
			stream_get_contents($tmpFile)
		);
		return $this;
	}
	/**
	 * Generate the full path to the location where the file should be created.
	 * @return string
	 */
	protected function _generateFilePath()
	{
		return self::normalPaths(
			Mage::getBaseDir(),
			Mage::helper('eems_affiliate/config', $this->getStore())->getExportFilePath(),
			$this->_getFileName()
		);
	}
	/**
	 * The CSV file headers should be the keys used in the configured mappings.
	 * @return array
	 */
	protected function _getHeaders()
	{
		$mappings = Mage::helper('eems_affiliate/config')->getCallbackMappings();
		$headers = array();
		foreach ($this->_getFeedFields() as $field) {
			$callbackMapping = isset($mappings[$field]) ? $mappings[$field] : array();
			if (!isset($callbackMapping['type']) || $callbackMapping['type'] !== 'disabled') {
				$this->_validateCallbackConfig($callbackMapping);
				$headers[] = $callbackMapping['column_name'];
			}
		}
		return $headers;
	}
	/**
	 * Make sure that all necessary directories in the given path exist. Create
	 * any that do not.
	 * @param  string $dirPath
	 * @return self
	 */
	protected function _checkAndCreateFolder($dirPath)
	{
		// Use the model factory to allow for DI via the factory
		$fileIo = Mage::getModel('Varien_Io_File');
		$fileIo->open(array('path' => Mage::getBaseDir()));
		$fileIo->checkAndCreateFolder($dirPath);
		return $this;
	}
	/**
	 * Given an arbitrary array of arguments, join them to make a valid path.
	 * @param  string $_,... Parts of the path to be joined
	 * @return string
	 */
	public static function normalPaths()
	{
		$paths = implode(DS, func_get_args());
		// Retain a single leading slash; otherwise remove all leading, trailing
		// and duplicate slashes.
		return ((substr($paths, 0, 1) === DS) ? DS : '') .
			implode(DS, array_filter(explode(DS, $paths)));
	}
}
