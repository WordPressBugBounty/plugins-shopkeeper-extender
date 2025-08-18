<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

return [
    'supported_themes' => [
        "shopkeeper" => [
            "theme_infos_url"       => "https://getbowtied.com",
            "theme_marketplace_id"  => "9553045",
            "theme_name"            => "Shopkeeper",
            "theme_docs_path"       => "/docs/shopkeeper/",
            "theme_changelog_path"  => "/documentation/shopkeeper/changelog/",
            "theme_sales_page_url"  => "https://getbowtied.net/shopkeeper-themeforest",
            "theme_backend_download_url" => "https://1.envato.market/backend-download-shopkeeper",
            "theme_customer_support_url" => "https://1.envato.market/customer-support-shopkeeper",
            "theme_update_url"      => "https://getbowtied.github.io/updates/themes/shopkeeper.json",
            "theme_default_price_regular_license"   => 59,
            "theme_default_price_extended_license" => 2950
        ],
        "theretailer" => [
            "theme_infos_url"       => "https://getbowtied.com",
            "theme_marketplace_id"  => "4287447",
            "theme_name"            => "The Retailer",
            "theme_docs_path"       => "/docs/the-retailer/",
            "theme_changelog_path"  => "/documentation/the-retailer/changelog-the-retailer/",
            "theme_sales_page_url"  => "https://getbowtied.net/the-retailer-themeforest",
            "theme_backend_download_url" => "https://1.envato.market/backend-download-the-retailer",
            "theme_customer_support_url" => "https://1.envato.market/customer-support-the-retailer",
            "theme_update_url"      => "https://getbowtied.github.io/updates/themes/theretailer.json",
            "theme_default_price_regular_license"   => 59,
            "theme_default_price_extended_license" => 2950
        ],
        "mrtailor" => [
            "theme_infos_url"       => "https://getbowtied.com",
            "theme_marketplace_id"  => "7292110",
            "theme_name"            => "Mr Tailor",
            "theme_docs_path"       => "/docs/mr-tailor/",
            "theme_changelog_path"  => "/documentation/mr-tailor/changelog-mr-tailor/",
            "theme_sales_page_url"  => "https://getbowtied.net/mr-tailor-themeforest",
            "theme_backend_download_url" => "https://1.envato.market/backend-download-mr-tailor",
            "theme_customer_support_url" => "https://1.envato.market/customer-support-mr-tailor",
            "theme_update_url"      => "https://getbowtied.github.io/updates/themes/mrtailor.json",
            "theme_default_price_regular_license"   => 59,
            "theme_default_price_extended_license" => 2950
        ],
        "merchandiser" => [
            "theme_infos_url"       => "https://getbowtied.com",
            "theme_marketplace_id"  => "15791151",
            "theme_name"            => "Merchandiser",
            "theme_docs_path"       => "/docs/merchandiser/",
            "theme_changelog_path"  => "/documentation/merchandiser/changelog-merchandiser/",
            "theme_sales_page_url"  => "https://getbowtied.net/merchandiser-themeforest",
            "theme_backend_download_url" => "https://1.envato.market/backend-download-merchandiser",
            "theme_customer_support_url" => "https://1.envato.market/customer-support-merchandiser",
            "theme_update_url"      => "https://getbowtied.github.io/updates/themes/merchandiser.json",
            "theme_default_price_regular_license"   => 59,
            "theme_default_price_extended_license" => 2950
        ],
        "the-hanger" => [
            "theme_infos_url"       => "https://getbowtied.com",
            "theme_marketplace_id"  => "21753302",
            "theme_name"            => "The Hanger",
            "theme_docs_path"       => "/docs/the-hanger/",
            "theme_changelog_path"  => "/documentation/the-hanger/changelog-the-hanger/",
            "theme_sales_page_url"  => "https://getbowtied.net/the-hanger-themeforest",
            "theme_backend_download_url" => "https://1.envato.market/backend-download-the-hanger",
            "theme_customer_support_url" => "https://1.envato.market/customer-support-the-hanger",
            "theme_update_url"      => "https://getbowtied.github.io/updates/themes/the-hanger.json",
            "theme_default_price_regular_license"   => 59,
            "theme_default_price_extended_license" => 2950
        ],
        "block-shop" => [
            "theme_infos_url"       => "https://woocommerce.com",
            "theme_marketplace_id"  => "",
            "theme_name"            => "Block Shop",
            "theme_docs_path"       => "/document/block-shop-theme/",
            "theme_changelog_path"  => "/products/block-shop/",
            "theme_sales_page_url"  => "https://woocommerce.com/products/block-shop/",
            "theme_backend_download_url" => "https://woocommerce.com/products/block-shop/",
            "theme_customer_support_url" => "https://woocommerce.com/products/block-shop/",
            "theme_update_url"      => "https://getbowtied.github.io/updates/themes/block-shop.json",
            "theme_default_price_regular_license"   => 59,
            "theme_default_price_extended_license" => 2950
        ]
    ],
    'support_prices' => [
        'support_price_formula' => function($theme_price) {
            return ceil((($theme_price - 12) * (1 - 0.125)) * 100) / 100;
        }
    ],
];
