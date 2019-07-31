<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2017 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License or
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
 * functions for the voucher system
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;



class tx_ttproducts_voucher_view extends tx_ttproducts_table_base_view {
	var $amount;
	var $code;
	var $bValid;
	var $marker = 'VOUCHER';
	var $usedCodeArray = array();


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for the voucher
	 *
	 * @return	void
	 * @access private
	 */
	public function getsubpartMarkerArray (
		&$subpartArray,
		&$wrappedSubpartArray,
		$charset=''
	)	{
        $local_cObj = \JambageCom\Div2007\Utility\FrontendUtility::getContentObjectRenderer();
		$modelObj = $this->getModelObj();

		$subpartArray['###SUB_VOUCHERCODE###'] = '';
        $wrappedSubpartArray['###SUB_VOUCHERCODE_START###'] = array();

		if (
            $modelObj->getValid() &&
            $modelObj->getCode() != ''
        ) {
			$subpartArray['###SUB_VOUCHERCODE_DISCOUNTWRONG###'] = '';
			$wrappedSubpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = array();
		} else {
			$code = $modelObj->getCode();

			if (isset($code)) {
				$tmp = $languageObj->getLabel('voucher_invalid');
				$tmpArray = explode('|',$tmp);
				$subpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = $tmpArray[0] . htmlspecialchars($modelObj->getCode()) . $tmpArray[1];
				$wrappedSubpartArray['###SUB_VOUCHERCODE_DISCOUNTWRONG###'] = array();
			} else {
				$subpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = '';
				$subpartArray['###SUB_VOUCHERCODE_DISCOUNTWRONG###'] = '';
			}
		}
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for the voucher
	 *
	 * @return	void
	 * @access private
	 */
	public function getMarkerArray (
		&$markerArray
	)	{
		$priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
		$modelObj = $this->getModelObj();
		$markerArray['###INSERT_VOUCHERCODE###'] = 'recs[tt_products][vouchercode]';
		$voucherCode = $modelObj->getCode();
		if (!$voucherCode)	{
			$voucherCode = $modelObj->getLastCodeUsed();
		}

		$amount = $modelObj->getRebateAmount();
		$markerArray['###VALUE_VOUCHERCODE###'] = htmlspecialchars($voucherCode);
		$markerArray['###VOUCHER_DISCOUNT###'] = $priceViewObj->priceFormat(abs($amount));
	} // getMarkerArray
}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_voucher_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_voucher_view.php']);
}



