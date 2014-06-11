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


class EbayEnterprise_Affiliate_Model_System_Config_Source_Attributes
{
	const PRODUCT_URL_VALUE = 'product_url';
	const PRODUCT_URL_LABEL = 'Product Url';
	/**
	 * Get product attributes
	 * @return array
	 */
	public function toOptionArray()
	{
		$helper = Mage::helper('catalog');
		$collection = Mage::getSingleton('eav/config')
			->getEntityType(Mage_Catalog_Model_Product::ENTITY)
			->getAttributeCollection();
		$attributes = array(array('value' => '', 'label' => ''));
		foreach ($collection as $attribute) {
			$attributes[] = array(
				'value' => $attribute->getAttributeCode(),
				'label' => $helper->__($attribute->getFrontendLabel() ?
					$attribute->getFrontendLabel() :
					$this->_convertToTitleCase($attribute->getAttributeCode())
				)
			);
		}
		$attributes[] = array(
			'value' => static::PRODUCT_URL_VALUE,
			'label' => $helper->__(static::PRODUCT_URL_LABEL)
		);
		// sort the attribute options by label
		usort($attributes, array($this, '_compareLabels'));
		return $attributes;
	}
	/**
	 * Convert the attribute code to title case. Replace '_'s with spaces
	 * and capitalize each word.
	 * @param  string $attributeCode
	 * @return string
	 */
	protected function _convertToTitleCase($attributeCode)
	{
		return ucwords(str_replace('_', ' ', $attributeCode));
	}
	/**
	 * Comparison method for sorting options by "label" key.
	 * @param  array $a
	 * @param  array $b
	 * @return int
	 */
	protected function _compareLabels($a, $b)
	{
		$aLabel = strtolower($a['label']);
		$bLabel = strtolower($b['label']);
		if ($aLabel === $bLabel) {
			return 0;
		}
		return $aLabel < $bLabel ? -1 : 1;
	}
}
