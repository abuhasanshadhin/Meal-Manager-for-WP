<?php

/**
 * Plugin Name: Meal Manager
 * Description: Meal management system
 * Plugin URI: http://mm.abuhasanshadhin.xyz
 * Author: Abu Hasan Shadhin
 * Author URI: http://facebook.com/abuhasanshadhin
 * Version: 1.0.0
 */

define('PLUGINS_URL', plugins_url());
define('PLUGIN_DIR_URL', plugin_dir_url(__FILE__));

register_activation_hook(__FILE__, 'mm_activate');

function mm_activate()
{
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $rooms_table = '
        CREATE TABLE IF NOT EXISTS `mm_rooms` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `room_number` varchar(255) NOT NULL,
            `deleted_at` datetime DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ';

    $members_table = '
        CREATE TABLE IF NOT EXISTS `mm_members` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `room_id` int(11) NOT NULL,
            `name` varchar(255) NOT NULL,
            `phone` varchar(15) NOT NULL,
            `address` text DEFAULT NULL,
            `deleted_at` DATETIME DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
    ';

    $grocery_shopping_master_table = '
        CREATE TABLE IF NOT EXISTS `mm_grocery_shopping_master` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `member_id` int(11) NOT NULL,
            `date` date NOT NULL,
            `total_amount` decimal(10,2) NOT NULL,
            `deleted_at` DATETIME DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
    ';

    $grocery_shopping_details_table = '
        CREATE TABLE IF NOT EXISTS `mm_grocery_shopping_details` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `master_id` int(11) NOT NULL,
            `item_name` varchar(255) NOT NULL,
            `quantity` varchar(255) DEFAULT NULL,
            `amount` decimal(10,2) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
    ';

    $meals_table = '
        CREATE TABLE IF NOT EXISTS `mm_meals` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `member_id` int(11) NOT NULL,
            `quantity` decimal(8,2) NOT NULL,
            `date` date NOT NULL,
            `deleted_at` DATETIME DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
    ';

    $payment_collections_table = '
        CREATE TABLE IF NOT EXISTS `mm_payment_collections` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `member_id` int(11) NOT NULL,
            `date` date NOT NULL,
            `collection_month_number` int(11) NOT NULL,
            `payable_amount` decimal(8,2) NOT NULL DEFAULT 0.00,
            `paid_amount` decimal(8,2) NOT NULL DEFAULT 0.00,
            `due_amount` decimal(8,2) NOT NULL DEFAULT 0.00,
            `deleted_at` DATETIME DEFAULT NULL,
            `comments` text DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
    ';

    $utility_bills_table = '
        CREATE TABLE IF NOT EXISTS `mm_utility_bills` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `amount` decimal(8,2) NOT NULL,
            `date` date NOT NULL,
            `comment` text DEFAULT NULL,
            `deleted_at` DATETIME DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
    ';

    dbDelta($rooms_table);
    dbDelta($grocery_shopping_master_table);
    dbDelta($grocery_shopping_details_table);
    dbDelta($meals_table);
    dbDelta($members_table);
    dbDelta($payment_collections_table);
    dbDelta($utility_bills_table);
}

register_deactivation_hook(__FILE__, 'mm_deactivate');

function mm_deactivate()
{
    global $wpdb;

    $wpdb->query('DROP TABLE IF EXISTS mm_rooms');
    $wpdb->query('DROP TABLE IF EXISTS mm_grocery_shopping_master');
    $wpdb->query('DROP TABLE IF EXISTS mm_grocery_shopping_details');
    $wpdb->query('DROP TABLE IF EXISTS mm_meals');
    $wpdb->query('DROP TABLE IF EXISTS mm_members');
    $wpdb->query('DROP TABLE IF EXISTS mm_payment_collections');
    $wpdb->query('DROP TABLE IF EXISTS mm_utility_bills');
}

add_action('admin_enqueue_scripts', 'mm_plugin_assets');

function mm_plugin_assets()
{
    wp_enqueue_style('mm-bootstrap', PLUGIN_DIR_URL . 'assets/bootstrap.min.css');
    wp_enqueue_style('mm-style', PLUGIN_DIR_URL . 'assets/style.css');
    wp_enqueue_script('mm-vue', PLUGIN_DIR_URL . 'assets/vue.js');
    wp_enqueue_script('mm-axios', PLUGIN_DIR_URL . 'assets/axios.min.js');
    wp_enqueue_script('mm-data-table', PLUGIN_DIR_URL . 'assets/components/DataTable.js');
}

add_action('admin_menu', 'mm_main_menu');

function mm_main_menu()
{
    add_menu_page(
        'Meal Manager',
        'Meal Manager',
        'manage_options',
        'meal-manager',
        'mm_add_main_menu',
        'dashicons-edit',
        3
    );
}

function mm_add_main_menu()
{
    switch ($_GET['mm-action']) {

        case 'room':
            require PLUGIN_DIR_URL . 'pages/room.php';
            break;

        case 'member':
            require PLUGIN_DIR_URL . 'pages/member.php';
            break;

        case 'grocery-shopping':
            require PLUGIN_DIR_URL . 'pages/grocery-shopping.php';
            break;

        case 'meal':
            require PLUGIN_DIR_URL . 'pages/meal.php';
            break;

        case 'utility-bill':
            require PLUGIN_DIR_URL . 'pages/utility-bill.php';
            break;

        case 'payment-collection':
            require PLUGIN_DIR_URL . 'pages/payment-collection.php';
            break;
        
        default:
            require PLUGIN_DIR_URL . 'pages/dashboard.php';
            break;
    }
}