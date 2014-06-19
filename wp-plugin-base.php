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
 * WP Plugin Base 0.3
 * ==================
 * Extendable PHP class for creation of WordPress plugins.
 * https://github.com/pjnorberg/wp-plugin-base
 *
 * @package wpb
 */

class Base {

    protected $meta_key_postfix = 'meta_value_key';

    protected $plugin_rel_base;

    protected $project_prefix;
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
    }

    public function admin_enqueue_scripts_hook($page) {

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

    public function register_metaboxes() {

        if ($this->metaboxes) {

            foreach ($this->metaboxes as $key => $mb) {

                $context = (isset($mb['metabox']['context']) ? $mb['metabox']['context'] : 'advanced');
                $priority = (isset($mb['metabox']['priority']) ? $mb['metabox']['priority'] : 'default');

                add_meta_box(
                    $this->project_prefix.'_'.$key,
                    $mb['metabox']['title'],
                    array($this, 'register_metabox_callback'),
                    $this->project_prefix.'_'.$mb['metabox']['post_type'],
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
            if ($post->post_type == $this->project_prefix.'_'.$mb['metabox']['post_type']) {
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
                $output .= '<strong>'.$data['label'].'</strong><br><input data-field="media" id="'.$meta_key.'_meta_value_field" name="'.$meta_key.'_meta_value_field" type="text" class="widefat" value="'.$content.'" style="width: 50%; margin-right: 5px;">';
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
}