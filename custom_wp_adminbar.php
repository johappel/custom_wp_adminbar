<?php
/**
 * Plugin Name: Custom WP Adminbar
 * Description: Passt die WordPress-Admin-Bar an, fügt Modal-Fenster und ein Mega-Menü hinzu.
 * Version: 0.1
 * Author: Joachim Happel
 */

class Custom_AdminBar {
    public $wp_admin_bar;
    public static $instance;
    public $custom_items = array();
    public $modal_contents = array();
    public $mega_menu_contents = array();
    public $user_role;
    public $logo_url = 'https://nextcloud.comenius.de/core/img/logo/logo.svg';

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'),9999);
    }
    public function set_logo($url) {
        $this->logo_url = $url;
    }
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function init() {
        if(is_admin()){
            return;
        }
        add_filter('show_admin_bar', '__return_true', 9999);

        if (is_admin_bar_showing()) {
            $this->set_user_role();
            add_action('admin_bar_menu', array($this, 'modify_admin_bar'), 9999);
            #add_action('wp_before_admin_bar_render', array($this, 'remove_unwanted_nodes'), 9999);
            # add_action('wp_head', array($this, 'add_custom_styles'), 9999);
            add_action('admin_head', array($this, 'add_custom_styles'), 9999);
            add_action('wp_footer', array($this, 'add_modal_and_mega_menu_html'), 9999);
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 9999);
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 9999);
        }
    }

    private function set_user_role() {
        if (is_user_logged_in()){
            $this->user_role = 'member';
            $user = wp_get_current_user();
            if(user_can($user, 'manage_options')){
                $this->user_role = 'administrator';
            }
        }

    }

    public function add($parent_slug, $title, $url, $dashicon_slug = '', $meta = array()) {
        $id = 'custom-' . sanitize_title($title);
        $this->custom_items[] = array(
            'id' => $id,
            'parent' => $parent_slug,
            'title' => $this->prepare_title($title, $dashicon_slug),
            'href' => $url,
            'meta' => $meta,
        );
        $this->add_custom_items();
        return $id;
    }

    public function edit($id, $title=null, $url=null, $dashicon_slug = null) {
        global $wp_admin_bar;
        $all_nodes = $wp_admin_bar->get_nodes();

        foreach ($all_nodes as $key => $item) {
            if ($key === $id) {
                $node = $wp_admin_bar->get_node($id);
                $wp_admin_bar->remove_node($id);
                if ($title !== null) {
                    $node->title = $this->prepare_title($title, $dashicon_slug);
                }
                if ($url !== null) {
                    $node->href = $url;
                }
                $node_array = [
                    'id'=>$node->id,
                    'parent'=>$node->parent,
                    'title'=>$node->title,
                    'href'=>$node->href,
                    'group'=>$node->group,
                    'meta'=>$node->meta
                ];
                $wp_admin_bar->add_node($node_array);
                return true;
            }
        }
        return false;
    }

    public function addModal($parent_slug, $title, $element_id, $dashicon_slug = '') {
        $id = 'custom-modal-' . sanitize_title($title);
        $this->custom_items[] = array(
            'id' => $id,
            'parent' => $parent_slug,
            'title' => $this->prepare_title($title, $dashicon_slug),
            'href' => '#',
            'meta' => array(
                'onclick' => 'openModal("'.$element_id.'")',
            ),
        );
        $this->add_custom_items();
        return $id;
    }

    public function addMegaMenu($parent_slug, $title, $element_id, $dashicon_slug = '') {
        $id = 'custom-mega-' . sanitize_title($title);
        $this->custom_items[] = array(
            'id' => $id,
            'parent' => $parent_slug,
            'title' => $this->prepare_title($title, $dashicon_slug),
            'href' => '#',
            'meta' => array(
                'onclick' => 'toggleMegaMenu("'.$element_id.'")',
            ),
        );
        $this->add_custom_items();
        return $id;
    }

    public function addModalContent($element_id, $content, $width = 'default', $has_iframe = false) {
        $this->modal_contents[$element_id] = array(
            'content' => $content,
            'width' => $width,
            'has_iframe' => $has_iframe,
        );
    }

    public function addMegaMenuContent($element_id, $content) {
        $this->mega_menu_contents[$element_id] = $content;
    }

    public function remove($id, $parent = null) {
        global $wp_admin_bar;
        $wp_admin_bar->remove_node($id);

        foreach ($this->custom_items as $key => $item) {
            if ($item['id'] === $id && ($parent === null || $item['parent'] === $parent)) {
                unset($this->custom_items[$key]);
                return true;
            }
        }
        $this->add_custom_items();
        return false;
    }

    private function prepare_title($title, $dashicon_slug) {
        if (!empty($dashicon_slug)) {
            return '<span class="dashicons ' . esc_attr($dashicon_slug) . '"></span><span class="ab-label">' . esc_html($title) . '</span>';
        }
        return esc_html($title);
    }

    public function modify_admin_bar($wp_admin_bar) {
        $this->wp_admin_bar = $wp_admin_bar;

        if ($this->user_role !== 'administrator') {
            $this->remove_unwanted_nodes();
            $this->add_custom_logo();

        }

        $this->add_custom_items();
    }


    public function remove_unwanted_nodes() {
        global $wp_admin_bar;

        $keep_nodes = array('custom-logo', 'top-secondary');
        foreach ($this->custom_items as $item) {
            $keep_nodes[] = $item['id'];
        }

        $all_nodes = $wp_admin_bar->get_nodes();
        foreach ($all_nodes as $node) {
            if (!in_array($node->id, $keep_nodes) && empty($node->parent)) {
                $wp_admin_bar->remove_node($node->id);
            }
        }
    }

    private function add_custom_logo() {
        $args = array(
            'id'    => 'custom-logo',
            'title' => '<img src="'.$this->logo_url.'" style="height:20px; width:auto;" />',
            'href'  => home_url(),
        );
        $this->wp_admin_bar->add_node($args);
    }

    public function add_custom_items() {
        foreach ($this->custom_items as $item) {
            $item['meta']['class'] = 'custom';
            $item['meta']['title'] = strip_tags($item['title']);

            $this->wp_admin_bar->add_node($item);
        }
    }


    public function add_modal_and_mega_menu_html() {
        $this->add_modal_html();
        $this->add_mega_menu_html();
    }

    private function add_modal_html() {
        foreach ($this->modal_contents as $id => $modal) {
            $width_class = $modal['width'] === 'full' ? 'full' : ($modal['width'] === 'wide' ? 'wide' : '');
            $content = $modal['has_iframe'] ? '<iframe src="' . esc_url($modal['content']) . '"></iframe>' : wp_kses_post($modal['content']);
            echo '
            <div id="' . esc_attr($id) . '" class="custom-modal">
                <div class="custom-modal-content ' . esc_attr($width_class) . '">
                    <span class="custom-modal-close" onclick="closeModal(\'' . esc_attr($id) . '\')">&times;</span>
                    ' . $content . '
                </div>
            </div>
            ';
        }
    }

    private function add_mega_menu_html() {
        foreach ($this->mega_menu_contents as $id => $content) {
            echo '
            <div id="' . esc_attr($id) . '" class="custom-mega-menu">
                <div class="custom-mega-menu-content">
                    <span class="custom-mega-menu-close" onclick="closeMegaMenu(\'' . esc_attr($id) . '\')">&times;</span>
                    ' . wp_kses_post($content) . '
                </div>
            </div>
            ';
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('modal_menu_handler', plugin_dir_url(__FILE__) . 'modal_menu_handler.js', array('jquery'), '1.0', true);
        wp_enqueue_style('custom_wp_adminbar', plugin_dir_url(__FILE__) . 'custom_wp_adminbar.css');
    }
}
global $customized_wordpress_adminbar;
$customized_wordpress_adminbar = new Custom_AdminBar();

