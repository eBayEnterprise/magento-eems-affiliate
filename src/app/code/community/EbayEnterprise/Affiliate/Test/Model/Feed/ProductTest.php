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


class EbayEnterprise_Affiliate_Test_Model_Feed_ProductTest
	extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * Test that EbayEnterprise_Affiliate_Model_Feed_Product::_getItems will return
	 * a Mage_Catalog_Model_Resource_Product_Collection
	 */
	public function testGetItems()
	{
		$store = Mage::getModel('core/store');
		$collection = $this->getResourceModelMockBuilder('catalog/product_collection')
			->disableOriginalConstructor()
			->setMethods(array('setStore', 'addAttributeToSelect', 'addStoreFilter', 'addFieldToFilter'))
			->getMock();
		$collection->expects($this->once())
			->method('setStore')
			->with($this->identicalTo($store))
			->will($this->returnSelf());
		$collection->expects($this->once())
			->method('addAttributeToSelect')
			->with($this->identicalTo(array('*')))
			->will($this->returnSelf());
		$collection->expects($this->once())
			->method('addStoreFilter')
			->with($this->identicalTo($store))
			->will($this->returnSelf());
		$collection->expects($this->once())
			->method('addFieldToFilter')
			->with($this->identicalTo('status'), $this->identicalTo(Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'catalog/product_collection', $collection);

		$feedProduct = Mage::getModel('eems_affiliate/feed_product', array('store' => $store));

		$this->assertSame($collection, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$feedProduct, '_getItems', array()
		));
	}
	/**
	 * Test that EbayEnterprise_Affiliate_Model_Feed_Product::_getFeedFields will
	 * retrieve a list of field from the configuration and explode it into an array
	 * of fields
	 */
	public function testGetFeedFields()
	{
		$maps = array(
			'field_1' => array('column_name' => 'field_1'),
			'field_2' => array('column_name' => 'field_2'),
			'field_3' => array('column_name' => 'field_3')
		);
		$fields = array('field_1' => 'key 1', 'field_2' => 'key 2', 'field_3' => 'key 3');
		$configHelper = $this->getHelperMock('eems_affiliate/config', array(
			'getProductFeedFields', 'getCallbackMappings'
		));
		$result = array_keys($fields);

		$configHelper->expects($this->once())
			->method('getProductFeedFields')
			->will($this->returnValue($fields));
		$configHelper->expects($this->once())
			->method('getCallbackMappings')
			->will($this->returnValue($maps));
		$this->replaceByMock('helper', 'eems_affiliate/config', $configHelper);

		$feedProduct = Mage::getModel('eems_affiliate/feed_product');

		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$feedProduct, '_getFeedFields', array()
		));
	}
	/**
	 * Test that EbayEnterprise_Affiliate_Model_Feed_Product::_getFileName will return
	 * a string representing a feed file name
	 */
	public function testFileName()
	{
		$filenameFormat = '%s_Some_Feed.csv';
		$programId = 'P12';
		$result = sprintf($filenameFormat, $programId);

		$store = Mage::getModel('core/store');
		$configHelper = $this->getHelperMock('eems_affiliate/config', array(
			'getProductFeedFilenameFormat', 'getProgramId'
		));
		$configHelper->expects($this->once())
			->method('getProductFeedFilenameFormat')
			->will($this->returnValue($filenameFormat));
		$configHelper->expects($this->once())
			->method('getProgramId')
			->with($this->identicalTo($store))
			->will($this->returnValue($programId));
		$this->replaceByMock('helper', 'eems_affiliate/config', $configHelper);

		$feedProduct = Mage::getModel('eems_affiliate/feed_product', array('store' => $store));

		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$feedProduct, '_getFileName', array()
		));
	}
	/**
	 * Test that EbayEnterprise_Affiliate_Model_Feed_Product::_applyMapping will
	 * be invoked with a Mage_Catalog_Model_Product object passed as its parameter
	 * and it will generate an array of callback result from configuration
	 */
	public function testApplyMapping()
	{
		$result = 'some value';
		$fields = array('field_1' => 'code_1');
		$mappings = array(
			'field_1' => array(
				'params' => array(),
				'column_name' => 'field_1',
				'type' => 'helper'
			),
		);
		$callback = $mappings['field_1'];
		$callback['params']['key'] = $fields['field_1'];

		$item = Mage::getModel('catalog/product');

		$callbackResult = array($result);

		$configHelper = $this->getHelperMock('eems_affiliate/config', array(
			'getCallbackMappings', 'getProductFeedFields'
		));
		$configHelper->expects($this->any())
			->method('getCallbackMappings')
			->will($this->returnValue($mappings));
		$configHelper->expects($this->any())
			->method('getProductFeedFields')
			->will($this->returnValue($fields));
		$this->replaceByMock('helper', 'eems_affiliate/config', $configHelper);

		$feedProduct = $this->getModelMock('eems_affiliate/feed_product', array('_invokeCallBack'));
		$feedProduct->expects($this->once())
			->method('_invokeCallBack')
			->with($this->identicalTo($callback), $this->identicalTo($item))
			->will($this->returnValue($result));

		$this->assertSame($callbackResult, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$feedProduct, '_applyMapping', array($item)
		));
	}
}
