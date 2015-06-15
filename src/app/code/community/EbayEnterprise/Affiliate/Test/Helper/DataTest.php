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

class EbayEnterprise_Affiliate_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Test that EbayEnterprise_Affiliate_Helper_Data::buildBeaconUrl yields the
     * expected, escaped url with query string when passed an array of params to append.
     */
    public function testBuildBeaconUrl()
    {
        $beaconUrl = 'https://example.com/track';
        $params = array('KEY1' => 'K1', 'KEY2' => 'K2');
        $result = $beaconUrl . '?' . http_build_query($params);

        $cfgHelper = $this->getHelperMock('eems_affiliate/config', array('getBeaconBaseUrl'));
        $cfgHelper->expects($this->once())
            ->method('getBeaconBaseUrl')
            ->will($this->returnValue($beaconUrl));
        $this->replaceByMock('helper', 'eems_affiliate/config', $cfgHelper);

        $this->assertSame($result, Mage::helper('eems_affiliate/data')->buildBeaconUrl($params));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testIsValidCookie($cookie, $value, $expectedResult)
    {
        $dataHelper = $this->getHelperMock('eems_affiliate', array('getSourceCookieName'));
        $dataHelper->expects(($this->any()))
            ->method('getSourceCookieName')
            ->will($this->returnValue('ebay_enterprise_affiliate_source'));
        $this->replaceByMock('helper', 'eems_affiliate', $dataHelper);

        $_COOKIE[$cookie] = $value;

        $this->assertEquals((bool)$expectedResult, $dataHelper->isValidCookie());
    }
}
