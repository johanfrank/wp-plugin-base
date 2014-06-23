<?php

namespace WPB;

/**
* Copyright 2014, Johan Norberg (http://johannorberg.biz)
*
* Licensed under The MIT License
* Redistributions of files must retain the above copyright notice.
*
* @copyright Copyright 2014, Johan Norberg (http://johannorberg.biz)
* @license MIT License (http://www.opensource.org/licenses/mit-license.php)
*/

/**
 * WP Plugin Base 0.5
 * ==================
 * Extendable PHP class for creation of WordPress plugins.
 * https://github.com/pjnorberg/wp-plugin-base
 *
 * @package wpb
 */

class Base {

    protected $meta_key_postfix = 'meta_value_key';

    protected $plugin_rel_base;

    protected $project_name;
    protected $project_prefix;
    protected $metaboxes;
    protected $post_types;
    protected $stylesheets;
    protected $scripts;
    protected $taxonomies;

    /**
     * [__construct]
     * 
     * @param string $child_class_path - file path from inheriting class
     */
    public function __construct($child_class_path) {

        $this->plugin_rel_base = dirname(plugin_basename($child_class_path));

        register_activation_hook($child_class_path, array(&$this, 'activation_hook'));
        register_deactivation_hook($child_class_path, array(&$this, 'deactivation_hook'));

        add_action('init', array($this, 'register_cpt'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'register_metaboxes'));
        add_action('save_post', array($this, 'save_post_meta'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts_hook'));
        add_action('admin_menu', array($this, 'add_uninstall_menu'));
    }

    public function admin_enqueue_scripts_hook($page) {

        wp_enqueue_media();
        wp_enqueue_script('wpb-base', plugins_url($this->plugin_rel_base.'/wp-plugin-base.js'), array('jquery', 'wp-color-picker'));
        wp_enqueue_style('wp-color-picker');

        if ($this->stylesheets) {
            
            foreach ($this->stylesheets as $handle => $args) {
                
                $deps = (isset($args['deps']) ? $args['deps'] : array());
                $ver = (isset($args['ver']) ? $args['ver'] : false);
                $media = (isset($args['media']) ? $args['media'] : 'all');

                wp_enqueue_style($this->project_prefix.'_'.$handle, plugins_url($this->plugin_rel_base.$args['src']), $deps, $ver, $media);
            }
        }

        if ($this->scripts) {

            foreach ($this->scripts as $handle => $args) {
                
                $deps = (isset($args['deps']) ? $args['deps'] : array());
                $ver = (isset($args['ver']) ? $args['ver'] : false);
                $in_footer = (isset($args['in_footer']) ? $args['in_footer'] : false);

                wp_enqueue_script($this->project_prefix.'_'.$handle, plugins_url($this->plugin_rel_base.$args['src']), $deps, $ver, $in_footer);
            }
        }
    }

    public function activation_hook($network_wide) {
    }

    public function deactivation_hook($network_wide) {
    }

    public function add_uninstall_menu() {

        if (isset($this->project_name)) {
            add_management_page(
                'Removes all data created by plugin '.$this->project_name,
                $this->project_name.' data uninstall',
                'manage_options',
                'uninstall_'.$this->project_prefix,
                array($this, 'uninstall_data')
            );
        }
    }

    public function uninstall_data() {

        echo '<div class="wrap">';
        echo '<h2>Uninstall data by '.$this->project_name.'</h2>';
        echo '<form action="" method="get">';
        echo '<input type="hidden" name="page" value="uninstall_'.$this->project_prefix.'">';

        if (isset($_GET['uninstall'])) {

            $all_posts = new \WP_Query(array(
                'posts_per_page' => -1,
                'post_type' => 'any'
            ));

            $post_meta_count = 0;
            $taxonomy_term_count = 0;
            $post_deleted_count = 0;

            foreach ($all_posts->posts as $post) {
        
                if (in_array('post_meta', $_GET['uninstall'])) {

                    foreach ($this->metaboxes as $mb_key => $mb) {
                        foreach ($mb['post_meta'] as $post_meta_key => $post_meta_settings) {
                            if (delete_post_meta($post->ID, $this->project_prefix.'_'.$post_meta_key.'_'.$this->meta_key_postfix)) {
                                $post_meta_count++;
                            }
                        }
                    }
                }

                if (in_array('custom_posts', $_GET['uninstall'])) {

                    $post_type = str_replace($this->project_prefix.'_', '', $post->post_type);

                    if (array_key_exists($post_type, $this->post_types)) {
                        if (wp_delete_post($post->ID, true)) {
                            $post_deleted_count++;
                        }
                    }
                }
            }

            foreach ($this->taxonomies as $taxonomy_key => $taxonomy_settings) {

                $taxonomy_terms = get_terms($this->project_prefix.'_'.$taxonomy_key, array(
                    'hide_empty' => false
                ));

                foreach ($taxonomy_terms as $term) {
                    if (wp_delete_term($term->term_id, $this->project_prefix.'_'.$taxonomy_key)) {
                        $taxonomy_term_count++;
                    }
                }
            }

            echo '<p>';
            echo '<strong>Results:</strong><br>';
            echo ($post_meta_count ? $post_meta_count.' post meta fields removed from database.<br>' : '');
            echo ($taxonomy_term_count ? $taxonomy_term_count.' taxonomy terms removed from database.<br>' : '');
            echo ($post_deleted_count ? $post_deleted_count.' custom posts removed from database.<br>' : '');
            echo '</p>';
        
        } else {

            if (isset($this->metaboxes))
                echo '<p><label><input type="checkbox" name="uninstall[]" value="post_meta">Post meta</label></p>';

            if (isset($this->taxonomies))
                echo '<p><label><input type="checkbox" name="uninstall[]" value="taxonomy_terms">Taxonomy terms</label></p>';

            if (isset($this->post_types))
                echo '<p><label><input type="checkbox" name="uninstall[]" value="custom_posts">Custom posts</label></p>';

            echo '<p><input type="submit" class="button action" value="Remove data from database"></p>';
        }

        echo '</form>';
        echo '</div>';
    }

    public function register_cpt() {

        if ($this->post_types) {

            foreach ($this->post_types as $post_type => $options) {
                register_post_type($this->project_prefix.'_'.$post_type, $options);
            }
        }
    }

    public function register_taxonomies() {

        if ($this->taxonomies) {

            foreach ($this->taxonomies as $taxonomy => $data) {

                if (isset($data['custom_post_types'])) {

                    if (is_array($data['custom_post_types'])) {
                        foreach ($data['custom_post_types'] as $cpt) {
                            $data['post_types'][] = $this->project_prefix.'_'.$cpt;
                        }
                    } else if (is_string($data['custom_post_types'])) {
                        $data['post_types'][] = $this->project_prefix.'_'.$data['custom_post_types'];
                    }
                }

                $args = (isset($data['args']) ? $data['args'] : array());

                register_taxonomy($this->project_prefix.'_'.$taxonomy, $data['post_types'], $args);
            }
        }
    }

    public function get_post_type($post_type) {

        switch ($post_type) {

            case 'post':
                $post_type = 'post';
                break;

            case 'page':
                $post_type = 'page';
                break;
            
            default:
                $post_type = $this->project_prefix.'_'.$post_type;
                break;
        }

        return $post_type;        
    }

    public function register_metaboxes() {

        if ($this->metaboxes) {

            foreach ($this->metaboxes as $key => $mb) {

                $context = (isset($mb['metabox']['context']) ? $mb['metabox']['context'] : 'advanced');
                $priority = (isset($mb['metabox']['priority']) ? $mb['metabox']['priority'] : 'default');

                $post_type = $this->get_post_type($mb['metabox']['post_type']);

                add_meta_box(
                    $this->project_prefix.'_'.$key,
                    $mb['metabox']['title'],
                    array($this, 'register_metabox_callback'),
                    $post_type,
                    $context,
                    $priority
                );
            }
        }
    }

    public function register_metabox_callback($post) {

        wp_nonce_field($this->project_prefix.'_meta_box', $this->project_prefix.'_meta_box_nonce');

        $this->load_post_meta($post->ID);

        foreach ($this->metaboxes as $key => $mb) {

            $post_type = $this->get_post_type($mb['metabox']['post_type']);

            if ($post->post_type == $post_type) {
                foreach ($mb['post_meta'] as $meta_key => $data) {
                    echo (isset($mb['metabox']['render']) ? $this->{$mb['metabox']['render']}($meta_key, $data) : $this->render($meta_key, $data));
                }
            }
        }
    }

    public function render($meta_key, $data) {

        $content = (isset($data['content']) ? $data['content'] : '');
        $type = (isset($data['type']) ? $data['type'] : 'text' );
        $output = '<p>';

        switch ($type) {

            case 'media':
                $output .= '<label class="media">';
                $output .= ($content ? '<img height="150" src="'.$content.'" class="choosen-image">' : '');
                $output .= '<div class="inputs">';
                $output .= '<strong>'.$data['label'].'</strong><br><input data-field="media" id="'.$meta_key.'_meta_value_field" name="'.$meta_key.'_meta_value_field" type="text" class="widefat" value="'.$content.'" style="width: 50%; margin-right: 5px;">';
                $output .= '</div>';
                $output .= '</label>';
                $output .= '<input data-clear="'.$meta_key.'_meta_value_field" class="button button-primary" type="button" value="Clear">';
                $output .= '<br>';
                break;

            case 'colorpicker':
                $output .= '<label>';
                $output .= $data['label'].'<br><input data-field="colorpicker" name="'.$meta_key.'_meta_value_field" type="text" class="widefat" value="'.$content.'"><br>';
                $output .= '</label>';
                break;

            case 'radio':

                $output .= $data['label'].'<br>';
                foreach ($data['options'] as $value => $label) {
                    $checked = ($data['content'] == $value ? ' checked="checked"' : '' );
                    $output .= '<label><input name="'.$meta_key.'_meta_value_field" type="radio" value="'.$value.'"'.$checked.'> '.$label.'<br></label>';
                }               
                break;

            case 'checkbox':

                $output .= $data['label'].'<br>';
                foreach ($data['options'] as $value => $label) {
                    $checked = (is_array($data['content']) && in_array($value, $data['content']) ? ' checked="checked"' : '' );
                    $output .= '<label><input name="'.$meta_key.'_meta_value_field[]" type="checkbox" value="'.$value.'"'.$checked.'> '.$label.'<br></label>';
                }               
                break;

            case 'date':
                $output .= '<label>';
                $output .= $data['label'].'<br><input name="'.$meta_key.'_meta_value_field" type="date" class="widefat" value="'.$content.'">';
                $output .= '</label>';
                break;

            case 'email':
                $output .= '<label>';
                $output .= $data['label'].'<br><input name="'.$meta_key.'_meta_value_field" type="email" class="widefat" value="'.$content.'">';
                $output .= '</label>';
                break;

            case 'textarea':

                $rows = (isset($data['rows']) ? $data['rows'] : 5 );
                $output .= '<label>';
                $output .= $data['label'].'<br><textarea name="'.$meta_key.'_meta_value_field" class="widefat" rows="'.$rows.'">'.$content.'</textarea>';
                $output .= '</label>';
                break;
            
            default:
                $output .= '<label>';
                $output .= $data['label'].'<br><input name="'.$meta_key.'_meta_value_field" type="text" class="widefat" value="'.$content.'">';
                $output .= '</label>';
                break;
        }

        if (isset($data['description'])) {
            $output .= '<small>'.$data['description'].'</small>';
        }

        return $output .= '</p>';
    }
    
    public function load_post_meta($post_id) {

        foreach ($this->metaboxes as $mb_key => $mb) {
            foreach ($mb['post_meta'] as $meta_key => $data) {

                if (isset($data['before_render'])) {
                    $rendered = $this->{$data['before_render']}(get_post_meta($post_id, $this->project_prefix.'_'.$meta_key.'_'.$this->meta_key_postfix, true));
                    $this->metaboxes[$mb_key]['post_meta'][$meta_key]['content'] = $rendered;
                } else {
                    $this->metaboxes[$mb_key]['post_meta'][$meta_key]['content'] = get_post_meta($post_id, $this->project_prefix.'_'.$meta_key.'_'.$this->meta_key_postfix, true);
                }
            }
        }
    }

    public function save_post_meta($post_id) {

        if (!isset($_POST[$this->project_prefix.'_meta_box_nonce']))
            return;

        if (wp_verify_nonce($_POST[$this->project_prefix.'_meta_box'], $this->project_prefix.'_meta_box_nonce'))
            return;

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id))
                return;
        } else {
            if (!current_user_can('edit_post', $post_id))
                return;
        }

        foreach ($this->metaboxes as $mb_key => $mb) {
            foreach ($mb['post_meta'] as $meta_key => $data) {
                if (isset($_POST[$meta_key.'_meta_value_field'])) {
                    if (isset($data['before_save'])) {
                        $finished = $this->{$data['before_save']}($_POST[$meta_key.'_meta_value_field']);
                        update_post_meta($post_id, $this->project_prefix.'_'.$meta_key.'_'.$this->meta_key_postfix, $finished);
                    } else {
                        update_post_meta($post_id, $this->project_prefix.'_'.$meta_key.'_'.$this->meta_key_postfix, $_POST[$meta_key.'_meta_value_field']);
                    }
                }
            }
        }
    }

    public function template($file = null) {

        if (!$file)
            return false;
        
        include_once("templates/$file.php");
    }
}