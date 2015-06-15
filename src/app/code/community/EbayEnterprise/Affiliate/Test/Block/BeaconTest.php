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

class EbayEnterprise_Affiliate_Test_Block_BeaconTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Get a mock of the config helper to return expected system configurations.
     * @param  string $programId
     * @param  string $transactionType
     * @param  string $itemizeOrders
     * @return Mock_EbayEnterprise_Affiliate_Helper_Config
     */
    protected function _getConfigHelper($programId, $transactionType, $itemizeOrders)
    {
        $helper = $this->getHelperMock('eems_affiliate/config', array(
            'getProgramId', 'getTransactionType', 'isItemizedOrders'
        ));
        $helper->expects($this->any())
            ->method('getProgramId')
            ->will($this->returnValue($programId));
        $helper->expects($this->any())
            ->method('getTransactionType')
            ->will($this->returnValue($transactionType));
        $helper->expects($this->any())
            ->method('isItemizedOrders')
            ->will($this->returnValue($itemizeOrders));
        return $helper;
    }
    /**
     * Test building the basic beacon url. Should only include order level
     * information about the order in the url params.
     */
    public function testGetBeaconUrlBasic()
    {
        $orderId = '000012';
        $subtotal = '10.99';
        $discount = '-5.00';
        $couponCode = 'COUPON';
        $programId = 'PROGRAM ID';
        $transactionType = 'TRANS TYPE';
        $itemizeOrders = false;
        $params = array(
            'PID' => $programId,
            'OID' => $orderId,
            'PROMOCODE' => $couponCode,
            'AMOUNT' => '5.99',
            'TYPE' => $transactionType
        );
        $beaconUrl = 'https://example.com/track';
        $order = Mage::getModel('sales/order', array(
            'increment_id' => $orderId,
            'subtotal' => $subtotal,
            'coupon_code' => $couponCode,
            'discount_amount' => $discount,
        ));
        // mock out the config helper to setup expected system configurations
        $this->replaceByMock(
            'helper',
            'eems_affiliate/config',
            $this->_getConfigHelper($programId, $transactionType, $itemizeOrders)
        );
        $helper = $this->getHelperMock('eems_affiliate/data', array('buildBeaconUrl'));
        $helper->expects($this->once())
            ->method('buildBeaconUrl')
            ->with($this->identicalTo($params))
            ->will($this->returnValue($beaconUrl));
        $this->replaceByMock('helper', 'eems_affiliate', $helper);

        $block = $this->getBlockMock('eems_affiliate/beacon', array('_getOrder'));
        $block->expects($this->any())
            ->method('_getOrder')
            ->will($this->returnValue($order));

        $this->assertSame($beaconUrl, $block->getBeaconUrl());
    }
    /**
     * Test building the itemized beacon url. Should include item level details
     * in the url params for each item in the order.
     */
    public function testGetBeaconUrlItemized()
    {
        $programId = 'PROGRAM ID';
        // should use itemized beacon when itemize orders is true
        $itemizeOrders = true;
        $orderId = '000012';
        $couponCode = 'COUPON';
        $itemSku = 'sku-12345';
        $altSku = 'sku-54321';
        $itemQty = 2;
        $altQty = 3;
        $itemAmt = '12.34';
        $altAmt = '10.67';
        $discountAmt = '5.00';
        $bundleSku = 'bundle-sku-made-up-of-child-skus';
        $bundleQty = 1;
        // This is the expected final output from the method - gets returned from
        // the eems_affiliate/data helper when called with the right params.
        $beaconUrl = 'https://example.com/track?PARAM=Value';
        // These are the "correct" params the helper method should be called with.
        // Order of the array is significant - both to the test and the
        // actual implementation.
        $beaconParams = array(
            'PID' => $programId,
            'OID' => $orderId,
            'PROMOCODE' => $couponCode,
            'INT' => 'ITEMIZED',
            'ITEM1' => $itemSku,
            'QTY1' => $itemQty * 2,
            'TOTALAMOUNT1' => number_format(($itemAmt - $discountAmt) * 2, 2),
            'ITEM2' => $altSku,
            'QTY2' => $altQty,
            'TOTALAMOUNT2' => $altAmt,
            'ITEM3' => $bundleSku,
            'QTY3' => $bundleQty,
            'TOTALAMOUNT3' => '0.00',
        );

        $item = Mage::getModel('sales/order_item', array(
            'sku' => $itemSku, 'qty_ordered' => $itemQty, 'row_total' => $itemAmt,
            'parent_item_id' => null, 'discount_amount' => $discountAmt,
            'product_type' => 'simple',
        ));
        $itemDupe = Mage::getModel('sales/order_item', array(
            'sku' => $itemSku, 'qty_ordered' => $itemQty, 'row_total' => $itemAmt,
            'parent_item_id' => null, 'discount_amount' => $discountAmt,
            'product_type' => 'simple',
        ));
        $parentItem = Mage::getModel('sales/order_item', array(
            'sku' => $altSku, 'qty_ordered' => $altQty, 'row_total' => $altAmt,
            'parent_item_id' => null, 'discount_amount' => 0.00,
            'product_type' => 'configurable',
        ));
        $altItem = Mage::getModel('sales/order_item', array(
            'sku' => $altSku, 'qty_ordered' => $altQty, 'row_total' => 0.00,
            'parent_item_id' => '2', 'discount_amount' => 0.00,
            'product_type' => 'simple',
        ));
        $bundleItem = Mage::getModel('sales/order_item', array(
            'sku' => $bundleSku, 'qty_ordered' => $bundleQty, 'row_total' => '500.00',
            'parent_item_id' => null, 'discount_amount' => 0.00,
            'product_type' => 'bundle',
        ));
        $order = Mage::getModel('sales/order', array(
            'increment_id' => $orderId, 'coupon_code' => $couponCode,
        ));
        $order->addItem($item);
        $order->addItem($itemDupe);
        $order->addItem($parentItem);
        $order->addItem($altItem);
        $order->addItem($bundleItem);

        // mock out the config helper to setup expected system configurations
        $this->replaceByMock(
            'helper',
            'eems_affiliate/config',
            $this->_getConfigHelper($programId, null, $itemizeOrders)
        );

        $helper = $this->getHelperMock('eems_affiliate/data', array('buildBeaconUrl'));
        $helper->expects($this->once())
            ->method('buildBeaconUrl')
            ->with($this->identicalTo($beaconParams))
            ->will($this->returnValue($beaconUrl));
        $this->replaceByMock('helper', 'eems_affiliate', $helper);

        $block = $this->getBlockMock('eems_affiliate/beacon', array('_getOrder'));
        $block->expects($this->any())
            ->method('_getOrder')
            ->will($this->returnValue($order));

        $this->assertSame($beaconUrl, $block->getBeaconUrl());
    }
    /**
     * Test that EbayEnterprise_Affiliate_Block_Beacon::_getOrder will be invoked
     * by this test and expect it to return Mage_Sales_Model_Order object
     */
    public function testGetOrder()
    {
        $orderId = '00000084848';
        $session = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $session->setLastOrderId($orderId);
        $this->replaceByMock('singleton', 'checkout/session', $session);

        $order = $this->getModelMock('sales/order', array('load'));
        $order->expects($this->once())
            ->method('load')
            ->with($this->identicalTo($orderId))
            ->will($this->returnSelf());
        $this->replaceByMock('model', 'sales/order', $order);

        $beacon = $this->getBlockMock('eems_affiliate/beacon', array());
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($beacon, '_order', null);

        $this->assertSame($order, EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $beacon,
            '_getOrder',
            array()
        ));
    }
    /**
     * Test that EbayEnterprise_Affiliate_Block_Beacon::showBeacon will return true
     * only if tracking is enabled and we have a valid order
     * AND either conditional pixel logic is OFF or it is ON and we have
     * a valid cookie.
     *
     * @dataProvider dataProvider
     */
    public function testShowBeacon($isEnabled, $isOrder, $isPixelLogicEnabled, $isValidCookie, $expectedResult)
    {
        $configHelper = $this->getHelperMock('eems_affiliate/config', array('isEnabled', 'isConditionalPixelEnabled'));
        $configHelper->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue($isEnabled));

        $configHelper->expects($this->any())
            ->method('isConditionalPixelEnabled')
            ->will($this->returnValue($isPixelLogicEnabled));
        $this->replaceByMock('helper', 'eems_affiliate/config', $configHelper);

        $dataHelper = $this->getHelperMock('eems_affiliate', array('isValidCookie'));
        $dataHelper->expects(($this->any()))
            ->method('isValidCookie')
            ->will($this->returnValue($isValidCookie));
        $this->replaceByMock('helper', 'eems_affiliate', $dataHelper);

        $order = (bool)$isOrder ? Mage::getModel('sales/order') : null;
        $beacon = $this->getBlockMock('eems_affiliate/beacon', array('_getOrder'));
        $beacon->expects($this->any())
            ->method('_getOrder')
            ->will($this->returnValue($order));

        $this->assertEquals((bool)$expectedResult, $beacon->showBeacon());
    }
}
