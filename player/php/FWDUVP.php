<?php
/**
 * Main class.
 *
 * @package fwduvp
 * @since fwduvp 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class FWDUVP{
	
	const MIN_WP_VER =  "3.5.0";
	const CAPABILITY = "edit_fwduvp";
	const VERSION = '11.0';

	private $_data;
	private $_dir_url;
    private static $_uvp_id = 0;
    private static $_pl_id = 0;
    public $set_id = 0;
    public $set_order_id = 0;
    public $tab_init_id = 0;

	private $_preset_id;
	private $_playlist_id;
	private $_start_at_playlist;
	private $_start_at_video_id;
	private $_is_woocommerce = false;
	public static $INIT = false;

	
    // Constructor.
    public function init(){
		$this->_dir_url = plugin_dir_url(dirname(__FILE__));
	
    	// Set hooks.
    	add_action("admin_menu", array($this, "add_plugin_menu"));
    	add_action('admin_enqueue_scripts', array($this, "fwduvp_enqueue_admin_files"));
		add_action('wp_ajax_fwduvp_update_presets', array($this, 'fwduvp_update_presets_ajax'));
		add_action('wp_ajax_fwduvp_update_playlists', array($this, 'fwduvp_update_playlists_ajax'));
		add_action('wp_ajax_fwduvp_update_global_ads', array($this, 'fwduvp_update_global_ads_ajax'));
		add_action('wp_ajax_fwduvp_update_global_popup_ads', array($this, 'fwduvp_update_global_popup_ads_ajax'));
		add_action("wp_enqueue_scripts", array($this, "fwduvp_add_scripts_and_styles"));
		
		// set data
		$this->_data = new FWDUVPData();		
		$this->_data->init();

		// Shortcode.
		add_shortcode("fwduvp", array($this, "fwduvp_set_player"));

		// WooComerce.
		add_action('wp', array($this, 'fwduvp_check_current_post'));		
    }


	/**
	 * Woocomerce logic.
	 */

	// Get the shortcode id if found in the current product.
	public function fwduvp_check_current_post() {
		
		if (is_single() && 'product' === get_post_type()){
			
			global $post;
			$product_id = $post->ID;
			$product = wc_get_product($product_id);

			if($product) {
				$pattern = '/\[fwduvp\s+preset_id="([^"]+)"\s+playlist_id="([^"]+)"(?:\s+start_at_playlist="([^"]*)")?(?:\s+start_at_video="([^"]*)")?\s*\]/';
				$product_description = $product->get_description();

				$this->_is_woocommerce = true;
			
				if (preg_match($pattern, $product_description, $matches)) {
					
					$this->_preset_id = $matches[1];
					$this->_playlist_id = $matches[2];
					$this->_start_playlist_id = $matches[3];
					$this->_start_track_id = $matches[4];

					// Add hooks.
					add_action('woocommerce_before_single_product_summary', array($this, 'fwduvp_remove_default_gallery'), 20);
					add_filter('woocommerce_single_product_image_thumbnail_html', array($this, 'fwduvp_replace_woocommerce_gallery'), 10, 19);
					add_filter('woocommerce_single_product_image_gallery_classes', array($this, 'fwduvp_add_custom_class'), 21 );
				}
			}
		}
	}

	// Remove default gallery.
	public function fwduvp_remove_default_gallery() {
		remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
	}

	// Replace default gallery with the shortcode.
	public function fwduvp_replace_woocommerce_gallery() {
		$gallery = do_shortcode('[fwduvp preset_id="' . $this->_preset_id . '" playlist_id="' . $this->_playlist_id . '"' . ' start_playlist_id="' . $this->_start_playlist_id . '"' . ' start_track_id="' . $this->_start_track_id . '"]');
		return $gallery;
	}

	// Add custom class to the gallery.
	public function fwduvp_add_custom_class($classes ){
	
		$className = NULL;

		// Check presets.
		$modelFound = NULL;
		foreach ($this->_data->settings_ar as $set){
    		if ($set["name"] == $this->_preset_id){
    			$modelFound = $set;
    		}
    	}

		// Ensure $classes is a string before appending
		if(is_array($classes) && $modelFound) {
			$classes[] = $modelFound['woo_commerce_display_type']; // Add the class to the array
		}
		
		return $classes;
	}

	 // Extract shortcode.
	 public function fwduvp_set_player($atts){

		if($this->_is_woocommerce && FWDUVP::$_uvp_id > 0){
			return;
		}

		extract(shortcode_atts(array("preset_id" => 0, "playlist_id" => 0, "start_at_playlist"=>"", "start_at_video"=>""), $atts, "fwduvp"));

		// Check for preset.
		$preset = NULL;
    	foreach ($this->_data->settings_ar as $set){
    		if ($set["name"] == $preset_id){
    			$preset = $set;
    		}
    	}
    	
    	if (is_null($preset)){
			if($this->_is_woocommerce){
				FWDUVP::$_uvp_id ++;	
			}
    		return "Ultimate Video Player with preset id <strong>". esc_html($preset_id) . "</strong> does not exist!";
    	}
    	
    	// Check for playlist.
    	$main_playlist = NULL;
    	foreach ($this->_data->main_playlists_ar as $pl){
    		if ($pl["name"] == $playlist_id){
    			$main_playlist = $pl;
    		}
    	}
    	 	
    	if (is_null($main_playlist)){
			if($this->_is_woocommerce){
				FWDUVP::$_uvp_id ++;	
			}
    		return "Ultimate Video Player playlist with id <strong>". esc_html($playlist_id) . "</strong> does not exist!";
    	}
	
		// Get data for output.
		$uvp_constructor = $this->fwduvp_get_constructor($preset, $playlist_id, $start_at_playlist, $start_at_video);
		$uvp_div = "<div id='fwduvpDiv" . FWDUVP::$_uvp_id. "' class='fwduvp fwd-hide'></div>";
		$uvp_main_playlist = $this->fwduvp_get_main_playlist($playlist_id);
		FWDUVP::$_uvp_id++;
		$uvp_output = $uvp_div . $uvp_main_playlist;

		// Register JS.
		wp_register_script('fwduvp-dummy-handle-footer', '', array(), FWDUVP::VERSION, true);
   		wp_enqueue_script( 'fwduvp-dummy-handle-footer'  );
    	wp_add_inline_script('fwduvp-dummy-handle-footer', $uvp_constructor);
		
		return $uvp_output; // All dynamic data was escaped!
	}
	

	
    // Add menu.
    public function add_plugin_menu(){
        add_menu_page("Ultimate Video Player", "Ultimate Video Player", FWDUVP::CAPABILITY, "fwduvp-menu-general-settings", array($this, "fwduvp_set_general_settings"), esc_url_raw($this->_dir_url) . "content/icons/menu-icon.png");
		add_submenu_page("fwduvp-menu-general-settings", esc_html__('General settings', 'fwd-ultimate-video-player'), esc_html__('General settings', 'fwd-ultimate-video-player'), FWDUVP::CAPABILITY, "fwduvp-menu-general-settings");
		add_submenu_page("fwduvp-menu-general-settings", esc_html__('Playlists manager', 'fwd-ultimate-video-player'), esc_html__('Playlists manager', 'fwd-ultimate-video-player'), FWDUVP::CAPABILITY, "fwduvp-menu-playlists-manager", array($this, "fwduvp_set_playlists_manager"));
		add_submenu_page("fwduvp-menu-general-settings", esc_html__('Global advertising', 'fwd-ultimate-video-player'), esc_html__('Global advertising', 'fwd-ultimate-video-player'), FWDUVP::CAPABILITY, "fwduvp-menu-global-advertising", array($this, "fwduvp_set_global_advertising"));
       	
       	// add meta boxes
       	$post_type_screens = array("post", "page");
       	$args = array(
	       'public'   => true,
	       '_builtin' => false,
	    );
       	$custom_post_types = get_post_types($args);
       	foreach ($custom_post_types as $screen){
       		$post_type_screens[] = $screen;
       	}
		
    	foreach ($post_type_screens as $screen){
       		add_meta_box("fwduvp-shortcode-generator", "Ultimate Video Player Shortcode Generator",  array($this, "fwduvp_set_custom_meta_box"), $screen, "side", "default");
    	}
    }

    // Add backend files.
    public function fwduvp_enqueue_admin_files($hook){

    	// General settings.
    	if($hook == 'toplevel_page_fwduvp-menu-general-settings'){
    		wp_enqueue_style("fwduvp_spectrum", esc_url_raw($this->_dir_url) . "css/spectrum.css", array(), FWDUVP::VERSION);
	    	wp_enqueue_style("fwduvp_general_settings", esc_url_raw($this->_dir_url) . "css/general_settings.css", array(), FWDUVP::VERSION);
			wp_enqueue_style("fwduvp_fwd_ui", esc_url_raw($this->_dir_url). "css/fwd_ui.css", array(), '1.10.4');
			wp_enqueue_script("fwduvp_fwdtooltip", esc_url_raw($this->_dir_url) . "js/fwdtooltip.js", array(), '1.0', true);
			wp_enqueue_script("fwduvp_spectrum", esc_url_raw($this->_dir_url) . "js/spectrum.js", array(), FWDUVP::VERSION, true);
			wp_enqueue_script("fwduvp_pro_feature_popup", esc_url_raw($this->_dir_url) . "js/FWDUVPProFeaturePopup.js", array(), FWDUVP::VERSION, true);
			wp_enqueue_script("jquery-ui-tabs");			
			wp_enqueue_media();
			wp_enqueue_script("fwduvp", esc_url_raw($this->_dir_url) . "js/FWDUVP.js", array(), FWDUVP::VERSION, true);
			wp_enqueue_script("fwduvp_message", esc_url_raw($this->_dir_url) . "js/FWDUVPMessage.js", array(), FWDUVP::VERSION, true);
			wp_enqueue_script("fwduvp_general_settings", esc_url_raw($this->_dir_url) . "js/general_settings.js", array(), FWDUVP::VERSION, true);

		// Playlist manager.
    	}else if($hook == 'ultimate-video-player_page_fwduvp-menu-playlists-manager'){
    		wp_enqueue_style("fwduvp_fwd_ui_css", esc_url_raw($this->_dir_url) . "css/fwd_ui.css", array(), '1.10.4');
    		wp_enqueue_style("fwduvp_playlist_manager", esc_url_raw($this->_dir_url) . "css/playlist_manager.css", array(), FWDUVP::VERSION);
			wp_enqueue_script("fwduvp_fwdtooltip", esc_url_raw($this->_dir_url) . "js/fwdtooltip.js", array(), '1.0', true);
			wp_enqueue_script("fwduvp_message", esc_url_raw($this->_dir_url) . "js/FWDUVPMessage.js", array(), FWDUVP::VERSION, true);
			wp_enqueue_script("fwduvp_pro_feature_popup", esc_url_raw($this->_dir_url) . "js/FWDUVPProFeaturePopup.js", array(), FWDUVP::VERSION, true);
			wp_enqueue_script("jquery-ui-sortable");
			wp_enqueue_script("jquery-ui-accordion");
			wp_enqueue_script("jquery-ui-dialog");
			wp_enqueue_media();
			wp_enqueue_script("fwduvp", esc_url_raw($this->_dir_url) . "js/FWDUVP.js", array(), FWDUVP::VERSION, true);
			wp_enqueue_script("fwduvp_playlist_manager", esc_url_raw($this->_dir_url) . "js/playlist_manager.js", array('fwduvp_pro_feature_popup'), FWDUVP::VERSION, true);

		// Global advertising.
	    }else if($hook == 'ultimate-video-player_page_fwduvp-menu-global-advertising'){
			$global_advertising_ver = FWDUVP::VERSION;
			$global_advertising_file = plugin_dir_path(dirname(__FILE__)) . 'js/global_advertising.js';
			if(file_exists($global_advertising_file)){
				$global_advertising_ver = (string) filemtime($global_advertising_file);
			}

			wp_enqueue_style("fwduvp_fwd_ui_css", esc_url_raw($this->_dir_url) . "css/fwd_ui.css", array(), '1.10.4');
			wp_enqueue_style("fwduvp_playlist_manager", esc_url_raw($this->_dir_url) . "css/playlist_manager.css", array(), FWDUVP::VERSION);
			wp_enqueue_script("fwduvp_fwdtooltip", esc_url_raw($this->_dir_url) . "js/fwdtooltip.js", array(), '1.0', true);
			wp_enqueue_script("fwduvp_message", esc_url_raw($this->_dir_url) . "js/FWDUVPMessage.js", array(), FWDUVP::VERSION, true);
			wp_enqueue_script("fwduvp_pro_feature_popup", esc_url_raw($this->_dir_url) . "js/FWDUVPProFeaturePopup.js", array(), FWDUVP::VERSION, true);
			wp_enqueue_script("jquery-ui-sortable");
			wp_enqueue_script("jquery-ui-dialog");
			wp_enqueue_media();
			wp_enqueue_script("fwduvp", esc_url_raw($this->_dir_url) . "js/FWDUVP.js", array(), FWDUVP::VERSION, true);
			wp_enqueue_script("fwduvp_global_advertising", esc_url_raw($this->_dir_url) . "js/global_advertising.js", array('fwduvp_pro_feature_popup'), $global_advertising_ver, true);

    	// Shortcode.
    	}else if($hook == 'post.php' || $hook == 'post-new.php'){
    		wp_enqueue_style("fwduvp_schortcode", esc_url_raw($this->_dir_url) . "css/shortcode.css", array(), FWDUVP::VERSION);
    		wp_enqueue_style("fwduvp_fwd_ui_css", esc_url_raw($this->_dir_url) . "css/fwd_ui.css", array(), '1.10.4');
			wp_enqueue_script("fwduvp_fwdtooltip", esc_url_raw($this->_dir_url) . "js/fwdtooltip.js", array(), '1.0', true);
			wp_enqueue_script("fwduvp_pro_feature_popup", esc_url_raw($this->_dir_url) . "js/FWDUVPProFeaturePopup.js", array(), FWDUVP::VERSION, true);
			wp_enqueue_script("fwduvp_shortcode_script", esc_url_raw($this->_dir_url) . "js/shortcode.js", array(), FWDUVP::VERSION, true);
    	}
    }


    // Add front js and css.
    public function fwduvp_add_scripts_and_styles(){
		global $post;
     	if(empty($post)) return;

		$shortcode_found = false;
       	if(has_shortcode($post->post_content, 'fwd-ultimate-video-player')){
          	$shortcode_found = true;
       	}else if(isset($post->ID)){ 
			$post_meta = get_post_meta($post->ID);
			if(!empty($post_meta) && is_array($post_meta)){
				foreach($post_meta as $meta_values){
					foreach((array)$meta_values as $meta_value){
						if(is_string($meta_value) && strpos($meta_value, 'fwduvp') !== false){
							$shortcode_found = true;
							break 2;
						}
					}
				}
			}
       	}
		
		// Uncomment this to add the front requires js/css files only if the shortcode is found in the page/post.
		//if(!empty($shortcode_found)){
		if(!preg_match('/acora/', FWDUVP_TEXT_DOMAIN)){
			wp_enqueue_style("fwduvp", esc_url_raw($this->_dir_url) . "css/fwduvp.css", array(), FWDUVP::VERSION);
		} 
		wp_enqueue_script("fwduvp", esc_url_raw($this->_dir_url) . "js/FWDUVP.js", array(), FWDUVP::VERSION, true);
		//}	
	}


 	// Check WP version.   
	private function fwduvp_check_wp_ver(){
	    global $wp_version;
	    
		$exit_msg = "The Ultimate Video Player plugin requires WordPress " . FWDUVP::MIN_WP_VER . " or newer. <a href='http://codex.wordpress.org/Updating_WordPress'>Please update!</a>";
		
		if (version_compare($wp_version, FWDUVP::MIN_WP_VER) <= 0){
			echo wp_kses_post($exit_msg);
			return false;
		}
		return true;
	}


	// Set general settings.
    public function fwduvp_set_general_settings($hook){
    	if (!$this->fwduvp_check_wp_ver()){
    		return;
    	}
    	
    	$msg = "";
    	$set_id = 0;
		$set_order_id = 0;
		$tab_init_id = 0;
		$tootlTipImgSrc = esc_url_raw($this->_dir_url . "content/icons/help-icon.png"); 
		$fwduvpIconsPath =  esc_url_raw($this->_dir_url . "content/icons/");
    	
	    if (!empty($_POST) && check_admin_referer("fwduvp_general_settings_update", "fwduvp_general_settings_nonce")){
			$raw_settings_data = isset($_POST['settings_data']) ? sanitize_textarea_field(wp_unslash($_POST['settings_data'])) : '';
			$data_obj = json_decode(str_replace("\\", "", $raw_settings_data), true);
			$save_result = $this->fwduvp_save_general_settings_data($data_obj);

			if(!empty($save_result)){
				$msg = $save_result['msg'];
				$set_id = $save_result['set_id'];
				$set_order_id = $save_result['set_order_id'];
				$tab_init_id = $save_result['tab_init_id'];
			}
		}
		
		// Add and escape required js vars.
        $vars = 'var fwduvpSettingsAr = ' . '"' . esc_html(htmlspecialchars(json_encode($this->_data->settings_ar))) . '";';
        $vars .= 'var fwduvpVideoStartBehaviour = ' . '"' . esc_html(htmlspecialchars(json_encode($this->_data->videoStartBehaviour))) . '";';
        $vars .= 'var fwduvpTextDomain = ' . '"' . esc_html(FWDUVP_TEXT_DOMAIN) . '";';
        $vars .= 'var fwduvpSpacesUrl = ' . '"' . esc_url_raw($this->_dir_url . "content/spaces/") . '";';
		$vars .= 'var fwduvpAjaxUrl = ' . '"' . esc_url_raw(admin_url('admin-ajax.php')) . '";';
		$vars .= 'var fwduvpGeneralSettingsAjaxNonce = ' . '"' . esc_html(wp_create_nonce('fwduvp_general_settings_ajax_nonce')) . '";';
        $vars .= 'var fwduvpSetId = ' .  esc_html($set_id) .';';
    	$vars .= 'var fwduvpCurOrderId = ' .  esc_html($set_order_id) .';';
    	$vars .= 'var fwduvpCurTabId = ' . esc_html($tab_init_id) .';';
        wp_add_inline_script('fwduvp_general_settings', $vars);

    	include_once "general_settings.php";
    }


	// Save preset data.
	private function fwduvp_save_general_settings_data($data_obj){
		if(empty($data_obj) || empty($data_obj['action']) || !isset($data_obj['settings_ar'])){
			return null;
		}

		$action = sanitize_text_field($data_obj['action']);
		$fwduvpSettingsAr = $data_obj['settings_ar'];
		$fwduvpVideoStartBehaviour = '';
		if(isset($data_obj['fwduvpVideoStartBehaviour'])){
			$fwduvpVideoStartBehaviour = sanitize_text_field($data_obj['fwduvpVideoStartBehaviour']);
		}

		// Validate input.
		foreach ($fwduvpSettingsAr as $key => $value) {

			if(!empty($fwduvpSettingsAr[$key]["autoPlayText"])){
				$fwduvpSettingsAr[$key]["autoPlayText"] = sanitize_text_field($fwduvpSettingsAr[$key]["autoPlayText"]);
			}

			if(!empty($fwduvpSettingsAr[$key]["googleAnalyticsMeasurementId"])){
				$fwduvpSettingsAr[$key]["googleAnalyticsMeasurementId"] = sanitize_text_field($fwduvpSettingsAr[$key]["googleAnalyticsMeasurementId"]);
			}

			if(!empty($fwduvpSettingsAr[$key]["subtitles_off_label"])){
				$fwduvpSettingsAr[$key]["subtitles_off_label"] = sanitize_text_field($this->fwduvp_clean_name($fwduvpSettingsAr[$key]["subtitles_off_label"]));
			}

			if(!empty($fwduvpSettingsAr[$key]["privateVideoPassword"])){
				$fwduvpSettingsAr[$key]["privateVideoPassword"] = sanitize_text_field($fwduvpSettingsAr[$key]["privateVideoPassword"]);
			}

			if(!empty($fwduvpSettingsAr[$key]["loggedInMessage"])){
				$fwduvpSettingsAr[$key]["loggedInMessage"] = esc_html($fwduvpSettingsAr[$key]["loggedInMessage"]);
			}
			if(!empty($fwduvpSettingsAr[$key]["logo_link"])){
				$fwduvpSettingsAr[$key]["logo_link"] = sanitize_text_field($fwduvpSettingsAr[$key]["logo_link"]);
			}
			if(!empty($fwduvpSettingsAr[$key]["logo_path"])){
				$fwduvpSettingsAr[$key]["logo_path"] = sanitize_text_field($fwduvpSettingsAr[$key]["logo_path"]);
			}
			if(!empty($fwduvpSettingsAr[$key]["skip_to_video_text"])){
				$fwduvpSettingsAr[$key]["skip_to_video_text"] = sanitize_text_field($fwduvpSettingsAr[$key]["skip_to_video_text"]);
			}
			if(!empty($fwduvpSettingsAr[$key]["skip_to_video_button_text"])){
				$fwduvpSettingsAr[$key]["skip_to_video_button_text"] = sanitize_text_field($fwduvpSettingsAr[$key]["skip_to_video_button_text"]);
			}
			if(!empty($fwduvpSettingsAr[$key]["aopwTitle"])){
				$fwduvpSettingsAr[$key]["aopwTitle"] = sanitize_text_field($fwduvpSettingsAr[$key]["aopwTitle"]);
			}
			if(!empty($fwduvpSettingsAr[$key]["uvp_mainBackgroundImagePath"])){
				$fwduvpSettingsAr[$key]["uvp_mainBackgroundImagePath"] = sanitize_text_field($fwduvpSettingsAr[$key]["uvp_mainBackgroundImagePath"]);
			}
		}

		$this->_data->settings_ar = $fwduvpSettingsAr;
		$this->_data->videoStartBehaviour = $fwduvpVideoStartBehaviour;
		$this->_data->set_data();

		$result = array(
			'msg' => '',
			'set_id' => 0,
			'set_order_id' => 0,
			'tab_init_id' => 0,
		);

		switch ($action){
			case "add":
				$result['msg'] = esc_html__("Your new preset has been added!", 'fwd-ultimate-video-player');
				$result['set_id'] = isset($data_obj['set_id']) ? intval($data_obj['set_id']) : 0;
				$result['set_order_id'] = isset($data_obj['set_order_id']) ? intval($data_obj['set_order_id']) : 0;
				$result['tab_init_id'] = isset($data_obj['fwduvpCurTabId']) ? intval($data_obj['fwduvpCurTabId']) : 0;
				break;
			case "save":
				$result['msg'] = esc_html__("Your preset settings have been updated!", 'fwd-ultimate-video-player');
				$result['set_id'] = isset($data_obj['set_id']) ? intval($data_obj['set_id']) : 0;
				$result['set_order_id'] = isset($data_obj['set_order_id']) ? intval($data_obj['set_order_id']) : 0;
				$result['tab_init_id'] = isset($data_obj['fwduvpCurTabId']) ? intval($data_obj['fwduvpCurTabId']) : 0;
				break;
			case "del":
				$result['msg'] = esc_html__("Your preset has been deleted!", 'fwd-ultimate-video-player');
				break;
		}

		return $result;
	}


	// Ajax save presets.
	public function fwduvp_update_presets_ajax(){
		if(!current_user_can(FWDUVP::CAPABILITY)){
			wp_send_json_error(array('msg' => esc_html__('You are not allowed to do this action.', 'fwd-ultimate-video-player')));
		}

		check_ajax_referer('fwduvp_general_settings_ajax_nonce', 'nonce');

		$raw_settings_data = isset($_POST['settings_data']) ? sanitize_textarea_field(wp_unslash($_POST['settings_data'])) : '';
		$data_obj = json_decode($raw_settings_data, true);
		$save_result = $this->fwduvp_save_general_settings_data($data_obj);

		if(empty($save_result)){
			wp_send_json_error(array('msg' => esc_html__('Failed to save preset settings.', 'fwd-ultimate-video-player')));
		}

		wp_send_json_success($save_result);
	}

	// Save playlist manager data.
	private function fwduvp_save_playlist_manager_data($fwduvpMainPlaylistsAr){
		if(!is_array($fwduvpMainPlaylistsAr)){
			return false;
		}
		$mainPlaylistNames = array();

		foreach($fwduvpMainPlaylistsAr as &$mainPlaylist){

			if(!empty($mainPlaylist['name'])){
				$mainPlaylist['name'] = sanitize_text_field($this->fwduvp_clean_name($mainPlaylist['name']));
				$normalizedMainPlaylistName = strtolower(trim($mainPlaylist['name']));
				if(in_array($normalizedMainPlaylistName, $mainPlaylistNames, true)){
					return false;
				}
				$mainPlaylistNames[] = $normalizedMainPlaylistName;
			}

			$playlistNames = array();
			foreach($mainPlaylist['playlists'] as &$playlist){
				
				if(!empty($playlist['password'])){
					$playlist['password'] = sanitize_text_field($playlist['password']);
				}
				if(!empty($playlist['name'])){
					$playlist['name'] = sanitize_text_field($this->fwduvp_clean_name($playlist['name']));
					$normalizedPlaylistName = strtolower(trim($playlist['name']));
					if(in_array($normalizedPlaylistName, $playlistNames, true)){
						return false;
					}
					$playlistNames[] = $normalizedPlaylistName;
				}

				if(!empty($playlist['source'])){
					if($playlist['type'] == 'folder'){
						$playlist['source'] = sanitize_text_field($this->fwduvp_clean_folder_name($playlist['source']));
					}else{
						$playlist['source'] = esc_url_raw($playlist['source']);
					}
				}

				if(!empty($playlist['thumb'])){
					$playlist['thumb'] = sanitize_text_field($playlist['thumb']);
				}

				if(!empty($playlist['text'])){
					$playlist['text'] = wp_kses_post($playlist['text']);
				}

				foreach($playlist['videos'] as &$video){
					if(!empty($video['name'])){
						$video['name'] = sanitize_text_field($this->fwduvp_clean_name($video['name']));
					}

					foreach($video['vids_ar'] as &$fvideo){
						if(!empty($fvideo['source'])){
							$fvideo['source'] = esc_url_raw($fvideo['source']);
						}
						if(!empty($fvideo['label'])){
							$fvideo['label'] = sanitize_text_field($this->fwduvp_clean_name($fvideo['label']));
						}
					}

					foreach($video['ads_ar'] as &$fads){
						if(!empty($fads['source'])){
							$fads['source'] = esc_url_raw($fads['source']);
						}
						if(!empty($fads['url'])){
							$fads['url'] = esc_url_raw($fads['url']);
						}
						if(!empty($fads['label'])){
							$fads['label'] = sanitize_text_field($this->fwduvp_clean_name($fads['label']));
						}
					}

					foreach($video['subtitles_ar'] as &$fsubtitles){
						if(!empty($fsubtitles['source'])){
							$fsubtitles['source'] = esc_url_raw($fsubtitles['source']);
						}
						if(!empty($fsubtitles['label'])){
							$fsubtitles['label'] = sanitize_text_field($this->fwduvp_clean_name($fsubtitles['label']));
						}
					}

					foreach($video['popupads_ar'] as &$fpopupads){
						if(!empty($fpopupads['source'])){
							$fpopupads['source'] = esc_url_raw($fpopupads['source']);
						}

						if(!empty($fpopupads['label'])){
							$fpopupads['label'] = sanitize_text_field($this->fwduvp_clean_name($fpopupads['label']));
						}

						if(!empty($fpopupads['url'])){
							$fpopupads['url'] = esc_url_raw($fpopupads['url']);
						}

						if(!empty($fpopupads['google_ad_client'])){
							$fpopupads['google_ad_client'] = esc_html($fpopupads['google_ad_client']);
						}

						if(!empty($fpopupads['google_ad_slot'])){
							$fpopupads['google_ad_slot'] = esc_html($fpopupads['google_ad_slot']);
						}
					}
				
					foreach($video['cuepoints_ar'] as &$fcuepoints){
						if(!empty($fcuepoints['label'])){
							$fcuepoints['label'] = sanitize_text_field($fcuepoints['code']);
						}
						if(!empty($fcuepoints['code'])){
							$fcuepoints['code'] = sanitize_text_field($fcuepoints['code']);
						}
					}
					
					if(!empty($video['popw_label'])){
						$video['popw_label'] = esc_url_raw($this->fwduvp_clean_name($video['popw_label']));
					}

					if(!empty($video['thumb'])){
						$video['thumb'] = sanitize_text_field($video['thumb']);
					}
					
					if(!empty($video['popw'])){
						$video['popw'] = esc_url_raw($video['popw']);
					}

					if(!empty($video['redirectURL'])){
						$video['redirectURL'] = esc_url_raw($video['redirectURL']);
					}

					if(!empty($video['poster'])){
						$video['poster'] = esc_url_raw($video['poster']);
					}	

					if(!empty($video['password'])){
						$video['password'] = sanitize_text_field($video['password']);
					}

					if(!empty($video['vastURL'])){
						$video['vastURL'] = esc_url_raw($video['vastURL']);
					}

					if(!empty($video['short_descr'])){
						$video['short_descr'] = wp_kses_post($video['short_descr']);
					}

					if(!empty($video['long_descr'])){
						$video['long_descr'] = wp_kses_post($video['long_descr']);
					}

					if(!empty($video['thumbnails_preview'])){
						$video['thumbnails_preview'] = sanitize_text_field($video['thumbnails_preview']);
					}
				}
			}
		}
		unset($mainPlaylist);
		unset($playlist);
		unset($video);
		unset($fvideo);
		unset($fsubtitles);
		unset($fcuepoints);
		unset($fpopupads);
		unset($fads);

		$this->_data->main_playlists_ar = $fwduvpMainPlaylistsAr;
		$this->_data->set_data();

		return true;
	}

	// Ajax save playlists.
	public function fwduvp_update_playlists_ajax(){
		if(!current_user_can(FWDUVP::CAPABILITY)){
			wp_send_json_error(array('msg' => esc_html__('You are not allowed to do this action.', 'fwd-ultimate-video-player')));
		}

		check_ajax_referer('fwduvp_playlist_manager_ajax_nonce', 'nonce');

		$raw_playlist_data = isset($_POST['playlist_data']) ? wp_kses_post(wp_unslash($_POST['playlist_data'])) : '';
		$fwduvpMainPlaylistsAr = json_decode($raw_playlist_data, true);

		if(empty($fwduvpMainPlaylistsAr) && !is_array($fwduvpMainPlaylistsAr)){
			wp_send_json_error(array('msg' => esc_html__('Failed to update playlists.', 'fwd-ultimate-video-player')));
		}

		$mainPlaylistNames = array();
		foreach($fwduvpMainPlaylistsAr as $mainPlaylistNameCheck){
			if(empty($mainPlaylistNameCheck['name'])){
				continue;
			}

			$mainPlaylistName = sanitize_text_field($this->fwduvp_clean_name($mainPlaylistNameCheck['name']));
			$normalizedMainPlaylistName = strtolower(trim($mainPlaylistName));
			if(in_array($normalizedMainPlaylistName, $mainPlaylistNames, true)){
				wp_send_json_error(array('msg' => esc_html__('A main playlist with this name already exists.', 'fwd-ultimate-video-player')));
			}
			$mainPlaylistNames[] = $normalizedMainPlaylistName;

			$playlistNames = array();
			if(!empty($mainPlaylistNameCheck['playlists']) && is_array($mainPlaylistNameCheck['playlists'])){
				foreach($mainPlaylistNameCheck['playlists'] as $playlistNameCheck){
					if(empty($playlistNameCheck['name'])){
						continue;
					}

					$playlistName = sanitize_text_field($this->fwduvp_clean_name($playlistNameCheck['name']));
					$normalizedPlaylistName = strtolower(trim($playlistName));
					if(in_array($normalizedPlaylistName, $playlistNames, true)){
						wp_send_json_error(array('msg' => esc_html__('A playlist with this name already exists in this main playlist.', 'fwd-ultimate-video-player')));
					}
					$playlistNames[] = $normalizedPlaylistName;
				}
			}
		}

		if(!$this->fwduvp_save_playlist_manager_data($fwduvpMainPlaylistsAr)){
			wp_send_json_error(array('msg' => esc_html__('Failed to update playlists.', 'fwd-ultimate-video-player')));
		}

		wp_send_json_success(array('msg' => esc_html__('Your playlists have been updated!', 'fwd-ultimate-video-player')));
	}

	private function fwduvp_save_global_ads_data($globalAdsAr, $globalVastData = array()){
		if(!is_array($globalAdsAr)){
			return false;
		}

		$sanitizedGlobalAds = array();
		foreach($globalAdsAr as $ad){
			if(!is_array($ad)){
				continue;
			}

			$sanitizedGlobalAds[] = array(
				'id' => isset($ad['id']) ? intval($ad['id']) : 0,
				'label' => isset($ad['label']) ? sanitize_text_field($this->fwduvp_clean_name($ad['label'])) : '',
				'source' => isset($ad['source']) ? esc_url_raw($ad['source']) : '',
				'url' => isset($ad['url']) ? esc_url_raw($ad['url']) : '',
				'target' => isset($ad['target']) && $ad['target'] === '_self' ? '_self' : '_blank',
				'startTime' => isset($ad['startTime']) ? sanitize_text_field($ad['startTime']) : '00:00:00',
				'timeToHoldAd' => isset($ad['timeToHoldAd']) ? intval($ad['timeToHoldAd']) : 0,
				'addDuration' => isset($ad['addDuration']) ? sanitize_text_field($ad['addDuration']) : '00:00:00'
			);
		}

		$this->_data->global_ads_ar = $sanitizedGlobalAds;
		$this->_data->global_vast_url = isset($globalVastData['url']) ? esc_url_raw($globalVastData['url']) : '';
		$this->_data->global_vast_target = isset($globalVastData['target']) && $globalVastData['target'] === '_self' ? '_self' : '_blank';
		$this->_data->global_vast_start_time = isset($globalVastData['startTime']) ? sanitize_text_field($globalVastData['startTime']) : '00:00:00';
		$this->_data->global_pause_ads_source = isset($globalVastData['pauseAdsSource']) ? esc_url_raw($globalVastData['pauseAdsSource']) : '';
		$this->_data->global_apply_to_youtube_videos = isset($globalVastData['applyToYoutubeVideos']) && $globalVastData['applyToYoutubeVideos'] === 'yes' ? 'yes' : 'no';
		$this->_data->set_data();

		return true;
	}

	private function fwduvp_save_global_popup_ads_data($globalPopupAdsAr){
		if(!is_array($globalPopupAdsAr)){
			return false;
		}

		$sanitizedPopupAds = array();
		foreach($globalPopupAdsAr as $ad){
			if(!is_array($ad)){
				continue;
			}

			$type = isset($ad['type']) && $ad['type'] === 'adsense' ? 'adsense' : 'image';
			$sanitizedPopupAds[] = array(
				'id' => isset($ad['id']) ? intval($ad['id']) : 0,
				'type' => $type,
				'label' => isset($ad['label']) ? sanitize_text_field($this->fwduvp_clean_name($ad['label'])) : '',
				'source' => isset($ad['source']) ? esc_url_raw($ad['source']) : '',
				'url' => isset($ad['url']) ? esc_url_raw($ad['url']) : '',
				'target' => isset($ad['target']) && $ad['target'] === '_self' ? '_self' : '_blank',
				'startTime' => isset($ad['startTime']) ? sanitize_text_field($ad['startTime']) : '00:00:00',
				'stopTime' => isset($ad['stopTime']) ? sanitize_text_field($ad['stopTime']) : '00:00:10',
				'google_ad_client' => isset($ad['google_ad_client']) ? sanitize_text_field($ad['google_ad_client']) : '',
				'google_ad_slot' => isset($ad['google_ad_slot']) ? sanitize_text_field($ad['google_ad_slot']) : '',
				'google_ad_width' => isset($ad['google_ad_width']) ? intval($ad['google_ad_width']) : 0,
				'google_ad_height' => isset($ad['google_ad_height']) ? intval($ad['google_ad_height']) : 0,
				'google_ad_start_time' => isset($ad['google_ad_start_time']) ? sanitize_text_field($ad['google_ad_start_time']) : '00:00:00',
				'google_ad_stop_time' => isset($ad['google_ad_stop_time']) ? sanitize_text_field($ad['google_ad_stop_time']) : '00:00:10'
			);
		}

		$this->_data->global_popup_ads_ar = $sanitizedPopupAds;
		$this->_data->set_data();

		return true;
	}

	public function fwduvp_update_global_ads_ajax(){
		if(!current_user_can(FWDUVP::CAPABILITY)){
			wp_send_json_error(array('msg' => esc_html__('You are not allowed to do this action.', 'fwd-ultimate-video-player')));
		}

		check_ajax_referer('fwduvp_global_ads_ajax_nonce', 'nonce');

		$raw_ads_data = isset($_POST['ads_data']) ? sanitize_textarea_field(wp_unslash($_POST['ads_data'])) : '';
		$globalAdsAr = json_decode($raw_ads_data, true);
		$globalVastData = array(
			'url' => isset($_POST['global_vast_url']) ? sanitize_text_field(wp_unslash($_POST['global_vast_url'])) : '',
			'target' => isset($_POST['global_vast_target']) ? sanitize_text_field(wp_unslash($_POST['global_vast_target'])) : '_blank',
			'startTime' => isset($_POST['global_vast_start_time']) ? sanitize_text_field(wp_unslash($_POST['global_vast_start_time'])) : '00:00:00',
			'pauseAdsSource' => isset($_POST['global_pause_ads_source']) ? sanitize_text_field(wp_unslash($_POST['global_pause_ads_source'])) : '',
			'applyToYoutubeVideos' => isset($_POST['global_apply_to_youtube_videos']) ? sanitize_text_field(wp_unslash($_POST['global_apply_to_youtube_videos'])) : 'no'
		);

		if(!is_array($globalAdsAr)){
			wp_send_json_error(array('msg' => esc_html__('Failed to update global advertisements.', 'fwd-ultimate-video-player')));
		}

		if(!$this->fwduvp_save_global_ads_data($globalAdsAr, $globalVastData)){
			wp_send_json_error(array('msg' => esc_html__('Failed to update global advertisements.', 'fwd-ultimate-video-player')));
		}

		wp_send_json_success(array('msg' => esc_html__('Global advertisements have been updated!', 'fwd-ultimate-video-player')));
	}

	public function fwduvp_update_global_popup_ads_ajax(){
		if(!current_user_can(FWDUVP::CAPABILITY)){
			wp_send_json_error(array('msg' => esc_html__('You are not allowed to do this action.', 'fwd-ultimate-video-player')));
		}

		check_ajax_referer('fwduvp_global_popup_ads_ajax_nonce', 'nonce');

		$raw_ads_data = isset($_POST['popup_ads_data']) ? sanitize_textarea_field(wp_unslash($_POST['popup_ads_data'])) : '';
		$globalPopupAdsAr = json_decode($raw_ads_data, true);

		if(!is_array($globalPopupAdsAr)){
			wp_send_json_error(array('msg' => esc_html__('Failed to update global pop-up advertisements.', 'fwd-ultimate-video-player')));
		}

		if(!$this->fwduvp_save_global_popup_ads_data($globalPopupAdsAr)){
			wp_send_json_error(array('msg' => esc_html__('Failed to update global pop-up advertisements.', 'fwd-ultimate-video-player')));
		}

		wp_send_json_success(array('msg' => esc_html__('Global pop-up advertisements have been updated!', 'fwd-ultimate-video-player')));
	}

	public function fwduvp_set_global_advertising(){
		if (!$this->fwduvp_check_wp_ver()){
			return;
		}

		$tootlTipImgSrc = esc_url_raw($this->_dir_url . "content/icons/help-icon.png");
		$vars = 'var fwduvpGlobalAdsAr = ' . wp_json_encode($this->_data->global_ads_ar) . ';';
		$vars .= 'var fwduvpGlobalPopupAdsAr = ' . wp_json_encode($this->_data->global_popup_ads_ar) . ';';
		$vars .= 'var fwduvpGlobalVastURL = ' . wp_json_encode(isset($this->_data->global_vast_url) ? $this->_data->global_vast_url : '') . ';';
		$vars .= 'var fwduvpGlobalVastTarget = ' . wp_json_encode(isset($this->_data->global_vast_target) ? $this->_data->global_vast_target : '_blank') . ';';
		$vars .= 'var fwduvpGlobalVastStartTime = ' . wp_json_encode(isset($this->_data->global_vast_start_time) ? $this->_data->global_vast_start_time : '00:00:00') . ';';
		$vars .= 'var fwduvpGlobalPauseAdsSource = ' . wp_json_encode(isset($this->_data->global_pause_ads_source) ? $this->_data->global_pause_ads_source : '') . ';';
		$vars .= 'var fwduvpGlobalApplyToYoutubeVideos = ' . wp_json_encode(isset($this->_data->global_apply_to_youtube_videos) ? $this->_data->global_apply_to_youtube_videos : 'no') . ';';
		$vars .= 'var fwduvpAdd__ = "' . esc_html__('Add', 'fwd-ultimate-video-player') . '";';
		$vars .= 'var fwduvpUpdate__ = "' . esc_html__('Update', 'fwd-ultimate-video-player') . '";';
		$vars .= 'var fwduvpDelete__ = "' . esc_html__('Delete', 'fwd-ultimate-video-player') . '";';
		$vars .= 'var fwduvpEdit__ = "' . esc_html__('Edit', 'fwd-ultimate-video-player') . '";';
		$vars .= 'var fwduvpYes__ = "' . esc_html__('Yes', 'fwd-ultimate-video-player') . '";';
		$vars .= 'var fwduvpNo__ = "' . esc_html__('No', 'fwd-ultimate-video-player') . '";';
		$vars .= 'var fwduvpCancel__ = "' . esc_html__('Cancel', 'fwd-ultimate-video-player') . '";';
		$vars .= 'var fwduvpUpgradeToProMessage__ = "";';
		$vars .= 'var fwduvpCanShowProMessage = false;';
		$vars .= 'var fwduvpUpgradeToProUrl = "";';
		$vars .= 'var fwduvpAjaxUrl = "' . esc_url_raw(admin_url('admin-ajax.php')) . '";';
		$vars .= 'var fwduvpGlobalAdsAjaxNonce = "' . esc_html(wp_create_nonce('fwduvp_global_ads_ajax_nonce')) . '";';
		$vars .= 'var fwduvpGlobalPopupAdsAjaxNonce = "' . esc_html(wp_create_nonce('fwduvp_global_popup_ads_ajax_nonce')) . '";';

		wp_add_inline_script('fwduvp_global_advertising', $vars, 'before');

		include_once "global_advertising.php";
	}
    

    // Set playlist settings.
 	public function fwduvp_set_playlists_manager(){
    	if (!$this->fwduvp_check_wp_ver()){
    		return;
    	}
    	
    	$msg = "";
    	$tootlTipImgSrc = esc_url_raw($this->_dir_url . "content/icons/help-icon.png");
    	
	    if (!empty($_POST) && check_admin_referer("fwduvp_playlist_manager_update", "fwduvp_playlist_manager_nonce")){
			$raw_playlist_data = isset($_POST['playlist_data']) ? wp_kses_post(wp_unslash($_POST['playlist_data'])) : '';
			$fwduvpMainPlaylistsAr = json_decode(str_replace("\\", "", $raw_playlist_data), true);
			$mainPlaylistNames = array();
			$duplicateMainPlaylistNameFound = false;
			$duplicatePlaylistNameFound = false;
			
			foreach($fwduvpMainPlaylistsAr as $mainPlaylistNameCheck){
				if(empty($mainPlaylistNameCheck['name'])){
					continue;
				}

				$mainPlaylistName = sanitize_text_field($this->fwduvp_clean_name($mainPlaylistNameCheck['name']));
				$normalizedMainPlaylistName = strtolower(trim($mainPlaylistName));
				if(in_array($normalizedMainPlaylistName, $mainPlaylistNames, true)){
					$duplicateMainPlaylistNameFound = true;
					break;
				}
				$mainPlaylistNames[] = $normalizedMainPlaylistName;

				$playlistNames = array();
				if(!empty($mainPlaylistNameCheck['playlists']) && is_array($mainPlaylistNameCheck['playlists'])){
					foreach($mainPlaylistNameCheck['playlists'] as $playlistNameCheck){
						if(empty($playlistNameCheck['name'])){
							continue;
						}

						$playlistName = sanitize_text_field($this->fwduvp_clean_name($playlistNameCheck['name']));
						$normalizedPlaylistName = strtolower(trim($playlistName));
						if(in_array($normalizedPlaylistName, $playlistNames, true)){
							$duplicatePlaylistNameFound = true;
							break 2;
						}
						$playlistNames[] = $normalizedPlaylistName;
					}
				}
			}

			if($duplicateMainPlaylistNameFound){
				$msg = esc_html__('A main playlist with this name already exists.', 'fwd-ultimate-video-player');
			}else if($duplicatePlaylistNameFound){
				$msg = esc_html__('A playlist with this name already exists in this main playlist.', 'fwd-ultimate-video-player');
			}else{
			
			// Validate input.
			foreach($fwduvpMainPlaylistsAr as &$mainPlaylist){

				if(!empty($mainPlaylist['name'])){
					$mainPlaylist['name'] = sanitize_text_field($this->fwduvp_clean_name($mainPlaylist['name']));
				}

				foreach($mainPlaylist['playlists'] as &$playlist){
					
					if(!empty($playlist['password'])){
						$playlist['password'] = sanitize_text_field($playlist['password']);
					}
					if(!empty($playlist['name'])){
						$playlist['name'] = sanitize_text_field($this->fwduvp_clean_name($playlist['name']));
					}

					if(!empty($playlist['source'])){
						if($playlist['type'] == 'folder'){
							$playlist['source'] = sanitize_text_field($this->fwduvp_clean_folder_name($playlist['source']));
						}else{
							$playlist['source'] = esc_url_raw($playlist['source']);
						}
					}

					if(!empty($playlist['thumb'])){
						$playlist['thumb'] = sanitize_text_field($playlist['thumb']);
					}

					if(!empty($playlist['text'])){
						$playlist['text'] = wp_kses_post($playlist['text']);
					}

					foreach($playlist['videos'] as &$video){
						if(!empty($video['name'])){
							$video['name'] = sanitize_text_field($this->fwduvp_clean_name($video['name']));
						}

						foreach($video['vids_ar'] as &$fvideo){
							if(!empty($fvideo['source'])){
								$fvideo['source'] = esc_url_raw($fvideo['source']);
							}
							if(!empty($fvideo['label'])){
								$fvideo['label'] = sanitize_text_field($this->fwduvp_clean_name($fvideo['label']));
							}
						}

						foreach($video['ads_ar'] as &$fads){
							if(!empty($fads['source'])){
								$fads['source'] = esc_url_raw($fads['source']);
							}
							if(!empty($fads['url'])){
								$fads['url'] = esc_url_raw($fads['url']);
							}
							if(!empty($fads['label'])){
								$fads['label'] = sanitize_text_field($this->fwduvp_clean_name($fads['label']));
							}
						}

						foreach($video['subtitles_ar'] as &$fsubtitles){
							if(!empty($fsubtitles['source'])){
								$fsubtitles['source'] = esc_url_raw($fsubtitles['source']);
							}
							if(!empty($fsubtitles['label'])){
								$fsubtitles['label'] = sanitize_text_field($this->fwduvp_clean_name($fsubtitles['label']));
							}
						}

						foreach($video['popupads_ar'] as &$fpopupads){
							if(!empty($fpopupads['source'])){
								$fpopupads['source'] = esc_url_raw($fpopupads['source']);
							}

							if(!empty($fpopupads['label'])){
								$fpopupads['label'] = sanitize_text_field($this->fwduvp_clean_name($fpopupads['label']));
							}

							if(!empty($fpopupads['url'])){
								$fpopupads['url'] = esc_url_raw($fpopupads['url']);
							}

							if(!empty($fpopupads['google_ad_client'])){
								$fpopupads['google_ad_client'] = esc_html($fpopupads['google_ad_client']);
							}

							if(!empty($fpopupads['google_ad_slot'])){
								$fpopupads['google_ad_slot'] = esc_html($fpopupads['google_ad_slot']);
							}
						}
					
						foreach($video['cuepoints_ar'] as &$fcuepoints){
							if(!empty($fcuepoints['label'])){
								$fcuepoints['label'] = sanitize_text_field($fcuepoints['code']);
							}
							if(!empty($fcuepoints['code'])){
								$fcuepoints['code'] = sanitize_text_field($fcuepoints['code']);
							}
						}
						
						if(!empty($video['popw_label'])){
							$video['popw_label'] = esc_url_raw($this->fwduvp_clean_name($video['popw_label']));
						}

						if(!empty($video['thumb'])){
							$video['thumb'] = sanitize_text_field($video['thumb']);
						}
						
						if(!empty($video['popw'])){
							$video['popw'] = esc_url_raw($video['popw']);
						}

						if(!empty($video['redirectURL'])){
							$video['redirectURL'] = esc_url_raw($video['redirectURL']);
						}

						if(!empty($video['poster'])){
							$video['poster'] = esc_url_raw($video['poster']);
						}	

						if(!empty($video['password'])){
							$video['password'] = sanitize_text_field($video['password']);
						}

						if(!empty($video['vastURL'])){
							$video['vastURL'] = esc_url_raw($video['vastURL']);
						}

						if(!empty($video['short_descr'])){
							$video['short_descr'] = wp_kses_post($video['short_descr']);
						}

						if(!empty($video['long_descr'])){
							$video['long_descr'] = wp_kses_post($video['long_descr']);
						}

						if(!empty($video['thumbnails_preview'])){
							$video['thumbnails_preview'] = sanitize_text_field($video['thumbnails_preview']);
						}
					}
				}
			}
			unset($mainPlaylist);
			unset($playlist);
			unset($video);
			unset($fvideo);
			unset($fsubtitles);
			unset($fcuepoints);
			unset($fpopupads);
			unset($fads);
			
			$this->_data->main_playlists_ar = $fwduvpMainPlaylistsAr;
			$this->_data->set_data();
			$msg = esc_html__("Your playlists have been updated!", 'fwd-ultimate-video-player');
		}
		}

		// Add and escape required js vars.
        $vars = 'var fwduvpMainPlaylistsAr = ' . '"' . esc_html(htmlspecialchars(json_encode($this->_data->main_playlists_ar))) . '";';
        $vars .= 'var fwduvpIconsPath = ' . '"' . esc_url_raw($this->_dir_url) . "content/icons/" . '";';
        $vars .= 'var fwduvpAddNewVideo__ = ' . '"' . esc_html__('Add new video', 'fwd-ultimate-video-player') . '";';
        $vars .= 'var fwduvpEdit__ = ' . '"' . esc_html__('Edit', 'fwd-ultimate-video-player') . '";';
        $vars .= 'var fwduvpDelete__ = ' . '"' . esc_html__('Delete', 'fwd-ultimate-video-player') . '";';
        $vars .= 'var fwduvpDuplicatePlaylist__ = ' . '"' . esc_html__('Duplicate playlist', 'fwd-ultimate-video-player') . '";';
        $vars .= 'var fwduvpAddNewPlaylist__ = ' . '"' . esc_html__('Add new playlist', 'fwd-ultimate-video-player') . '";';
        $vars .= 'var fwduvpUpdate__ = ' . '"' . esc_html__('Update', 'fwd-ultimate-video-player') . '";';
        $vars .= 'var fwduvpAdd__ = ' . '"' . esc_html__('Add', 'fwd-ultimate-video-player') . '";';
        $vars .= 'var fwduvpCancel__ = ' . '"' . esc_html__('Cancel', 'fwd-ultimate-video-player') . '";';
        $vars .= 'var fwduvpYes__ = ' . '"' . esc_html__('Yes', 'fwd-ultimate-video-player') . '";';
        $vars .= 'var fwduvpNo__ = ' . '"' . esc_html__('No', 'fwd-ultimate-video-player') . '";';
        $vars .= 'var fwduvpYoutubeInfo__ = ' . '"' . esc_html__('The source must be a youtube playlist or youtube channel URL.', 'fwd-ultimate-video-player') . '";';
        $vars .= 'var fwduvpFolderInfo__ = ' . '"' . esc_html__('The source represents the relative path to a folder containing only MP4 files that must be a subfolder of the \'content\' folder contained in the plugin directory \'wp-content/plugins/fwduvp\'.', 'fwd-ultimate-video-player') . '";';
        $vars .= 'var fwduvpXmlInfo__ = ' . '"' . esc_html__('The source represents the absolute path of an XML file that contains a formatted XML playlist. You can get the file example from the plugin main zip file or from the following URL  http://webdesign-flash.ro/w/uvp/content/playlist_dark.xml.', 'fwd-ultimate-video-player') . '";';
        $vars .= 'var fwduvpVideoTip__ = ' . '"' . esc_html__('The video name, source and thumbnail path fields are required.', 'fwd-ultimate-video-player') . '";';
        $vars .= 'var fwduvpVideoOneSource__ = ' . '"' . esc_html__('Please make sure at least one video source is added.', 'fwd-ultimate-video-player') . '";';
        $vars .= 'var fwduvpPlaylistNameRequired__ = ' . '"' . esc_html__('The playlist name is required (and also the playlist source if the type is not normal).', 'fwd-ultimate-video-player') . '";';
		$vars .= 'var fwduvpMainPlaylistDuplicateName__ = ' . '"' . esc_html__('A main playlist with this name already exists.', 'fwd-ultimate-video-player') . '";';
		$vars .= 'var fwduvpPlaylistDuplicateName__ = ' . '"' . esc_html__('A playlist with this name already exists in this main playlist.', 'fwd-ultimate-video-player') . '";';
		$vars .= 'var fwduvpUpgradeToProMessage__ = "";';
		$vars .= 'var fwduvpHlsRequiresPro__ = "";';
		$vars .= 'var fwduvpCanShowProMessage = false;';
		$vars .= 'var fwduvpUpgradeToProUrl = "";';
		$vars .= 'var fwduvpAjaxUrl = ' . '"' . esc_url_raw(admin_url('admin-ajax.php')) . '";';
		$vars .= 'var fwduvpPlaylistManagerAjaxNonce = ' . '"' . esc_html(wp_create_nonce('fwduvp_playlist_manager_ajax_nonce')) . '";';
        
        wp_add_inline_script('fwduvp_playlist_manager', $vars);

    	include_once "playlist_manager.php";
    }
    // Set action link.
	public static function fwduvp_set_action_links($links){
		$settings_link = "<a href='" . get_admin_url(null, "admin.php?page=fwduvp-menu-general-settings") . "'>Settings</a>";
   		array_unshift($links, $settings_link);
   		
   		return $links;
	}


	// Add shortcode metabox.
	public function fwduvp_set_custom_meta_box($post){
		
		if (!$this->fwduvp_check_wp_ver()){
    		return;
    	}
		
		$tootlTipImgSrc = esc_url_raw($this->_dir_url) . "content/icons/help-icon.png"; 

		// presets
		$presetsNames = array();
		
		foreach ($this->_data->settings_ar as $setting){
    		$el = array(
					"id" => $setting["id"],
					"name" => $setting["name"]
			   );
    				   
    		array_push($presetsNames, $el);
    	}
    	
		// playlists
		$mainPlaylistsNames = array();
		
		if (isset($this->_data->main_playlists_ar)){
			foreach ($this->_data->main_playlists_ar as $main_playlist){
	    		$el = array(
    						"id" => $main_playlist["id"],
    						"name" => $main_playlist["name"]
    				   );
	    				   
	    		array_push($mainPlaylistsNames, $el);
	    	}
		}

		// Add and escape required js vars.
		$vars = 'var fwduvpPresetsObj = ' . '"' . esc_html(htmlspecialchars(json_encode($presetsNames))) . '";';
		$vars .= 'var fwduvpMainPlaylistsObj = ' . '"' . esc_html(htmlspecialchars(json_encode($mainPlaylistsNames))) . '";';
		wp_add_inline_script('fwduvp_shortcode_script', $vars);
		
    	include_once "meta_box.php";
	}
	

	// Check if user is lggedin.
	public function fwduvp_is_user_logged_in() {
		$user = wp_get_current_user();
		return $user->exists();
	}


	// Get user IP.
	public function fwduvp_get_the_user_ip(){
		$ip = '';

		if ( isset($_SERVER['HTTP_CLIENT_IP']) && ! empty($_SERVER['HTTP_CLIENT_IP']) ) {
			// Check ip from share internet.
			$ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
		} elseif ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && ! empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
			// Check ip is pass from proxy.
			$forwarded_for = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
			$forwarded_parts = explode(',', $forwarded_for);
			$ip = trim($forwarded_parts[0]);
		} else {
			$ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
		}

		if ( ! filter_var($ip, FILTER_VALIDATE_IP) ) {
			return '';
		}

		return $ip;
	}
	

	// Get constructor.
	public function fwduvp_get_constructor($preset, $playlistId, $start_at_playlist, $start_at_video) {
    	
    	if(!is_numeric($start_at_playlist)){
    		$start_at_playlist = intval($preset['start_at_playlist']);
    	}

    	if(!is_numeric($start_at_video)){
    		$start_at_video = intval($preset['start_at_video']);
    	}

		$isLoggedIn = $this->fwduvp_is_user_logged_in();
		if($preset['playIfLoggedIn'] == "yes" && $isLoggedIn) $preset['playIfLoggedIn'] = 'no';
		
		$youtubePlaylistAPI = empty($preset['youtubePlaylistAPI']) ? '' : $preset['youtubePlaylistAPI'];
		$fps = '';
		if($preset['useFingerPrintStamp'] == 'yes'){
			$fps .= 'var fwduvpFingerPrintStamp={';
			$fps .= '\'<span class="fwduvp-finger-print-stamp"><span class="fwduvp-user-header">User:</span>\':\'<span class="fwduvp-user-text">' . wp_get_current_user()->display_name .'</span></span>\',';
			$fps .= '\'<span class="fwduvp-finger-print-stamp"><span class="fwduvp-name-header">Name:</span>\':\'<span class="fwduvp-name-text">' . wp_get_current_user()->user_nicename .'</span></span>\',';
			$fps .= '\'<span class="fwduvp-finger-print-stamp"><span class="fwduvp-email-header">Email:</span>\':\'<span class="fwduvp-email-text">' . wp_get_current_user()->user_email .'</span></span>\',';
			$fps .= '\'<span class="fwduvp-finger-print-stamp"><span class="fwduvp-ip-header">IP:</span>\':\'<span class="fwduvp-ip-text">' . $this->fwduvp_get_the_user_ip() .'</span></span>\',';
			$fps .= '\'<span class="fwduvp-finger-print-stamp"><span class="fwduvp-time-header">Time:</span>\':\'<span class="fwduvp-time-text">' . gmdate('F j, Y H:i:s', current_time('timestamp', true)) .'</span></span>\',';
			$fps .= '};';
		}
	

	    	$output =  html_entity_decode(esc_html($fps), ENT_QUOTES) . "document.addEventListener('DOMContentLoaded', function(){if(document.getElementById('fwduvpDiv" . esc_html(FWDUVP::$_uvp_id) . "')){loadUVP" . esc_html(FWDUVP::$_uvp_id) . "();}});function loadUVP" . esc_html(FWDUVP::$_uvp_id) . "(){FWDUVPUtils.checkIfHasTransofrms();FWDUVPlayer.videoStartBehaviour = '" . esc_html($this->_data->videoStartBehaviour) . "';new FWDUVPlayer({" . "instanceName:'fwduvpPlayer" . esc_html(FWDUVP::$_uvp_id) . "',parentId:'fwduvpDiv" . esc_html(FWDUVP::$_uvp_id) . "',playlistsId:\"fwduvpMainPlaylist" . html_entity_decode(esc_html($playlistId), ENT_QUOTES) . "\",goFullScreenOnButtonPlay:'" . esc_html($preset['goFullScreenOnButtonPlay']) ."',fillEntireposterScreen:'" . esc_html($preset['fillEntireposterScreen']) ."',fillEntireVideoScreen:'" . esc_html($preset['fill_entire_video_screen']) . "',useHEXColorsForSkin:'" . esc_html($preset['use_HEX_colors_for_skin']) . "',normalHEXButtonsColor:'" . esc_html($preset['normal_HEX_buttons_color']) . "',privateVideoPassword:'" . esc_html($preset['privateVideoPassword']) . "',showContextmenu:'" .  esc_html($preset['showContextmenu']) . "',showScriptDeveloper:'" .  esc_html($preset['showScriptDeveloper']) . "',contextMenuBackgroundColor:'" .  esc_html($preset['contextMenuBackgroundColor']) . "',contextMenuBorderColor:'" .  esc_html($preset['contextMenuBorderColor']) . "',contextMenuSpacerColor:'" .  esc_html($preset['contextMenuSpacerColor']) . "',contextMenuItemNormalColor:'" .  esc_html($preset['contextMenuItemNormalColor']) . "',contextMenuItemSelectedColor:'" .  esc_html($preset['contextMenuItemSelectedColor']) . "',showYoutubeRelAndInfo:'" . esc_html($preset['showYoutubeRelAndInfo']) . "',contextMenuItemDisabledColor:'" .  esc_html($preset['contextMenuItemDisabledColor']) . "',stickyOnScroll:'" . esc_html($preset['stickyOnScroll']) . "',stickyOnScrollShowOpener:'" . esc_html($preset['stickyOnScrollShowOpener'])  . "',youtubeAPIKey:'" .  esc_html($youtubePlaylistAPI)  . "',stickyOnScrollWidth:" . esc_html($preset['stickyOnScrollWidth']) . ",stickyOnScrollHeight:" . esc_html($preset['stickyOnScrollHeight']) .",googleAnalyticsMeasurementId:'" . esc_html($preset['googleAnalyticsMeasurementId']) . "',randomizePlaylist:'" . esc_html($preset['randomizePlaylist']) . "',showRewindButton:'" . esc_html($preset['showRewindButton']) . "',showDefaultControllerForVimeo:'" . esc_html($preset['showDefaultControllerForVimeo']) . "',preloaderBackgroundColor:'" . esc_html($preset['preloaderColor1']) . "',preloaderFillColor:'" . esc_html($preset['preloaderColor2'])  . "',isLoggedIn:'" . esc_html($isLoggedIn) . "',playIfLoggedIn:'" .  esc_html($preset['playIfLoggedIn']) . "',lightBoxBackgroundColor:'" .  esc_html($preset['lightBoxBackgroundColor']) . "',closeLightBoxWhenPlayComplete:'" .  esc_html($preset['closeLightBoxWhenPlayComplete']) . "',lightBoxBackgroundOpacity:" .  esc_html($preset['lightBoxBackgroundOpacity']) . ",playIfLoggedInMessage:\"" . esc_js($preset['loggedInMessage']) . "\",executeCuepointsOnlyOnce:'" .   esc_html($preset['executeCuepointsOnlyOnce']) . "',showOpener:'" .   esc_html($preset['showOpener']) . "',verticalPosition:'" . esc_html($preset['verticalPosition'])  . "',useResumeOnPlay:'" .  esc_html($preset['useResumeOnPlay']) . "',logoTarget:'" .  esc_html($preset['logoTarget']) . "',useFingerPrintStamp:'" .  esc_html($preset['useFingerPrintStamp']) . "',frequencyOfFingerPrintStamp:" .  esc_html($preset['frequencyOfFingerPrintStamp']) . ",durationOfFingerPrintStamp:" . esc_html($preset['durationOfFingerPrintStamp']) . ",playsinline:'" .  esc_html($preset['playsinline']) . "',horizontalPosition:'" .  esc_html($preset['horizontalPosition']) . "',showPlayerByDefault:'" .  esc_html($preset['showPlayerByDefault']) . "',animatePlayer:'" .  esc_html($preset['animatePlayer']) . "',showOpenerPlayPauseButton:'" .  esc_html($preset['showOpenerPlayPauseButton']) . "',openerAlignment:'" . esc_html($preset['openerAlignment']) . "',mainBackgroundImagePath:'" .  esc_html($preset['mainBackgroundImagePath']) . "',openerEqulizerOffsetTop:" .  esc_html($preset['openerEqulizerOffsetTop']) . ",openerEqulizerOffsetLeft:" .  esc_html($preset['openerEqulizerOffsetLeft']) . ",offsetX:" .  esc_html($preset['offsetX']) . ",offsetY:" .  esc_html($preset['offsetY']). ",showScrubberWhenControllerIsHidden:'" . esc_html($preset['showScrubberWhenControllerIsHidden']) . "',useVectorIcons:'" .  esc_html($preset['useVectorIcons']) . "',mainFolderPath:'" . esc_url_raw($this->_dir_url) . "content'," . "skinPath:'" . esc_html($preset['skin_path']) . "',displayType:'" . esc_html($preset['display_type']) . "',showSubtitleButton:'" . esc_html($preset['showSubtitleButton']) . "',useYoutube:'" . esc_html($preset['showErrorInfo']) . "',initializeOnlyWhenVisible:'" . esc_html($preset['initializeOnlyWhenVisible']) . "',showPreloader:'" . esc_html($preset['showPreloader']) . "',useDeepLinking:'" . esc_html($preset['use_deeplinking']) . "',addKeyboardSupport:'" . esc_html($preset['add_keyboard_support']) . "',autoScale:'" . esc_html($preset['auto_scale']) . "',showButtonsToolTip:'" . esc_html($preset['show_buttons_tooltips']) . "',stopVideoWhenPlayComplete:'" . esc_html($preset['stop_video_when_play_complete']) . "',autoPlayText:'" . esc_html($preset['autoPlayText']) . "',autoPlay:'" . esc_html($preset['autoplay']) . "',loop:'" . esc_html($preset['loop']) . "',shuffle:'" . esc_html($preset['shuffle']) . "',maxWidth:" . esc_html($preset['max_width']) . ",maxHeight:" . esc_html($preset['max_height']) . ",buttonsToolTipHideDelay:" . esc_html($preset['buttons_tooltip_hide_delay']) . ",showPopupAdsCloseButton:'" . esc_html($preset['show_popup_ads_close_button']) . "',volume:" . esc_html($preset['volume']) . ",rewindTime:" . esc_html($preset['rewindTime']) . ",backgroundColor:'" . esc_html($preset['bg_color']) . "',showErrorInfo:'" . esc_html($preset['showErrorInfo']) . "',aopwTitle:'" . esc_html($preset['aopwTitle']) . "',aopwWidth:" . esc_html($preset['aopwWidth']) . ",aopwHeight:" . esc_html($preset['aopwHeight']) . ",aopwBorderSize:" . esc_html($preset['aopwBorderSize']) . ",aopwTitleColor:'" . esc_html($preset['aopwTitleColor']) . "',playAfterVideoStop:'" . esc_html($preset['playAfterVideoStop']) . "',stopAfterLastVideoHasPlayed:'" . esc_html($preset['stopAfterLastVideoHasPlayed']) . "',disableVideoScrubber:'" . esc_html($preset['disable_video_scrubber']) . "',videoBackgroundColor:'" . esc_html($preset['video_bg_color']) . "',posterBackgroundColor:'" . esc_html($preset['poster_bg_color']) . "',buttonsToolTipFontColor:'" . esc_html($preset['buttons_tooltip_font_color']) . "'," . "showControllerWhenVideoIsStopped:'" . esc_html($preset['show_controller_when_video_is_stopped']) . "', showController:'" . esc_html($preset['showController']) . "', audioVisualizerLinesColor:'" . esc_html($preset['audioVisualizerLinesColor']) . "', audioVisualizerCircleColor:'" . esc_html($preset['audioVisualizerCircleColor']) . "',showNextAndPrevButtonsInController:'" . esc_html($preset['show_next_and_prev_buttons_in_controller']) . "',defaultPlaybackRate:" . esc_html($preset['defaultPlaybackRate']) . ",showPlaybackRateButton:'" . esc_html($preset['showPlaybackRateButton']) . "',showVolumeButton:'" . esc_html($preset['show_volume_button']) . "',showTime:'" . esc_html($preset['show_time']) . "',showYoutubeQualityButton:'" . esc_html($preset['show_youtube_quality_button']) . "',showInfoButton:'" . esc_html($preset['show_info_button']) . "',showDownloadButton:'" . esc_html($preset['show_download_button']) . "',showShareButton:'" . esc_html($preset['show_share_button']) . "',showAudioTracksButton:'" . esc_html($preset['showAudioTracksButton']) . "',showChromecastButton:'" . esc_html($preset['showChromecastButton']) . "',show360DegreeVideoVrButton:'" . esc_html($preset['show360DegreeVideoVrButton']) . "',showEmbedButton:'" . esc_html($preset['show_embed_button']) . "',showFullScreenButton:'" . esc_html($preset['show_fullscreen_button']) . "',repeatBackground:'" . esc_html($preset['repeat_background']) . "',controllerHeight:" . esc_html($preset['controller_height']) . ",controllerHideDelay:" . esc_html($preset['controller_hide_delay']) . ",startSpaceBetweenButtons:" . esc_html($preset['start_space_between_buttons']) . ",spaceBetweenButtons:" . esc_html($preset['space_between_buttons']) . ",scrubbersOffsetWidth:" . esc_html($preset['scrubbers_offset_width']) . ",mainScrubberOffestTop:" . esc_html($preset['main_scrubber_offest_top']) . ",timeOffsetLeftWidth:" . esc_html($preset['time_offset_left_width']) . ",timeOffsetRightWidth:" . esc_html($preset['time_offset_right_width']) . ",timeOffsetTop:" . esc_html($preset['time_offset_top']) . ",volumeScrubberHeight:" . esc_html($preset['volume_scrubber_height']) . ",volumeScrubberOfsetHeight:" . esc_html($preset['volume_scrubber_ofset_height']) . ",timeColor:'" . esc_html($preset['time_color']) . "',youtubeQualityButtonNormalColor:'" . esc_html($preset['youtube_quality_button_normal_color']) . "',youtubeQualityButtonSelectedColor:'" . esc_html($preset['youtube_quality_button_selected_color']) . "'," . "showPlaylistsButtonAndPlaylists:'" . esc_html($preset['show_playlists_button_and_playlists']) . "',usePlaylistsSelectBox:'" . esc_html($preset['use_playlists_select_box']) . "',showPlaylistsByDefault:'" . esc_html($preset['show_playlists_by_default']) . "',thumbnailSelectedType:'" . esc_html($preset['thumbnail_selected_type']) . "',startAtPlaylist:" . esc_html($start_at_playlist) . ",buttonsMargins:" . esc_html($preset['buttons_margins']) . ",thumbnailMaxWidth:" . esc_html($preset['thumbnail_max_width']) . ", thumbnailMaxHeight:" . esc_html($preset['thumbnail_max_height']) . ",thumbnailsPreviewWidth:" . esc_html($preset['thumbnails_preview_width']) . ",thumbnailsPreviewHeight:" . esc_html($preset['thumbnails_preview_height']) . ",thumbnailsPreviewBackgroundColor:'" . esc_html($preset['thumbnails_preview_background_color']) . "',thumbnailsPreviewBorderColor:'" . esc_html($preset['thumbnails_preview_border_color']) . "',thumbnailsPreviewLabelBackgroundColor:'" . esc_html($preset['thumbnails_preview_label_background_color']) . "',thumbnailsPreviewLabelFontColor:'" . esc_html($preset['thumbnails_preview_label_font_color']) . "',horizontalSpaceBetweenThumbnails:" . esc_html($preset['horizontal_space_between_thumbnails']) . ",mainSelectorBackgroundSelectedColor:'" . esc_html($preset['main_selector_background_selected_color']) . "',mainSelectorTextNormalColor:'" . esc_html($preset['main_selector_text_normal_color']) . "',mainSelectorTextSelectedColor:'" . esc_html($preset['main_selector_text_selected_color']) . "',mainButtonBackgroundNormalColor:'" . esc_html($preset['main_button_background_normal_color']) . "',mainButtonBackgroundSelectedColor:'" . esc_html($preset['main_button_background_selected_color']) . "',mainButtonTextNormalColor:'" . esc_html($preset['main_button_text_normal_color']) . "',mainButtonTextSelectedColor:'" . esc_html($preset['main_button_text_selected_color']) . "',verticalSpaceBetweenThumbnails:" . esc_html($preset['vertical_space_between_thumbnails']) . "," . "showPlaylistButtonAndPlaylist:'" . esc_html($preset['show_playlist_button_and_playlist']) . "',showPlaylistsSearchInput:'" . esc_html($preset['showPlaylistsSearchInput']) . "',playlistPosition:'" . esc_html($preset['playlist_position']) . "',showPlaylistByDefault:'" . esc_html($preset['show_playlist_by_default']) . "',addScrollOnMouseMove:'" . esc_html($preset['addScrollOnMouseMove']) . "',showPlaylistOnFullScreen:'" . esc_html($preset['showPlaylistOnFullScreen']) . "',showOnlyThumbnail:'" . esc_html($preset['showOnlyThumbnail']) . "',showThumbnail:'" . esc_html($preset['showThumbnail']) . "',showPlaylistName:'" . esc_html($preset['show_playlist_name']) . "',showSearchInput:'" . esc_html($preset['show_search_input']) . "',showLoopButton:'" . esc_html($preset['show_loop_button']) . "',showShuffleButton:'" . esc_html($preset['show_shuffle_button']) . "',showNextAndPrevButtons:'" . esc_html($preset['show_next_and_prev_buttons']) . "',forceDisableDownloadButtonForFolder:'" . esc_html($preset['force_disable_download_button_for_folder']) . "',addMouseWheelSupport:'" . esc_html($preset['add_mouse_wheel_support']) . "',startAtRandomVideo:'" . esc_html($preset['start_at_random_video'])  . "',playlistRightWidth:" . esc_html($preset['playlist_right_width']) . ",playlistBottomHeight:" . esc_html($preset['playlist_bottom_height']) . ",startAtVideo:" . esc_html($start_at_video) . ",maxPlaylistItems:" . esc_html($preset['max_playlist_items']) . ",thumbnailWidth:" . esc_html($preset['thumbnail_width']) . ",thumbnailHeight:" . esc_html($preset['thumbnail_height'] . ",spaceBetweenControllerAndPlaylist:" . $preset['space_between_controller_and_playlist'] . ",spaceBetweenThumbnails:" . $preset['space_between_thumbnails'] . ",scrollbarOffestWidth:" . $preset['scrollbar_offest_width']) . ",scollbarSpeedSensitivity:" . esc_html($preset['scollbar_speed_sensitivity']) . ",playlistBackgroundColor:'" . esc_html($preset['playlist_background_color']) . "',playlistNameColor:'" . esc_html($preset['playlist_name_color']) . "',thumbnailNormalBackgroundColor:'" . esc_html($preset['thumbnail_normal_background_color']) . "',thumbnailHoverBackgroundColor:'" . esc_html($preset['thumbnail_hover_background_color']) . "',thumbnailDisabledBackgroundColor:'" . esc_html($preset['thumbnail_disabled_background_color']) . "',searchInputBackgroundColor:'" . esc_html($preset['search_input_background_color']) . "',searchInputColor:'" . esc_html($preset['search_input_color']) . "',youtubeAndFolderVideoTitleColor:'" . esc_html($preset['youtube_and_folder_video_title_color']) . "',youtubeOwnerColor:'" . esc_html($preset['youtube_owner_color']) . "',youtubeDescriptionColor:'" . esc_html($preset['youtube_description_color']) . "'," . "showLogo:'" . esc_html($preset['show_logo']) . "',hideLogoWithController:'" . esc_html($preset['hide_logo_with_controller']) . "',logoPosition:'" . esc_html($preset['logo_position']) . "',logoPath:'" . esc_html($preset['logo_path']) . "',logoLink:'" . esc_html($preset['logo_link']) . "',logoMargins:" . esc_html($preset['logo_margins']) . "," ."subtitlesOffLabel:'" . esc_html($preset['subtitles_off_label']) . "'," . "embedAndInfoWindowCloseButtonMargins:" . esc_html($preset['embed_and_info_window_close_button_margins']) . ",borderColor:'" . (empty($preset['border_color']) ? 'transparent' : esc_html($preset['border_color'])) . "',mainLabelsColor:'" . esc_html($preset['main_labels_color']) . "',secondaryLabelsColor:'" . esc_html($preset['secondary_labels_color']) . "',shareAndEmbedTextColor:'" . esc_html($preset['share_and_embed_text_color']) . "',inputBackgroundColor:'" . esc_html($preset['search_input_background_color']) . "',inputColor:'" . esc_html($preset['input_color']) . "'," ."openNewPageAtTheEndOfTheAds:'" . esc_html($preset['open_new_page_at_the_end_of_the_ads']) . "',playAdsOnlyOnce:'" . esc_html($preset['play_ads_only_once']) . "',adsButtonsPosition:'" . esc_html($preset['ads_buttons_position']) . "',skipToVideoText:'" . esc_html($preset['skip_to_video_text']) . "',skipToVideoButtonText:'" . esc_html($preset['skip_to_video_button_text']) . "',adsTextNormalColor:'" . esc_html($preset['ads_text_normal_color']) . "',adsTextSelectedColor:'" . esc_html($preset['ads_text_selected_color']) . "',adsBorderNormalColor:'" . esc_html($preset['ads_border_normal_color']) . "',adsBorderSelectedColor:'" . esc_html($preset['ads_border_selected_color']) . "',showMainScrubberToolTipLabel:'" . esc_html($preset['showMainScrubberToolTipLabel']) . "',scrubbersToolTipLabelFontColor:'" . esc_html($preset['scrubbersToolTipLabelFontColor']) .  "',scrubbersToolTipLabelBackgroundColor:'" . esc_html($preset['scrubbersToolTipLabelBackgroundColor']) . "',useAToB:'" . esc_html($preset['useAToB']) . "',atbTimeBackgroundColor:'transparent',atbTimeTextColorNormal:'" . esc_html($preset['atbTimeTextColorNormal']) . "',atbTimeTextColorSelected:'" . esc_html($preset['atbTimeTextColorSelected']) . "',atbButtonTextNormalColor:'" . esc_html($preset['atbButtonTextNormalColor']) . "',atbButtonTextSelectedColor:'" . esc_html($preset['atbButtonTextSelectedColor']) . "',atbButtonBackgroundNormalColor:'" . esc_html($preset['atbButtonBackgroundNormalColor']) . "',atbButtonBackgroundSelectedColor:'" . esc_html($preset['atbButtonBackgroundSelectedColor']) . "'});};";
    		
    	return $output;
    }
						
	
	// Get main playlist.
    public function fwduvp_get_main_playlist($playlistId){
		$allowed_post_html = wp_kses_allowed_html('post');

    	$main_playlist = NULL;
    	if(is_null($this->_data->main_playlists_ar)) return;

    	foreach ($this->_data->main_playlists_ar as $pl){
    		if($pl["name"] == $playlistId){
    			$main_playlist = $pl;
    		}
    	}
		
    	if (is_null($main_playlist)){
    		return;
    	}
    	
    	// To be safe force display none, this must be hidden!
    	$main_playlist_str = '<ul id="fwduvpMainPlaylist' . html_entity_decode(esc_html($playlistId), ENT_QUOTES) . '" class="fwduvp-playlist-data">';
		$normal_playlist_str = "";
    	
    	foreach ($main_playlist["playlists"] as $playlist){
			
			if ($playlist["type"] == "normal"){
				$main_playlist_str .= '<li data-source="fwduvpPlaylist' . esc_html(FWDUVP::$_pl_id) . '"';
				if(!empty($playlist["password"])){
					$main_playlist_str .= ' data-password="' . esc_html(md5($playlist["password"])) . '"';
				}

				// To be safe force display none, this must be hidden!
				$normal_playlist_str .= '<ul id="fwduvpPlaylist' . esc_html(FWDUVP::$_pl_id) . '" class="fwduvp-playlist-data">';
				
				foreach ($playlist["videos"] as $video){
					$normal_playlist_str .= "<li data-video-source=\"[";
					foreach ($video["vids_ar"] as $vid){
						$source = $vid['source'];
						if($vid['encrypt'] == "yes"){
							$normal_playlist_str .= "{source:'encrypt:" . base64_encode(esc_url($source)) . "', label:&quot;" . esc_html($vid['label']) ."&quot;";
							if(!empty($vid['is360']) && $vid['is360'] == 'yes'){
								$normal_playlist_str .= ", is360:'yes'";
							}

							if(!empty($vid['startWhenPlayButtonClick360DegreeVideo']) && $vid['startWhenPlayButtonClick360DegreeVideo'] && $vid['startWhenPlayButtonClick360DegreeVideo'] == "yes"){
								$normal_playlist_str .= ", startWhenPlayButtonClick360DegreeVideo:'" . esc_html($vid["startWhenPlayButtonClick360DegreeVideo"]) . "'";
							}

							if(!empty($vid['rotationY360DegreeVideo'])){
								$normal_playlist_str .= ", rotationY360DegreeVideo:'" . esc_html($vid["rotationY360DegreeVideo"]) . "'";
							}
							
							$normal_playlist_str .= "},";
						}else{
							$normal_playlist_str .= "{source:'" . esc_url($source) . "', label:&quot;" . esc_html($vid['label']) ."&quot;";
							if(!empty($vid['is360']) && $vid['is360'] == 'yes'){
								$normal_playlist_str .= ", is360:'yes'";
							}

							if(!empty($vid['startWhenPlayButtonClick360DegreeVideo']) && $vid['startWhenPlayButtonClick360DegreeVideo'] == "yes"){
								$normal_playlist_str .= ", startWhenPlayButtonClick360DegreeVideo:'" . esc_html($vid["startWhenPlayButtonClick360DegreeVideo"]) . "'";
							}

							if(!empty($vid['rotationY360DegreeVideo'])){
								$normal_playlist_str .= ", rotationY360DegreeVideo:'" . esc_html($vid["rotationY360DegreeVideo"]) . "'";
							}

							$normal_playlist_str .= "},";
						}
					}
					$normal_playlist_str .= "]\"";
					$normal_playlist_str = str_replace("},]", "}]", $normal_playlist_str); //All dynamic content was escaped!
					$countVids = 0;
					foreach ($video["vids_ar"] as $vid){
						if($vid['checked'] == true){
							$normal_playlist_str .= ' data-start-at-video="' . esc_html($countVids) . '"';
						}
						$countVids ++;
					}
					
					$videoVastUrl = !empty($video["vastURL"]) ? $video["vastURL"] : '';
					$videoVastTarget = !empty($video["vastTarget"]) ? $video["vastTarget"] : '_blank';
					$videoVastStartTime = !empty($video["vastStartTime"]) ? $video["vastStartTime"] : '00:00:00';

					$globalVastUrl = !empty($this->_data->global_vast_url) ? $this->_data->global_vast_url : '';
					$globalVastTarget = !empty($this->_data->global_vast_target) ? $this->_data->global_vast_target : '_blank';
					$globalVastStartTime = !empty($this->_data->global_vast_start_time) ? $this->_data->global_vast_start_time : '00:00:00';
					
					if(!empty($videoVastUrl)){
						$normal_playlist_str .= ' data-vast-url="' . esc_url($videoVastUrl) . '" data-vast-clicktrough-target="' . esc_attr($videoVastTarget) .  '" data-vast-linear-astart-at-time="' . esc_attr($videoVastStartTime) . '"';
					}else if(!empty($globalVastUrl)){
						$normal_playlist_str .= ' data-vast-url="' . esc_url($globalVastUrl) . '" data-vast-clicktrough-target="' . esc_attr($globalVastTarget) .  '" data-vast-linear-astart-at-time="' . esc_attr($globalVastStartTime) . '"';
					}
					
					if($video["startAtTime"]){
						$normal_playlist_str .= ' data-start-at-time="' . esc_html($video["startAtTime"]) . '"';
					}
					
					if($video["stopAtTime"]){
						$normal_playlist_str .= ' data-stop-at-time="' . esc_html($video["stopAtTime"]) . '"';
					}

					if(!empty($video["thumbnails_preview"])){
						$thumbnails_preview_src = $video["thumbnails_preview"];
						if($thumbnails_preview_src == 'auto'){
							$thumbnails_preview_src = esc_attr($thumbnails_preview_src);
						}else{
							$thumbnails_preview_src = esc_url($thumbnails_preview_src);
						}
						$normal_playlist_str .= ' data-thumbnails-preview="' . $thumbnails_preview_src . '"';
					}
				
					if(!empty($video["password"])){
						$normal_playlist_str .= ' data-private-video-password="' . esc_html(md5($video["password"])) . '"';
					}
				
					$normal_playlist_str .= ' data-is-private="' . esc_html($video["isPrivate"]) . '"';
					
					if(count($video["subtitles_ar"]) > 0){
						$normal_playlist_str .= " data-subtitle-soruce=\"[";
						foreach ($video["subtitles_ar"] as $subtitle){
							$source = $subtitle['source'];
							if($subtitle['encrypt'] == "yes"){
								 $normal_playlist_str .= "{source:'encrypt:" . base64_encode(esc_url($subtitle['source'])) . "', label:&quot;" . esc_html($subtitle['label']) ."&quot;},";

							}else{
								$normal_playlist_str .= "{source:'" . esc_url($source) . "', label:&quot;" . esc_html($subtitle['label']) ."&quot;},";
							}
							
						}
						$normal_playlist_str .= "]\"";
						$normal_playlist_str = str_replace("},]", "}]", $normal_playlist_str); // All dynamic content was escaped!
						$countSubtitles = 1;
						foreach ($video["subtitles_ar"] as $subtitle){
							if($subtitle['checked'] == true){
								$normal_playlist_str .= ' data-start-at-subtitle="' . esc_html($countSubtitles) . '"';
							}
							$countSubtitles ++;
						}
					}
					
					if(strlen($video["thumb"]) >= 1){
						$normal_playlist_str .= ' data-thumb-source="' . esc_url($video["thumb"]) . '"';
					}
					
					if(strlen($video["poster"]) >= 1){
						$normal_playlist_str .= " data-poster-source=\"" .  esc_url($video["poster"]) . "\"";
					}
					
					$videoPauseAdsSource = !empty($video['popw']) ? $video['popw'] : '';
					$globalPauseAdsSource = !empty($this->_data->global_pause_ads_source) ? $this->_data->global_pause_ads_source : '';
					$effectivePauseAdsSource = !empty($videoPauseAdsSource) ? $videoPauseAdsSource : $globalPauseAdsSource;

					if(strlen($effectivePauseAdsSource) >= 3){
						$normal_playlist_str .= ' data-advertisement-on-pause-source="' .  esc_url($effectivePauseAdsSource) . '"';
					}
					$normal_playlist_str .= ' data-downloadable="' . esc_html($video["downloadable"]) . '"';
					
					if(!empty($video["atob"])){
						$normal_playlist_str .= ' data-use-a-to-b="' . esc_html($video["atob"]) . '"';
					}
					
					if (isset($video["ads_source"]) && strlen($video["ads_source"]) >= 1){
						if (isset($video["ads_source_mobile"]) && strlen($video["ads_source_mobile"]) >= 1){
							$normal_playlist_str .= ' data-ads-source="' .  esc_attr($video["ads_source"] . "," . $video["ads_source_mobile"]) . '"';
						}else{
							$normal_playlist_str .= ' data-ads-source="' .  esc_attr($video["ads_source"]) . '"';
						}
					}
					
					if (isset($video["ads_url"]) && strlen($video["ads_url"]) >= 1){
						$normal_playlist_str .= ' data-ads-page-to-open-url="' .  esc_url($video["ads_url"]) . '"';
					}
					
					if (isset($video["ads_url_target"]) && strlen($video["ads_url_target"]) >= 1){
						$normal_playlist_str .= ' data-ads-page-target="' .  esc_html($video["ads_url_target"]) . '"';
					}
					
					if (isset($video["redirectURL"])){
						$normal_playlist_str .= ' data-redirect-url="' . esc_url($video["redirectURL"]) . '" data-redirect-target="' . esc_attr($video["redirectTarget"]) . '"'; 
					}
					
					if (isset($video["ads_hold_time"]) && strlen($video["ads_hold_time"]) >= 1){
						$normal_playlist_str .= ' data-time-to-hold-ads="' .  esc_html($video["ads_hold_time"]) . '"';
					}

					if(isset($video["playOnlyIfLoggedIn"])){
						$isLoggedIn = $this->fwduvp_is_user_logged_in();
						if($video["playOnlyIfLoggedIn"] == 'yes' && !$isLoggedIn){
							 $normal_playlist_str .= ' data-play-if-logged-in="yes"';
						}
					}
					
					$normal_playlist_str .= ">";
					
					$normal_playlist_str .= '<div data-video-short-description="">';
					
					$normal_playlist_str .= wp_kses($video["short_descr"], $allowed_post_html);
					
					$normal_playlist_str .= "</div>";
					
					if (strlen($video["long_descr"]) >= 1){
						$normal_playlist_str .= "<div data-video-long-description=''>";
						$normal_playlist_str .= wp_kses($video["long_descr"], $allowed_post_html);
						$normal_playlist_str .= "</div>";
					}
					
					if(count($video["cuepoints_ar"]) > 0){
						$normal_playlist_str .= '<ul data-cuepoints="">';
						foreach ($video["cuepoints_ar"] as $cuepoint){
							$normal_playlist_str .= '<li data-time-start="' . esc_attr($cuepoint['startAtTime']) . '" data-javascript-call="' . esc_attr($cuepoint['code']) . '"></li>';
						}
						$normal_playlist_str .= '</ul>';
					}
					
					$videoAds = (isset($video["ads_ar"]) && is_array($video["ads_ar"])) ? $video["ads_ar"] : array();
					$globalAds = (isset($this->_data->global_ads_ar) && is_array($this->_data->global_ads_ar)) ? $this->_data->global_ads_ar : array();
					$adsToRender = !empty($videoAds) ? $videoAds : $globalAds;

					if(!empty($adsToRender)){
						$normal_playlist_str .= "<div data-ads=''>";
						foreach ($adsToRender as $ad){
							$normal_playlist_str .= "<p data-source='" . esc_url($ad['source']) . "' data-time-start='" . esc_attr($ad['startTime']) . "' data-time-to-hold-ads='" . esc_attr($ad['timeToHoldAd'])  . "' data-add-duration='" . esc_attr($ad['addDuration']) ."' data-link='" . esc_url($ad['url']) . "' data-target='" . esc_attr($ad['target']) . "'></p>";
						}
						$normal_playlist_str .= "</div>";
					}
					
					$videoPopupAds = (isset($video["popupads_ar"]) && is_array($video["popupads_ar"])) ? $video["popupads_ar"] : array();
					$globalPopupAds = (isset($this->_data->global_popup_ads_ar) && is_array($this->_data->global_popup_ads_ar)) ? $this->_data->global_popup_ads_ar : array();
					$popupAdsToRender = !empty($videoPopupAds) ? $videoPopupAds : $globalPopupAds;

					if(!empty($popupAdsToRender)){
						$normal_playlist_str .= "<div data-add-popup=''>";
						foreach ($popupAdsToRender as $ad){
							
							if($ad["type"] == "image"){
								$normal_playlist_str .= "<p data-image-path='" . esc_url($ad['source']) . "' data-time-start='" . esc_attr($ad['startTime']) . "' data-time-end='" . esc_attr($ad['stopTime']) ."' data-link='" . esc_url($ad['url']) . "' data-target='" . esc_attr($ad['target']) . "' ></p>";
							}else{
								$normal_playlist_str .= "<p data-google-ad-client='" . esc_attr($ad['google_ad_client']) . "' data-google-ad-slot='" . esc_attr($ad['google_ad_slot']) . "' data-google-ad-width=" . esc_attr($ad['google_ad_width']) . " data-google-ad-height=" . esc_attr($ad['google_ad_height']) ." data-time-start='" . esc_attr($ad['google_ad_start_time']) . "' data-time-end='" . esc_attr($ad['google_ad_stop_time']) . "' ></p>";
							}
						}
						$normal_playlist_str .= "</div>";
					}
					$normal_playlist_str .= "</li>";
					
					
				}
				
				$normal_playlist_str .= "</ul>";
				
				FWDUVP::$_pl_id++;
			}else if ($playlist["type"] == "youtube"){
				$youtube_playlist_source = $playlist["source"];
				if(strpos($youtube_playlist_source, 'list=') !== false) {
					$youtube_playlist_source = "list=";
				
					$reg_exp = "/[\?\&]list\=.+/";
					
					if (preg_match($reg_exp, $playlist["source"], $matches)){
						$youtube_playlist_source .= substr($matches[0], 6);
					}
				}
			
				$main_playlist_str .= '<li data-source="' . esc_attr($youtube_playlist_source) . '"';
				if(!empty($playlist["password"])){
					$main_playlist_str .= ' data-password="' . esc_html(md5($playlist["password"])) . '"';
				}
			}else if ($playlist["type"] == "folder"){
				$main_playlist_str .= '<li data-source="folder=' . esc_html($playlist["source"]) . '"';
				if(!empty($playlist["password"])){
					$main_playlist_str .= ' data-password="' . esc_html(md5($playlist["password"])) . '"';
				}
			}else if ($playlist["type"] == "vimeo"){
				$main_playlist_str .= '<li data-source="' . esc_attr($playlist["vimeoSource"]) . '" data-user-id="' . esc_attr($playlist["userId"]) . '"' . ' data-client-id="' . esc_attr($playlist["clientId"]) . '" data-vimeo-secret="' . esc_attr($playlist["vimeoSecret"]) . '" data-vimeo-token="' . esc_attr($playlist["vimeoToken"]) . '"';
				if(!empty($playlist["password"])){
					$main_playlist_str .= ' data-password="' . esc_html(md5($playlist["password"])) . '"';
				}
			}else{
				$main_playlist_str .= '<li data-source="' . esc_url($playlist["source"]) . '"';
				if(!empty($playlist["password"])){
					$main_playlist_str .= ' data-password="' . esc_html(md5($playlist["password"])) . '"';
				}
			}
			$main_playlist_str .= ' data-playlist-name="' . esc_attr($playlist["name"]) . '"';
		
			if (isset($playlist["thumb"])){
				$main_playlist_str .= ' data-thumbnail-path="' . esc_url($playlist["thumb"]) . '">';
			}else{
				$main_playlist_str .= '>';
			}
			
			$main_playlist_str .= wp_kses($playlist["text"], $allowed_post_html);
    		
    		$main_playlist_str .= '</li>';
    	}
    	
    	$main_playlist_str .= '</ul>';
		$main_playlist_str .= $normal_playlist_str;
    	return $main_playlist_str; // All dynamic content was escaped!
    }


	// Clean function for names/labels.
	private function fwduvp_clean_name($string) {
		$string = preg_replace('/"/', '\'', $string);
	   	return preg_replace('/[\[\]\&\/<>|\\\\]/', '', $string);
	}

	private function fwduvp_clean_folder_name($string) {
		$string = preg_replace('/"/', '\'', $string);
	   	return preg_replace('/[\/<>|\\\\]/', '', $string);
	}
}
?>