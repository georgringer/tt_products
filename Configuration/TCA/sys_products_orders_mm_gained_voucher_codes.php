<?php
defined('TYPO3_MODE') || die('Access denied.');

// ******************************************************************
// order to voucher codes table, sys_products_orders_mm_gained_voucher_codes
// ******************************************************************
$result = array (
    'ctrl' => array (
        'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_orders_mm_gained_voucher_codes',
        'label' => 'uid_local',
        'tstamp' => 'tstamp',
        'delete' => 'deleted',
        'enablecolumns' => array (
            'disabled' => 'hidden'
        ),
        'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
        'crdate' => 'crdate',
        'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_relations.gif',
        'hideTable' => true,
    ),
    'types' => array(
        '0' => array(
            'showitem' => ''
        )
    ),
    'columns' => array()
);


if (
    defined('TYPO3_version') &&
    version_compare(TYPO3_version, '10.0.0', '<')
) {
    $result['interface'] = [];
    $result['interface']['showRecordFieldList'] =   
        'uid_local,uid_foreign';
}

return $result;

