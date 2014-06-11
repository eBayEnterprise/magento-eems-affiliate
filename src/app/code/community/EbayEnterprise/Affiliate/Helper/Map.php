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
 * This class is composed of methods used as callbacks to the feed generation
 * process. All methods accepting a generic `$params` argument are allowed to
 * make the following assumptions about the contents of the array:
 * 1. It will always include an "item" key with a value of the object
 *    being prcessed.
 * 2. It will always include a "store" key with a value of the store context
 *    in which the feed is being processed.
 * 3. Will contain additioanl key/value pairs set in the callback mappings
 *    in config.xml
 *
 * Additional key/value pairs may be included but may not be guaranteed.
 * If the methods make any additional assumptions about the contents of the
 * `$params` array, they must be stated with the method. This should include
 * any additional key/value pairs expected to be set in the config.xml.
 *
 * All such mapping methods are expected to return a single value that can be
 * inserted directly into the resulting feed file.
 */
class EbayEnterprise_Affiliate_Helper_Map
{
	/**
	 * Get the program id using the store passed in params. Pass through to the
	 * config helper using the store included in the $params array as the store
	 * context to get the config value from.
	 * @param  array $params
	 * @return string
	 */
	public function getProgramId($params)
	{
		return Mage::helper('eems_affiliate/config')->getProgramId($params['store']);
	}
	/**
	 * Get data for the key from the item. Expects "item" to be a Varien_Object,
	 * "key" must be set. Additionally, if "format" is also included, it must
	 * be a valid string format and will be used to format the data before it is
	 * returned from this method.
	 * @param  array $params
	 * @return mixed
	 * @throws Mage_Core_Exception If the value of the `item` key is not a Varien_Object or the `key` key/value pair is not set.
	 */
	public function getDataValue($params)
	{
		if (!$params['item'] instanceof Varien_Object) {
			throw new Mage_Core_Exception(
				sprintf(
					'Item of type %s not compatible with %s',
					get_class($params['item']), __METHOD__
				)
			);
		}
		if (!isset($params['key'])) {
			throw new Mage_Core_Exception(
				'The data "key" must be provided in the configured params for this callback.'
			);
		}
		$helper = Mage::helper('core');
		return sprintf(
			isset($params['format']) ? $params['format'] : '%s',
			preg_replace('/\s\s+/', ' ', $helper->stripTags($params['item']->getDataUsingMethod($params['key'])))
		);
	}
	/**
	 * Simply return the "value" included in the params.
	 * @param  array $params
	 * @return string
	 * @throws Mage_Core_Exception If the `value` key/value pair is not set.
	 */
	public function passStatic($params)
	{
		if (!isset($params['value'])) {
			throw new Mage_Core_Exception(sprintf(
				'No value provided to return from %s', __METHOD__
			));
		}
		return $params['value'];
	}
	/**
	 * check if an attribute has value then return 'yes' otherwise  return
	 * 'no'
	 * @param  array $params
	 * @return string
	 */
	public function getValueYesNo(array $params)
	{
		return sprintf(
			isset($params['format']) ? $params['format'] : '%s',
			Mage::helper('eems_affiliate')->parseBoolToYesNo(
				$params['item']->getDataUsingMethod($params['key'])
			)
		);
	}
}
