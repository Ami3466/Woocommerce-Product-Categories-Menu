<?php
/*
Plugin Name: Woo Categories Menu
Description: Create a WordPress menu with the WooCommerce product categories.
Version: 1.0
Author: Amit Elharar
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WooCategoriesMenu {

    public function __construct() {
        add_action('admin_menu', array($this, 'wc_add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function wc_add_admin_menu() {
        add_menu_page('WooCategories Menu', 'WooCategories Menu', 'manage_options', 'woocategories-menu', array($this, 'wc_options_page'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('woo-categories-menu', plugin_dir_url(__FILE__) . 'woo-categories-menu.js', array('jquery'), null, true);
        wp_enqueue_style('woo-categories-menu-style', plugin_dir_url(__FILE__) . 'woo-categories-menu.css');
    }

    public function wc_options_page() {
        ?>
        <div class="wrap">
            <h1>WooCategories Menu</h1>

            <form method="post" action="">
                <input type="submit" id="add-category-menu-btn" name="add_category_menu" value="Add Products Categories Menu" class="button button-primary">
                
                <input type="submit" id="update-category-menu-btn" name="update_category_menu" value="Update Menu" class="button button-secondary">
            </form>

            <div id="woo-categories-loader" class="woo-categories-loader" style="display:none;"></div>

            <?php if (isset($_POST['add_category_menu']) || isset($_POST['update_category_menu'])): ?>

                <?php
                if (isset($_POST['add_category_menu'])) {
                    $this->add_wc_categories_menu();
                } elseif (isset($_POST['update_category_menu'])) {
                    $this->update_wc_categories_menu();
                }
                ?>

            <?php endif; ?>
        </div>
        <?php
    }

    public function add_wc_categories_menu() {
        if ($this->menu_exists()) {
            echo '<div class="notice notice-error"><p>A menu called "Woo Categories" already exists.</p></div>';
        } else {
            wp_create_nav_menu('Woo Categories');
            $this->sync_wc_categories_menu();
            echo '<div class="notice notice-success"><p>Products categories menu added successfully.</p></div>';
        }
    }

    public function update_wc_categories_menu() {
        if (!$this->menu_exists()) {
            echo '<div class="notice notice-error"><p>No "Woo Categories" menu found. Please create it first.</p></div>';
        } else {
            $this->sync_wc_categories_menu(true); // true parameter to only add new categories
            echo '<div class="notice notice-success"><p>Menu updated successfully with new categories.</p></div>';
        }
    }

    private function sync_wc_categories_menu($update_only = false) {
        $menu_name = 'Woo Categories';
        $menu = wp_get_nav_menu_object($menu_name);
        $menu_id = $menu->term_id;

        $this->add_wc_category_items($menu_id, 0, 0, $update_only);
    }

    private function add_wc_category_items($menu_id, $parent_id, $menu_parent_id, $update_only) {
        $args = array(
            'taxonomy'     => 'product_cat',
            'orderby'      => 'name',
            'order'        => 'ASC',
            'hide_empty'   => false,
            'parent'       => $parent_id
        );

        $product_categories = get_terms($args);

        foreach ($product_categories as $category) {
            if ($update_only) {
                $item_check = wp_get_nav_menu_items($menu_id, array('meta_query' => array(array('key' => '_menu_item_object_id', 'value' => $category->term_id, 'compare' => '='))));
                if (!empty($item_check)) {
                    // Category already in menu, skip
                    continue;
                }
            }

            $menu_item_data = array(
                'menu-item-object-id' => $category->term_id,
                'menu-item-object' => 'product_cat',
                'menu-item-parent-id' => $menu_parent_id,
                'menu-item-type' => 'taxonomy',
                'menu-item-status' => 'publish',
                'menu-item-title' => $category->name,
            );

            $menu_item_id = wp_update_nav_menu_item($menu_id, 0, $menu_item_data);
            $this->add_wc_category_items($menu_id, $category->term_id, $menu_item_id, $update_only);
        }
    }

    private function menu_exists() {
        $menu_name = 'WooCategories';
        $menu = wp_get_nav_menu_object($menu_name);
        return $menu ? true : false;
    }
}

new WooCategoriesMenu();
?>