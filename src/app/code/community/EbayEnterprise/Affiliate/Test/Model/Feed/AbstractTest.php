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


class EbayEnterprise_Affiliate_Test_Model_Feed_AbstractTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Test generating a feed file
     */
    public function testGenerateFeed()
    {
        $feedData = array(array('one', 'two', 'three'));
        $feed = $this->getModelMock(
            'eems_affiliate/feed_abstract',
            array('_buildFeedData', '_generateFile'),
            true
        );
        $feed->expects($this->once())
            ->method('_buildFeedData')
            ->will($this->returnValue($feedData));
        $feed->expects($this->once())
            ->method('_generateFile')
            ->with($this->identicalTo($feedData))
            ->will($this->returnSelf());
        $this->assertSame($feed, $feed->generateFeed());
    }
    /**
     * Test generating array of data to place into feed
     */
    public function testBuildFeedData()
    {
        $itemOneMappedData = array('item', 'one', 'data');
        $itemTwoMappedData = array('item', 'two', 'data');
        $feedData = array($itemOneMappedData, $itemTwoMappedData);

        $itemCollection = new Varien_Data_Collection();
        $itemOne = Mage::getModel('sales/order');
        $itemTwo = Mage::getModel('sales/order');
        $itemCollection->addItem($itemOne)->addItem($itemTwo);

        $feed = $this->getModelMock(
            'eems_affiliate/feed_abstract',
            array('_getItems', '_applyMapping'),
            true
        );
        // get items will vary per feed type - itemized order, basic order or product
        $feed->expects($this->once())
            ->method('_getItems')
            ->will($this->returnValue($itemCollection));
        // should get called once for each item, returning the array of item data for the feed
        $feed->expects($this->exactly(2))
            ->method('_applyMapping')
            ->will($this->returnValueMap(array(
                array($itemOne, $itemOneMappedData),
                array($itemTwo, $itemTwoMappedData),
            )));

        $this->assertSame(
            $feedData,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($feed, '_buildFeedData')
        );
    }
    /**
     * Test applying the configured mapping to the item to get back an array of
     * data for each field in the feed.
     */
    public function testApplyMapping()
    {
        $orderAmt = 23.23;
        $store = Mage::app()->getStore();
        $fields = array('fieldA', 'fieldB');
        // callback mapping to use
        $mappings = array(
            'fieldA' => array(
                'class' => 'eems_affiliate/map_order', 'type' => 'helper',
                'method' => 'getOrderAmount', 'params' => array('key' => 'value'),
                'column_name' => 'OID'
            ),
            'fieldB' => array('type' => 'disabled'),
        );
        $itemData = array($orderAmt);
        // The type of this object will vary depending on feed type but will likely
        // be a descendent of Varien_Object. It shouldn't matter much to the test
        // as it should just get passed through to the callbacks.
        $item = new Varien_Object();

        // All callbacks setup to use this helper
        $feedHelper = $this->getHelperMock(
            'eems_affiliate/map_order',
            array('getOrderAmount')
        );
        $feedHelper->expects($this->once())
            ->method('getOrderAmount')
            ->with($this->identicalTo(array('key' => 'value', 'item' => $item, 'store' => $store)))
            ->will($this->returnValue($orderAmt));
        $this->replaceByMock('helper', 'eems_affiliate/map_order', $feedHelper);

        $config = $this->getHelperMock('eems_affiliate/config', array('getCallbackMappings'));
        $config->expects($this->any())
            ->method('getCallbackMappings')
            ->will($this->returnValue($mappings));
        $this->replaceByMock('helper', 'eems_affiliate/config', $config);

        $feed = $this->getModelMock(
            'eems_affiliate/feed_abstract',
            array('_getFeedFields'),
            true,
            array(array('store' => $store))
        );
        // The _getFeedFields method will be defined for each type of feed and should
        // return array of mapped fields to include.
        $feed->expects($this->any())
            ->method('_getFeedFields')
            ->will($this->returnValue($fields));

        $this->assertSame(
            $itemData,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($feed, '_applyMapping', array($item))
        );
    }
    /**
     * Test that when a configured method does not exist on the specified class,
     * an exception is thrown.
     */
    public function testInvokeCallbackFailures()
    {
        $callbackConfig = array('class' => 'eems_affiliate', 'type' => 'helper', 'method' => 'getSomething');
        $feed = $this->getModelMock(
            'eems_affiliate/feed_abstract',
            array('_getCallbackInstance'),
            true
        );
        // Sub an object that will certainly not have the configured method on it.
        $feed->expects($this->once())
            ->method('_getCallbackInstance')
            ->with($this->identicalTo($callbackConfig))
            ->will($this->returnValue(new StdClass));

        $this->setExpectedException('EbayEnterprise_Affiliate_Exception_Configuration');
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $feed,
            '_invokeCallback',
            array($callbackConfig, new Varien_Object())
        );
    }
    /**
     * Provide a sample callback configuration and whether the callback is
     * expected to be deemed valid.
     * @return array
     */
    public function provideCallbacksToValidate()
    {
        return array(
            array(
                array(),
                false
            ),
            array(
                array('type' => 'disabled'),
                true
            ),
            array(
                array('type' => 'disabled', 'class' => 'eems_affiliate'),
                true
            ),
            array(
                array('type' => 'model'),
                false
            ),
            array(
                array('type' => 'singleton', 'class' => 'eems_affiliate/feed_abstract'),
                false
            ),
            array(
                array('type' => 'helper', 'class' => 'eems_affiliate/map', 'method' => 'getOrderId'),
                false
            ),
            array(
                array('type' => 'helper', 'class' => 'eems_affiliate/map', 'method' => 'getOrderId', 'column_name' => 'OID'),
                true
            ),
        );
    }
    /**
     * Test checking for a callback configuration to be valid. When the is valid
     * param indicates the configuration is invalid, an exception should be
     * thrown. Otherwise, the method should simply reurun self.
     * @param  array $config Array of config data to validate
     * @param  boolean $isValid Is the config data valid
     * @dataProvider provideCallbacksToValidate
     */
    public function testValidateCallbackConfig($config, $isValid)
    {
        $feed = $this->getModelMock(
            'eems_affiliate/feed_abstract',
            array(),
            true
        );

        if (!$isValid) {
            $this->setExpectedException('EbayEnterprise_Affiliate_Exception_Configuration');
        }

        $result = EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $feed,
            '_validateCallbackConfig',
            array($config)
        );
        if ($isValid) {
            $this->assertSame($result, $feed);
        }
    }
    /**
     * Test getting column headers based on the configured mappings for the feed.
     */
    public function testGetHeaders()
    {
        $fields = array('program_id', 'order_id');
        $mapping = array(
            'program_id' => array('class' => 'some/class', 'method' => 'getProgramId', 'column_name' => 'PID'),
            'order_id' => array('class' => 'some/class', 'method' => 'getOrderId', 'column_name' => 'OID'),
        );

        $config = $this->getHelperMock('eems_affiliate/config', array('getCallbackMappings'));
        $config->expects($this->any())
            ->method('getCallbackMappings')
            ->will($this->returnValue($mapping));
        $this->replaceByMock('helper', 'eems_affiliate/config', $config);

        $feed = $this->getModelMock(
            'eems_affiliate/feed_abstract',
            array('_getFeedFields'),
            true
        );
        $feed->expects($this->once())
            ->method('_getFeedFields')
            ->will($this->returnValue($fields));

        $this->assertSame(
            // keys don't matter, so make sure to compare just values to values
            array('PID', 'OID'),
            array_values(EcomDev_Utils_Reflection::invokeRestrictedMethod($feed, '_getHeaders'))
        );
    }
    /**
     * Test generating the full path to the file. Should consist of Magento's
     * base dir, the configured export feed path, and the name of the file.
     */
    public function testGenerateFilePath()
    {
        $format = 'file_name.csv';
        $configuredPath = 'var/feed/export';
        $configHelper = $this->getHelperMock(
            'eems_affiliate/config',
            array('getExportFilePath', 'getProgramId')
        );
        $configHelper->expects($this->any())
            ->method('getExportFilePath')
            ->will($this->returnValue($configuredPath));
        $configHelper->expects($this->any())
            ->method('getProgramId')
            ->will($this->returnValue('PROGRAM_ID'));
        $this->replaceByMock('helper', 'eems_affiliate/config', $configHelper);

        $feed = $this->getModelMock(
            'eems_affiliate/feed_abstract',
            array('_getFileName'),
            true
        );
        $feed->expects($this->any())
            ->method('_getFileName')
            ->will($this->returnValue($format));

        $this->assertSame(
            Mage::getBaseDir() . DS . 'var/feed/export' . DS . $format,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($feed, '_generateFilePath')
        );
    }
}
