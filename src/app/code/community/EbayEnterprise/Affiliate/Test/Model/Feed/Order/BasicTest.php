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


class EbayEnterprise_Affiliate_Test_Model_Feed_Order_BasicTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Test getting the fields to include in the feed. Should be pulling the
     * comma-separated list of fields from config.xml and splitting it to produce
     * an array of fields.
     * @return array
     */
    public function testFeedFields()
    {
        $config = $this->getHelperMock('eems_affiliate/config', array('getBasicOrderFeedFields'));
        $config->expects($this->any())
            ->method('getBasicOrderFeedFields')
            ->will($this->returnValue('one,two,three'));
        $this->replaceByMock('helper', 'eems_affiliate/config', $config);
        $feed = Mage::getModel('eems_affiliate/feed_order_basic');
        $this->assertSame(
            array('one', 'two', 'three'),
            EcomDev_Utils_Reflection::invokeRestrictedMethod($feed, '_getFeedFields')
        );
    }
}
