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
 * The public methods of this class are all expected to be used as callbacks
 * for building the Affiliate product feed.
 * @see EbayEnterprise_Affiliate_Helper_Map
 */
class EbayEnterprise_Affiliate_Helper_Map_Product
{
	const MEDIA_PATH = 'catalog/product';
	const NO_SELECTION = 'no_selection';
	/**
	 * Get a product first chained categories meaning that a product can be
	 * long to many chained of categories. Chained of categories here mean that
	 * a category root to it's inner most leaf child. Expects "item" to be a
	 * Mage_Catalog_Model_Product. If "format" is included in `$params`, it will
	 * must be a valid format string and will be used to format the data returned
	 * from this method.
	 * @param  array $params
	 * @return string
	 */
	public function getCategory(array $params)
	{
		$categories = $params['item']->getCategoryCollection();
		$category = $categories->getFirstItem();
		$format = isset($params['format']) ? $params['format'] : '%s';
		return !is_null($category) ?
			sprintf($format, $this->_buildCategoryTree($category)) : null;
	}
	/**
	 * Take an array of category entity ids return a collection of categories
	 * in this array of category ids
	 * @param array $entityIds list of category ids
	 * @return Mage_Catalog_Model_Resource_Category_Collection
	 */
	protected function _getCategoriesByIds(array $entityIds)
	{
		return Mage::getResourceModel('catalog/category_collection')
			->addAttributeToSelect(array('name', 'entity_id'))
			->addAttributeToFilter(array(array('attribute' => 'entity_id', 'in' => $entityIds)))
			->load();
	}
	/**
	 * Take a Mage_Catalog_Model_Category object and build a category tree from
	 * the child leaf to the root of the category (root > inner child > inner most child)
	 * @param Mage_Catalog_Model_Category $category the inner most child
	 * @return string
	 */
	protected function _buildCategoryTree(Mage_Catalog_Model_Category $category)
	{
		$collecton = $this->_getCategoriesByIds(explode('/', $category->getPath()));
		$categories = array();
		foreach ($collecton as $cat) {
			$categories[] = $cat->getName();
		}
		return implode(' > ', array_filter($categories));
	}
	/**
	 * get a product image view URL
	 * Note: calling Mage_Catalog_Model_Product::getImageUrl, or getThumbnailUrl
	 *       will return the wrong URL when running the feed via CRONJOB will return
	 *       to something similar to this:
	 *       (http://<host>/skin/frontend/default/default/images/catalog/product/placeholder/image.jpg)
	 *       so this method will try to extrapolate as best it can the absolute path of
	 *       the image by calling getImage or getThumbnail which will give the
	 *       a relative path to the image in which we passed to a specialize method to try
	 *       to build the absolute URL path to the image
	 * @param  array $params
	 * @return string
	 */
	public function getImageUrl(array $params)
	{
		$item = $params['item'];
		// calling the getThumbnail or getImage will return a relative path
		// to where we think product image will live, see (self::MEDIA_PATH) constant
		$image = trim($item->getDataUsingMethod($params['key']));
		$format = isset($params['format']) ? $params['format'] : '%s';
		return ($image !== '' && $image !== static::NO_SELECTION)?
			sprintf($format, $this->_getAbsoluteImagePath($image)) : null;
	}
	/**
	 * get the absolute URL product media path
	 * @param string $image the relative image
	 * @return string
	 * @codeCoverageIgnore
	 */
	protected function _getAbsoluteImagePath($image)
	{
		// concatenating magento absolute path to the media folder, with a class
		// constant base on observation of where we assume all product images stay
		// and along with the passed in image relative path
		return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) .
			static::MEDIA_PATH . $image;
	}
	/**
	 * get a product manage stock quantity
	 * @param  array $params
	 * @return string
	 */
	public function getInStockQty(array $params)
	{
		return (int) Mage::getModel('cataloginventory/stock_item')
			->loadByProduct($params['item'])
			->getQty();
	}
	/**
	 * check if a product is in stock and return 'yes' or 'no' in respect to
	 * the outcome
	 * @param  array $params
	 * @return string
	 */
	public function getInStockYesNo(array $params)
	{
		return Mage::helper('eems_affiliate')->parseBoolToYesNo(
			Mage::getModel('cataloginventory/stock_item')
				->loadByProduct($params['item'])
				->getIsInStock()
		);
	}
}
