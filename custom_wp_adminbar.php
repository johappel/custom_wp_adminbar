<?php
/**
 * Plugin Name: Angepasste Admin-Bar mit Modal-Funktionalität für angemeldete User
 * Description: Stellt eine angepasste Adminbar auch für Abonnenten bereit und stellt eine Modal-Fenster Funktionalität zur Verfügung.
 * Version: 0.0.1
 * Author: Joachim Happel
 */

class Custom_AdminBar {
    private $wp_admin_bar;
    private $custom_items = array();
    private $modal_contents = array();
    private $user_role;

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'), 9999);
    }

    public function init() {
        // Stellen Sie sicher, dass die Admin-Bar angezeigt wird
        add_filter('show_admin_bar', '__return_true', 9999);

        if (is_admin_bar_showing()) {
            $this->set_user_role();
            add_action('admin_bar_menu', array($this, 'modify_admin_bar'), 9999);
            add_action('wp_before_admin_bar_render', array($this, 'remove_unwanted_nodes'), 9999);
            add_action('wp_head', array($this, 'add_custom_styles'), 9999);
            add_action('admin_head', array($this, 'add_custom_styles'), 9999);
            add_action('wp_footer', array($this, 'add_modal_html'), 9999);
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 9999);
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 9999);
        }
    }

    private function set_user_role() {
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $this->user_role = !empty($roles) ? $roles[0] : '';
    }

    public function add($parent_slug, $title, $url, $dashicon_slug = '') {
        $id = 'custom-' . sanitize_title($title);
        $this->custom_items[] = array(
            'id' => $id,
            'parent' => $parent_slug,
            'title' => $this->prepare_title($title, $dashicon_slug),
            'href' => $url,
        );
        return $id;
    }

    public function addModal($parent_slug, $title, $element_id, $dashicon_slug = '') {
        $id = 'custom-modal-' . sanitize_title($title);
        $this->custom_items[] = array(
            'id' => $id,
            'parent' => $parent_slug,
            'title' => $this->prepare_title($title, $dashicon_slug),
            'href' => '#',
            'meta' => array(
                'onclick' => "openModal('$element_id'); return false;",
            ),
        );
        return $id;
    }

    public function addModalContent($element_id, $content, $width = 'default', $has_iframe = false) {
        $this->modal_contents[$element_id] = array(
            'content' => $content,
            'width' => $width,
            'has_iframe' => $has_iframe,
        );
    }

    public function remove($id, $parent = null) {
        foreach ($this->custom_items as $key => $item) {
            if ($item['id'] === $id && ($parent === null || $item['parent'] === $parent)) {
                unset($this->custom_items[$key]);
                return true;
            }
        }
        return false;
    }

    private function prepare_title($title, $dashicon_slug) {
        if (!empty($dashicon_slug)) {
            return '<span class="dashicons ' . esc_attr($dashicon_slug) . '"></span> ' . esc_html($title);
        }
        return esc_html($title);
    }

    public function modify_admin_bar($wp_admin_bar) {
        $this->wp_admin_bar = $wp_admin_bar;

        if ($this->user_role === 'subscriber') {
            $this->remove_default_items();
            $this->add_custom_logo();
        }

        $this->add_custom_items();
    }

    private function remove_default_items() {
        $default_nodes = array('wp-logo', 'site-name', 'comments', 'new-content', 'edit', 'my-account');
        foreach ($default_nodes as $node) {
            $this->wp_admin_bar->remove_node($node);
        }
    }

    public function remove_unwanted_nodes() {
        global $wp_admin_bar;

        // Liste der zu behaltenden Knoten
        $keep_nodes = array('custom-logo', 'top-secondary');
        foreach ($this->custom_items as $item) {
            $keep_nodes[] = $item['id'];
        }

        // Entferne alle Knoten, die nicht in der Liste sind
        $all_nodes = $wp_admin_bar->get_nodes();
        foreach ($all_nodes as $node) {
            if (!in_array($node->id, $keep_nodes) && $node->parent === '') {
                $wp_admin_bar->remove_node($node->id);
            }
        }
    }

    private function add_custom_logo() {
        //@TODO: Pfad zur Logo-Datei ermitteln
        $args = array(
            'id'    => 'custom-logo',
            'title' => '<img src="' . get_template_directory_uri() . '/path/to/your/logo.png" style="height:20px; width:auto;" />',
            'href'  => home_url(),
        );
        $this->wp_admin_bar->add_node($args);
    }

    private function add_custom_items() {
        foreach ($this->custom_items as $item) {
            $this->wp_admin_bar->add_node($item);
        }
    }

    public function add_custom_styles() {
        echo '
        <style type="text/css">
            #wpadminbar {display: block !important; visibility: visible !important;}
            #wpadminbar .ab-top-menu > li > .ab-item .dashicons,
            #wpadminbar .ab-sub-wrapper .dashicons {
                font-family: dashicons;
                font-size: 20px;
                line-height: 1;
                vertical-align: middle;
                padding-right: 4px;
            }
            .custom-modal {
                display: none;
                position: fixed;
                z-index: 999999;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.4);
            }
            .custom-modal-content {
                background-color: #fefefe;
                margin: 5% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
                max-width: 800px;
            }
            .custom-modal-content.full {
                width: 95%;
                max-width: none;
            }
            .custom-modal-content.wide {
                width: 90%;
                max-width: 1200px;
            }
            .custom-modal-close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }
            .custom-modal-close:hover,
            .custom-modal-close:focus {
                color: #000;
                text-decoration: none;
                cursor: pointer;
            }
            .custom-modal iframe {
                width: 100%;
                height: 80vh;
                border: none;
            }
        </style>
        ';
    }

    public function add_modal_html() {
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

    public function enqueue_scripts() {
        wp_add_inline_script('jquery', '
            function openModal(modalId) {
                jQuery("#" + modalId).show();
            }
            function closeModal(modalId) {
                jQuery("#" + modalId).hide();
            }
            jQuery(document).click(function(event) {
                if (jQuery(event.target).hasClass("custom-modal")) {
                    jQuery(event.target).hide();
                }
            });
            // Stellen Sie sicher, dass die Admin-Bar sichtbar bleibt
            jQuery(document).ready(function($) {
                $("#wpadminbar").show();
            });
        ');
    }
}

$customized_wordpress_adminbar = new Custom_AdminBar();
