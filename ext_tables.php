<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {
 
    $tables = [
        'tt_products',
        'tt_products_language',
        'tt_products_articles',
        'tt_products_articles_language',
        'tt_products_cat',
        'tt_products_cat_language',
        'tt_products_emails',
        'tt_products_downloads',
        'tt_products_downloads_language',
        'tt_products_graduated_price',
        'tt_products_mm_graduated_price',
        'tt_products_texts',
        'tt_products_texts_language',
        'sys_products_accounts',
        'sys_products_cards',
        'sys_products_orders'
    ];

    foreach ($tables as $table) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages($table);
    }

    $tables = [
        'tt_products',
        'tt_products_articles',
        'tt_products_cat',
        'tt_products_emails',
        'tt_products_downloads',
        'tt_products_texts',
        'sys_products_accounts',
        'sys_products_cards',
        'sys_products_orders'
    ];

    foreach ($tables as $table) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr($table, 'EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'Csh/locallang_csh_' . $table . '.xlf');
    }

    if (TYPO3_MODE == 'BE') {

        $GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']['JambageCom\\TtProducts\\Controller\\Plugin\\WizardIcon'] = PATH_BE_TTPRODUCTS . 'Classes/Controller/Plugin/WizardIcon.php';

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'web_func',
            'web_func',
            'tx_ttproducts_modfunc1',
            PATH_BE_TTPRODUCTS . 'modfunc1/class.tx_ttproducts_modfunc1.php',
            'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'Modfunc/locallang.xlf:moduleFunction.tx_ttproducts_modfunc1',
            'wiz'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'web_func',
            'tx_ttproducts_modfunc2',
            PATH_BE_TTPRODUCTS . 'modfunc2/class.tx_ttproducts_modfunc2.php',
            'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'Modfunc/locallang.xlf:moduleFunction.tx_ttproducts_modfunc2',
            'wiz'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'web_func',
            'tx_ttproducts_modfunc3',
            PATH_BE_TTPRODUCTS . 'modfunc3/class.tx_ttproducts_modfunc3.php',
            'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'Modfunc/locallang.xlf:moduleFunction.tx_ttproducts_modfunc3',
            'wiz'
        );
    }
});

