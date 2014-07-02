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


class EbayEnterprise_Affiliate_Test_Model_Feed_Order_AbstractTest
	extends EcomDev_PHPUnit_Test_Case
{
	/**
	 * Test generating the file name using the
	 */
	public function testGetFileName()
	{
		$format = '%s_corrected_orders_%s.csv';
		$programId = 'PROGRAM_ID';
		$time = 1397829577;
		$formattedTime = date('YmdHis', $time);
		$fileName = "{$programId}_corrected_orders_{$formattedTime}.csv";

		// get the default store to be used as context for config getting
		$store = Mage::app()->getStore();
		// get the order feed instance with the expected store and start time injected
		$feed = $this->getModelMock(
			'eems_affiliate/feed_order_itemized',
			array('_getFileNameFormat'),
			true,
			array(array('store' => $store, 'start_time' => $time))
		);
		$feed->expects($this->any())
			->method('_getFileNameFormat')
			->will($this->returnValue($format));

		$config = $this->getHelperMock('eems_affiliate/config', array('getProgramId'));
		// Will have different program ids per feed so store context definitely
		// matters when getting this value.
		$config->expects($this->any())
			->method('getProgramId')
			->with($this->identicalTo($store))
			->will($this->returnValue($programId));
		$this->replaceByMock('helper', 'eems_affiliate/config', $config);

		$generated = EcomDev_Utils_Reflection::invokeRestrictedMethod($feed, '_getFileName');
		$this->assertSame(
			$fileName,
			$generated
		);
	}
}
