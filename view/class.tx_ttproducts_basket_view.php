<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Kasper Skårhøj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the tt_products (Shop System) extension.
 *
 * basket functions for a basket object
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Renè Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @author	Els Verberne <verberne@bendoo.nl>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\TtProducts\Api\PaymentShippingHandling;

class tx_ttproducts_basket_view implements \TYPO3\CMS\Core\SingletonInterface {
	public $conf;
	public $config;
	public $price; // price object
	public $urlObj; // url functions
	public $urlArray; // overridden url destinations
	public $funcTablename;
	public $errorCode;
	public $useArticles;


	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basketObj is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param	string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
	 */
	public function init (
		$urlArray = array(),
		$useArticles,
		$errorCode
	) {
		$this->errorCode = $errorCode;
		$this->useArticles = $useArticles;

		$this->urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view'); // a copy of it
		$this->urlObj->setUrlArray($urlArray);
	} // init


	public function getMarkerArray (
		$basketExtra,
		$calculatedArray,
		$taxArray
	) {
		$cObj = FrontendUtility::getContentObjectRenderer();
		$taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');
		$priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
		$markerArray = array();
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$conf = $cnf->conf;

			// This is the total for the goods in the basket.
		$markerArray['###PRICE_GOODSTOTAL_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceTax']['goodstotal']['ALL'] + $calculatedArray['deposittax']['goodstotal']['ALL']);
		$markerArray['###PRICE_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceNoTax']['goodstotal']['ALL'] + $calculatedArray['depositnotax']['goodstotal']['ALL']);
		$markerArray['###PRICE_GOODSTOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceTax']['goodstotal']['ALL'] - $calculatedArray['priceNoTax']['goodstotal']['ALL'] + $calculatedArray['deposittax']['goodstotal']['ALL'] - $calculatedArray['depositnotax']['goodstotal']['ALL']);

		$markerArray['###PRICE2_GOODSTOTAL_TAX###'] = $priceViewObj->priceFormat($calculatedArray['price2Tax']['goodstotal']['ALL']);
		$markerArray['###PRICE2_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($calculatedArray['price2NoTax']['goodstotal']['ALL']);
		$markerArray['###PRICE2_GOODSTOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($calculatedArray['price2Tax']['goodstotal']['ALL'] - $calculatedArray['price2NoTax']['goodstotal']['ALL']);

		$markerArray['###PRICE_DISCOUNT_GOODSTOTAL_TAX###'] = $priceViewObj->priceFormat($calculatedArray['noDiscountPriceTax']['goodstotal']['ALL'] - $calculatedArray['priceTax']['goodstotal']['ALL']);
		$markerArray['###PRICE_DISCOUNT_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($calculatedArray['noDiscountPriceNoTax']['goodstotal']['ALL'] - $calculatedArray['priceNoTax']['goodstotal']['ALL']);

		if (
			isset($taxArray) &&
			is_array($taxArray) &&
			!empty($taxArray)
		) {
			foreach ($taxArray as $k => $taxrate) {
				$calculatedTax = $taxObj->getFieldCalculatedValue($taxrate, $basketExtra);
				if ($calculatedTax !== false) {
					$taxrate = $calculatedTax;
				}
				$taxstr = strval(number_format(floatval($taxrate), 2));
				$label = chr(ord('A') + $k);
				$markerArray['###PRICE_TAXRATE_NAME' . ($k + 1) . '###'] = $label;
				$markerArray['###PRICE_TAXRATE_TAX' . ($k + 1) . '###'] = $taxrate;

				if (isset($calculatedArray['priceNoTax']['sametaxtotal']['ALL'][$taxstr])) {
                    $label = $calculatedArray['priceNoTax']['sametaxtotal']['ALL'][$taxstr] + $calculatedArray['depositnotax']['sametaxtotal']['ALL'][$taxstr];
                    $markerArray['###PRICE_TAXRATE_TOTAL' . ($k + 1) . '###'] = $priceViewObj->priceFormat($label);

                    $label = $calculatedArray['priceNoTax']['goodssametaxtotal']['ALL'][$taxstr] + $calculatedArray['depositnotax']['goodssametaxtotal']['ALL'][$taxstr];
                    $markerArray['###PRICE_TAXRATE_GOODSTOTAL' . ($k + 1) . '###'] = $priceViewObj->priceFormat($label);

                    $label =
                        $priceViewObj->priceFormat(
                            (
                                $calculatedArray['priceNoTax']['sametaxtotal']['ALL'][$taxstr] +
                                $calculatedArray['depositnotax']['sametaxtotal']['ALL'][$taxstr]
                            ) *
                            ($taxrate / 100)
                        );
                    $markerArray['###PRICE_TAXRATE_ONLY_TAX' . ($k + 1) . '###'] = $label;

                    $label = $priceViewObj->priceFormat(($calculatedArray['priceNoTax']['goodssametaxtotal']['ALL'][$taxstr] +
                    $calculatedArray['depositnotax']['goodssametaxtotal']['ALL'][$taxstr]) * ($taxrate / 100));
                    $markerArray['###PRICE_TAXRATE_GOODSTOTAL_ONLY_TAX' . ($k + 1) . '###'] = $label;
                } else {
                    $zeroPrice = $priceViewObj->priceFormat(0);
                    $markerArray['###PRICE_TAXRATE_TOTAL' . ($k + 1) . '###'] = $zeroPrice;
                    $markerArray['###PRICE_TAXRATE_GOODSTOTAL' . ($k + 1) . '###'] = $zeroPrice;
                    $markerArray['###PRICE_TAXRATE_ONLY_TAX' . ($k + 1) . '###'] = $zeroPrice;
                    $markerArray['###PRICE_TAXRATE_GOODSTOTAL_ONLY_TAX' . ($k + 1) . '###'] = $zeroPrice;
                }
			}
		}

		// This is for the Basketoverview
		$markerArray['###NUMBER_GOODSTOTAL###'] = $calculatedArray['count'];
        $fileresource = FrontendUtility::fileResource($conf['basketPic']);
		$markerArray['###IMAGE_BASKET###'] = $fileresource;

		return $markerArray;
	}


	static public function getDiscountSubpartArray(
		&$subpartArray,
		&$wrappedSubpartArray,
		$calculatedArray
	) {
		$discountValue = tx_ttproducts_basket_calculate::getRealDiscount($calculatedArray);
		if ($discountValue) {
			$wrappedSubpartArray['###DISCOUNT_NOT_EMPTY###'] = '';
		} else {
			$subpartArray['###DISCOUNT_NOT_EMPTY###'] = '';
		}
	}


	public function getBoundaryMarkerArray (
		$templateCode,
		$cObj,
		$cnf,
		$calculatedArray,
		$checkPriceArray,
		$markerArray,
		&$subpartArray,
		&$wrappedSubpartArray
	) {
        $parser = tx_div2007_core::newHtmlParser(false);
		$basketConfArray = array();
		// check the basket limits
		$basketConfArray['minimum'] = $cnf->getBasketConf('minPrice');
		$basketConfArray['maximum'] = $cnf->getBasketConf('maxPrice');
		$priceSuccessArray = array();
		$priceSuccessArray['minimum'] = true;
		$priceSuccessArray['maximum'] = true;
		$boundaryArray = array('minimum', 'maximum');

		foreach ($boundaryArray as $boundaryType) {
			switch ($boundaryType) {
				case 'minimum':
					$markerKey = 'MINPRICE';
					break;
				case 'maximum':
					$markerKey = 'MAXPRICE';
					break;
			}

			if (
                isset($checkPriceArray[$boundaryType]) &&
				$checkPriceArray[$boundaryType] &&
				isset($basketConfArray[$boundaryType]['type']) &&
				$basketConfArray[$boundaryType]['type'] == 'price'
			) {
				$value = $calculatedArray['priceTax'][$basketConfArray[$boundaryType]['collect']]['ALL'];

				if (
					isset($value) &&
					isset($basketConfArray[$boundaryType]['collect']) &&
					(
						($boundaryType == 'minimum' && $value < doubleval($basketConfArray[$boundaryType]['value'])) ||
						($boundaryType == 'maximum' && $value > doubleval($basketConfArray[$boundaryType]['value']))
					)
				) {
					$subpartArray['###MESSAGE_' . $markerKey . '###'] = '';
					$tmpSubpart = tx_div2007_core::getSubpart($templateCode, '###MESSAGE_' . $markerKey . '_ERROR###');
					$subpartArray['###MESSAGE_' . $markerKey . '_ERROR###'] = $parser->substituteMarkerArray($tmpSubpart,  $markerArray);
					$priceSuccessArray[$boundaryType] = false;
				}
			}

			if ($priceSuccessArray[$boundaryType]) {

				$subpartArray['###MESSAGE_' . $markerKey . '_ERROR###'] = '';
				$tmpSubpart = tx_div2007_core::getSubpart($templateCode, '###MESSAGE_' . $markerKey . '###');
				$subpartArray['###MESSAGE_' . $markerKey . '###'] = $parser->substituteMarkerArray($tmpSubpart, $markerArray);
			}
		} // foreach

		if ($priceSuccessArray['minimum'] && $priceSuccessArray['maximum']) {
			$wrappedSubpartArray['###MESSAGE_PRICE_VALID###'] = '';
		} else {
			$subpartArray['###MESSAGE_PRICE_VALID###'] = '';
		}
	}


	/**
	 * This generates the shopping basket layout and also calculates the totals. Very important function.
	 * TODO: basket view must not make any complex reading of data of articles. Only the itemarray of the basket should be treated with at all.
	 */
	public function getView (
		&$errorCode,
		$templateCode,
		$theCode,
		$infoViewObj,
		$bSelectSalutation,
		$bSelectVariants,
		$calculatedArray,
		$bHtml = true,
		$subpartMarker = 'BASKET_TEMPLATE',
		$mainMarkerArray = array(),
		$templateFilename = '',
		$itemArray = array(),
        $notOverwritePriceIfSet = false,
		$multiOrderArray = array(),
		$productRowArray = array(),
		$basketExtra = array(),
		$basketRecs = array()
	) {
			/*

				Very central function in the library.
				By default it extracts the subpart, ###BASKET_TEMPLATE###, from the $templateCode (if given, else the default $this->templateCode)
				and substitutes a lot of fields and subparts.
				Any pre-preparred fields can be set in $mainMarkerArray, which is substituted in the subpart before the item-and-categories part is substituted.
			*/
		$out = '';
		$calculationField = \JambageCom\TtProducts\Model\Field\FieldInterface::PRICE_CALCULATED;
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$creditpointsObj = GeneralUtility::makeInstance('tx_ttproducts_field_creditpoints');
		$basketExt = tx_ttproducts_control_basket::getBasketExt();
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$itemObj = GeneralUtility::makeInstance('tx_ttproducts_basketitem');
		$basketItemView = GeneralUtility::makeInstance('tx_ttproducts_basketitem_view');
		$cObj = FrontendUtility::getContentObjectRenderer();
        $parser = tx_div2007_core::newHtmlParser(false);

		$taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');
		$piVars = tx_ttproducts_model_control::getPiVars();
		$articleViewTagArray = array();
		$checkPriceZero = true;
		$this->urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view'); // a copy of it

		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$billdeliveryObj = GeneralUtility::makeInstance('tx_ttproducts_billdelivery');
		$viewControlConf = $cnf->getViewControlConf($theCode);

		$viewControlConf = $cnf->getViewControlConf($theCode);
		if (!empty($viewControlConf)) {
			if (
				isset($viewControlConf['param.']) &&
				is_array($viewControlConf['param.'])
			) {
				$viewParamConf = $viewControlConf['param.'];
			}
		}

		$bUseBackPid =
			(
				isset($viewParamConf) && $viewParamConf['use'] == 'backPID' ?
					true :
					false
			);

		$conf = $cnf->getConf();
		$config = $cnf->getConfig();

		$funcTablename = tx_ttproducts_control_basket::getFuncTablename();
		$itemTableView = $tablesObj->get($funcTablename, true);
		$itemTable = $itemTableView->getModelObj();
		$tableConf = $itemTable->getTableConf($theCode);
		$itemTable->initCodeConf($theCode, $tableConf);
		$quantityArray = array();
		$quantityArray['minimum'] = array();
		$quantityArray['maximum'] = array();

		$articleViewObj = $tablesObj->get('tt_products_articles', true);
		$articleTable = $articleViewObj->getModelObj();
		$priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');

		if ($templateCode == '') {
            $templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
            $errorCode[0] = 'empty_template';
            $errorCode[1] = ($templateFilename ? $templateFilename : $templateObj->getTemplateFile());
            return '';
		}
			// Getting subparts from the template code.
		$t = array();

		$tempContent =
			tx_div2007_core::getSubpart(
				$templateCode,
				$subpartmarkerObj->spMarker(
					'###' . $subpartMarker . $config['templateSuffix'] . '###'
				)
			);

		if (!$tempContent) {
			$tempContent =
				tx_div2007_core::getSubpart(
					$templateCode,
					$subpartmarkerObj->spMarker(
						'###' . $subpartMarker . '###'
					)
				);
		}

		$feuserSubpartArray = array();
		$feuserWrappedSubpartArray = array();
		$viewTagArray = $markerObj->getAllMarkers($tempContent);

		$orderAddressViewObj = $tablesObj->get('fe_users', true);
		$orderAddressObj = $orderAddressViewObj->getModelObj();
		$orderAddressViewObj->getWrappedSubpartArray(
			$viewTagArray,
			$bUseBackPid,
			$feuserSubpartArray,
			$feuserWrappedSubpartArray
		);

		$feUserRow = array();

		if (
            \JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn() &&
			isset($GLOBALS['TSFE']->fe_user->user) &&
			is_array($GLOBALS['TSFE']->fe_user->user)
		) {
			$feUserRow = $GLOBALS['TSFE']->fe_user->user;
		}

		$tmp = [];
        $feUsersParentArray = [];
		$feUsersArray = $markerObj->getMarkerFields(
			$tempContent,
			$orderAddressObj->getTableObj()->tableFieldArray,
			$orderAddressObj->getTableObj()->requiredFieldArray,
			$tmp,
			$orderAddressViewObj->marker,
			$feUsersViewTagArray,
			$feUsersParentArray
		);

		$orderAddressViewObj->getItemSubpartArrays(
			$tempContent,
			'fe_users',
			$feUserRow,
			$feuserSubpartArray,
			$feuserWrappedSubpartArray,
			$feUsersViewTagArray,
			$theCode,
			$basketExtra
		);

		$markerArray = array();
		if (isset($mainMarkerArray) && is_array($mainMarkerArray)) {
			$markerArray = array_merge($markerArray, $mainMarkerArray);
		}
			// add Global Marker Array
		$globalMarkerArray = $markerObj->getGlobalMarkerArray();
		$markerArray = array_merge($markerArray, $globalMarkerArray);

		$tempContent =
			tx_div2007_core::substituteMarkerArrayCached(
				$tempContent,
				$markerArray,
				$feuserSubpartArray  // The emptied subparts must be considered before the wrapped subparts are added because TYPO3 does not support nested subparts.
			);

		$t['basketFrameWork'] =
			tx_div2007_core::substituteMarkerArrayCached(
				$tempContent,
				array(),
				array(),
				$feuserWrappedSubpartArray
			);

		$subpartEmptyArray = array(
			'EMAIL_PLAINTEXT_TEMPLATE_SHOP',
			'EMAIL_HTML_TEMPLATE_SHOP',
			'BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE'
		);

		if (
			!$t['basketFrameWork'] &&
			!in_array($subpartMarker, $subpartEmptyArray)
		) {
			$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
			$errorCode[0] = 'no_subtemplate';
			$errorCode[1] = '###' . $subpartMarker . $templateObj->getTemplateSuffix() . '###';
			$errorCode[2] = ($templateFilename ? $templateFilename : $templateObj->getTemplateFile());
			return '';
		}

		if ($t['basketFrameWork']) {
			$checkExpression = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['templateCheck'];
			if (!empty($checkExpression)) {
				$wrongPounds = preg_match_all($checkExpression, $t['basketFrameWork'], $matches);
				if ($wrongPounds) {
					$errorCode[0] = 'template_invalid_marker_border';
					$errorCode[1] = '###' . $subpartMarker . '###';
					$errorCode[2] = htmlspecialchars(implode('|', $matches['0']));
					return '';
				}
			}

			if (!$bHtml) {
				$t['basketFrameWork'] = html_entity_decode($t['basketFrameWork'], ENT_QUOTES);
			}

				// If there is a specific section for the billing address if user is logged in (used because the address may then be hardcoded from the database
			if (
				trim(
					tx_div2007_core::getSubpart(
						$t['basketFrameWork'],
						'###BILLING_ADDRESS_LOGIN###'
					)
				)
			) {
				if (\JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn() && $conf['lockLoginUserInfo']) {
					$t['basketFrameWork'] = $parser->substituteSubpart($t['basketFrameWork'], '###BILLING_ADDRESS###', '');
				} else {
					$t['basketFrameWork'] = $parser->substituteSubpart($t['basketFrameWork'], '###BILLING_ADDRESS_LOGIN###', '');
				}
			}
			$t['categoryFrameWork'] = tx_div2007_core::getSubpart($t['basketFrameWork'], '###ITEM_CATEGORY###');
			$t['itemFrameWork'] = tx_div2007_core::getSubpart($t['basketFrameWork'], '###ITEM_LIST###');

			$t['item'] = tx_div2007_core::getSubpart($t['itemFrameWork'], '###ITEM_SINGLE###');
			$t['taxes'] = tx_div2007_core::getSubpart($t['basketFrameWork'], '###COUNTRY_TAXRATES###');

			$currentP='';
			$itemsOut='';
			$viewTagArray = array();
			$markerFieldArray = array('BULKILY_WARNING' => 'bulkily',
				'PRODUCT_SPECIAL_PREP' => 'special_preparation',
				'PRODUCT_ADDITIONAL_SINGLE' => 'additional',
				'PRODUCT_LINK_DATASHEET' => 'datasheet');
			$parentArray = array();
			$fieldsArray = $markerObj->getMarkerFields(
				$t['item'],
				$itemTable->getTableObj()->tableFieldArray,
				$itemTable->getTableObj()->requiredFieldArray,
				$markerFieldArray,
				$itemTable->marker,
				$viewTagArray,
				$parentArray
			);
			$count = 0;
			$bCopyProduct2Article = false;

			if ($this->useArticles == 0) {
				if (
					strpos($t['item'],
					$articleViewObj->getMarker()) !== false
				) {
					$bCopyProduct2Article = true;
				}
			}

			$checkPriceArray = array();
			$checkPriceArray['minimum'] = false;
			$checkPriceArray['maximum'] = false;

			if ($this->useArticles == 1 || $this->useArticles == 3) {
				$markerFieldArray = array();
				$articleParentArray = array();
				$articleFieldsArray = $markerObj->getMarkerFields(
					$t['item'],
					$itemTable->getTableObj()->tableFieldArray,
					$itemTable->getTableObj()->requiredFieldArray,
					$markerFieldArray,
					$articleTable->marker,
					$articleViewTagArray,
					$articleParentArray
				);

				$prodUidField = $cnf->getTableDesc($articleTable->getTableObj()->name, 'uid_product');
				$fieldsArray = array_merge($fieldsArray, $articleFieldsArray);
				$uidKey = array_search($prodUidField, $fieldsArray);
				if ($uidKey != '') {
					unset($fieldsArray[$uidKey]);
				}
			}

			$damViewTagArray = array();
			// DAM support
			if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dam') || $piVars['dam']) {
				$damParentArray = array();
				$damObj = $tablesObj->get('tx_dam');
				$fieldsArray = $markerObj->getMarkerFields(
					$itemFrameWork,
					$damObj->getTableObj()->tableFieldArray,
					$damObj->getTableObj()->requiredFieldArray,
					$markerFieldArray,
					$damObj->marker,
					$damViewTagArray,
					$damParentArray
				);
				$damCatObj = $tablesObj->get('tx_dam_cat');
				$damCatMarker = $damCatObj->marker;
				$damCatObj->marker = 'DAM_CAT';

				$viewDamCatTagArray = array();
				$catParentArray = array();
				$tmp = [];
				$catfieldsArray = $markerObj->getMarkerFields(
					$itemFrameWork,
					$damCatObj->getTableObj()->tableFieldArray,
					$damCatObj->getTableObj()->requiredFieldArray,
					$tmp,
					$damCatObj->marker,
					$viewDamCatTagArray,
					$catParentArray
				);
			}
			$hiddenFields = '';

			// loop over all items in the basket indexed by sorting text
			foreach ($itemArray as $sort => $actItemArray) {
				foreach ($actItemArray as $k1 => $actItem) {
					$row = $actItem['rec'];
					if (!$row) {	// avoid bug with missing row
						continue;
					}

					$extArray = $row['ext'];
					$pid = intval($row['pid']);
					if (!tx_ttproducts_control_basket::getPidListObj()->getPageArray($pid)) {
						// product belongs to another basket
						continue;
					}
					$quantity = $itemObj->getQuantity($actItem);
					$itemObj->getMinMaxQuantity($actItem, $minQuantity, $maxQuantity);

					if ($minQuantity != '0.00' && $quantity < $minQuantity) {
						$quantityArray['minimum'][] =
							array(
								'rec' => $row,
								'limitQuantity' => $minQuantity,
								'quantity' => $quantity
							);
					}
					if ($maxQuantity != '0.00' && $quantity > $maxQuantity) {
						$quantityArray['maximum'][] =
							array(
								'rec' => $row,
								'limitQuantity' => $maxQuantity,
								'quantity' => $quantity
							);
					}
					$count++;
					$actItem['rec'] = $row;	// fix bug with PHP 5.2.1
					$bIsNoMinPrice = $itemTable->hasAdditional($row, 'noMinPrice');
					if (!$bIsNoMinPrice) {
						$checkPriceArray['minimum'] = true;
					}

					$bIsNoMaxPrice = $itemTable->hasAdditional($row, 'noMaxPrice');

					if (!$bIsNoMaxPrice) {
						$checkPriceArray['maximum'] = true;
					}

					$pidcategory = ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'] == 1 ? $pid : '');
					$currentPnew = $pidcategory . '_' . $actItem['rec']['category'];

						// Print Category Title
					if ($currentPnew != $currentP) {
						if ($itemsOut) {
							$out .=
								$parser->substituteSubpart(
									$t['itemFrameWork'],
									'###ITEM_SINGLE###',
									$itemsOut
								);
						}
						$itemsOut = '';		// Clear the item-code var
						$currentP = $currentPnew;

						if ($conf['displayBasketCatHeader']) {
							$markerArray = array();
							$pageCatTitle = '';
							if (
								$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'] == 1
							) {
								$page = $tablesObj->get('pages');
								$pageTmp = $page->get($pid);
								$pageCatTitle = $pageTmp['title'] . '/';
							}
							$catTmp = '';
							if ($actItem['rec']['category']) {
								$catTmp = $tablesObj->get('tt_products_cat')->get($actItem['rec']['category']);
								$catTmp = $catTmp['title'];
							}
							$catTitle = $pageCatTitle.$catTmp;
							$cObj->setCurrentVal($catTitle);
							$markerArray['###CATEGORY_TITLE###'] =
								$cObj->cObjGetSingle(
									$conf['categoryHeader'],
									$conf['categoryHeader.'],
									'categoryHeader'
								);

							$categoryQuantity = $basketObj->getCategoryQuantity();

								// compatible with bill/delivery
							$currentCategory = $row['category'];
							$markerArray['###CATEGORY_QTY###'] = $categoryQuantity[$currentCategory];

 							$categoryPriceTax = $calculatedArray['categoryPriceTax']['goodstotal']['ALL'][$currentCategory];
 							$markerArray['###PRICE_GOODS_TAX###'] = $priceViewObj->priceFormat($categoryPriceTax);
 							$categoryPriceNoTax = $calculatedArray['categoryPriceNoTax']['goodstotal']['ALL'][$currentCategory];
 							$markerArray['###PRICE_GOODS_NO_TAX###'] = $priceViewObj->priceFormat($categoryPriceNoTax);
 							$markerArray['###PRICE_GOODS_ONLY_TAX###'] = $priceViewObj->priceFormat($categoryPriceTax - $categoryPriceNoTax);

							$out .= $parser->substituteMarkerArray($t['categoryFrameWork'], $markerArray);
						}
					}
						// Fill marker arrays
					$wrappedSubpartArray = array();
					$subpartArray = array();
					$markerArray = array();

					$bInputDisabled = ($row['inStock'] <= 0);

					// $extRow = array('extTable' => $row['extTable'], 'extUid' => $row['extUid']);
					$basketItemView->getItemMarkerArray(
						$funcTablename,
						false,
						$actItem,
						$markerArray,
						$viewTagArray,
						$hiddenFields,
						$theCode,
						$bInputDisabled,
						$count,
						false,
						'UTF-8'
					);

					$basketItemView->getItemMarkerSubpartArrays(
						$t['item'],
						$itemTable->getFuncTablename(),
						$row,
						'SINGLE',
						$viewTagArray,
						false,
						$productRowArray,
						$markerArray,
						$subpartArray,
						$wrappedSubpartArray
					);

					$catRow = $row['category'] ? $tablesObj->get('tt_products_cat')->get($row['category']) : array();
					// $catTitle= $actItem['rec']['category'] ? $this->tt_products_cat->get($actItem['rec']['category']) : '';
					$catTitle = $catRow['title'] ?? '';
					$tmp = array();

						// use the product if no article row has been found
					$prodVariantRow = $row;

					if (isset($actItem[$calculationField])) {
						$prodVariantRow[$calculationField] = $actItem[$calculationField];
					}

					$prodMarkerRow = $prodVariantRow;
					$itemTable->tableObj->substituteMarkerArray($prodMarkerRow);
					$bIsGift = tx_ttproducts_gifts_div::isGift($row, $conf['whereGift']);
					$itemTableView->getModelMarkerArray(
						$prodMarkerRow,
						'',
						$markerArray,
						$catTitle,
						0,
						'basketImage',
						$viewTagArray,
						$tmp,
						$theCode,
						$basketExtra,
						$basketRecs,
						$count,
						'',
						'',
						'',
						$bHtml,
						'UTF-8',
						'',
						$multiOrderArray,
						$productRowArray,
                        true,
						$notOverwritePriceIfSet
					);

					if (
						$this->useArticles == 1 ||
						$this->useArticles == 3 ||
						$bCopyProduct2Article
					) {
						$articleRows = array();

						if (!$bCopyProduct2Article) {
							// get the article uid with these colors, sizes and gradings
							if (
								is_array($extArray) &&
								isset($extArray['mergeArticles']) &&
								is_array($extArray['mergeArticles'])
							) {
								$prodVariantRow = $extArray['mergeArticles'];
							} else if (
								isset($extArray[$articleTable->getFuncTablename()]) &&
								is_array($extArray[$articleTable->getFuncTablename()])
							) {
								$articleExtArray = $extArray[$articleTable->getFuncTablename()];
								foreach($articleExtArray as $k => $articleData) {
									$articleRows[$k] = $articleTable->get($articleData['uid']);
								}
							} else {
								$articleRow = $itemTable->getArticleRow($row, $theCode);
								if ($articleRow) {
									$articleRows['0'] = $articleRow;
								}
							}
						}

						if (
							is_array($articleRows) &&
							!empty($articleRows)
						) {
							$bKeepNotEmpty = (boolean) $conf['keepProductData']; // Auskommentieren nicht möglich wenn mehrere Artikel dem Produkt zugewiesen werden

							if ($this->useArticles == 3) {
								$itemTable->fillVariantsFromArticles(
									$prodVariantRow
								);
								$itemTable->getVariant()->modifyRowFromVariant($prodVariantRow);
							}
							foreach ($articleRows as $articleRow) {

								$itemTable->mergeAttributeFields(
									$prodVariantRow,
									$articleRow,
									$bKeepNotEmpty,
									true,
									true,
									$calculationField,
									false
								);
							}
						} else {
							$variant = $itemTable->getVariant()->getVariantFromRow($row);
							$itemTable->getVariant()->modifyRowFromVariant(
								$prodVariantRow,
								$variant
							);
						}
						// use the fields of the article instead of the product
						//

						if (
							isset($extArray) &&
							isset($extArray['records']) &&
							is_array($extArray['records'])
						) {
							$newTitleArray = array();
							$externalRowArray = $extArray['records'];
							foreach ($externalRowArray as $tablename => $externalRow) {
								$newTitleArray[] = $externalRow['title'];
							}
							$prodVariantRow['title'] = implode(' | ', $newTitleArray);
						}
						$prodMarkerRow = $prodVariantRow;
						$itemTable->tableObj->substituteMarkerArray($prodMarkerRow);

						$articleViewObj->getModelMarkerArray(
							$prodMarkerRow,
							'',
							$markerArray,
							$catTitle,
							0,
							'basketImage',
							$articleViewTagArray,
							$tmp,
							$theCode,
							$basketExtra,
							$basketRecs,
							$count,
							'',
							'',
							'',
							$bHtml,
							'UTF-8',
							'',
							$multiOrderArray,
							$productRowArray,
                            false, // FHO wieder zurück korrigiert, sonst wird bei Artikel Tax=0 nicht die TAXpercentage genommen.
                            $notOverwritePriceIfSet
						);

						$articleViewObj->getItemMarkerSubpartArrays(
							$t['item'],
							$articleViewObj->getModelObj()->getFuncTablename(),
							$prodVariantRow,
							$markerArray,
							$subpartArray,
							$wrappedSubpartArray,
							$articleViewTagArray,
							$theCode,
							$basketExtra
						);
					}

					$itemTableView->getItemMarkerSubpartArrays(
						$t['item'],
						$itemTableView->getModelObj()->getFuncTablename(),
						$prodVariantRow,
						$markerArray,
						$subpartArray,
						$wrappedSubpartArray,
						$viewTagArray,
						array(),
						array(),
						$theCode,
						$basketExtra,
						$basketRecs,
						$count,
						$checkPriceZero
					);

					$cObj->setCurrentVal($catTitle);
					$markerArray['###CATEGORY_TITLE###'] =
						$cObj->cObjGetSingle(
							$conf['categoryHeader'],
							$conf['categoryHeader.'],
							'categoryHeader'
						);

					$markerArray['###PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat($actItem['totalTax'] + $actItem['deposittax'] * $quantity);

					$markerArray['###PRICE_TOTAL_NO_TAX###'] = $priceViewObj->priceFormat($actItem['totalNoTax'] + $actItem['depositnotax'] * $quantity);
					$markerArray['###PRICE_TOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($actItem['totalTax'] - $actItem['totalNoTax'] + ($actItem['deposittax'] - $actItem['depositnotax']) * $quantity);

					$markerArray['###PRICE_TOTAL_0_TAX###'] = $priceViewObj->priceFormat($actItem['total0Tax']);
					$markerArray['###PRICE_TOTAL_0_NO_TAX###'] = $priceViewObj->priceFormat($actItem['total0NoTax']);
					$markerArray['###PRICE_TOTAL_0_ONLY_TAX###'] = $priceViewObj->priceFormat($actItem['total0Tax'] - $actItem['total0NoTax']);

                    $pricecredits_total_totunits_no_tax = 0;
                    $pricecredits_total_totunits_tax = 0;
					if ($row['category'] == $conf['creditsCategory']) {
						// creditpoint system start
						$pricecredits_total_totunits_no_tax = $actItem['totalNoTax'] * ($row['unit_factor'] ?? 0);
						$pricecredits_total_totunits_tax = $actItem['totalTax'] * ( $row['unit_factor'] ?? 0);
					}
					$markerArray['###PRICE_TOTAL_TOTUNITS_NO_TAX###'] = $priceViewObj->priceFormat($pricecredits_total_totunits_no_tax);
					$markerArray['###PRICE_TOTAL_TOTUNITS_TAX###'] = $priceViewObj->priceFormat($pricecredits_total_totunits_tax);
					$sum_pricecredits_total_totunits_no_tax += $pricecredits_total_totunits_no_tax;
					$sum_price_total_totunits_no_tax += $pricecredits_total_totunits_no_tax;
					$sum_pricecreditpoints_total_totunits += $pricecredits_total_totunits_no_tax;

					// creditpoint system end
					$page = $tablesObj->get('pages');
					$pid = $page->getPID(
						$conf['PIDitemDisplay'],
						$conf['PIDitemDisplay.'] ?? '',
						$row,
						$GLOBALS['TSFE']->rootLine[1] ?? ''
					);
					$addQueryString = array();
					$addQueryString[$itemTable->type] = intval($row['uid']);

					if (
						is_array($extArray) && is_array($extArray[tx_ttproducts_control_basket::getFuncTablename()])
					) {
						$addQueryString['variants'] = htmlspecialchars($extArray[tx_ttproducts_control_basket::getFuncTablename()][0]['vars']);
					}
					$isImageProduct = $itemTable->hasAdditional($row, 'isImage');
					$damMarkerArray = array();
					$damCategoryMarkerArray = array();

					if (
						(
							$isImageProduct ||
							$funcTablename == 'tt_products'
						) &&
						is_array($extArray) &&
						isset($extArray['tx_dam'])
					) {
						reset($extArray['tx_dam']);
						$damext = current($extArray['tx_dam']);
						$damUid = $damext['uid'];
						$damRow = $tablesObj->get('tx_dam')->get($damUid);
						$damItem = array();
						$damItem['rec'] = $damRow;
						$damCategoryArray =
							$tablesObj->get('tx_dam_cat')->getCategoryArray ($damRow);
						if (!empty($damCategoryArray)) {
							reset ($damCategoryArray);
							$damCat = current($damCategoryArray);
						}

						$tablesObj->get('tx_dam_cat', true)->getMarkerArray(
							$damCategoryMarkerArray,
							'',
							$damCat,
							$damRow['pid'],
							0,
							'basketImage',
							$viewDamCatTagArray,
							array(),
							$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'],
							'SINGLE',
							1,
							'',
							''
						);

						$tablesObj->get('tx_dam', true)->getModelMarkerArray(
							$damRow,
							'',
							$damMarkerArray,
							$damCatRow['title'],
							0,
							'basketImage',
							$damViewTagArray,
							$tmp,
							$theCode,
							$basketExtra,
							$basketRecs,
							$count,
							'',
							'',
							'',
							$bHtml,
							'UTF-8',
							'',
							$multiOrderArray,
							$productRowArray,
                            false,
                            $notOverwritePriceIfSet
						);
					}
					$markerArray = array_merge($markerArray, $damMarkerArray, $damCategoryMarkerArray);

                    $tempUrl =
                        FrontendUtility::getTypoLink_URL(
                            $cObj,
                            $pid,
                            $this->urlObj->getLinkParams(
                                '',
                                $addQueryString,
                                true,
                                $bUseBackPid,
                                0,
                                ''
                            ),
                            '',
                            array('useCacheHash' => true)
                        );

					$wrappedSubpartArray['###LINK_ITEM###'] =
						array(
							'<a class="singlelink" href="' . htmlspecialchars($tempUrl) . '"' . $css_current . '>',
							'</a>'
						);

					if (is_object($itemTableView->getVariant())) {
						$itemTableView->getVariant()->removeEmptyMarkerSubpartArray(
							$markerArray,
							$subpartArray,
							$wrappedSubpartArray,
							$prodVariantRow,
							$conf,
							$itemTable->hasAdditional($row, 'isSingle'),
							!$itemTable->hasAdditional($row, 'noGiftService')
						);
					}

					$orderAddressViewObj->getModelObj()->setCondition($row, $funcTablename);
					$orderAddressViewObj->getWrappedSubpartArray(
						$viewTagArray,
						$bUseBackPid,
						$subpartArray,
						$wrappedSubpartArray
					
					);

				// workaround for TYPO3 bug #44270
					$tempContent = tx_div2007_core::substituteMarkerArrayCached(
						$t['item'],
						array(),
						$subpartArray,
						$wrappedSubpartArray
					);

					$tempContent = $parser->substituteMarkerArray(
						$tempContent,
						$markerArray
					);

					$itemsOut .= $tempContent;
				}

				if ($itemsOut) {
					$tempContent =
						$parser->substituteSubpart(
							$t['itemFrameWork'],
							'###ITEM_SINGLE###',
							$itemsOut
						);

					$out .= $tempContent;
					$itemsOut = '';	// Clear the item-code var
				}
			} // end of foreach ($itemArray

			if (isset($damCatMarker)) {
				$damCatObj->marker = $damCatMarker; // restore original value
			}
			$subpartArray = array();
			$wrappedSubpartArray = array();
			$shopCountryArray = array();
			$taxRateArray =
				$taxObj->getTaxRates(
					$shopCountryArray,
					$taxInfoArray,
					$basketObj->getUidArray(),
					$basketRecs
				);

			if (
				isset($taxRateArray) &&
				is_array($taxRateArray) &&
				isset($shopCountryArray) &&
				is_array($shopCountryArray) &&
				isset($shopCountryArray['country_code']) &&
				isset($taxRateArray[$shopCountryArray['country_code']])
			) {
				$taxArray = $taxRateArray[$shopCountryArray['country_code']];
			} else if (
				isset($taxRateArray) &&
				is_array($taxRateArray)
			) {
				$taxArray = current($taxRateArray);
			} else {
				$taxArray = array();
			}

			$basketMarkerArray =
				$this->getMarkerArray(
					$basketExtra,
					$calculatedArray,
					$taxArray
				);

				// Initializing the markerArray for the rest of the template
			$markerArray = $mainMarkerArray;
			$markerArray = array_merge($markerArray, $basketMarkerArray);
			$activityArray = tx_ttproducts_model_activity::getActivityArray();

			if (is_array($activityArray)) {
				$activity = '';
				if (!empty($activityArray['products_payment'])) {
					$activity = 'payment';
				} else if (!empty($activityArray['products_info'])) {
					$activity = 'info';
				}
				if ($activity) {
					$bUseXHTML = !empty($GLOBALS['TSFE']->config['config']['xhtmlDoctype']);
					$hiddenFields .= '<input type="hidden" name="' . TT_PRODUCTS_EXT . '[activity][' . $activity . ']" value="1" ' . ($bUseXHTML ? '/' : '') . '>';
				}
			}
			$markerArray['###HIDDENFIELDS###'] = $hiddenFields;
			$pid = ($conf['PIDbasket'] ? $conf['PIDbasket'] : $GLOBALS['TSFE']->id);

			$confCache = array('useCacheHash' => false);

			$excludeList = '';

			if (
				isset($viewParamConf) &&
				is_array($viewParamConf) &&
				$viewParamConf['ignore']
			){
				$excludeList = $viewParamConf['ignore'];
			}

			$url = FrontendUtility::getTypoLink_URL(
				$cObj,
				$pid,
				$this->urlObj->getLinkParams(
					$excludeList,
					[],
					true,
					$bUseBackPid,
					0,
					''
				),
				$target = '',
				$confCache
			);

			$htmlUrl = htmlspecialchars(
					$url,
					ENT_NOQUOTES,
					'UTF-8'
				);

			$wrappedSubpartArray['###LINK_BASKET###'] = array('<a href="' . $htmlUrl . '">', '</a>');

			PaymentShippingHandling::getMarkerArray(
				$theCode,
				$markerArray,
				$pid,
				$bUseBackPid,
				$calculatedArray,
				$basketExtra
			);

			// for receipt from DIBS script
			$markerArray['###TRANSACT_CODE###'] = GeneralUtility::_GP('transact');
			$markerArray['###CUR_SYM###'] = ' ' . ($bHtml ?  htmlentities($conf['currencySymbol'], ENT_QUOTES) : $conf['currencySymbol']);
			$discountValue = tx_ttproducts_basket_calculate::getRealDiscount($calculatedArray, true);

			$markerArray['###PRICE_TAX_DISCOUNT###'] = $markerArray['###PRICE_DISCOUNT_TAX###'] = $priceViewObj->priceFormat($discountValue);

			$discountValue = tx_ttproducts_basket_calculate::getRealDiscount($calculatedArray, false);

			$markerArray['###PRICE_NO_TAX_DISCOUNT###'] = $priceViewObj->priceFormat($discountValue);

			self::getDiscountSubpartArray(
				$subpartArray,
				$wrappedSubpartArray,
				$calculatedArray
			);

			$markerArray['###PRICE_VAT###'] =
				$priceViewObj->priceFormat(
					$calculatedArray['priceTax']['goodstotal']['ALL'] -
					$calculatedArray['priceNoTax']['goodstotal']['ALL'] +
					$calculatedArray['deposittax']['goodstotal']['ALL'] -
					$calculatedArray['depositnotax']['goodstotal']['ALL']
				);

			$orderViewObj = $tablesObj->get('sys_products_orders', true);
			$orderViewObj->getBasketRecsMarkerArray($markerArray, $multiOrderArray['0']);
			$trackingCode = '';
			if (isset($multiOrderArray['0']['tracking_code'])) {
				$trackingCode = $multiOrderArray['0']['tracking_code'];
			}
			$billdeliveryObj->getMarkerArray($markerArray, $trackingCode, 'bill');
			$billdeliveryObj->getMarkerArray($markerArray, $trackingCode, 'delivery');

				// URL
			$markerArray = $this->urlObj->addURLMarkers(
				0,
				$markerArray,
				array(),
				'',
				$bUseBackPid,
				0
			); // Applied it here also...

			$taxFromShipping = PaymentShippingHandling::getReplaceTaxPercentage($basketExtra);
			$taxInclExcl = (isset($taxFromShipping) && is_double($taxFromShipping) && $taxFromShipping == 0 ? 'tax_zero' : 'tax_included');
			$markerArray['###TAX_INCL_EXCL###'] = ($taxInclExcl ? $languageObj->getLabel($taxInclExcl) : '');

			$pricefactor = tx_ttproducts_creditpoints_div::getPriceFactor($conf);

	/* Added els6: do not execute the redeeming of the gift certificate if template = OVERVIEW */
			if ($subpartMarker != 'BASKET_OVERVIEW_TEMPLATE') {

	// Added Franz: GIFT CERTIFICATE
				$markerArray['###GIFT_CERTIFICATE_UNIQUE_NUMBER_NAME###']='recs[tt_products][giftcode]'; // deprecated
				$markerArray['###FORM_NAME###']='BasketForm';
				$markerArray['###FORM_NAME_GIFT_CERTIFICATE###']='BasketGiftForm';

	/* Added els5: markerarrays for gift certificates */
	/* Added Els6: routine for redeeming the gift certificate (other way then proposed by Franz */
				$markerArray['###INSERT_GIFTCODE###'] = 'recs[tt_products][giftcode]';
				$markerArray['###VALUE_GIFTCODE###'] = htmlspecialchars($basketObj->recs['tt_products']['giftcode']);
				$cpArray = tx_ttproducts_control_session::readSession('cp');
				$creditpointsGifts = '';
				if (
					isset($cpArray['gift']) &&
					is_array($cpArray['gift']) &&
					isset($cpArray['gift']['amount'])
				) {
					$creditpointsGifts = $cpArray['gift']['amount'];
				}
				$markerArray['###CREDITPOINTS_GIFTS###'] = htmlspecialchars($creditpointsGifts);

				if ($basketObj->recs['tt_products']['giftcode'] == '') {
					$subpartArray['###SUB_GIFTCODE_DISCOUNT###'] = '';
					$subpartArray['###SUB_GIFTCODE_DISCOUNTWRONG###'] = '';
					if ($creditpointsGifts == '') {
						$subpartArray['###SUB_GIFTCODE_DISCOUNT_true###'] = '';
					}
				} else {
					$uniqueId = GeneralUtility::trimExplode ('-', $basketObj->recs['tt_products']['giftcode'], true);
					$query='uid=\'' . intval($uniqueId[0]) . '\' AND crdate=\'' . intval($uniqueId[1]) . '\'';
					$giftRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products_gifts', $query);
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($giftRes);
					$GLOBALS['TYPO3_DB']->sql_free_result($giftRes);
					$creditpointsDiscount = intval($creditpointsGifts) * $pricefactor;
					$markerArray['###GIFT_DISCOUNT###'] = $creditpointsDiscount;
					$markerArray['###VALUE_GIFTCODE_USED###'] = htmlspecialchars($basketObj->recs['tt_products']['giftcode']);

					if ($row && $creditpointsGifts && $pricefactor > 0) {
						$subpartArray['###SUB_GIFTCODE_DISCOUNTWRONG###']= '';
						if ($creditpointsGifts == '') {
							$subpartArray['###SUB_GIFTCODE_DISCOUNT_true###'] = '';
						}
					} else {
						$markerArray['###VALUE_GIFTCODE_USED###'] = '**********';
						if (GeneralUtility::_GP('creditpoints_gifts') == '') {
							$subpartArray['###SUB_GIFTCODE_DISCOUNT_true###'] = '';
						}
					}
				}
			}
			$amountCreditpoints = $GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'] + intval($creditpointsGifts);
			$markerArray['###AMOUNT_CREDITPOINTS###'] = $amountCreditpoints;
			$autoCreditpointsTotal = $GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'];

			$creditpoints = $autoCreditpointsTotal + $sum_pricecreditpoints_total_totunits * tx_ttproducts_creditpoints_div::getCreditPoints($sum_pricecreditpoints_total_totunits, $conf['creditpoints.']);
 			$markerArray['###AUTOCREDITPOINTS_TOTAL###'] = number_format($autoCreditpointsTotal, 0);
 			$markerArray['###AUTOCREDITPOINTS_PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat($autoCreditpointsTotal * $pricefactor);
			$remainingCreditpoints = 0;
			$creditpointsObj->getBasketMissingCreditpoints(0, $tmp, $remainingCreditpoints);
 			$markerArray['###AUTOCREDITPOINTS_REMAINING###'] = number_format($remainingCreditpoints, 0);
 			if (\JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn()) {
                $markerArray['###CREDITPOINTS_AVAILABLE###'] = number_format($GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'], 0);
			} else {
                $markerArray['###CREDITPOINTS_AVAILABLE###'] = 0;
            }
 			$markerArray['###USERCREDITPOINTS_PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat(($autoCreditpointsTotal < $amountCreditpoints ? $autoCreditpointsTotal : $amountCreditpoints) * $pricefactor);

			// maximum1 amount of creditpoint to change is amount on account minus amount already spended in the credit-shop
			$max1_creditpoints = $GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'] + intval($creditpointsGifts);
			// maximum2 amount of creditpoint to change is amount bought multiplied with creditpointfactor
			$max2_creditpoints = 0;

			if ($pricefactor > 0) {
				$max2_creditpoints = intval (($calculatedArray['priceTax']['total']['ALL'] - $calculatedArray['priceTax']['vouchertotal']['ALL']) / $pricefactor );
			}
			// real maximum amount of creditpoint to change is minimum of both maximums
			$markerArray['###AMOUNT_CREDITPOINTS_MAX###'] = number_format(min($max1_creditpoints,$max2_creditpoints), 0);

			// if quantity is 0 than
			if ($amountCreditpoints == '0') {
				$subpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
				$wrappedSubpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
                $subpartArray['###SUB_CREDITPOINTS_AMOUNT_EMPTY###'] = '';
				$subpartArray['###SUB_CREDITPOINTS_AMOUNT###'] = '';
			} else {
				$wrappedSubpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
				$subpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
				$wrappedSubpartArray['###SUB_CREDITPOINTS_AMOUNT_EMPTY###'] = '';
				$wrappedSubpartArray['###SUB_CREDITPOINTS_AMOUNT###'] = '';
			}
			$markerArray['###CHANGE_AMOUNT_CREDITPOINTS###'] = 'recs[tt_products][creditpoints]';
			if ($basketObj->recs['tt_products']['creditpoints'] == '') {
				$markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = 0;
				$subpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
	/* Added Els8: put credit_discount 0 for plain text email */
				$markerArray['###CREDIT_DISCOUNT###'] = '0.00';
			} else {
				// quantity chosen can not be larger than the maximum amount, above calculated
				if ($basketObj->recs['tt_products']['creditpoints'] > min ($max1_creditpoints,$max2_creditpoints)) {
					$basketObj->recs['tt_products']['creditpoints'] = min ($max1_creditpoints, $max2_creditpoints);
				}
				$markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = number_format($basketObj->recs['tt_products']['creditpoints'], 0);
				$subpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
				$markerArray['###CREDIT_DISCOUNT###'] = $priceViewObj->priceFormat($calculatedArray['priceTax']['creditpoints']);
			}

	/* Added els5: CREDITPOINTS_SPENDED: creditpoint needed, check if user has this amount of creditpoints on his account (winkelwagen.tmpl), only if user has logged in */
			$markerArray['###CREDITPOINTS_SPENDED###'] = $sum_pricecredits_total_totunits_no_tax;
			if ($sum_pricecredits_total_totunits_no_tax <= $amountCreditpoints) {
				$subpartArray['###SUB_CREDITPOINTS_SPENDED_EMPTY###'] = '';
				$markerArray['###CREDITPOINTS_SPENDED###'] = $sum_pricecredits_total_totunits_no_tax;
				// new saldo: creditpoints
				$markerArray['###AMOUNT_CREDITPOINTS###'] = $amountCreditpoints - $markerArray['###CREDITPOINTS_SPENDED###'];
			} else {
				if (!$markerArray['###FE_USER_UID###']) {
					$subpartArray['###SUB_CREDITPOINTS_SPENDED_EMPTY###'] = '';
				} else {
					$markerArray['###CREDITPOINTS_SPENDED_ERROR###'] = 'Wijzig de artikelen in de kurkenshop: onvoldoende kurken op uw saldo (' . $amountCreditpoints . ') . '; // TODO
					$markerArray['###CREDITPOINTS_SPENDED###'] = '&nbsp;';
				}
			}

			foreach ($quantityArray as $k => $subQuantityArray) {
				switch ($k) {
					case 'minimum':
						$markerkey = 'MINQUANTITY';
						break;
					case 'maximum':
						$markerkey = 'MAXQUANTITY';
						break;
				}

				if (is_array($subQuantityArray) && count($subQuantityArray)) {
					$subpartArray['###MESSAGE_' . $markerkey . '###'] = '';
					$tmpSubpart =
						tx_div2007_core::getSubpart(
							$t['basketFrameWork'],
							'###MESSAGE_' . $markerkey . '_ERROR###'
						);
					$quantityCode = array();
					$quantityCode[0] = 'error_' . strtolower($markerkey);
					$quantityCode[1] = '';

					foreach ($subQuantityArray as $subQuantityRow) {
						$quantityCode[1] .= $subQuantityRow['rec']['title'] . ': ' . $subQuantityRow['quantity'] . ' ' . ($k == 'minimum' ? '&lt;' : '&gt;') . ' ' . $subQuantityRow['limitQuantity'];
					}

					$errorOut = tx_div2007_error::getMessage($languageObj, $quantityCode);
					$markerArray['###ERROR_' . $markerkey . '###'] = $errorOut;
					$subpartArray['###MESSAGE_' . $markerkey . '_ERROR###'] = $parser->substituteMarkerArray($tmpSubpart, $markerArray);
				} else {
					$subpartArray['###MESSAGE_' . $markerkey . '_ERROR###'] = '';
					$tmpSubpart =
						tx_div2007_core::getSubpart(
							$t['basketFrameWork'],
							'###MESSAGE_' . $markerkey . '###'
						);
					$subpartArray['###MESSAGE_' . $markerkey . '###'] = $parser->substituteMarkerArray($tmpSubpart, $markerArray);
				}
			}

			if (count($quantityArray['minimum']) || count($quantityArray['maximum'])/* || !$minPriceSuccess*/) {
				$subpartArray['###MESSAGE_NO_ERROR###'] = '';
			} else {
				$subpartArray['###MESSAGE_ERROR###'] = '';
			}
			$voucherView = $tablesObj->get('voucher', true);

			if (is_object($voucherView)) {
				$voucherView->getSubpartMarkerArray(
					$subpartArray,
					$wrappedSubpartArray
				);
				$voucherView->getMarkerArray($markerArray);
			}

			$markerArray['###CREDITPOINTS_SAVED###'] = number_format($creditpoints, '0');
			$pidagb = intval($conf['PIDagb']);

			$addQueryString = array();

			// $addQueryString['id'] = $pidagb;
			if ($GLOBALS['TSFE']->type) {
				$addQueryString['type'] = $GLOBALS['TSFE']->type;
			}

			$pointerExcludeArray = array_keys(tx_ttproducts_model_control::getPointerParamsCodeArray());
			$singleExcludeList = $this->urlObj->getSingleExcludeList(implode(',', $pointerExcludeArray));
			$tempUrl =
                FrontendUtility::getTypoLink_URL(
					$cObj,
					$pidagb,
					$this->urlObj->getLinkParams(
						$singleExcludeList,
						$addQueryString,
						true,
						$bUseBackPid,
						0,
						''
					)
				);

			$wrappedSubpartArray['###LINK_AGB###'] = array(
				'<a href="' . htmlspecialchars($tempUrl) . '" target="' . $conf['AGBtarget'] . '">',
				'</a>'
			);

            $pidPrivacy = intval($conf['PIDprivacy']);
            $tempUrl =
                FrontendUtility::getTypoLink_URL(
                    $cObj,
                    $pidPrivacy,
                    $this->urlObj->getLinkParams(
                        $singleExcludeList,
                        $addQueryString,
                        true,
                        $bUseBackPid,
                        0,
                        ''
                    )
                );
            $wrappedSubpartArray['###LINK_PRIVACY###'] = array(
                '<a href="' . htmlspecialchars($tempUrl) . '" target="' . $conf['AGBtarget'] . '">',
                '</a>'
            );

			$pidRevocation = intval($conf['PIDrevocation']);

			$tempUrl =
				FrontendUtility::getTypoLink_URL(
					$cObj,
					$pidRevocation,
					$this->urlObj->getLinkParams(
						$singleExcludeList,
						$addQueryString,
						true,
						$bUseBackPid,
						0,
						''
					)
				);

			$wrappedSubpartArray['###LINK_REVOCATION###'] = array(
				'<a href="' . htmlspecialchars($tempUrl) . '" target="' . $conf['AGBtarget'] . '">',
				'</a>'
			);

				// Final substitution:
			if (!\JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn()) {		// Remove section for FE_USERs only, if there are no fe_user
				$subpartArray['###FE_USER_SECTION###'] = '';
			}

			if (is_object($infoViewObj)) {
				$infoViewObj->getRowMarkerArray(
					$basketExtra,
					$markerArray,
					$bHtml,
					$bSelectSalutation
				);
			}

			$fieldsTempArray = $markerObj->getMarkerFields(
				$t['basketFrameWork'],
				$itemTable->getTableObj()->tableFieldArray,
				$itemTable->getTableObj()->requiredFieldArray,
				$markerFieldArray,
				$itemTable->marker,
				$viewTagArray,
				$parentArray
			);

			$priceCalcMarkerArray = array(
				'PRICE_TOTAL_TAX' =>
					$calculatedArray['priceTax']['total']['ALL'] +
					$calculatedArray['deposittax']['goodstotal']['ALL'],
				'PRICE_TOTAL_NO_TAX' =>
					$calculatedArray['priceNoTax']['total']['ALL'] +
					$calculatedArray['depositnotax']['goodstotal']['ALL'],
				'PRICE_TOTAL_0_TAX' =>
					$calculatedArray['price0Tax']['total']['ALL'] +
					$calculatedArray['depositnotax']['goodstotal']['ALL'],
				'PRICE_TOTAL_ONLY_TAX' =>
					$calculatedArray['priceTax']['total']['ALL'] -
					$calculatedArray['priceNoTax']['total']['ALL'] +
					$calculatedArray['deposittax']['goodstotal']['ALL'] -
					$calculatedArray['depositnotax']['goodstotal']['ALL'],
				'PRICE_GOODSTOTAL_0_TAX' =>
					$calculatedArray['price0Tax']['goodstotal']['ALL'],
				'PRICE_GOODSTOTAL_0_NO_TAX' =>
					$calculatedArray['price0NoTax']['goodstotal']['ALL'],
				'PRICE_VOUCHERTOTAL_TAX' =>
					$calculatedArray['priceTax']['vouchertotal']['ALL'] +
					$calculatedArray['deposittax']['goodstotal']['ALL'],
				'PRICE_VOUCHERTOTAL_NO_TAX' =>
					$calculatedArray['priceNoTax']['vouchertotal']['ALL'] +
					$calculatedArray['depositnotax']['goodstotal']['ALL'],
				'PRICE_VOUCHERGOODSTOTAL_TAX' =>
					$calculatedArray['priceTax']['vouchergoodstotal']['ALL'] +
					$calculatedArray['deposittax']['goodstotal']['ALL'],
				'PRICE_VOUCHERGOODSTOTAL_NO_TAX' =>
					$calculatedArray['priceNoTax']['vouchergoodstotal']['ALL'] +
					$calculatedArray['depositnotax']['goodstotal']['ALL'],
				'PRICE_TOTAL_TAX_WITHOUT_PAYMENT' =>
					$calculatedArray['priceTax']['total']['ALL'] +
					$calculatedArray['deposittax']['goodstotal']['ALL'] -
					$calculatedArray['payment']['priceTax'],
				'PRICE_TOTAL_NO_TAX_WITHOUT_PAYMENT' =>
					$calculatedArray['priceNoTax']['total']['ALL'] +
						$calculatedArray['depositnotax']['goodstotal']['ALL'] -
						$calculatedArray['payment']['priceNoTax'],
				'PRICE_TOTAL_TAX_CENT' =>
					intval(round(100 * $calculatedArray['priceTax']['total']['ALL'])),
				'PRICE_VOUCHERTOTAL_TAX_CENT' =>
					intval(
						round(
							100 * (
								$calculatedArray['priceTax']['vouchertotal']['ALL'] +
								$calculatedArray['deposittax']['goodstotal']['ALL']
							)
						)
					)
			);

			foreach ($priceCalcMarkerArray as $markerKey => $value) {
				$markerArray['###' . $markerKey . '###'] = (is_int($value) ? $value : $priceViewObj->priceFormat($value));
			}

			$variantFieldArray = array();
			$variantMarkerArray = array();
			$taxContent = '';

			if (tx_ttproducts_static_tax::isInstalled()) {
				$staticTaxViewObj = $tablesObj->get('static_taxes', true);
				if (is_object($staticTaxViewObj)) {
					$staticTaxObj = $staticTaxViewObj->getModelObj();

					$bUseTaxArray = false;
					$viewTaxTagArray = array();
					$parentArray = array();
					$markerFieldArray = array();

					$fieldsArray = $markerObj->getMarkerFields(
						$t['basketFrameWork'],
						$staticTaxObj->getTableObj()->tableFieldArray,
						$staticTaxObj->getTableObj()->requiredFieldArray,
						$markerFieldArray,
						$staticTaxObj->marker,
						$viewTaxTagArray,
						$parentArray
					);

					if (
						isset($taxInfoArray) &&
						is_array($taxInfoArray) &&
						!empty($taxInfoArray)
					) {
						$bUseTaxArray = true;
						$bEnableTaxZero = false;
						foreach ($taxInfoArray as $countryCode => $taxArray) {
							foreach ($taxArray as $k => $taxRow) {
								$theTax = $taxRow['tx_rate'] * 0.01;
								$markerKey = 'STATICTAX_' . ($taxId) . '_' . ($k + 1);
								$staticTaxMarkerArray = array();

								$staticTaxViewObj->getRowMarkerArray(
									'static_tax_rates',
									$taxRow,
									$markerKey,
									$staticTaxMarkerArray,
									$variantFieldArray,
									$variantMarkerArray,
									$viewTagArray,
									$theCode,
									$basketExtra,
									$basketRecs,
									$bHtml,
									$charset,
									0,
									'',
									$id,
									$prefix, // if false, then no table marker will be added
									$suffix,
									'',
									$bEnableTaxZero
								);

								$markerArray = array_merge($markerArray, $staticTaxMarkerArray);
								$countryMarkerArray = array();
								$countrySubpartArray = array();
								$countryWrappedSubpartArray = array();
								foreach ($staticTaxMarkerArray as $key => $value) {
									$countryMarkerKey =  str_replace($markerKey, 'STATICTAX', $key);
									$countryMarkerArray[$countryMarkerKey] = $value;
								}

								$priceArray = array();

								$value = 0;
								if (
									isset($calculatedArray['priceNoTax']['goodssametaxtotal'][$countryCode]) &&
									isset($calculatedArray['priceNoTax']['goodssametaxtotal'][$countryCode][$taxRow['tx_rate']])
								) {
									$value = $calculatedArray['priceNoTax']['goodssametaxtotal'][$countryCode][$taxRow['tx_rate']];
								}
								$priceArray['priceNoTax'] = $value;
								$priceArray['priceTax'] = $priceArray['priceNoTax'] * (1 + $theTax);
								$priceArray['onlyTax'] = $priceArray['priceTax'] - $priceArray['priceNoTax'];
								$priceCalcMarkerArray2 = array(
									'PRICE_TOTAL_ONLY_TAX' => $priceArray['onlyTax']
								);

								foreach ($priceCalcMarkerArray2 as $markerKey => $value) {
									$markerArray['###STATICTAX_' . ($taxId) . '_' . ($k + 1) . '_' . $markerKey . '###'] = $countryMarkerArray['###' . $markerKey . '###'] = $priceViewObj->priceFormat($value);
								}

								$countryMarkerArray['###COUNTRY_CODE###'] = $countryCode;
								$tempContent = tx_div2007_core::substituteMarkerArrayCached(
									$t['taxes'],
									$countryMarkerArray,
									$countrySubpartArray,
									$countryWrappedSubpartArray
								);
								$taxContent .= $tempContent;
							}
						}
					} // $t['taxes']


					foreach ($viewTaxTagArray as $theTag => $v1) {
						if (!isset($markerArray['###' . $theTag . '###'])) {
							foreach ($priceCalcMarkerArray as $markerKey => $value) {
								if (strpos($theTag, $markerKey) !== false) {
									$markerArray['###' . $theTag . '###'] = '';
								}
							}
							if (strpos($theTag, 'STATICTAX_') === 0) {
								$markerArray['###' . $theTag . '###'] = '';
							}
						}
					}
				}
			}

			$subpartArray['###COUNTRY_TAXRATES###'] = $taxContent;

			$this->getBoundaryMarkerArray(
				$t['basketFrameWork'],
				$cObj,
				$cnf,
				$calculatedArray,
				$checkPriceArray,
				$markerArray,
				$subpartArray,
				$wrappedSubpartArray
			);

				// Call all getBasketView hooks at the end of this method
            if (
                isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getBasketView']) &&
                is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getBasketView'])
            ) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getBasketView'] as $classRef) {
					$hookObj= GeneralUtility::makeInstance($classRef);
					if (method_exists($hookObj, 'getMarkerArrays')) {
						$hookObj->getMarkerArrays(
							$this,
							$templateCode,
							$theCode,
							$markerArray,
							$subpartArray,
							$wrappedSubpartArray,
							$mainMarkerArray,
							$count
						);
					}
				}
			}

			$pidListObj = tx_ttproducts_control_basket::getPidListObj();
			$relatedListView = GeneralUtility::makeInstance('tx_ttproducts_relatedlist_view');
			$relatedListView->init(
				$pidListObj->getPidlist(),
				$pidListObj->getRecursive()
			);

			$relatedMarkerArray = $relatedListView->getListMarkerArray(
				$theCode,
				$templateCode,
				$viewTagArray,
				$funcTablename,
				$basketObj->getUidArray(),
				array(),
				array(),
				false,
                $multiOrderArray,
				$this->useArticles,
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'],
				$GLOBALS['TSFE']->id,
				$errorCode
			);

			if ($relatedMarkerArray && is_array($relatedMarkerArray)) {
				$markerArray = array_merge($markerArray, $relatedMarkerArray);
			}

			$frameWork =
				$parser->substituteSubpart(
					$t['basketFrameWork'],
					'###ITEM_CATEGORY_AND_ITEMS###',
					$out
				);

			PaymentShippingHandling::getSubpartArrays(
				$basketExtra,
				$markerArray,
				$subpartArray,
				$wrappedSubpartArray,
				$frameWork
			);
			$orderAddressViewObj->getWrappedSubpartArray(
				$viewTagArray,
				$bUseBackPid,
				$subpartArray,
				$wrappedSubpartArray
			);

				// This cObject may be used to call a function which manipulates the shopping basket based on settings in an external order system. The output is included in the top of the order (HTML) on the basket-page.
			$externalCObject =  \JambageCom\Div2007\Utility\ObsoleteUtility::getExternalCObject($this, 'externalProcessing');

			$markerArray['###EXTERNAL_COBJECT###'] = $externalCObject . '';  // adding extra preprocessing CObject

 // workaround for TYPO3 bug #44270
				// substitute the main subpart with the rendered content.
			$frameWork =
				tx_div2007_core::substituteMarkerArrayCached(
					$frameWork,
					array(),
					$subpartArray,
					$wrappedSubpartArray
				);

			$out =
				$parser->substituteMarkerArray(
					$frameWork,
					$markerArray
				); // workaround for TYPO3 bug

		} // if ($t['basketFrameWork'])

		return $out;
	} // getView
}

