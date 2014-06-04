<?php

namespace WPB;

/**
 * wp-plugin-base
 */

class Base {

	protected $meta_key_postfix = 'meta_value_key';

	protected $plugin_base;
	protected $plugin_rel_base;

	protected $project_prefix;
	protected $post_types;

	/**
	 * [__construct]
	 * 
	 * @param string $prefix - prefix for various plugin fields and values
	 */
	public function __construct() {

		$this->plugin_base = rtrim(dirname(__FILE__), '/');
		$this->plugin_rel_base = dirname(plugin_basename(__FILE__));

		register_activation_hook(__FILE__, array(&$this, 'activation_hook'));
		register_activation_hook(__FILE__, array(&$this, 'deactivation_hook'));
		register_uninstall_hook(__FILE__, array(get_class(), 'uninstall_hook'));

		add_action('init', array($this, 'register_cpt'));
		add_action('add_meta_boxes', array($this, 'register_metaboxes'));
		add_action('save_post', array($this, 'save_post_meta'));
	}

	public function activation_hook($network_wide) {
		$this->check_requirements();
	}

	public function deactivation_hook($network_wide) {
	}

	public static function uninstall_hook($network_wide) {

		if (! defined('WP_UNINSTALL_PLUGIN')) {
			die();
		}
	}

	public function register_cpt() {

		if ($this->post_types) {

			foreach ($this->post_types as $post_type => $options) {

				$post_type = ($post_type === 0 ? $options : $post_type );
				$options = (!is_array($options) ? null : $options);

				register_post_type($this->project_prefix . '_' . $post_type, $options);
			}
		}
	}

	public function register_metaboxes() {

		if ($this->metaboxes) {

			foreach ($this->metaboxes as $key => $mb) {

				$context = (isset($mb['metabox']['context']) ? $mb['metabox']['context'] : 'advanced');
				$priority = (isset($mb['metabox']['priority']) ? $mb['metabox']['priority'] : 'default');

				add_meta_box(
					$this->project_prefix . '_' . $key,
					$mb['metabox']['title'],
					array($this, 'register_metabox_callback'),
					$this->project_prefix . '_' . $mb['metabox']['post_type'],
					$context,
					$priority
				);
			}
		}
	}

	public function register_metabox_callback($post) {

		wp_nonce_field($this->project_prefix . '_meta_box', $this->project_prefix . '_meta_box_nonce');

		$this->load_post_meta($post->ID);

		foreach ($this->metaboxes as $key => $mb) {
			if ($post->post_type == $this->project_prefix . '_' . $mb['metabox']['post_type']) {
				foreach ($mb['post_meta'] as $meta_key => $data) {
					echo (isset($mb['metabox']['render']) ? $this->{$mb['metabox']['render']}($meta_key, $data) : $this->render($meta_key, $data));
				}
			}
		}
	}

	public function render($meta_key, $data) {

		$content = (isset($data['content']) ? $data['content'] : '');

		$output = '<label>';

		switch ($data['type']) {

			case 'date':
				$output .= $data['label'] . '<br><input name="'.$meta_key.'_meta_value_field" type="date" class="widefat" value="'.$content.'">';
				break;

			case 'email':
				$output .= $data['label'] . '<br><input name="'.$meta_key.'_meta_value_field" type="email" class="widefat" value="'.$content.'">';
				break;
			
			default:
				$output .= $data['label'] . '<br><input name="'.$meta_key.'_meta_value_field" type="text" class="widefat" value="'.$content.'">';
				break;
		}

		return $output .= '</label>';
	}
	
	public function load_post_meta($post_id) {

		foreach ($this->metaboxes as $mb_key => $mb) {
			foreach ($mb['post_meta'] as $meta_key => $data) {

				if (isset($data['before_render'])) {
					$rendered = $this->{$data['before_render']}(get_post_meta($post_id, $this->project_prefix . '_' . $meta_key . '_' . $this->meta_key_postfix, true));
					$this->metaboxes[$mb_key]['post_meta'][$meta_key]['content'] = $rendered;
				} else {
					$this->metaboxes[$mb_key]['post_meta'][$meta_key]['content'] = get_post_meta($post_id, $this->project_prefix . '_' . $meta_key . '_' . $this->meta_key_postfix, true);
				}
			}
		}
	}

	public function save_post_meta($post_id) {

		if (!isset( $_POST[$this->project_prefix . '_meta_box_nonce']))
			return;

		if (wp_verify_nonce($_POST[$this->project_prefix . '_meta_box'], $this->project_prefix . '_meta_box_nonce'))
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
				if (isset($_POST[$meta_key . '_meta_value_field'])) {
					if (isset($data['before_save'])) {
						$finished = $this->{$data['before_save']}($_POST[$meta_key . '_meta_value_field']);
						update_post_meta($post_id, $this->project_prefix . '_' . $meta_key . '_' . $this->meta_key_postfix, $finished);
					} else {
						update_post_meta($post_id, $this->project_prefix . '_' . $meta_key . '_' . $this->meta_key_postfix, $_POST[$meta_key . '_meta_value_field']);
					}
				}
			}
		}
	}
}