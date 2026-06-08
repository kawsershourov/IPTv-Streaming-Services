<?php
/**
 * Plugin data.
 *
 * @package fwduvp
 * @since fwduvp 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class FWDUVPData {

	const DEFAULT_SKINS_NR = 8;
	public $settings_ar;
    public $main_playlists_ar;
	public $global_ads_ar;
	public $global_popup_ads_ar;
	public $global_vast_url;
	public $global_vast_target;
	public $global_vast_start_time;
	public $global_pause_ads_source;
	public $global_apply_to_youtube_videos;

	public $videoStartBehaviour;
	

    // Constructor.
    public function init(){
		$cur_data = get_option("fwduvp_data");
		
	  	if (!$cur_data){
	    	$this->init_settings();
	    	$this->init_playlist();
	    	$this->set_data();
	    }
		
		$this->set_updates();
		$this->get_data();
    }

	
	// Reset presets.
	private function reset_presets(){
		$this->get_data();
		$this->init_settings();
	    $this->set_data();
	}

	
	// Set updates.
	private function set_updates(){
		
		$this->get_data();

		if(!is_array($this->global_ads_ar)){
			$this->global_ads_ar = array();
		}

		if(!is_array($this->global_popup_ads_ar)){
			$this->global_popup_ads_ar = array();
		}

		if(!isset($this->global_vast_url)){
			$this->global_vast_url = '';
		}

		if(!isset($this->global_vast_target)){
			$this->global_vast_target = '_blank';
		}

		if(!isset($this->global_vast_start_time)){
			$this->global_vast_start_time = '00:00:00';
		}

		if(!isset($this->global_pause_ads_source)){
			$this->global_pause_ads_source = '';
		}

		if(!isset($this->global_apply_to_youtube_videos)){
			$this->global_apply_to_youtube_videos = 'no';
		}
		
		$this->videoStartBehaviour = get_option("fwduvp_data")->videoStartBehaviour;
		if(!$this->videoStartBehaviour)  $this->videoStartBehaviour = "pause";
   		foreach ($this->settings_ar as &$preset){	

			if(!array_key_exists("woo_commerce_display_type", $preset)){
				$preset["woo_commerce_display_type"] = 'fwduvp-responsive';
			}
   			
   			if(!array_key_exists("logoTarget", $preset)){
	    		$preset["logoTarget"] = "_blank";
			}

   			if(!array_key_exists("showYoutubeRelAndInfo", $preset)){
	    		$preset["showYoutubeRelAndInfo"] = "no";
			}
   		
   			if(!array_key_exists("showAudioTracksButton", $preset)){
	    		$preset["showAudioTracksButton"] = "yes";
			}

   			if(!array_key_exists("audioVisualizerLinesColor", $preset)){
	    		$preset["audioVisualizerLinesColor"] = "#CCCCCC";
			}

			if(!array_key_exists("audioVisualizerCircleColor", $preset)){
	    		$preset["audioVisualizerCircleColor"] = "#FFFFFF";
			}

   			if(!array_key_exists("showController", $preset)){
	    		$preset["showController"] = "yes";
			}
    		
    		if(!array_key_exists("rewindTime", $preset)){
	    		$preset["rewindTime"] = 10;
			}

    		if(!array_key_exists("useFingerPrintStamp", $preset)){
	    		$preset["useFingerPrintStamp"] = 'no';
			}

			if(!array_key_exists("frequencyOfFingerPrintStamp", $preset)){
	    		$preset["frequencyOfFingerPrintStamp"] = 20000;
			}

			if(!array_key_exists("durationOfFingerPrintStamp", $preset)){
	    		$preset["durationOfFingerPrintStamp"] = 50;
			}
    		
    		if(!array_key_exists("fillEntireposterScreen", $preset)){
	    		$preset["fillEntireposterScreen"] = 'yes';
			}

    		if(!array_key_exists("executeCuepointsOnlyOnce", $preset)){
	    		$preset["executeCuepointsOnlyOnce"] = 'no';
			}
    		if(!array_key_exists("showPlaylistOnFullScreen", $preset)){
	    		$preset["showPlaylistOnFullScreen"] = 'no';
			}
    		if(!array_key_exists("addScrollOnMouseMove", $preset)){
	    		$preset["addScrollOnMouseMove"] = 'no';
			}
    		if(!array_key_exists("showThumbnail", $preset)){
	    		$preset["showThumbnail"] =  'yes';
			}
			if(!array_key_exists("showOnlyThumbnail", $preset)){
	    		$preset["showOnlyThumbnail"] =  'no';
			}

    		if(!array_key_exists("goFullScreenOnButtonPlay", $preset)){
	    		$preset["goFullScreenOnButtonPlay"] =  'no';
			}

    		if(!array_key_exists("autoPlayText", $preset)){
	    		$preset["autoPlayText"] =  'Click To Unmute';
			}

			if(!array_key_exists("show360DegreeVideoVrButton", $preset)){
	    		$preset["show360DegreeVideoVrButton"] =  'no';
			}

    		if(!array_key_exists("showChromecastButton", $preset)){
	    		$preset["showChromecastButton"] =  'no';
			}
			if(!array_key_exists("showSubtitleButton", $preset)){
	    		$preset["showSubtitleButton"] =  'yes';
			}
    		if(!array_key_exists("randomizePlaylist", $preset)){
	    		$preset["randomizePlaylist"] =  'no';
			}
    		if(!array_key_exists("thumbnails_preview_width", $preset)){
	    		$preset["thumbnails_preview_width"] =  196;
			}
			if(!array_key_exists("thumbnails_preview_height", $preset)){
	    		$preset["thumbnails_preview_height"] =  110;
			}
			if(!array_key_exists("thumbnails_preview_background_color", $preset)){
	    		$preset["thumbnails_preview_background_color"] =  "#000";
			}
			if(!array_key_exists("thumbnails_preview_border_color", $preset)){
	    		$preset["thumbnails_preview_border_color"] =  "#666";
			}
			if(!array_key_exists("thumbnails_preview_label_background_color", $preset)){
	    		$preset["thumbnails_preview_label_background_color"] =  "#666";
			}
			if(!array_key_exists("thumbnails_preview_label_font_color", $preset)){
	    		$preset["thumbnails_preview_label_font_color"] =  "#FFF";
			}
			
			if(!array_key_exists("showScrubberWhenControllerIsHidden", $preset)){
	    		$preset["showScrubberWhenControllerIsHidden"] =  "yes";
			}
			if(!array_key_exists("playsinline", $preset)){
	    		$preset["playsinline"] =  "yes";
			}
			if(!array_key_exists("useResumeOnPlay", $preset)){
	    		$preset["useResumeOnPlay"] =  "no";
			}
			
			if(!array_key_exists("useVectorIcons", $preset)){
	    		$preset["useVectorIcons"] =  "no";
			}
			if(!array_key_exists("stickyOnScroll", $preset)){
	    		$preset["stickyOnScroll"] =  "no";
			}
			
			if(!array_key_exists("stickyOnScrollShowOpener", $preset)){
	    		$preset["stickyOnScrollShowOpener"] =  "yes";
			}
			
			if(!array_key_exists("stickyOnScrollWidth", $preset)){
	    		$preset["stickyOnScrollWidth"] =  700;
			}
			
			if(!array_key_exists("stickyOnScrollHeight", $preset)){
	    		$preset["stickyOnScrollHeight"] =  394;
			}
			
			if(!array_key_exists("showMainScrubberToolTipLabel", $preset)){
	    		$preset["showMainScrubberToolTipLabel"] =  "yes";
			}
			
			if(!array_key_exists("scrubbersToolTipLabelBackgroundColor", $preset)){
	    		$preset["scrubbersToolTipLabelBackgroundColor"] =  "#FFFFFF";
			}
			
			if(!array_key_exists("scrubbersToolTipLabelFontColor", $preset)){
	    		$preset["scrubbersToolTipLabelFontColor"] =  "#000000";
			}
			
			// update new or existing fields
			
			if(!array_key_exists("closeLightBoxWhenPlayComplete", $preset)){
	    		$preset["closeLightBoxWhenPlayComplete"] = 'no';
			}

			if(!array_key_exists("lightBoxBackgroundOpacity", $preset)){
	    		$preset["lightBoxBackgroundOpacity"] =  .6;
			}
			
			if(!array_key_exists("useVectorIcons", $preset)){
	    		$preset["useVectorIcons"] =  "no";
			}
			
			if(!array_key_exists("lightBoxBackgroundColor", $preset)){
	    		$preset["lightBoxBackgroundColor"] =  "#000000";
			}
			
			if(!array_key_exists("googleAnalyticsMeasurementId", $preset)){
	    		$preset["googleAnalyticsMeasurementId"] =  "";
			}
			
			if(!array_key_exists("showRewindButton", $preset)){
	    		$preset["showRewindButton"] =  "yes";
			}
					
			if(!array_key_exists("showOpener", $preset)){
	    		$preset["showOpener"] =  "yes";
			}
			if(!array_key_exists("showOpenerPlayPauseButton", $preset)){
	    		$preset["showOpenerPlayPauseButton"] =  "yes";
			}
			if(!array_key_exists("verticalPosition", $preset)){
	    		$preset["verticalPosition"] =  "bottom";
			}
			if(!array_key_exists("horizontalPosition", $preset)){
	    		$preset["horizontalPosition"] =  "center";
			}
			if(!array_key_exists("showPlayerByDefault", $preset)){
	    		$preset["showPlayerByDefault"] =  "yes";
			}
			if(!array_key_exists("animatePlayer", $preset)){
	    		$preset["animatePlayer"] =  "yes";
			}
			if(!array_key_exists("openerAlignment", $preset)){
	    		$preset["openerAlignment"] =  "right";
			}
			if(!array_key_exists("mainBackgroundImagePath", $preset)){
	    		$preset["mainBackgroundImagePath"] =  plugin_dir_url(dirname(__FILE__)) . "content/minimal_skin_dark/main-background.png";
			}
			if(!array_key_exists("openerEqulizerOffsetTop", $preset)){
	    		$preset["openerEqulizerOffsetTop"] =  -1;
			}
			if(!array_key_exists("openerEqulizerOffsetLeft", $preset)){
	    		$preset["openerEqulizerOffsetLeft"] =  3;
			}
			if(!array_key_exists("offsetX", $preset)){
	    		$preset["offsetX"] =  0;
			}
			if(!array_key_exists("offsetY", $preset)){
	    		$preset["offsetY"] =  0;
			}
			
			
			if (!array_key_exists("stopAfterLastVideoHasPlayed", $preset)){
	    		$preset["stopAfterLastVideoHasPlayed"] =  "no";
			}
			
			if (!array_key_exists("showDefaultControllerForVimeo", $preset)){
	    		$preset["showDefaultControllerForVimeo"] =  "yes";
			}
			
			if (!array_key_exists("preloaderColor1", $preset)){
	    		$preset["preloaderColor1"] =  "#FFFFFF";
			}
			
			if (!array_key_exists("preloaderColor2", $preset)){
	    		$preset["preloaderColor2"] =  "#666666";
			}
			
		
			if (!array_key_exists("initializeOnlyWhenVisible", $preset)){
	    		$preset["initializeOnlyWhenVisible"] =  "yes";
			}
			
			if (!array_key_exists("playAfterVideoStop", $preset)){
	    		$preset["playAfterVideoStop"] =  "no";
			}
			
			
			
			if (!array_key_exists("showPlaylistsSearchInput", $preset)){
	    		$preset["showPlaylistsSearchInput"] =  "yes";
			}
			
			
			
			if (!array_key_exists("showPlaybackRateButton", $preset)){
	    		$preset["showPlaybackRateButton"] =  "yes";
			}
			
			if (!array_key_exists("showErrorInfo", $preset)){
				$preset["showErrorInfo"] = "yes";
			}
			
			if (!array_key_exists("playIfLoggedIn", $preset)){
				$preset["playIfLoggedIn"] = "no";
			}
			
			if (!array_key_exists("loggedInMessage", $preset)){
				$preset["loggedInMessage"] = "Please loggin to view this video.";
			}	
			
			
			if (!array_key_exists("showPreloader", $preset)){
	    		$preset["showPreloader"] =  "yes";
			}
			
			if (!array_key_exists("defaultPlaybackRate", $preset)){
	    		$preset["defaultPlaybackRate"] =  1;
			}
			
			if (!array_key_exists("privateVideoPassword", $preset)){
	    		$preset["privateVideoPassword"] = "428c841430ea18a70f7b06525d4b748a";
			}
			
			if (!array_key_exists("use_playlists_select_box", $preset)){
	    		$preset["use_playlists_select_box"] =  "yes";
			}
			
			if (!array_key_exists("fill_entire_video_screen", $preset)){
	    		$preset["fill_entire_video_screen"] =  "yes";
			}
			
			if (!array_key_exists("use_HEX_colors_for_skin", $preset)){
	    		$preset["use_HEX_colors_for_skin"] =  "no";
			}
			
			if (!array_key_exists("normal_HEX_buttons_color", $preset)){
	    		$preset["normal_HEX_buttons_color"] =  "yes";
			}
			
			
			if (!array_key_exists("show_popup_ads_close_button", $preset)){
	    		$preset["show_popup_ads_close_button"] =  "yes";
			}
			
			if (!array_key_exists("aopwTitle", $preset)){
				$preset["aopwTitle"] = "Advertisement";
			}
	
	
			if (!array_key_exists("aopwWidth", $preset)){
				$preset["aopwWidth"] = 400;
			}
			
			
			if (!array_key_exists("aopwHeight", $preset)){
				$preset["aopwHeight"] = 240;
			}
			
			
			if (!array_key_exists("aopwBorderSize", $preset)){
				$preset["aopwBorderSize"] = 6;
			}
	
	
			
	
			if (!array_key_exists("showErrorInfo", $preset)){
	    		$preset["showErrorInfo"] =  "yes";
			}
			
			if (!array_key_exists("subtitles_off_label", $preset)){
	    		$preset["subtitles_off_label"] =  "Subtitle off";
			}
			
			if (!array_key_exists("show_share_button", $preset)){
	    		$preset["show_share_button"] =  "yes";
			}
			
			if (!array_key_exists("logo_path", $preset))
	    	{
	    		$preset["logo_path"] = plugin_dir_url(dirname(__FILE__)) . "content/logo.png";
			}
			
			if (!array_key_exists("stop_video_when_play_complete", $preset))
			{
				$preset["stop_video_when_play_complete"] = "no";
			}
			
			if (!array_key_exists("disable_video_scrubber", $preset))
			{
				$preset["disable_video_scrubber"] = "no";
			}
			
			if (!array_key_exists("start_at_random_video", $preset))
			{
				$preset["start_at_random_video"] = "no";
			}
			
			if (!array_key_exists("open_new_page_at_the_end_of_the_ads", $preset))
			{
				$preset["open_new_page_at_the_end_of_the_ads"] = "no";
			}
			
			if (!array_key_exists("play_ads_only_once", $preset))
			{
				$preset["play_ads_only_once"] = "no";
			}
			if (!array_key_exists("ads_buttons_position", $preset))
			{
				$preset["ads_buttons_position"] = "left";
			}
			if (!array_key_exists("skip_to_video_text", $preset))
			{
				$preset["skip_to_video_text"] = "You can skip to video in: ";
			}
			if (!array_key_exists("skip_to_video_button_text", $preset))
			{
				$preset["skip_to_video_button_text"] = "Skip Ad";
			}

			switch ($preset["skin_path"]) {
				case "acora_skin":

				if (!array_key_exists("youtubePlaylistAPI", $preset)){
					$preset["youtubePlaylistAPI"] = "AIzaSyD6LNmbZVbixO1s4ZzQV8odsDZsO2NUsl4";
				}
				
				if (!array_key_exists("useAToB", $preset)){
					$preset["useAToB"] = "no";
				}
				if (!array_key_exists("atbTimeBackgroundColor", $preset)){
					$preset["atbTimeBackgroundColor"] = "transparent";
				}
				if (!array_key_exists("atbTimeTextColorNormal", $preset)){
					$preset["atbTimeTextColorNormal"] = "#888888";
				}
				if (!array_key_exists("atbTimeTextColorSelected", $preset)){
					$preset["atbTimeTextColorSelected"] = "#FFFFFF";
				}
				if (!array_key_exists("atbButtonTextNormalColor", $preset)){
					$preset["atbButtonTextNormalColor"] = "#888888";
				}
				if (!array_key_exists("atbButtonTextSelectedColor", $preset)){
					$preset["atbButtonTextSelectedColor"] = "#FFFFFF";
				}
				if (!array_key_exists("atbButtonBackgroundNormalColor", $preset)){
					$preset["atbButtonBackgroundNormalColor"] = "#FFFFFF";
				}
				if (!array_key_exists("atbButtonBackgroundSelectedColor", $preset)){
					$preset["atbButtonBackgroundSelectedColor"] = "#000000";
				}
				
				
				if (!array_key_exists("inputBackgroundColor", $preset)){
					$preset["inputBackgroundColor"] = "#333333";
				}
				
				if (!array_key_exists("inputColor", $preset)){
					$preset["inputColor"] = "#999999";
				}
				
				
				
				if (!array_key_exists("main_selector_background_selected_color", $preset)){
					$preset["main_selector_background_selected_color"] = "#FFFFFF";
				}
				
				if (!array_key_exists("main_selector_text_normal_color", $preset)){
					$preset["main_selector_text_normal_color"] = "#FFFFFF";
				}
				
				if (!array_key_exists("main_selector_text_selected_color", $preset)){
					$preset["main_selector_text_selected_color"] = "#000000";
				}
				
				if (!array_key_exists("main_button_background_normal_color", $preset)){
					$preset["main_button_background_normal_color"] = "#212021";
				}
				
				if (!array_key_exists("main_button_background_selected_color", $preset)){
					$preset["main_button_background_selected_color"] = "#FFFFFF";
				}
				
				if (!array_key_exists("main_button_text_normal_color", $preset)){
					$preset["main_button_text_normal_color"] = "#FFFFFF";
				}
				
				if (!array_key_exists("main_button_text_selected_color", $preset)){
					$preset["main_button_text_selected_color"] = "#000000";
				}
				
				
				if (!array_key_exists("aopwTitleColor", $preset)){
						$preset["aopwTitleColor"] = "#000000";
					}
					if (!array_key_exists("ads_text_normal_color", $preset)){
						$preset["ads_text_normal_color"] = "#888888";
					}
					
					if (!array_key_exists("ads_text_selected_color", $preset))
					{
						$preset["ads_text_selected_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("ads_border_normal_color", $preset))
					{
						$preset["ads_border_normal_color"] = "#666666";
					}
					
					if (!array_key_exists("ads_border_selected_color", $preset))
					{
						$preset["ads_border_selected_color"] = "#FFFFFF";
					}
					break;
				case "minimal_skin_dark":

				if (!array_key_exists("showContextmenu", $preset)){
					$preset["showContextmenu"] = "yes";
				}

				if (!array_key_exists("showScriptDeveloper", $preset)){
					$preset["showScriptDeveloper"] = "no";
				}

				if (!array_key_exists("contextMenuBackgroundColor", $preset)){
					$preset["contextMenuBackgroundColor"] = "#1f1f1f";
				}

				if (!array_key_exists("contextMenuBorderColor", $preset)){
					$preset["contextMenuBorderColor"] = "#1f1f1f";
				}

				if (!array_key_exists("contextMenuSpacerColor", $preset)){
					$preset["contextMenuSpacerColor"] = "#333";
				}

				if (!array_key_exists("contextMenuItemNormalColor", $preset)){
					$preset["contextMenuItemNormalColor"] = "#888888";
				}

				if (!array_key_exists("contextMenuItemSelectedColor", $preset)){
					$preset["contextMenuItemSelectedColor"] = "#FFF";
				}

				if (!array_key_exists("contextMenuItemDisabledColor", $preset)){
					$preset["contextMenuItemDisabledColor"] = "#444";
				}

				if (!array_key_exists("useAToB", $preset)){
					$preset["useAToB"] = "no";
				}
				if (!array_key_exists("atbTimeBackgroundColor", $preset)){
					$preset["atbTimeBackgroundColor"] = "transparent";
				}
				if (!array_key_exists("atbTimeTextColorNormal", $preset)){
					$preset["atbTimeTextColorNormal"] = "#888888";
				}
				if (!array_key_exists("atbTimeTextColorSelected", $preset)){
					$preset["atbTimeTextColorSelected"] = "#FFFFFF";
				}
				if (!array_key_exists("atbButtonTextNormalColor", $preset)){
					$preset["atbButtonTextNormalColor"] = "#888888";
				}
				if (!array_key_exists("atbButtonTextSelectedColor", $preset)){
					$preset["atbButtonTextSelectedColor"] = "#FFFFFF";
				}
				if (!array_key_exists("atbButtonBackgroundNormalColor", $preset)){
					$preset["atbButtonBackgroundNormalColor"] = "#FFFFFF";
				}
				if (!array_key_exists("atbButtonBackgroundSelectedColor", $preset)){
					$preset["atbButtonBackgroundSelectedColor"] = "#000000";
				}
				
				
				if (!array_key_exists("inputBackgroundColor", $preset)){
					$preset["inputBackgroundColor"] = "#333333";
				}
				
				if (!array_key_exists("inputColor", $preset)){
					$preset["inputColor"] = "#999999";
				}
				
				
				
				if (!array_key_exists("main_selector_background_selected_color", $preset)){
					$preset["main_selector_background_selected_color"] = "#FFFFFF";
				}
				
				if (!array_key_exists("main_selector_text_normal_color", $preset)){
					$preset["main_selector_text_normal_color"] = "#FFFFFF";
				}
				
				if (!array_key_exists("main_selector_text_selected_color", $preset)){
					$preset["main_selector_text_selected_color"] = "#000000";
				}
				
				if (!array_key_exists("main_button_background_normal_color", $preset)){
					$preset["main_button_background_normal_color"] = "#212021";
				}
				
				if (!array_key_exists("main_button_background_selected_color", $preset)){
					$preset["main_button_background_selected_color"] = "#FFFFFF";
				}
				
				if (!array_key_exists("main_button_text_normal_color", $preset)){
					$preset["main_button_text_normal_color"] = "#FFFFFF";
				}
				
				if (!array_key_exists("main_button_text_selected_color", $preset)){
					$preset["main_button_text_selected_color"] = "#000000";
				}
				
				
				if (!array_key_exists("aopwTitleColor", $preset)){
						$preset["aopwTitleColor"] = "#000000";
					}
					if (!array_key_exists("ads_text_normal_color", $preset)){
						$preset["ads_text_normal_color"] = "#888888";
					}
					
					if (!array_key_exists("ads_text_selected_color", $preset))
					{
						$preset["ads_text_selected_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("ads_border_normal_color", $preset))
					{
						$preset["ads_border_normal_color"] = "#666666";
					}
					
					if (!array_key_exists("ads_border_selected_color", $preset))
					{
						$preset["ads_border_selected_color"] = "#FFFFFF";
					}
					break;
				case "modern_skin_dark":

					if (!array_key_exists("showContextmenu", $preset)){
						$preset["showContextmenu"] = "yes";
					}
					if (!array_key_exists("showScriptDeveloper", $preset)){
						$preset["showScriptDeveloper"] = "no";
					}
					if (!array_key_exists("contextMenuBackgroundColor", $preset)){
						$preset["contextMenuBackgroundColor"] = "#1f1f1f";
					}
					if (!array_key_exists("contextMenuBorderColor", $preset)){
						$preset["contextMenuBorderColor"] = "#1f1f1f";
					}
					if (!array_key_exists("contextMenuSpacerColor", $preset)){
						$preset["contextMenuSpacerColor"] = "#333";
					}
					if (!array_key_exists("contextMenuItemNormalColor", $preset)){
						$preset["contextMenuItemNormalColor"] = "#6a6a6a";
					}
					if (!array_key_exists("contextMenuItemSelectedColor", $preset)){
						$preset["contextMenuItemSelectedColor"] = "#FFFFFF";
					}
					if (!array_key_exists("contextMenuItemDisabledColor", $preset)){
						$preset["contextMenuItemDisabledColor"] = "#333";
					}

					if (!array_key_exists("useAToB", $preset)){
						$preset["useAToB"] = "no";
					}
					if (!array_key_exists("atbTimeBackgroundColor", $preset)){
						$preset["atbTimeBackgroundColor"] = "transparent";
					}
					if (!array_key_exists("atbTimeTextColorNormal", $preset)){
						$preset["atbTimeTextColorNormal"] = "#888888";
					}
					if (!array_key_exists("atbTimeTextColorSelected", $preset)){
						$preset["atbTimeTextColorSelected"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonTextNormalColor", $preset)){
						$preset["atbButtonTextNormalColor"] = "#888888";
					}
					if (!array_key_exists("atbButtonTextSelectedColor", $preset)){
						$preset["atbButtonTextSelectedColor"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonBackgroundNormalColor", $preset)){
						$preset["atbButtonBackgroundNormalColor"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonBackgroundSelectedColor", $preset)){
						$preset["atbButtonBackgroundSelectedColor"] = "#000000";
					}
				
					if (!array_key_exists("inputBackgroundColor", $preset)){
						$preset["inputBackgroundColor"] = "#333333";
					}
					
					if (!array_key_exists("inputColor", $preset)){
						$preset["inputColor"] = "#999999";
					}
				
					if (!array_key_exists("aopwTitleColor", $preset)){
						$preset["aopwTitleColor"] = "#FFFFFF";
					}
					if (!array_key_exists("ads_text_normal_color", $preset))
					{
						$preset["ads_text_normal_color"] = "#888888";
					}
					
					if (!array_key_exists("ads_text_selected_color", $preset))
					{
						$preset["ads_text_selected_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("ads_border_normal_color", $preset))
					{
						$preset["ads_border_normal_color"] = "#666666";
					}
					
					if (!array_key_exists("ads_border_selected_color", $preset))
					{
						$preset["ads_border_selected_color"] = "#FFFFFF";
					}
					break;
				case "classic_skin_dark":
					if (!array_key_exists("showContextmenu", $preset)){
						$preset["showContextmenu"] = "yes";
					}
					if (!array_key_exists("showScriptDeveloper", $preset)){
						$preset["showScriptDeveloper"] = "no";
					}
					if (!array_key_exists("contextMenuBackgroundColor", $preset)){
						$preset["contextMenuBackgroundColor"] = "#1b1b1b";
					}
					if (!array_key_exists("contextMenuBorderColor", $preset)){
						$preset["contextMenuBorderColor"] = "#1b1b1b";
					}
					if (!array_key_exists("contextMenuSpacerColor", $preset)){
						$preset["contextMenuSpacerColor"] = "#333";
					}
					if (!array_key_exists("contextMenuItemNormalColor", $preset)){
						$preset["contextMenuItemNormalColor"] = "#bdbdbd";
					}
					if (!array_key_exists("contextMenuItemSelectedColor", $preset)){
						$preset["contextMenuItemSelectedColor"] = "#FFFFFF";
					}
					if (!array_key_exists("contextMenuItemDisabledColor", $preset)){
						$preset["contextMenuItemDisabledColor"] = "#333";
					}
				
					if (!array_key_exists("useAToB", $preset)){
						$preset["useAToB"] = "no";
					}
					if (!array_key_exists("atbTimeBackgroundColor", $preset)){
						$preset["atbTimeBackgroundColor"] = "transparent";
					}
					if (!array_key_exists("atbTimeTextColorNormal", $preset)){
						$preset["atbTimeTextColorNormal"] = "#888888";
					}
					if (!array_key_exists("atbTimeTextColorSelected", $preset)){
						$preset["atbTimeTextColorSelected"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonTextNormalColor", $preset)){
						$preset["atbButtonTextNormalColor"] = "#888888";
					}
					if (!array_key_exists("atbButtonTextSelectedColor", $preset)){
						$preset["atbButtonTextSelectedColor"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonBackgroundNormalColor", $preset)){
						$preset["atbButtonBackgroundNormalColor"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonBackgroundSelectedColor", $preset)){
						$preset["atbButtonBackgroundSelectedColor"] = "#000000";
					}
					
					if (!array_key_exists("inputBackgroundColor", $preset)){
						$preset["inputBackgroundColor"] = "#333333";
					}
					
					if (!array_key_exists("inputColor", $preset)){
						$preset["inputColor"] = "#999999";
					}
				
					if (!array_key_exists("aopwTitleColor", $preset)){
						$preset["aopwTitleColor"] = "#FFFFFF";
					}
					if (!array_key_exists("ads_text_normal_color", $preset))
					{
						$preset["ads_text_normal_color"] = "#bdbdbd";
					}
					
					if (!array_key_exists("ads_text_selected_color", $preset))
					{
						$preset["ads_text_selected_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("ads_border_normal_color", $preset))
					{
						$preset["ads_border_normal_color"] = "#444444";
					}
					
					if (!array_key_exists("ads_border_selected_color", $preset))
					{
						$preset["ads_border_selected_color"] = "#FFFFFF";
					}
					break;
				case "metal_skin_dark":

					if (!array_key_exists("showContextmenu", $preset)){
						$preset["showContextmenu"] = "yes";
					}
					if (!array_key_exists("showScriptDeveloper", $preset)){
						$preset["showScriptDeveloper"] = "no";
					}
					if (!array_key_exists("contextMenuBackgroundColor", $preset)){
						$preset["contextMenuBackgroundColor"] = "#1b1b1b";
					}
					if (!array_key_exists("contextMenuBorderColor", $preset)){
						$preset["contextMenuBorderColor"] = "#1b1b1b";
					}
					if (!array_key_exists("contextMenuSpacerColor", $preset)){
						$preset["contextMenuSpacerColor"] = "#333";
					}
					if (!array_key_exists("contextMenuItemNormalColor", $preset)){
						$preset["contextMenuItemNormalColor"] = "#888888";
					}
					if (!array_key_exists("contextMenuItemSelectedColor", $preset)){
						$preset["contextMenuItemSelectedColor"] = "#FFFFFF";
					}
					if (!array_key_exists("contextMenuItemDisabledColor", $preset)){
						$preset["contextMenuItemDisabledColor"] = "#333";
					}

					if (!array_key_exists("useAToB", $preset)){
						$preset["useAToB"] = "no";
					}
					if (!array_key_exists("atbTimeBackgroundColor", $preset)){
						$preset["atbTimeBackgroundColor"] = "transparent";
					}
					if (!array_key_exists("atbTimeTextColorNormal", $preset)){
						$preset["atbTimeTextColorNormal"] = "#888888";
					}
					if (!array_key_exists("atbTimeTextColorSelected", $preset)){
						$preset["atbTimeTextColorSelected"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonTextNormalColor", $preset)){
						$preset["atbButtonTextNormalColor"] = "#888888";
					}
					if (!array_key_exists("atbButtonTextSelectedColor", $preset)){
						$preset["atbButtonTextSelectedColor"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonBackgroundNormalColor", $preset)){
						$preset["atbButtonBackgroundNormalColor"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonBackgroundSelectedColor", $preset)){
						$preset["atbButtonBackgroundSelectedColor"] = "#000000";
					}
				
					if (!array_key_exists("inputBackgroundColor", $preset)){
						$preset["inputBackgroundColor"] = "#333333";
					}
					
					if (!array_key_exists("inputColor", $preset)){
						$preset["inputColor"] = "#999999";
					}
				
					if (!array_key_exists("aopwTitleColor", $preset)){
						$preset["aopwTitleColor"] = "#FFFFFF";
					}
					if (!array_key_exists("ads_text_normal_color", $preset))
					{
						$preset["ads_text_normal_color"] = "#999999";
					}
					
					if (!array_key_exists("ads_text_selected_color", $preset))
					{
						$preset["ads_text_selected_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("ads_border_normal_color", $preset))
					{
						$preset["ads_border_normal_color"] = "#666666";
					}
					
					if (!array_key_exists("ads_border_selected_color", $preset))
					{
						$preset["ads_border_selected_color"] = "#FFFFFF";
					}
					break;
				case "minimal_skin_white":
					if (!array_key_exists("showContextmenu", $preset)){
						$preset["showContextmenu"] = "yes";
					}
					if (!array_key_exists("showScriptDeveloper", $preset)){
						$preset["showScriptDeveloper"] = "no";
					}
					if (!array_key_exists("contextMenuBackgroundColor", $preset)){
						$preset["contextMenuBackgroundColor"] = "#ebebeb";
					}
					if (!array_key_exists("contextMenuBorderColor", $preset)){
						$preset["contextMenuBorderColor"] = "#ebebeb";
					}
					if (!array_key_exists("contextMenuSpacerColor", $preset)){
						$preset["contextMenuSpacerColor"] = "#CCC";
					}
					if (!array_key_exists("contextMenuItemNormalColor", $preset)){
						$preset["contextMenuItemNormalColor"] = "#888888";
					}
					if (!array_key_exists("contextMenuItemSelectedColor", $preset)){
						$preset["contextMenuItemSelectedColor"] = "#000";
					}
					if (!array_key_exists("contextMenuItemDisabledColor", $preset)){
						$preset["contextMenuItemDisabledColor"] = "#BBB";
					}

					if (!array_key_exists("useAToB", $preset)){
						$preset["useAToB"] = "no";
					}
					if (!array_key_exists("atbTimeBackgroundColor", $preset)){
						$preset["atbTimeBackgroundColor"] = "transparent";
					}
					if (!array_key_exists("atbTimeTextColorNormal", $preset)){
						$preset["atbTimeTextColorNormal"] = "#888888";
					}
					if (!array_key_exists("atbTimeTextColorSelected", $preset)){
						$preset["atbTimeTextColorSelected"] = "#000000";
					}
					if (!array_key_exists("atbButtonTextNormalColor", $preset)){
						$preset["atbButtonTextNormalColor"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonTextSelectedColor", $preset)){
						$preset["atbButtonTextSelectedColor"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonBackgroundNormalColor", $preset)){
						$preset["atbButtonBackgroundNormalColor"] = "#888888";
					}
					if (!array_key_exists("atbButtonBackgroundSelectedColor", $preset)){
						$preset["atbButtonBackgroundSelectedColor"] = "#000000";
					}
			
					if (!array_key_exists("inputBackgroundColor", $preset)){
						$preset["inputBackgroundColor"] = "#333333";
					}
					
					if (!array_key_exists("inputColor", $preset)){
						$preset["inputColor"] = "#000000";
					}
					
					if (!array_key_exists("main_selector_background_selected_color", $preset)){
						$preset["main_selector_background_selected_color"] = "#000000";
					}
					
					if (!array_key_exists("main_selector_text_normal_color", $preset)){
						$preset["main_selector_text_normal_color"] = "#000000";
					}
					
					if (!array_key_exists("main_selector_text_selected_color", $preset)){
						$preset["main_selector_text_selected_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("main_button_background_normal_color", $preset)){
						$preset["main_button_background_normal_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("main_button_background_selected_color", $preset)){
						$preset["main_button_background_selected_color"] = "#000000";
					}
					
					if (!array_key_exists("main_button_text_normal_color", $preset)){
						$preset["main_button_text_normal_color"] = "#000000";
					}
					
					if (!array_key_exists("main_button_text_normal_color", $preset)){
						$preset["main_button_text_normal_color"] = "#000000";
					}
					
					if (!array_key_exists("main_button_text_normal_color", $preset)){
						$preset["main_button_text_selected_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("aopwTitleColor", $preset)){
						$preset["aopwTitleColor"] = "#000000";
					}
					if (!array_key_exists("ads_text_normal_color", $preset)){
						$preset["ads_text_normal_color"] = "#888888";
					}
					
					if (!array_key_exists("ads_text_selected_color", $preset))
					{
						$preset["ads_text_selected_color"] = "#000000";
					}
					
					if (!array_key_exists("ads_border_normal_color", $preset))
					{
						$preset["ads_border_normal_color"] = "#AAAAAA";
					}
					
					if (!array_key_exists("ads_border_selected_color", $preset))
					{
						$preset["ads_border_selected_color"] = "#000000";
					}
					break;
				case "modern_skin_white":
					if (!array_key_exists("showContextmenu", $preset)){
						$preset["showContextmenu"] = "yes";
					}
					if (!array_key_exists("showScriptDeveloper", $preset)){
						$preset["showScriptDeveloper"] = "no";
					}
					if (!array_key_exists("contextMenuBackgroundColor", $preset)){
						$preset["contextMenuBackgroundColor"] = "#e3e3e3";
					}
					if (!array_key_exists("contextMenuBorderColor", $preset)){
						$preset["contextMenuBorderColor"] = "#e3e3e3";
					}
					if (!array_key_exists("contextMenuSpacerColor", $preset)){
						$preset["contextMenuSpacerColor"] = "#CCC";
					}
					if (!array_key_exists("contextMenuItemNormalColor", $preset)){
						$preset["contextMenuItemNormalColor"] = "#777";
					}
					if (!array_key_exists("contextMenuItemSelectedColor", $preset)){
						$preset["contextMenuItemSelectedColor"] = "#000";
					}
					if (!array_key_exists("contextMenuItemDisabledColor", $preset)){
						$preset["contextMenuItemDisabledColor"] = "#BBB";
					}

					if (!array_key_exists("useAToB", $preset)){
						$preset["useAToB"] = "no";
					}
					if (!array_key_exists("atbTimeBackgroundColor", $preset)){
						$preset["atbTimeBackgroundColor"] = "transparent";
					}
					if (!array_key_exists("atbTimeTextColorNormal", $preset)){
						$preset["atbTimeTextColorNormal"] = "#888888";
					}
					if (!array_key_exists("atbTimeTextColorSelected", $preset)){
						$preset["atbTimeTextColorSelected"] = "#000000";
					}
					if (!array_key_exists("atbButtonTextNormalColor", $preset)){
						$preset["atbButtonTextNormalColor"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonTextSelectedColor", $preset)){
						$preset["atbButtonTextSelectedColor"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonBackgroundNormalColor", $preset)){
						$preset["atbButtonBackgroundNormalColor"] = "#888888";
					}
					if (!array_key_exists("atbButtonBackgroundSelectedColor", $preset)){
						$preset["atbButtonBackgroundSelectedColor"] = "#000000";
					}
				
					if (!array_key_exists("inputBackgroundColor", $preset)){
						$preset["inputBackgroundColor"] = "#333333";
					}
					
					if (!array_key_exists("inputColor", $preset)){
						$preset["inputColor"] = "#000000";
					}
				
					if (!array_key_exists("main_selector_background_selected_color", $preset)){
						$preset["main_selector_background_selected_color"] = "#000000";
					}
					
					if (!array_key_exists("main_selector_text_normal_color", $preset)){
						$preset["main_selector_text_normal_color"] = "#000000";
					}
					
					if (!array_key_exists("main_selector_text_selected_color", $preset)){
						$preset["main_selector_text_selected_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("main_button_background_normal_color", $preset)){
						$preset["main_button_background_normal_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("main_button_background_selected_color", $preset)){
						$preset["main_button_background_selected_color"] = "#000000";
					}
					
					if (!array_key_exists("main_button_text_normal_color", $preset)){
						$preset["main_button_text_normal_color"] = "#000000";
					}
					
					if (!array_key_exists("main_button_text_normal_color", $preset)){
						$preset["main_button_text_normal_color"] = "#000000";
					}
					
					if (!array_key_exists("main_button_text_normal_color", $preset)){
						$preset["main_button_text_selected_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("aopwTitleColor", $preset)){
						$preset["aopwTitleColor"] = "#000000";
					}
					if (!array_key_exists("ads_text_normal_color", $preset))
					{
						$preset["ads_text_normal_color"] = "#6a6a6a";
					}
					
					if (!array_key_exists("ads_text_selected_color", $preset))
					{
						$preset["ads_text_selected_color"] = "#000000";
					}
					
					if (!array_key_exists("ads_border_normal_color", $preset))
					{
						$preset["ads_border_normal_color"] = "#BBBBBB";
					}
					
					if (!array_key_exists("ads_border_selected_color", $preset))
					{
						$preset["ads_border_selected_color"] = "#000000";
					}
					break;
				case "classic_skin_white":
					if (!array_key_exists("showContextmenu", $preset)){
						$preset["showContextmenu"] = "yes";
					}
					if (!array_key_exists("showScriptDeveloper", $preset)){
						$preset["showScriptDeveloper"] = "no";
					}
					if (!array_key_exists("contextMenuBackgroundColor", $preset)){
						$preset["contextMenuBackgroundColor"] = "#ebebeb";
					}
					if (!array_key_exists("contextMenuBorderColor", $preset)){
						$preset["contextMenuBorderColor"] = "#ebebeb";
					}
					if (!array_key_exists("contextMenuSpacerColor", $preset)){
						$preset["contextMenuSpacerColor"] = "#CCC";
					}
					if (!array_key_exists("contextMenuItemNormalColor", $preset)){
						$preset["contextMenuItemNormalColor"] = "#666666";
					}
					if (!array_key_exists("contextMenuItemSelectedColor", $preset)){
						$preset["contextMenuItemSelectedColor"] = "#000";
					}
					if (!array_key_exists("contextMenuItemDisabledColor", $preset)){
						$preset["contextMenuItemDisabledColor"] = "#AAA";
					}

					if (!array_key_exists("useAToB", $preset)){
						$preset["useAToB"] = "no";
					}
					if (!array_key_exists("atbTimeBackgroundColor", $preset)){
						$preset["atbTimeBackgroundColor"] = "transparent";
					}
					if (!array_key_exists("atbTimeTextColorNormal", $preset)){
						$preset["atbTimeTextColorNormal"] = "#888888";
					}
					if (!array_key_exists("atbTimeTextColorSelected", $preset)){
						$preset["atbTimeTextColorSelected"] = "#000000";
					}
					if (!array_key_exists("atbButtonTextNormalColor", $preset)){
						$preset["atbButtonTextNormalColor"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonTextSelectedColor", $preset)){
						$preset["atbButtonTextSelectedColor"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonBackgroundNormalColor", $preset)){
						$preset["atbButtonBackgroundNormalColor"] = "#888888";
					}
					if (!array_key_exists("atbButtonBackgroundSelectedColor", $preset)){
						$preset["atbButtonBackgroundSelectedColor"] = "#000000";
					}
				
					if (!array_key_exists("inputBackgroundColor", $preset)){
						$preset["inputBackgroundColor"] = "#333333";
					}
					
					if (!array_key_exists("inputColor", $preset)){
						$preset["inputColor"] = "#000000";
					}
				
					if (!array_key_exists("main_selector_background_selected_color", $preset)){
						$preset["main_selector_background_selected_color"] = "#000000";
					}
					
					if (!array_key_exists("main_selector_text_normal_color", $preset)){
						$preset["main_selector_text_normal_color"] = "#000000";
					}
					
					if (!array_key_exists("main_selector_text_selected_color", $preset)){
						$preset["main_selector_text_selected_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("main_button_background_normal_color", $preset)){
						$preset["main_button_background_normal_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("main_button_background_selected_color", $preset)){
						$preset["main_button_background_selected_color"] = "#000000";
					}
					
					if (!array_key_exists("main_button_text_normal_color", $preset)){
						$preset["main_button_text_normal_color"] = "#000000";
					}
					
					if (!array_key_exists("main_button_text_normal_color", $preset)){
						$preset["main_button_text_normal_color"] = "#000000";
					}
					
					if (!array_key_exists("main_button_text_normal_color", $preset)){
						$preset["main_button_text_selected_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("aopwTitleColor", $preset)){
						$preset["aopwTitleColor"] = "#000000";
					}
					if (!array_key_exists("ads_text_normal_color", $preset))
					{
						$preset["ads_text_normal_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("ads_text_selected_color", $preset))
					{
						$preset["ads_text_selected_color"] = "#494949";
					}
					
					if (!array_key_exists("ads_border_normal_color", $preset))
					{
						$preset["ads_border_normal_color"] = "#BBBBBB";
					}
					
					if (!array_key_exists("ads_border_selected_color", $preset))
					{
						$preset["ads_border_selected_color"] = "#494949";
					}
					break;
				case "metal_skin_white":
					if (!array_key_exists("showContextmenu", $preset)){
						$preset["showContextmenu"] = "yes";
					}
					if (!array_key_exists("showScriptDeveloper", $preset)){
						$preset["showScriptDeveloper"] = "no";
					}
					if (!array_key_exists("contextMenuBackgroundColor", $preset)){
						$preset["contextMenuBackgroundColor"] = "#dcdcdc";
					}
					if (!array_key_exists("contextMenuBorderColor", $preset)){
						$preset["contextMenuBorderColor"] = "#dcdcdc";
					}
					if (!array_key_exists("contextMenuSpacerColor", $preset)){
						$preset["contextMenuSpacerColor"] = "#CCC";
					}
					if (!array_key_exists("contextMenuItemNormalColor", $preset)){
						$preset["contextMenuItemNormalColor"] = "#666666";
					}
					if (!array_key_exists("contextMenuItemSelectedColor", $preset)){
						$preset["contextMenuItemSelectedColor"] = "#000";
					}
					if (!array_key_exists("contextMenuItemDisabledColor", $preset)){
						$preset["contextMenuItemDisabledColor"] = "#AAA";
					}
				
					if (!array_key_exists("useAToB", $preset)){
						$preset["useAToB"] = "no";
					}
					if (!array_key_exists("atbTimeBackgroundColor", $preset)){
						$preset["atbTimeBackgroundColor"] = "transparent";
					}
					if (!array_key_exists("atbTimeTextColorNormal", $preset)){
						$preset["atbTimeTextColorNormal"] = "#888888";
					}
					if (!array_key_exists("atbTimeTextColorSelected", $preset)){
						$preset["atbTimeTextColorSelected"] = "#000000";
					}
					if (!array_key_exists("atbButtonTextNormalColor", $preset)){
						$preset["atbButtonTextNormalColor"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonTextSelectedColor", $preset)){
						$preset["atbButtonTextSelectedColor"] = "#FFFFFF";
					}
					if (!array_key_exists("atbButtonBackgroundNormalColor", $preset)){
						$preset["atbButtonBackgroundNormalColor"] = "#888888";
					}
					if (!array_key_exists("atbButtonBackgroundSelectedColor", $preset)){
						$preset["atbButtonBackgroundSelectedColor"] = "#000000";
					}
				
					if (!array_key_exists("inputBackgroundColor", $preset)){
						$preset["inputBackgroundColor"] = "#333333";
					}
					
					if (!array_key_exists("inputColor", $preset)){
						$preset["inputColor"] = "#000000";
					}
				
					if (!array_key_exists("main_selector_background_selected_color", $preset)){
						$preset["main_selector_background_selected_color"] = "#000000";
					}
					
					if (!array_key_exists("main_selector_text_normal_color", $preset)){
						$preset["main_selector_text_normal_color"] = "#000000";
					}
					
					if (!array_key_exists("main_selector_text_selected_color", $preset)){
						$preset["main_selector_text_selected_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("main_button_background_normal_color", $preset)){
						$preset["main_button_background_normal_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("main_button_background_selected_color", $preset)){
						$preset["main_button_background_selected_color"] = "#000000";
					}
					
					if (!array_key_exists("main_button_text_normal_color", $preset)){
						$preset["main_button_text_normal_color"] = "#000000";
					}
					
					if (!array_key_exists("main_button_text_normal_color", $preset)){
						$preset["main_button_text_normal_color"] = "#000000";
					}
					
					if (!array_key_exists("main_button_text_normal_color", $preset)){
						$preset["main_button_text_selected_color"] = "#FFFFFF";
					}
					
					if (!array_key_exists("aopwTitleColor", $preset)){
						$preset["aopwTitleColor"] = "#000000";
					}
					if (!array_key_exists("ads_text_normal_color", $preset))
					{
						$preset["ads_text_normal_color"] = "#777777";
					}
					
					if (!array_key_exists("ads_text_selected_color", $preset))
					{
						$preset["ads_text_selected_color"] = "#333333";
					}
					
					if (!array_key_exists("ads_border_normal_color", $preset))
					{
						$preset["ads_border_normal_color"] = "#AAAAAA";
					}
					
					if (!array_key_exists("ads_border_selected_color", $preset))
					{
						$preset["ads_border_selected_color"] = "#333333";
					}
					break;
			}
    	}
		
		$this->set_data();
	}

    
    // Initialize settings.
    private function init_settings(){

		if(empty($this->videoStartBehaviour)){
			$this->videoStartBehaviour = "pause";
		}else{
			$this->videoStartBehaviour = get_option("fwduvp_data")->videoStartBehaviour;
		}

		if(FWDUVP_TEXT_DOMAIN == 'acora'){
			$this->settings_ar = array(
									array(
											// main settings
											"id" => 0,
											"woo_commerce_display_type" => "fwduvp-responsive",
											"name" => "default",
											"showErrorInfo" => "yes",
											"initializeOnlyWhenVisible" => "yes",
											"showPlaybackRateButton" => "yes",
											"showPreloader" => "yes",
											"defaultPlaybackRate" => 1,
											"privateVideoPassword" => "428c841430ea18a70f7b06525d4b748a",
											"use_playlists_select_box" => "yes",
											"main_selector_background_selected_color" => "#FFFFFF",
											"main_selector_text_normal_color" => "#FFFFFF",
											"main_selector_text_selected_color" => "#000000",
											"main_button_background_normal_color" => "#212021",
											"main_button_background_selected_color" => "#FFFFFF",
											"main_button_text_normal_color" => "#FFFFFF",
											"main_button_text_selected_color" => "#000000",
											"playAfterVideoStop" => "no",
											"stickyOnScroll" => "no",
											"stickyOnScrollShowOpener" =>"yes",
											"stickyOnScrollWidth" => 700,
											"stickyOnScrollHeight" => 394,
											"preloaderColor1" => "#FFFFFF",
											"preloaderColor2" => "#666666",
											"stopAfterLastVideoHasPlayed" => "no",
											"showDefaultControllerForVimeo" => "yes",
											"fill_entire_video_screen" => "no",
											"fillEntireposterScreen" => "yes",
											"goFullScreenOnButtonPlay" => "no",
											"use_HEX_colors_for_skin" => "no",
											"normal_HEX_buttons_color" => "#FF0000",
											"useVectorIcons" => "no",
											"showScrubberWhenControllerIsHidden" => "yes",
											"playsinline" => "yes",
											"useResumeOnPlay" => "no",
											"subtitles_off_label" => "Subtitle off",
											"playVideoOnlyWhenLoggedIn" => "no",
											"loggedInMessage" => "Please loggin to view this video.",
											"skin_path" => "acora_skin",
											"googleAnalyticsMeasurementId" => "",
											"closeLightBoxWhenPlayComplete" => "no",
											"lightBoxBackgroundOpacity" => ".6",
											"lightBoxBackgroundColor" => "#000000",
											"display_type" => "responsive",
											"use_deeplinking" => "yes",
											"right_click_context_menu" => "disabled",
											"add_keyboard_support" => "yes",
											"auto_scale" => "yes",
											"show_buttons_tooltips" => "yes",
											"autoplay" => "no",
											"autoPlayText" => "Click To Unmute",
											"loop" => "no",
											"shuffle" => "no",
											"showMainScrubberToolTipLabel" => "yes",
											"show_popup_ads_close_button" => "yes",
											"max_width" => 1320,
											"max_height" => 732,
											"volume" => .8,
											"rewindTime" => 10,
											"bg_color" => "#000000",
											"video_bg_color" => "#000000",
											"poster_bg_color" => "#000000",
											"buttons_tooltip_hide_delay" => 1.5,
											"buttons_tooltip_font_color" => "#5a5a5a",
								   			"audioVisualizerLinesColor" => "#CCCCCC",
								   			"audioVisualizerCircleColor" => "#FFFFFF",
											//apw
											"aopwTitle" => "Advertisement",
											"aopwWidth" => 400,
											"aopwHeight" => 240,
											"aopwBorderSize" => 6,
											"aopwTitleColor" => "#000000",
											//thumbnails preview
											"thumbnails_preview_width" => 196,
											"thumbnails_preview_height" => 110,
											"thumbnails_preview_background_color" => "#000000",
											"thumbnails_preview_border_color" => "#666",
											"thumbnails_preview_label_background_color" => "#666",
											"thumbnails_preview_label_font_color" => "#FFF",
											//a to b loop
											"useAToB" => "no",
											"atbTimeBackgroundColor" => "transparent",
											"atbTimeTextColorNormal" => "#888888",
											"atbTimeTextColorSelected" => "#FFFFFF",
											"atbButtonTextNormalColor" => "#888888",
											"atbButtonTextSelectedColor" => "#FFFFFF",
											"atbButtonBackgroundNormalColor" => "#FFFFFF",
											"atbButtonBackgroundSelectedColor" => "#000000",
											//sticky
											"showOpener" => "yes",
											"showOpenerPlayPauseButton" => "yes",
											"verticalPosition" => "bottom",
											"horizontalPosition" => "center",
											"showPlayerByDefault" =>"yes",
											"animatePlayer:" => "yes",
											"openerAlignment" =>"right",
											"mainBackgroundImagePath" =>  plugin_dir_url(dirname(__FILE__)) . "content/minimal_skin_dark/main-background.png",
											"openerEqulizerOffsetTop" => -1,
											"openerEqulizerOffsetLeft" => 3,
											"offsetX" => 0,
											"offsetY" => 0,
											// controller settings
											"showController" => "yes",
											"show_controller_when_video_is_stopped" => "yes",
											"show_next_and_prev_buttons_in_controller" => "no",
											"show_volume_button" => "yes",
											"show_time" => "yes",
											"show_youtube_quality_button" => "yes",
											"show_info_button" => "no",
											"show_download_button" => "yes",
											"show_share_button" => "yes",
											"show_embed_button" => "yes",
											"showChromecastButton" => "no",
											"show360DegreeVideoVrButton" => "no",
											"showSubtitleButton" => "yes",
											"showRewindButton" => "yes",
											"showAudioTracksButton" => "yes",
											"show_fullscreen_button" => "yes",
											"disable_video_scrubber" => "no",
											"repeat_background" => "yes",
											"controller_height" => 47,
											"controller_hide_delay" => 3,
											"start_space_between_buttons" => 14,
											"space_between_buttons" => 12,
											"scrubbers_offset_width" => 2,
											"main_scrubber_offest_top" => 14,
											"time_offset_left_width" => 5,
											"time_offset_right_width" => 3,
											"time_offset_top" => 0,
											"volume_scrubber_height" => 80,
											"volume_scrubber_ofset_height" => 12,
											"time_color" => "#888888",
											"showYoutubeRelAndInfo"=> "no",
											"youtube_quality_button_normal_color" => "#888888",
											"youtube_quality_button_selected_color" => "#FFFFFF",
											
											// playlists window settings
											"showPlaylistsSearchInput" => "yes",
											"inputBackgroundColor" => "#FF0000",
											"show_playlists_button_and_playlists" => "no",
											"show_playlists_by_default" => "no",
											"thumbnail_selected_type" => "opacity",
											"youtubePlaylistAPI"	=> "AIzaSyD6LNmbZVbixO1s4ZzQV8odsDZsO2NUsl4",
											"start_at_playlist" => 0,
											"buttons_margins" => 15,
											"thumbnail_max_width" => 450,
											"thumbnail_max_height" => 330,
											"horizontal_space_between_thumbnails" => 40,
											"vertical_space_between_thumbnails" => 40,
											
											// playlist settings
											"randomizePlaylist" => "no",
											"show_playlist_button_and_playlist" => "yes",
											"playlist_position" => "right",
											"show_playlist_by_default" => "yes",
											"show_playlist_name" => "yes",
											"show_search_input" => "yes",
											"show_loop_button" => "yes",
											"show_shuffle_button" => "yes",
											"show_next_and_prev_buttons" => "yes",
											"force_disable_download_button_for_folder" => "yes",
											"add_mouse_wheel_support" => "yes",
											"showThumbnail" => "yes",
											"showOnlyThumbnail" => "no",
											"addScrollOnMouseMove" => "no",
											"showPlaylistOnFullScreen" => "yes",
											"executeCuepointsOnlyOnce" => "no",
											"folder_video_label" => "VIDEO",
											"playlist_right_width" => 390,
											"playlist_bottom_height" => 400,
											"start_at_video" => 0,
											"max_playlist_items" => 50,
											"thumbnail_width" => 71,
											"thumbnail_height" => 71,
											"space_between_controller_and_playlist" => 1,
											"space_between_thumbnails" => 1,
											"scrollbar_offest_width" => 8,
											"scollbar_speed_sensitivity" => .5,
											"playlist_background_color" => "#111111",
											"playlist_name_color" => "#FFFFFF",
											"thumbnail_normal_background_color" => "#111111",
											"thumbnail_hover_background_color" => "#1f1f1f",
											"thumbnail_disabled_background_color" => "#1f1f1f",
											"search_input_background_color" => "#111111",
											"search_input_color" => "#999999",
											"youtube_and_folder_video_title_color" => "#FFFFFF",
											"youtube_owner_color" => "#888888",
											"youtube_description_color" => "#888888",
											
											// logo settings
											"show_logo" => "yes",
											"hide_logo_with_controller" => "yes",
											"logo_position" => "topRight",
											"logo_path" => plugin_dir_url(dirname(__FILE__)) . "content/logo.png",
											"logo_link" => "http://www.webdesign-flash.ro/",
											"logoTarget" => "_blank",
											"logo_margins" => 5,
											
											// embed and info windows settings
											"embed_and_info_window_close_button_margins" => 15,
											"border_color" => "#333333",
											"main_labels_color" => "#FFFFFF",
											"secondary_labels_color" => "#a1a1a1",
											"share_and_embed_text_color" => "#5a5a5a",
											"input_background_color" => "#000000",
											"input_color" => "#FFFFFF",

											// context menu
											"showContextmenu" =>'yes',
											"showScriptDeveloper" => "no",
											"contextMenuBackgroundColor" => "#1f1f1f",
											"contextMenuBorderColor" => "#1f1f1f",
											"contextMenuSpacerColor" => "#333",
											"contextMenuItemNormalColor" => "#888888",
											"contextMenuItemSelectedColor" => "#FFFFFF",
											"contextMenuItemDisabledColor" => "#444",

											// finger pring stamp
											"useFingerPrintStamp" => "no",
								    		"frequencyOfFingerPrintStamp" => 20000,
								    		"durationOfFingerPrintStamp" => 50
									)
								);
		}else{
			$this->settings_ar = array(
									array(
											// main settings
											"id" => 0,
											"woo_commerce_display_type" => "fwduvp-responsive",
											"name" => "skin_minimal_dark",
											
											"showErrorInfo" => "yes",
											"initializeOnlyWhenVisible" => "yes",
											"showPlaybackRateButton" => "yes",
											"showPreloader" => "yes",
											"defaultPlaybackRate" => 1,
											"privateVideoPassword" => "428c841430ea18a70f7b06525d4b748a",
											"use_playlists_select_box" => "yes",
											"main_selector_background_selected_color" => "#FFFFFF",
											"main_selector_text_normal_color" => "#FFFFFF",
											"main_selector_text_selected_color" => "#000000",
											"main_button_background_normal_color" => "#212021",
											"main_button_background_selected_color" => "#FFFFFF",
											"main_button_text_normal_color" => "#FFFFFF",
											"main_button_text_selected_color" => "#000000",
											"playAfterVideoStop" => "no",
											"stickyOnScroll" => "no",
											"stickyOnScrollShowOpener" =>"yes",
											"stickyOnScrollWidth" => 700,
											"stickyOnScrollHeight" => 394,
											"preloaderColor1" => "#FFFFFF",
											"preloaderColor2" => "#666666",
											"stopAfterLastVideoHasPlayed" => "no",
											"showDefaultControllerForVimeo" => "yes",
											"fill_entire_video_screen" => "no",
											"fillEntireposterScreen" => "yes",
											"goFullScreenOnButtonPlay" => "no",
											"use_HEX_colors_for_skin" => "no",
											"normal_HEX_buttons_color" => "#FF0000",
											"useVectorIcons" => "no",
											"showScrubberWhenControllerIsHidden" => "yes",
											"playsinline" => "yes",
											"useResumeOnPlay" => "no",
											"subtitles_off_label" => "Subtitle off",
											"playVideoOnlyWhenLoggedIn" => "no",
											"loggedInMessage" => "Please loggin to view this video.",
											"skin_path" => "minimal_skin_dark",
											"googleAnalyticsMeasurementId" => "",
											"closeLightBoxWhenPlayComplete" => "no",
											"lightBoxBackgroundOpacity" => ".6",
											"lightBoxBackgroundColor" => "#000000",
											"display_type" => "responsive",
											"use_deeplinking" => "yes",
											"right_click_context_menu" => "developer",
											"add_keyboard_support" => "yes",
											"auto_scale" => "yes",
											"show_buttons_tooltips" => "yes",
											"autoplay" => "no",
											"autoPlayText" => "Click To Unmute",
											"loop" => "no",
											"shuffle" => "no",
											"showMainScrubberToolTipLabel" => "yes",
											
											"show_popup_ads_close_button" => "yes",
											"max_width" => 980,
											"max_height" => 552,
											"volume" => .8,
											"rewindTime" => 10,
											"bg_color" => "#000000",
											"video_bg_color" => "#000000",
											"poster_bg_color" => "#000000",
											"buttons_tooltip_hide_delay" => 1.5,
											"buttons_tooltip_font_color" => "#5a5a5a",
											"audioVisualizerLinesColor" => "#CCCCCC",
								   			"audioVisualizerCircleColor" => "#FFFFFF",
	
											//apw
											"aopwTitle" => "Advertisement",
											"aopwWidth" => 400,
											"aopwHeight" => 240,
											"aopwBorderSize" => 6,
											"aopwTitleColor" => "#000000",
											//thumbnails preview
											"thumbnails_preview_width" => 196,
											"thumbnails_preview_height" => 110,
											"thumbnails_preview_background_color" => "#000000",
											"thumbnails_preview_border_color" => "#666",
											"thumbnails_preview_label_background_color" => "#666",
											"thumbnails_preview_label_font_color" => "#FFF",
											//a to b loop
											"useAToB" => "no",
											"atbTimeBackgroundColor" => "transparent",
											"atbTimeTextColorNormal" => "#888888",
											"atbTimeTextColorSelected" => "#FFFFFF",
											"atbButtonTextNormalColor" => "#888888",
											"atbButtonTextSelectedColor" => "#FFFFFF",
											"atbButtonBackgroundNormalColor" => "#FFFFFF",
											"atbButtonBackgroundSelectedColor" => "#000000",
											
											//sticky
											"showOpener" => "yes",
											"showOpenerPlayPauseButton" => "yes",
											"verticalPosition" => "bottom",
											"horizontalPosition" => "center",
											"showPlayerByDefault" =>"yes",
											"animatePlayer:" => "yes",
											"openerAlignment" =>"right",
											"mainBackgroundImagePath" =>  plugin_dir_url(dirname(__FILE__)) . "content/minimal_skin_dark/main-background.png",
											"openerEqulizerOffsetTop" => -1,
											"openerEqulizerOffsetLeft" => 3,
											"offsetX" => 0,
											"offsetY" => 0,
											// controller settings
											"showController" => "yes",
											"show_controller_when_video_is_stopped" => "yes",
											"show_next_and_prev_buttons_in_controller" => "no",
											"show_volume_button" => "yes",
											"show_time" => "yes",
											"show_youtube_quality_button" => "yes",
											"show_info_button" => "yes",
											"show_download_button" => "yes",
											"show_share_button" => "yes",
											"show_embed_button" => "yes",
											"showChromecastButton" => "no",
											"show360DegreeVideoVrButton" => "no",
											"showSubtitleButton" => "yes",
											"showRewindButton" => "yes",
											"showAudioTracksButton" => "yes",
											"show_fullscreen_button" => "yes",
											"disable_video_scrubber" => "no",
											"repeat_background" => "yes",
											"controller_height" => 42,
											"controller_hide_delay" => 3,
											"start_space_between_buttons" => 7,
											"space_between_buttons" => 8,
											"scrubbers_offset_width" => 2,
											"main_scrubber_offest_top" => 14,
											"time_offset_left_width" => 5,
											"time_offset_right_width" => 3,
											"time_offset_top" => 0,
											"volume_scrubber_height" => 80,
											"volume_scrubber_ofset_height" => 12,
											"time_color" => "#888888",
											"showYoutubeRelAndInfo"=> "no",
											"youtube_quality_button_normal_color" => "#888888",
											"youtube_quality_button_selected_color" => "#FFFFFF",
											
											// playlists window settings
											"showPlaylistsSearchInput" => "yes",
											"inputBackgroundColor" => "#999999",
											
											"show_playlists_button_and_playlists" => "yes",
											"show_playlists_by_default" => "no",
											"thumbnail_selected_type" => "opacity",
											"youtubePlaylistAPI"	=> "AIzaSyD6LNmbZVbixO1s4ZzQV8odsDZsO2NUsl4",
											"start_at_playlist" => 0,
											"buttons_margins" => 15,
											"thumbnail_max_width" => 350,
											"thumbnail_max_height" => 350,
											"horizontal_space_between_thumbnails" => 40,
											"vertical_space_between_thumbnails" => 40,
											
											// playlist settings
											"randomizePlaylist" => "no",
											"show_playlist_button_and_playlist" => "yes",
											"playlist_position" => "right",
											"show_playlist_by_default" => "yes",
											"show_playlist_name" => "yes",
											"show_search_input" => "yes",
											"show_loop_button" => "yes",
											"show_shuffle_button" => "yes",
											"show_next_and_prev_buttons" => "yes",
											"force_disable_download_button_for_folder" => "yes",
											"add_mouse_wheel_support" => "yes",
											"showThumbnail" => "yes",
											"showOnlyThumbnail" => "no",
											"addScrollOnMouseMove" => "no",
											"showPlaylistOnFullScreen" => "no",
											"executeCuepointsOnlyOnce" => "no",
											"folder_video_label" => "VIDEO",
											"playlist_right_width" => 310,
											"playlist_bottom_height" => 400,
											"start_at_video" => 0,
											"max_playlist_items" => 50,
											"thumbnail_width" => 71,
											"thumbnail_height" => 71,
											"space_between_controller_and_playlist" => 1,
											"space_between_thumbnails" => 1,
											"scrollbar_offest_width" => 8,
											"scollbar_speed_sensitivity" => .5,
											"playlist_background_color" => "#000000",
											"playlist_name_color" => "#FFFFFF",
											"thumbnail_normal_background_color" => "#1b1b1b",
											"thumbnail_hover_background_color" => "#313131",
											"thumbnail_disabled_background_color" => "#272727",
											"search_input_background_color" => "#000000",
											"search_input_color" => "#999999",
											"youtube_and_folder_video_title_color" => "#FFFFFF",
											"youtube_owner_color" => "#888888",
											"youtube_description_color" => "#888888",
											
											// logo settings
											"show_logo" => "yes",
											"hide_logo_with_controller" => "yes",
											"logo_position" => "topRight",
											"logo_path" => plugin_dir_url(dirname(__FILE__)) . "content/logo.png",
											"logo_link" => "http://www.webdesign-flash.ro/",
											"logoTarget" => "_blank",
											"logo_margins" => 5,
											
											// embed and info windows settings
											"embed_and_info_window_close_button_margins" => 15,
											"border_color" => "#333333",
											"main_labels_color" => "#FFFFFF",
											"secondary_labels_color" => "#a1a1a1",
											"share_and_embed_text_color" => "#5a5a5a",
											"input_background_color" => "#000000",
											"input_color" => "#FFFFFF",

											// context menu
											"showContextmenu" =>'yes',
											"showScriptDeveloper" => "no",
											"contextMenuBackgroundColor" => "#1f1f1f",
											"contextMenuBorderColor" => "#1f1f1f",
											"contextMenuSpacerColor" => "#333",
											"contextMenuItemNormalColor" => "#888888",
											"contextMenuItemSelectedColor" => "#FFFFFF",
											"contextMenuItemDisabledColor" => "#444",

											// finger pring stamp
											"useFingerPrintStamp" => "no",
								    		"frequencyOfFingerPrintStamp" => 20000,
								    		"durationOfFingerPrintStamp" => 50
									),
									array(
											// main settings
											"id" => 1,
											"woo_commerce_display_type" => "fwduvp-responsive",
											"name" => "skin_modern_dark",
											
											"showErrorInfo" => "yes",
											"initializeOnlyWhenVisible" => "yes",
											"preloaderColor1" => "#FFFFFF",
											"preloaderColor2" => "#666666",
											"showPlaybackRateButton" => "yes",
											"showPreloader" => "yes",
											"defaultPlaybackRate" => 1,
											"privateVideoPassword" => "428c841430ea18a70f7b06525d4b748a",
											"use_playlists_select_box" => "yes",
											"main_selector_background_selected_color" => "#FFFFFF",
											"main_selector_text_normal_color" => "#FFFFFF",
											"main_selector_text_selected_color" => "#000000",
											"main_button_background_normal_color" => "#212021",
											"main_button_background_selected_color" => "#FFFFFF",
											"main_button_text_normal_color" => "#FFFFFF",
											"main_button_text_selected_color" => "#000000",
											"playAfterVideoStop" => "no",
											"stickyOnScroll" => "no",
											"stickyOnScrollShowOpener" =>"yes",
											"stickyOnScrollWidth" => 700,
											"stickyOnScrollHeight" => 394,
											"preloaderColor1" => "#FFFFFF",
											"preloaderColor2" => "#666666",
											"stopAfterLastVideoHasPlayed" => "no",
											"showDefaultControllerForVimeo" => "yes",
											"fill_entire_video_screen" => "no",
											"fillEntireposterScreen" => "yes",
											"goFullScreenOnButtonPlay" => "no",
											"use_HEX_colors_for_skin" => "no",
											"normal_HEX_buttons_color" => "#FF0000",
											"useVectorIcons" => "no",
											"showScrubberWhenControllerIsHidden" => "yes",
											"playsinline" => "yes",
											"useResumeOnPlay" => "no",
											"subtitles_off_label" => "Subtitle off",
											"playVideoOnlyWhenLoggedIn" => "no",
											"loggedInMessage" => "Please loggin to view this video.",
											"skin_path" => "modern_skin_dark",
											"googleAnalyticsMeasurementId" => "",
											"closeLightBoxWhenPlayComplete" => "no",
											"lightBoxBackgroundOpacity" => ".6",
											"lightBoxBackgroundColor" => "#000000",
											"display_type" => "responsive",
											"use_deeplinking" => "yes",
											"right_click_context_menu" => "developer",
											"add_keyboard_support" => "yes",
											"auto_scale" => "yes",
											"show_buttons_tooltips" => "yes",
											"autoplay" => "no",
											"autoPlayText" => "Click To Unmute",
											"loop" => "no",
											"shuffle" => "no",
											"showMainScrubberToolTipLabel" => "yes",
											"scrubbersToolTipLabelBackgroundColor" => "#FFFFFF",
											"scrubbersToolTipLabelFontColor" => "#5a5a5a",
											"show_popup_ads_close_button" => "yes",
											"max_width" => 980,
											"max_height" => 552,
											"volume" => .8,
											"rewindTime" => 10,
											"bg_color" => "#000000",
											"video_bg_color" => "#000000",
											"poster_bg_color" => "#000000",
											"buttons_tooltip_hide_delay" => 1.5,
											"buttons_tooltip_font_color" => "#5a5a5a",
											"audioVisualizerLinesColor" => "#CCCCCC",
								   			"audioVisualizerCircleColor" => "#FFFFFF",
											//apw
											"aopwTitle" => "Advertisement",
											"aopwWidth" => 400,
											"aopwHeight" => 240,
											"aopwBorderSize" => 6,
											"aopwTitleColor" => "#000000",
											//thumbnails preview
											"thumbnails_preview_width" => 196,
											"thumbnails_preview_height" => 110,
											"thumbnails_preview_background_color" => "#000000",
											"thumbnails_preview_border_color" => "#666",
											"thumbnails_preview_label_background_color" => "#666",
											"thumbnails_preview_label_font_color" => "#FFF",
											//a to b loop
											"useAToB" => "no",
											"atbTimeBackgroundColor" => "transparent",
											"atbTimeTextColorNormal" => "#888888",
											"atbTimeTextColorSelected" => "#FFFFFF",
											"atbButtonTextNormalColor" => "#888888",
											"atbButtonTextSelectedColor" => "#FFFFFF",
											"atbButtonBackgroundNormalColor" => "#FFFFFF",
											"atbButtonBackgroundSelectedColor" => "#000000",
											//sticky
											"showOpener" => "yes",
											"showOpenerPlayPauseButton" => "yes",
											"verticalPosition" => "bottom",
											"horizontalPosition" => "center",
											"showPlayerByDefault" =>"yes",
											"animatePlayer:" => "yes",
											"openerAlignment" =>"right",
											"mainBackgroundImagePath" =>  plugin_dir_url(dirname(__FILE__)) . "content/minimal_skin_dark/main-background.png",
											"openerEqulizerOffsetTop" => -1,
											"openerEqulizerOffsetLeft" => 3,
											"offsetX" => 0,
											"offsetY" => 0,
											// controller settings
											"showController" => "yes",
											"show_controller_when_video_is_stopped" => "yes",
											"show_next_and_prev_buttons_in_controller" => "no",
											"show_volume_button" => "yes",
											"show_time" => "yes",
											"show_youtube_quality_button" => "yes",
											"show_info_button" => "yes",
											"show_download_button" => "yes",
											"show_share_button" => "yes",
											"show_embed_button" => "yes",
											"showChromecastButton" => "no",
											"show360DegreeVideoVrButton" => "no",
											"showSubtitleButton" => "yes",
											"showRewindButton" => "yes",
											"showAudioTracksButton" => "yes",
											"show_fullscreen_button" => "yes",
											"disable_video_scrubber" => "no",
											"repeat_background" => "yes",
											"controller_height" => 43,
											"controller_hide_delay" => 3,
											"start_space_between_buttons" => 10,
											"space_between_buttons" => 10,
											"scrubbers_offset_width" => 2,
											"main_scrubber_offest_top" => 17,
											"time_offset_left_width" => 2,
											"time_offset_right_width" => 2,
											"time_offset_top" => -1,
											"volume_scrubber_height" => 80,
											"volume_scrubber_ofset_height" => 20,
											"time_color" => "#888888",
											"showYoutubeRelAndInfo"=> "no",
											"youtube_quality_button_normal_color" => "#888888",
											"youtube_quality_button_selected_color" => "#FFFFFF",
											
											// playlists window settings
											"showPlaylistsSearchInput" => "yes",
											"show_playlists_button_and_playlists" => "yes",
											"show_playlists_by_default" => "no",
											"thumbnail_selected_type" => "opacity",
											"youtubePlaylistAPI"	=> "AIzaSyD6LNmbZVbixO1s4ZzQV8odsDZsO2NUsl4",
											"start_at_playlist" => 0,
											"buttons_margins" => 15,
											"thumbnail_max_width" => 350,
											"thumbnail_max_height" => 350,
											"horizontal_space_between_thumbnails" => 40,
											"vertical_space_between_thumbnails" => 40,
											
											// playlist settings
											"randomizePlaylist" => "no",
											"show_playlist_button_and_playlist" => "yes",
											"playlist_position" => "right",
											"show_playlist_by_default" => "yes",
											"show_playlist_name" => "yes",
											"show_search_input" => "yes",
											"show_loop_button" => "yes",
											"show_shuffle_button" => "yes",
											"show_next_and_prev_buttons" => "yes",
											"force_disable_download_button_for_folder" => "yes",
											"add_mouse_wheel_support" => "yes",
											"showThumbnail" => "yes",
											"showOnlyThumbnail" => "no",
											"addScrollOnMouseMove" => "no",
											"showPlaylistOnFullScreen" => "no",
											"executeCuepointsOnlyOnce" => "no",
											"folder_video_label" => "VIDEO",
											"playlist_right_width" => 290,
											"playlist_bottom_height" => 400,
											"start_at_video" => 0,
											"max_playlist_items" => 50,
											"thumbnail_width" => 71,
											"thumbnail_height" => 71,
											"space_between_controller_and_playlist" => 1,
											"space_between_thumbnails" => 1,
											"scrollbar_offest_width" => 8,
											"scollbar_speed_sensitivity" => .5,
											"playlist_background_color" => "#000000",
											"playlist_name_color" => "#FFFFFF",
											"thumbnail_normal_background_color" => "#1b1b1b",
											"thumbnail_hover_background_color" => "#313131",
											"thumbnail_disabled_background_color" => "#272727",
											"search_input_background_color" => "#000000",
											"search_input_color" => "#999999",
											"youtube_and_folder_video_title_color" => "#FFFFFF",
											"youtube_owner_color" => "#888888",
											"youtube_description_color" => "#888888",
											
											// logo settings
											"show_logo" => "yes",
											"hide_logo_with_controller" => "yes",
											"logo_position" => "topRight",
											"logo_path" => plugin_dir_url(dirname(__FILE__)) . "content/logo.png",
											"logo_link" => "http://www.webdesign-flash.ro/",
											"logoTarget" => "_blank",
											"logo_margins" => 5,
											
											// embed and info windows settings
											"embed_and_info_window_close_button_margins" => 15,
											"border_color" => "#333333",
											"main_labels_color" => "#FFFFFF",
											"secondary_labels_color" => "#a1a1a1",
											"share_and_embed_text_color" => "#5a5a5a",
											"input_background_color" => "#000000",
											"input_color" => "#FFFFFF",

											// context menu
											"showContextmenu" => 'yes',
											"showScriptDeveloper" => "no",
											"contextMenuBackgroundColor" => "#1f1f1f",
											"contextMenuBorderColor" => "#1f1f1f",
											"contextMenuSpacerColor" => "#333",
											"contextMenuItemNormalColor" => "#6a6a6a",
											"contextMenuItemSelectedColor" => "#FFFFFF",
											"contextMenuItemDisabledColor" => "#333",

											// finger pring stamp
											"useFingerPrintStamp" => "no",
								    		"frequencyOfFingerPrintStamp" => 20000,
								    		"durationOfFingerPrintStamp" => 50
									),
									array(
											// main settings
											"id" => 2,
											"woo_commerce_display_type" => "fwduvp-responsive",
											"name" => "skin_classic_dark",
											
											"showErrorInfo" => "yes",
											"initializeOnlyWhenVisible" => "yes",
											"preloaderColor1" => "#FFFFFF",
											"preloaderColor2" => "#666666",
											"showPlaybackRateButton" => "yes",
											"showPreloader" => "yes",
											"defaultPlaybackRate" => 1,
											"privateVideoPassword" => "428c841430ea18a70f7b06525d4b748a",
											"use_playlists_select_box" => "yes",
											"main_selector_background_selected_color" => "#FFFFFF",
											"main_selector_text_normal_color" => "#FFFFFF",
											"main_selector_text_selected_color" => "#000000",
											"main_button_background_normal_color" => "#212021",
											"main_button_background_selected_color" => "#FFFFFF",
											"main_button_text_normal_color" => "#FFFFFF",
											"main_button_text_selected_color" => "#000000",
											"playAfterVideoStop" => "no",
											"stickyOnScroll" => "no",
											"stickyOnScrollShowOpener" =>"yes",
											"stickyOnScrollWidth" => 700,
											"stickyOnScrollHeight" => 394,
											"preloaderColor1" => "#FFFFFF",
											"preloaderColor2" => "#666666",
											"stopAfterLastVideoHasPlayed" => "no",
											"showDefaultControllerForVimeo" => "yes",
											"useVectorIcons" => "no",
											"showScrubberWhenControllerIsHidden" => "yes",
											"playsinline" => "yes",
											"useResumeOnPlay" => "no",
											"fill_entire_video_screen" => "no",
											"fillEntireposterScreen" => "yes",
											"goFullScreenOnButtonPlay" => "no",
											"use_HEX_colors_for_skin" => "no",
											"normal_HEX_buttons_color" => "#FF0000",
											"subtitles_off_label" => "Subtitle off",
											"playVideoOnlyWhenLoggedIn" => "no",
											"loggedInMessage" => "Please loggin to view this video.",
											"skin_path" => "classic_skin_dark",
											"googleAnalyticsMeasurementId" => "",
											"closeLightBoxWhenPlayComplete" => "no",
											"lightBoxBackgroundOpacity" => ".6",
											"lightBoxBackgroundColor" => "#000000",
											"display_type" => "responsive",
											"use_deeplinking" => "yes",
											"right_click_context_menu" => "developer",
											"add_keyboard_support" => "yes",
											"auto_scale" => "yes",
											"show_buttons_tooltips" => "yes",
											"autoplay" => "no",
											"autoPlayText" => "Click To Unmute",
											"loop" => "no",
											"shuffle" => "no",
											"showMainScrubberToolTipLabel" => "yes",
											"scrubbersToolTipLabelBackgroundColor" => "#FFFFFF",
											"scrubbersToolTipLabelFontColor" => "#5a5a5a",
											"show_popup_ads_close_button" => "yes",
											"max_width" => 980,
											"max_height" => 552,
											"volume" => .8,
											"rewindTime" => 10,
											"bg_color" => "#000000",
											"video_bg_color" => "#000000",
											"poster_bg_color" => "#000000",
											"buttons_tooltip_hide_delay" => 1.5,
											"buttons_tooltip_font_color" => "#5a5a5a",
											"audioVisualizerLinesColor" => "#CCCCCC",
								   			"audioVisualizerCircleColor" => "#FFFFFF",
											//apw
											"aopwTitle" => "Advertisement",
											"aopwWidth" => 400,
											"aopwHeight" => 240,
											"aopwBorderSize" => 6,
											"aopwTitleColor" => "#000000",
											//thumbnails preview
											"thumbnails_preview_width" => 196,
											"thumbnails_preview_height" => 110,
											"thumbnails_preview_background_color" => "#000000",
											"thumbnails_preview_border_color" => "#666",
											"thumbnails_preview_label_background_color" => "#666",
											"thumbnails_preview_label_font_color" => "#FFF",
											//a to b loop
											"useAToB" => "no",
											"atbTimeBackgroundColor" => "transparent",
											"atbTimeTextColorNormal" => "#888888",
											"atbTimeTextColorSelected" => "#FFFFFF",
											"atbButtonTextNormalColor" => "#888888",
											"atbButtonTextSelectedColor" => "#FFFFFF",
											"atbButtonBackgroundNormalColor" => "#FFFFFF",
											"atbButtonBackgroundSelectedColor" => "#000000",
											//sticky
											"showOpener" => "yes",
											"showOpenerPlayPauseButton" => "yes",
											"verticalPosition" => "bottom",
											"horizontalPosition" => "center",
											"showPlayerByDefault" =>"yes",
											"animatePlayer:" => "yes",
											"openerAlignment" =>"right",
											"mainBackgroundImagePath" =>  plugin_dir_url(dirname(__FILE__)) . "content/minimal_skin_dark/main-background.png",
											"openerEqulizerOffsetTop" => -1,
											"openerEqulizerOffsetLeft" => 3,
											"offsetX" => 0,
											"offsetY" => 0,
											// controller settings
											"showController" => "yes",
											"show_controller_when_video_is_stopped" => "yes",
											"show_next_and_prev_buttons_in_controller" => "no",
											"show_volume_button" => "yes",
											"show_time" => "yes",
											"show_youtube_quality_button" => "yes",
											"show_info_button" => "yes",
											"show_download_button" => "yes",
											"show_share_button" => "yes",
											"show_embed_button" => "yes",
											"showChromecastButton" => "no",
											"show360DegreeVideoVrButton" => "no",
											"showSubtitleButton" => "yes",
											"showRewindButton" => "yes",
											"showAudioTracksButton" => "yes",
											"show_fullscreen_button" => "yes",
											"disable_video_scrubber" => "no",
											"repeat_background" => "no",
											"controller_height" => 42,
											"controller_hide_delay" => 3,
											"start_space_between_buttons" => 10,
											"space_between_buttons" => 10,
											"scrubbers_offset_width" => 2,
											"main_scrubber_offest_top" => 16,
											"time_offset_left_width" => 2,
											"time_offset_right_width" => 3,
											"time_offset_top" => 0,
											"volume_scrubber_height" => 80,
											"volume_scrubber_ofset_height" => 12,
											"time_color" => "#bdbdbd",
											"showYoutubeRelAndInfo"=> "no",
											"youtube_quality_button_normal_color" => "#bdbdbd",
											"youtube_quality_button_selected_color" => "#FFFFFF",
											
											// playlists window settings
											"showPlaylistsSearchInput" => "yes",
											"show_playlists_button_and_playlists" => "yes",
											"show_playlists_by_default" => "no",
											"thumbnail_selected_type" => "opacity",
											"youtubePlaylistAPI"	=> "AIzaSyD6LNmbZVbixO1s4ZzQV8odsDZsO2NUsl4",
											"start_at_playlist" => 0,
											"buttons_margins" => 15,
											"thumbnail_max_width" => 350,
											"thumbnail_max_height" => 350,
											"horizontal_space_between_thumbnails" => 40,
											"vertical_space_between_thumbnails" => 40,
											
											// playlist settings
											"randomizePlaylist" => "no",
											"show_playlist_button_and_playlist" => "yes",
											"playlist_position" => "right",
											"show_playlist_by_default" => "yes",
											"show_playlist_name" => "yes",
											"show_search_input" => "yes",
											"show_loop_button" => "yes",
											"show_shuffle_button" => "yes",
											"show_next_and_prev_buttons" => "yes",
											"force_disable_download_button_for_folder" => "yes",
											"add_mouse_wheel_support" => "yes",
											"showThumbnail" => "yes",
											"showOnlyThumbnail" => "no",
											"addScrollOnMouseMove" => "no",
											"showPlaylistOnFullScreen" => "no",
											"executeCuepointsOnlyOnce" => "no",
											"folder_video_label" => "VIDEO",
											"playlist_right_width" => 310,
											"playlist_bottom_height" => 400,
											"start_at_video" => 0,
											"max_playlist_items" => 50,
											"thumbnail_width" => 71,
											"thumbnail_height" => 71,
											"space_between_controller_and_playlist" => 1,
											"space_between_thumbnails" => 1,
											"scrollbar_offest_width" => 10,
											"scollbar_speed_sensitivity" => .5,
											"playlist_background_color" => "#000000",
											"playlist_name_color" => "#FFFFFF",
											"thumbnail_normal_background_color" => "#1b1b1b",
											"thumbnail_hover_background_color" => "#313131",
											"thumbnail_disabled_background_color" => "#272727",
											"search_input_background_color" => "#000000",
											"search_input_color" => "#bdbdbd",
											"youtube_and_folder_video_title_color" => "#FFFFFF",
											"youtube_owner_color" => "#bdbdbd",
											"youtube_description_color" => "#bdbdbd",
											
											// logo settings
											"show_logo" => "yes",
											"hide_logo_with_controller" => "yes",
											"logo_position" => "topRight",
											"logo_path" => plugin_dir_url(dirname(__FILE__)) . "content/logo.png",
											"logo_link" => "http://www.webdesign-flash.ro/",
											"logoTarget" => "_blank",
											"logo_margins" => 5,
											
											// embed and info windows settings
											"embed_and_info_window_close_button_margins" => 15,
											"border_color" => "#333333",
											"main_labels_color" => "#FFFFFF",
											"secondary_labels_color" => "#bdbdbd",
											"share_and_embed_text_color" => "#5a5a5a",
											"input_background_color" => "#000000",
											"input_color" => "#FFFFFF",

											// context menu
											"showContextmenu" => 'yes',
											"showScriptDeveloper" => "no",
											"contextMenuBackgroundColor" => "#1b1b1b",
											"contextMenuBorderColor" => "#1b1b1b",
											"contextMenuSpacerColor" => "#333",
											"contextMenuItemNormalColor" => "#bdbdbd",
											"contextMenuItemSelectedColor" => "#FFFFFF",
											"contextMenuItemDisabledColor" => "#333",

											// finger pring stamp
											"useFingerPrintStamp" => "no",
								    		"frequencyOfFingerPrintStamp" => 20000,
								    		"durationOfFingerPrintStamp" => 50
									),
									array(
											// main settings
											"id" => 3,
											"woo_commerce_display_type" => "fwduvp-responsive",
											"name" => "skin_metal_dark",
											
											"showErrorInfo" => "yes",
											"initializeOnlyWhenVisible" => "yes",
											"preloaderColor1" => "#FFFFFF",
											"preloaderColor2" => "#666666",
											"showPlaybackRateButton" => "yes",
											"showPreloader" => "yes",
											"defaultPlaybackRate" => 1,
											"privateVideoPassword" => "428c841430ea18a70f7b06525d4b748a",
											"use_playlists_select_box" => "yes",
											"main_selector_background_selected_color" => "#FFFFFF",
											"main_selector_text_normal_color" => "#FFFFFF",
											"main_selector_text_selected_color" => "#000000",
											"main_button_background_normal_color" => "#212021",
											"main_button_background_selected_color" => "#FFFFFF",
											"main_button_text_normal_color" => "#FFFFFF",
											"main_button_text_selected_color" => "#000000",
											"playAfterVideoStop" => "no",
											"stickyOnScroll" => "no",
											"stickyOnScrollShowOpener" =>"yes",
											"stickyOnScrollWidth" => 700,
											"stickyOnScrollHeight" => 394,
											"preloaderColor1" => "#FFFFFF",
											"preloaderColor2" => "#666666",
											"stopAfterLastVideoHasPlayed" => "no",
											"showDefaultControllerForVimeo" => "yes",
											"useVectorIcons" => "no",
											"showScrubberWhenControllerIsHidden" => "yes",
											"playsinline" => "yes",
											"useResumeOnPlay" => "no",
											"fill_entire_video_screen" => "no",
											"fillEntireposterScreen" => "yes",
											"goFullScreenOnButtonPlay" => "no",
											"use_HEX_colors_for_skin" => "no",
											"normal_HEX_buttons_color" => "#FF0000",
											"subtitles_off_label" => "Subtitle off",
											"playVideoOnlyWhenLoggedIn" => "no",
											"loggedInMessage" => "Please loggin to view this video.",
											"skin_path" => "metal_skin_dark",
											"googleAnalyticsMeasurementId" => "",
											"closeLightBoxWhenPlayComplete" => "no",
											"lightBoxBackgroundOpacity" => ".6",
											"lightBoxBackgroundColor" => "#000000",
											"display_type" => "responsive",
											"use_deeplinking" => "yes",
											"right_click_context_menu" => "developer",
											"add_keyboard_support" => "yes",
											"auto_scale" => "yes",
											"show_buttons_tooltips" => "yes",
											"autoplay" => "no",
											"autoPlayText" => "Click To Unmute",
											"loop" => "no",
											"shuffle" => "no",
											"showMainScrubberToolTipLabel" => "yes",
											"scrubbersToolTipLabelBackgroundColor" => "#FFFFFF",
											"scrubbersToolTipLabelFontColor" => "#5a5a5a",
											"show_popup_ads_close_button" => "yes",
											"max_width" => 980,
											"max_height" => 552,
											"volume" => .8,
											"rewindTime" => 10,
											"bg_color" => "#000000",
											"video_bg_color" => "#000000",
											"poster_bg_color" => "#000000",
											"buttons_tooltip_hide_delay" => 1.5,
											"buttons_tooltip_font_color" => "#5a5a5a",
											"audioVisualizerLinesColor" => "#CCCCCC",
								   			"audioVisualizerCircleColor" => "#FFFFFF",
											//apw
											"aopwTitle" => "Advertisement",
											"aopwWidth" => 400,
											"aopwHeight" => 240,
											"aopwBorderSize" => 6,
											"aopwTitleColor" => "#000000",
											//thumbnails preview
											"thumbnails_preview_width" => 196,
											"thumbnails_preview_height" => 110,
											"thumbnails_preview_background_color" => "#000000",
											"thumbnails_preview_border_color" => "#666",
											"thumbnails_preview_label_background_color" => "#666",
											"thumbnails_preview_label_font_color" => "#FFF",
											//a to b loop
											"useAToB" => "no",
											"atbTimeBackgroundColor" => "transparent",
											"atbTimeTextColorNormal" => "#888888",
											"atbTimeTextColorSelected" => "#FFFFFF",
											"atbButtonTextNormalColor" => "#888888",
											"atbButtonTextSelectedColor" => "#FFFFFF",
											"atbButtonBackgroundNormalColor" => "#FFFFFF",
											"atbButtonBackgroundSelectedColor" => "#000000",
											//sticky
											"showOpener" => "yes",
											"showOpenerPlayPauseButton" => "yes",
											"verticalPosition" => "bottom",
											"horizontalPosition" => "center",
											"showPlayerByDefault" =>"yes",
											"animatePlayer:" => "yes",
											"openerAlignment" =>"right",
											"mainBackgroundImagePath" =>  plugin_dir_url(dirname(__FILE__)) . "content/minimal_skin_dark/main-background.png",
											"openerEqulizerOffsetTop" => -1,
											"openerEqulizerOffsetLeft" => 3,
											"offsetX" => 0,
											"offsetY" => 0,
											// controller settings
											"showController" => "yes",
											"show_controller_when_video_is_stopped" => "yes",
											"show_next_and_prev_buttons_in_controller" => "no",
											"show_volume_button" => "yes",
											"show_time" => "yes",
											"show_youtube_quality_button" => "yes",
											"show_info_button" => "yes",
											"show_download_button" => "yes",
											"show_share_button" => "yes",
											"show_embed_button" => "yes",
											"showChromecastButton" => "no",
											"show360DegreeVideoVrButton" => "no",
											"showSubtitleButton" => "yes",
											"showRewindButton" => "yes",
											"showAudioTracksButton" => "yes",
											"show_fullscreen_button" => "yes",
											"disable_video_scrubber" => "no",
											"repeat_background" => "yes",
											"controller_height" => 43,
											"controller_hide_delay" => 3,
											"start_space_between_buttons" => 7,
											"space_between_buttons" => 8,
											"scrubbers_offset_width" => 4,
											"main_scrubber_offest_top" => 14,
											"time_offset_left_width" => 5,
											"time_offset_right_width" => 3,
											"time_offset_top" => 1,
											"volume_scrubber_height" => 80,
											"volume_scrubber_ofset_height" => 12,
											"time_color" => "#888888",
											"showYoutubeRelAndInfo"=> "no",
											"youtube_quality_button_normal_color" => "#888888",
											"youtube_quality_button_selected_color" => "#FFFFFF",
											
											// playlists window settings
											"showPlaylistsSearchInput" => "yes",
											"show_playlists_button_and_playlists" => "yes",
											"show_playlists_by_default" => "no",
											"thumbnail_selected_type" => "opacity",
											"youtubePlaylistAPI"	=> "AIzaSyD6LNmbZVbixO1s4ZzQV8odsDZsO2NUsl4",
											"start_at_playlist" => 0,
											"buttons_margins" => 0,
											"thumbnail_max_width" => 350,
											"thumbnail_max_height" => 350,
											"horizontal_space_between_thumbnails" => 40,
											"vertical_space_between_thumbnails" => 40,
											
											// playlist settings
											"randomizePlaylist" => "no",
											"show_playlist_button_and_playlist" => "yes",
											"playlist_position" => "right",
											"show_playlist_by_default" => "yes",
											"show_playlist_name" => "yes",
											"show_search_input" => "yes",
											"show_loop_button" => "yes",
											"show_shuffle_button" => "yes",
											"show_next_and_prev_buttons" => "yes",
											"force_disable_download_button_for_folder" => "yes",
											"add_mouse_wheel_support" => "yes",
											"showThumbnail" => "yes",
											"showOnlyThumbnail" => "no",
											"addScrollOnMouseMove" => "no",
											"showPlaylistOnFullScreen" => "no",
											"executeCuepointsOnlyOnce" => "no",
											"folder_video_label" => "VIDEO",
											"playlist_right_width" => 310,
											"playlist_bottom_height" => 400,
											"start_at_video" => 0,
											"max_playlist_items" => 50,
											"thumbnail_width" => 71,
											"thumbnail_height" => 71,
											"space_between_controller_and_playlist" => 1,
											"space_between_thumbnails" => 1,
											"scrollbar_offest_width" => 8,
											"scollbar_speed_sensitivity" => .5,
											"playlist_background_color" => "#000000",
											"playlist_name_color" => "#FFFFFF",
											"thumbnail_normal_background_color" => "#1b1b1b",
											"thumbnail_hover_background_color" => "#313131",
											"thumbnail_disabled_background_color" => "#272727",
											"search_input_background_color" => "#000000",
											"search_input_color" => "#999999",
											"youtube_and_folder_video_title_color" => "#FFFFFF",
											"youtube_owner_color" => "#888888",
											"youtube_description_color" => "#888888",
											
											// logo settings
											"show_logo" => "yes",
											"hide_logo_with_controller" => "yes",
											"logo_position" => "topRight",
											"logo_path" => plugin_dir_url(dirname(__FILE__)) . "content/logo.png",
											"logo_link" => "http://www.webdesign-flash.ro/",
											"logoTarget" => "_blank",
											"logo_margins" => 5,
											
											// embed and info windows settings
											"embed_and_info_window_close_button_margins" => 0,
											"border_color" => "#333333",
											"main_labels_color" => "#FFFFFF",
											"secondary_labels_color" => "#a1a1a1",
											"share_and_embed_text_color" => "#5a5a5a",
											"input_background_color" => "#000000",
											"input_color" => "#FFFFFF",

											// context menu
											"showContextmenu" => 'yes',
											"showScriptDeveloper" => "no",
											"contextMenuBackgroundColor" => "#1b1b1b",
											"contextMenuBorderColor" => "#1b1b1b",
											"contextMenuSpacerColor" => "#333",
											"contextMenuItemNormalColor" => "#888888",
											"contextMenuItemSelectedColor" => "#FFFFFF",
											"contextMenuItemDisabledColor" => "#333",

											// finger pring stamp
											"useFingerPrintStamp" => "no",
								    		"frequencyOfFingerPrintStamp" => 20000,
								    		"durationOfFingerPrintStamp" => 50
									),
									array(
											// main settings
											"id" => 4,
											"woo_commerce_display_type" => "fwduvp-responsive",
											"name" => "skin_minimal_white",
											
											"showErrorInfo" => "yes",
											"initializeOnlyWhenVisible" => "yes",
											"preloaderColor1" => "#FFFFFF",
											"preloaderColor2" => "#666666",
											"showPlaybackRateButton" => "yes",
											"showPreloader" => "yes",
											"defaultPlaybackRate" => 1,
											"privateVideoPassword" => "428c841430ea18a70f7b06525d4b748a",
											"use_playlists_select_box" => "yes",
											"main_selector_background_selected_color" =>"#000000",
											"main_selector_text_normal_color" =>"#000000",
											"main_selector_text_selected_color" =>"#FFFFFF",
											"main_button_background_normal_color" =>"#FFFFFF",
											"main_button_background_selected_color" =>"#000000",
											"main_button_text_normal_color" =>"#000000",
											"main_button_text_selected_color" =>"#FFFFFF",
											"playAfterVideoStop" => "no",
											"stickyOnScroll" => "no",
											"stickyOnScrollShowOpener" =>"yes",
											"stickyOnScrollWidth" => 700,
											"stickyOnScrollHeight" => 394,
											"preloaderColor1" => "#FFFFFF",
											"preloaderColor2" => "#666666",
											"stopAfterLastVideoHasPlayed" => "no",
											"showDefaultControllerForVimeo" => "yes",
											"useVectorIcons" => "no",
											"showScrubberWhenControllerIsHidden" => "yes",
											"playsinline" => "yes",
											"useResumeOnPlay" => "no",
											"fill_entire_video_screen" => "no",
											"fillEntireposterScreen" => "yes",
											"goFullScreenOnButtonPlay" => "no",
											"use_HEX_colors_for_skin" => "no",
											"normal_HEX_buttons_color" => "#FF0000",
											"subtitles_off_label" => "Subtitle off",
											"playVideoOnlyWhenLoggedIn" => "no",
											"loggedInMessage" => "Please loggin to view this video.",
											"skin_path" => "minimal_skin_white",
											"googleAnalyticsMeasurementId" => "",
											"closeLightBoxWhenPlayComplete" => "no",
											"lightBoxBackgroundOpacity" => ".6",
											"lightBoxBackgroundColor" => "#000000",
											"display_type" => "responsive",
											"use_deeplinking" => "yes",
											"right_click_context_menu" => "developer",
											"add_keyboard_support" => "yes",
											"auto_scale" => "yes",
											"show_buttons_tooltips" => "yes",
											"autoplay" => "no",
											"autoPlayText" => "Click To Unmute",
											"loop" => "no",
											"shuffle" => "no",
											"showMainScrubberToolTipLabel" => "yes",
											"scrubbersToolTipLabelBackgroundColor" => "#000000",
											"scrubbersToolTipLabelFontColor" => "#FFFFFF",
											"show_popup_ads_close_button" => "yes",
											"max_width" => 980,
											"max_height" => 552,
											"volume" => .8,
											"rewindTime" => 10,
											"bg_color" => "#DDDDDD",
											"video_bg_color" => "#000000",
											"poster_bg_color" => "#000000",
											"buttons_tooltip_hide_delay" => 1.5,
											"buttons_tooltip_font_color" => "#FFFFFF",
											"audioVisualizerLinesColor" => "#CCCCCC",
								   			"audioVisualizerCircleColor" => "#FFFFFF",
											//apw
											"aopwTitle" => "Advertisement",
											"aopwWidth" => 400,
											"aopwHeight" => 240,
											"aopwBorderSize" => 6,
											"aopwTitleColor" => "#FFFFFF",
											//thumbnails preview
											"thumbnails_preview_width" => 196,
											"thumbnails_preview_height" => 110,
											"thumbnails_preview_background_color" => "#000000",
											"thumbnails_preview_border_color" => "#666",
											"thumbnails_preview_label_background_color" => "#666",
											"thumbnails_preview_label_font_color" => "#FFF",
											//a to b loop
											"useAToB" => "no",
											"atbTimeBackgroundColor" => "transparent",
											"atbTimeTextColorNormal" => "#888888",
											"atbTimeTextColorSelected" => "#000000",
											"atbButtonTextNormalColor" => "#FFFFFF",
											"atbButtonTextSelectedColor" => "#FFFFFF",
											"atbButtonBackgroundNormalColor" => "#888888",
											"atbButtonBackgroundSelectedColor" => "#000000",
											//sticky
											"showOpener" => "yes",
											"showOpenerPlayPauseButton" => "yes",
											"verticalPosition" => "bottom",
											"horizontalPosition" => "center",
											"showPlayerByDefault" =>"yes",
											"animatePlayer:" => "yes",
											"openerAlignment" =>"right",
											"mainBackgroundImagePath" =>  plugin_dir_url(dirname(__FILE__)) . "content/minimal_skin_dark/main-background.png",
											"openerEqulizerOffsetTop" => -1,
											"openerEqulizerOffsetLeft" => 3,
											"offsetX" => 0,
											"offsetY" => 0,
											// controller settings
											"showController" => "yes",
											"show_controller_when_video_is_stopped" => "yes",
											"show_next_and_prev_buttons_in_controller" => "no",
											"show_volume_button" => "yes",
											"show_time" => "yes",
											"show_youtube_quality_button" => "yes",
											"show_info_button" => "yes",
											"show_download_button" => "yes",
											"show_share_button" => "yes",
											"show_embed_button" => "yes",
											"showChromecastButton" => "no",
											"show360DegreeVideoVrButton" => "no",
											"showSubtitleButton" => "yes",
											"showAudioTracksButton" => "yes",
											"showRewindButton" => "yes",
											"show_fullscreen_button" => "yes",
											"disable_video_scrubber" => "no",
											"repeat_background" => "yes",
											"controller_height" => 42,
											"controller_hide_delay" => 3,
											"start_space_between_buttons" => 7,
											"space_between_buttons" => 8,
											"scrubbers_offset_width" => 2,
											"main_scrubber_offest_top" => 14,
											"time_offset_left_width" => 5,
											"time_offset_right_width" => 3,
											"time_offset_top" => 0,
											"volume_scrubber_height" => 80,
											"volume_scrubber_ofset_height" => 12,
											"time_color" => "#919191",
											"showYoutubeRelAndInfo"=> "no",
											"youtube_quality_button_normal_color" => "#919191",
											"youtube_quality_button_selected_color" => "#000000",
											
											// playlists window settings
											"showPlaylistsSearchInput" => "yes",
											"show_playlists_button_and_playlists" => "yes",
											"show_playlists_by_default" => "no",
											"thumbnail_selected_type" => "opacity",
											"youtubePlaylistAPI"	=> "AIzaSyD6LNmbZVbixO1s4ZzQV8odsDZsO2NUsl4",
											"start_at_playlist" => 0,
											"buttons_margins" => 15,
											"thumbnail_max_width" => 350,
											"thumbnail_max_height" => 350,
											"horizontal_space_between_thumbnails" => 40,
											"vertical_space_between_thumbnails" => 40,
											
											// playlist settings
											"randomizePlaylist" => "no",
											"show_playlist_button_and_playlist" => "yes",
											"playlist_position" => "right",
											"show_playlist_by_default" => "yes",
											"show_playlist_name" => "yes",
											"show_search_input" => "yes",
											"show_loop_button" => "yes",
											"show_shuffle_button" => "yes",
											"show_next_and_prev_buttons" => "yes",
											"force_disable_download_button_for_folder" => "yes",
											"add_mouse_wheel_support" => "yes",
											"showThumbnail" => "yes",
											"showOnlyThumbnail" => "no",
											"addScrollOnMouseMove" => "no",
											"showPlaylistOnFullScreen" => "no",
											"executeCuepointsOnlyOnce" => "no",
											"folder_video_label" => "VIDEO",
											"playlist_right_width" => 310,
											"playlist_bottom_height" => 400,
											"start_at_video" => 0,
											"max_playlist_items" => 50,
											"thumbnail_width" => 71,
											"thumbnail_height" => 71,
											"space_between_controller_and_playlist" => 1,
											"space_between_thumbnails" => 1,
											"scrollbar_offest_width" => 8,
											"scollbar_speed_sensitivity" => .5,
											"playlist_background_color" => "#eeeeee",
											"playlist_name_color" => "#000000",
											"thumbnail_normal_background_color" => "#ffffff",
											"thumbnail_hover_background_color" => "#eeeeee",
											"thumbnail_disabled_background_color" => "#eeeeee",
											"search_input_background_color" => "#F3F3F3",
											"search_input_color" => "#888888",
											"youtube_and_folder_video_title_color" => "#000000",
											"youtube_owner_color" => "#919191",
											"youtube_description_color" => "#919191",
											
											// logo settings
											"show_logo" => "yes",
											"hide_logo_with_controller" => "yes",
											"logo_position" => "topRight",
											"logo_path" => plugin_dir_url(dirname(__FILE__)) . "content/logo.png",
											"logo_link" => "http://www.webdesign-flash.ro/",
											"logoTarget" => "_blank",
											"logo_margins" => 5,
											
											// embed and info windows settings
											"embed_and_info_window_close_button_margins" => 15,
											"border_color" => "#CDCDCD",
											"main_labels_color" => "#000000",
											"secondary_labels_color" => "#444444",
											"share_and_embed_text_color" => "#777777",
											"input_background_color" => "#c0c0c0",
											"input_color" => "#333333",

											// context menu
											"showContextmenu" => 'yes',
											"showScriptDeveloper" => "no",
											"contextMenuBackgroundColor" => "#ebebeb",
											"contextMenuBorderColor" => "#ebebeb",
											"contextMenuSpacerColor" => "#CCC",
											"contextMenuItemNormalColor" => "#888888",
											"contextMenuItemSelectedColor" => "#000",
											"contextMenuItemDisabledColor" => "#BBB",

											// finger pring stamp
											"useFingerPrintStamp" => "no",
								    		"frequencyOfFingerPrintStamp" => 20000,
								    		"durationOfFingerPrintStamp" => 50
									),
									array(
											// main settings
											"id" => 5,
											"woo_commerce_display_type" => "fwduvp-responsive",
											"name" => "skin_modern_white",
											
											"showErrorInfo" => "yes",
											"initializeOnlyWhenVisible" => "yes",
											"showPlaybackRateButton" => "yes",
											"showPreloader" => "yes",
											"defaultPlaybackRate" => 1,
											"privateVideoPassword" => "428c841430ea18a70f7b06525d4b748a",
											"use_playlists_select_box" => "yes",
											"main_selector_background_selected_color" =>"#000000",
											"main_selector_text_normal_color" =>"#000000",
											"main_selector_text_selected_color" =>"#FFFFFF",
											"main_button_background_normal_color" =>"#FFFFFF",
											"main_button_background_selected_color" =>"#000000",
											"main_button_text_normal_color" =>"#000000",
											"main_button_text_selected_color" =>"#FFFFFF",
											"playAfterVideoStop" => "no",
											"stickyOnScroll" => "no",
											"stickyOnScrollShowOpener" =>"yes",
											"stickyOnScrollWidth" => 700,
											"stickyOnScrollHeight" => 394,
											"preloaderColor1" => "#FFFFFF",
											"preloaderColor2" => "#666666",
											"stopAfterLastVideoHasPlayed" => "no",
											"showDefaultControllerForVimeo" => "yes",
											"useVectorIcons" => "no",
											"showScrubberWhenControllerIsHidden" => "yes",
											"playsinline" => "yes",
											"useResumeOnPlay" => "no",
											"fill_entire_video_screen" => "no",
											"fillEntireposterScreen" => "yes",
											"goFullScreenOnButtonPlay" => "no",
											"use_HEX_colors_for_skin" => "no",
											"normal_HEX_buttons_color" => "#FF0000",
											"subtitles_off_label" => "Subtitle off",
											"playVideoOnlyWhenLoggedIn" => "no",
											"loggedInMessage" => "Please loggin to view this video.",
											"skin_path" => "modern_skin_white",
											"googleAnalyticsMeasurementId" => "",
											"closeLightBoxWhenPlayComplete" => "no",
											"lightBoxBackgroundOpacity" => ".6",
											"lightBoxBackgroundColor" => "#000000",
											"display_type" => "responsive",
											"use_deeplinking" => "yes",
											"right_click_context_menu" => "developer",
											"add_keyboard_support" => "yes",
											"auto_scale" => "yes",
											"show_buttons_tooltips" => "yes",
											"autoplay" => "no",
											"autoPlayText" => "Click To Unmute",
											"loop" => "no",
											"shuffle" => "no",
											"showMainScrubberToolTipLabel" => "yes",
											"scrubbersToolTipLabelBackgroundColor" => "#000000",
											"scrubbersToolTipLabelFontColor" => "#FFFFFF",
											"show_popup_ads_close_button" => "yes",
											"max_width" => 980,
											"max_height" => 552,
											"volume" => .8,
											"rewindTime" => 10,
											"bg_color" => "#eeeeee",
											"video_bg_color" => "#000000",
											"poster_bg_color" => "#000000",
											"buttons_tooltip_hide_delay" => 1.5,
											"buttons_tooltip_font_color" => "#FFFFFF",
											"audioVisualizerLinesColor" => "#CCCCCC",
								   			"audioVisualizerCircleColor" => "#FFFFFF",
											//apw
											"aopwTitle" => "Advertisement",
											"aopwWidth" => 400,
											"aopwHeight" => 240,
											"aopwBorderSize" => 6,
											"aopwTitleColor" => "#FFFFFF",
											//thumbnails preview
											"thumbnails_preview_width" => 196,
											"thumbnails_preview_height" => 110,
											"thumbnails_preview_background_color" => "#000000",
											"thumbnails_preview_border_color" => "#666",
											"thumbnails_preview_label_background_color" => "#666",
											"thumbnails_preview_label_font_color" => "#FFF",
											//a to b loop
											"useAToB" => "no",
											"atbTimeBackgroundColor" => "transparent",
											"atbTimeTextColorNormal" => "#888888",
											"atbTimeTextColorSelected" => "#000000",
											"atbButtonTextNormalColor" => "#FFFFFF",
											"atbButtonTextSelectedColor" => "#FFFFFF",
											"atbButtonBackgroundNormalColor" => "#888888",
											"atbButtonBackgroundSelectedColor" => "#000000",
											//sticky
											"showOpener" => "yes",
											"showOpenerPlayPauseButton" => "yes",
											"verticalPosition" => "bottom",
											"horizontalPosition" => "center",
											"showPlayerByDefault" =>"yes",
											"animatePlayer:" => "yes",
											"openerAlignment" =>"right",
											"mainBackgroundImagePath" =>  plugin_dir_url(dirname(__FILE__)) . "content/minimal_skin_dark/main-background.png",
											"openerEqulizerOffsetTop" => -1,
											"openerEqulizerOffsetLeft" => 3,
											"offsetX" => 0,
											"offsetY" => 0,
											// controller settings
											"showController" => "yes",
											"show_controller_when_video_is_stopped" => "yes",
											"show_next_and_prev_buttons_in_controller" => "no",
											"show_volume_button" => "yes",
											"show_time" => "yes",
											"show_youtube_quality_button" => "yes",
											"show_info_button" => "yes",
											"show_download_button" => "yes",
											"show_share_button" => "yes",
											"show_embed_button" => "yes",
											"showChromecastButton" => "no",
											"show360DegreeVideoVrButton" => "no",
											"showSubtitleButton" => "yes",
											"showRewindButton" => "yes",
											"showAudioTracksButton" => "yes",
											"show_fullscreen_button" => "yes",
											"disable_video_scrubber" => "no",
											"repeat_background" => "yes",
											"controller_height" => 43,
											"controller_hide_delay" => 3,
											"start_space_between_buttons" => 10,
											"space_between_buttons" => 10,
											"scrubbers_offset_width" => 2,
											"main_scrubber_offest_top" => 17,
											"time_offset_left_width" => 2,
											"time_offset_right_width" => 2,
											"time_offset_top" => 1,
											"volume_scrubber_height" => 80,
											"volume_scrubber_ofset_height" => 20,
											"time_color" => "#919191",
											"showYoutubeRelAndInfo"=> "no",
											"youtube_quality_button_normal_color" => "#919191",
											"youtube_quality_button_selected_color" => "#000000",
											
											// playlists window settings
											"showPlaylistsSearchInput" => "yes",
											"show_playlists_button_and_playlists" => "yes",
											"show_playlists_by_default" => "no",
											"thumbnail_selected_type" => "opacity",
											"youtubePlaylistAPI"	=> "AIzaSyD6LNmbZVbixO1s4ZzQV8odsDZsO2NUsl4",
											"start_at_playlist" => 0,
											"buttons_margins" => 15,
											"thumbnail_max_width" => 350,
											"thumbnail_max_height" => 350,
											"horizontal_space_between_thumbnails" => 40,
											"vertical_space_between_thumbnails" => 40,
											
											// playlist settings
											"randomizePlaylist" => "no",
											"show_playlist_button_and_playlist" => "yes",
											"playlist_position" => "right",
											"show_playlist_by_default" => "yes",
											"show_playlist_name" => "yes",
											"show_search_input" => "yes",
											"show_loop_button" => "yes",
											"show_shuffle_button" => "yes",
											"show_next_and_prev_buttons" => "yes",
											"force_disable_download_button_for_folder" => "yes",
											"add_mouse_wheel_support" => "yes",
											"showThumbnail" => "yes",
											"showOnlyThumbnail" => "no",
											"addScrollOnMouseMove" => "no",
											"showPlaylistOnFullScreen" => "no",
											"executeCuepointsOnlyOnce" => "no",
											"folder_video_label" => "VIDEO",
											"playlist_right_width" => 290,
											"playlist_bottom_height" => 400,
											"start_at_video" => 0,
											"max_playlist_items" => 50,
											"thumbnail_width" => 71,
											"thumbnail_height" => 71,
											"space_between_controller_and_playlist" => 1,
											"space_between_thumbnails" => 1,
											"scrollbar_offest_width" => 8,
											"scollbar_speed_sensitivity" => .5,
											"playlist_background_color" => "#FFFFFF",
											"playlist_name_color" => "#000000",
											"thumbnail_normal_background_color" => "#FFFFFF",
											"thumbnail_hover_background_color" => "#eeeeee",
											"thumbnail_disabled_background_color" => "#eeeeee",
											"search_input_background_color" => "#F3F3F3",
											"search_input_color" => "#888888",
											"youtube_and_folder_video_title_color" => "#000000",
											"youtube_owner_color" => "#919191",
											"youtube_description_color" => "#919191",
											
											// logo settings
											"show_logo" => "yes",
											"hide_logo_with_controller" => "yes",
											"logo_position" => "topRight",
											"logo_path" => plugin_dir_url(dirname(__FILE__)) . "content/logo.png",
											"logo_link" => "http://www.webdesign-flash.ro/",
											"logoTarget" => "_blank",
											"logo_margins" => 5,
											
											// embed and info windows settings
											"embed_and_info_window_close_button_margins" => 15,
											"border_color" => "#CDCDCD",
											"main_labels_color" => "#000000",
											"secondary_labels_color" => "#444444",
											"share_and_embed_text_color" => "#777777",
											"input_background_color" => "#c0c0c0",
											"input_color" => "#333333",

											// context menu
											"showContextmenu" => 'yes',
											"showScriptDeveloper" => "no",
											"contextMenuBackgroundColor" => "#e3e3e3",
											"contextMenuBorderColor" => "#e3e3e3",
											"contextMenuSpacerColor" => "#CCC",
											"contextMenuItemNormalColor" => "#777",
											"contextMenuItemSelectedColor" =>"#000",
											"contextMenuItemDisabledColor" => "#BBB",

											// finger pring stamp
											"useFingerPrintStamp" => "no",
								    		"frequencyOfFingerPrintStamp" => 20000,
								    		"durationOfFingerPrintStamp" => 50
									),
									array(
											// main settings
											"id" => 6,
											"woo_commerce_display_type" => "fwduvp-responsive",
											"name" => "skin_classic_white",
											
											"showErrorInfo" => "yes",
											"initializeOnlyWhenVisible" => "yes",
											"showPlaybackRateButton" => "yes",
											"showPreloader" => "yes",
											"defaultPlaybackRate" => 1,
											"privateVideoPassword" => "428c841430ea18a70f7b06525d4b748a",
											"use_playlists_select_box" => "yes",
											"main_selector_background_selected_color" =>"#000000",
											"main_selector_text_normal_color" =>"#000000",
											"main_selector_text_selected_color" =>"#FFFFFF",
											"main_button_background_normal_color" =>"#FFFFFF",
											"main_button_background_selected_color" =>"#000000",
											"main_button_text_normal_color" =>"#000000",
											"main_button_text_selected_color" =>"#FFFFFF",
											"playAfterVideoStop" => "no",
											"stickyOnScroll" => "no",
											"stickyOnScrollShowOpener" =>"yes",
											"stickyOnScrollWidth" => 700,
											"stickyOnScrollHeight" => 394,
											"preloaderColor1" => "#FFFFFF",
											"preloaderColor2" => "#666666",
											"stopAfterLastVideoHasPlayed" => "no",
											"showDefaultControllerForVimeo" => "yes",
											"useVectorIcons" => "no",
											"showScrubberWhenControllerIsHidden" => "yes",
											"playsinline" => "yes",
											"useResumeOnPlay" => "no",
											"fill_entire_video_screen" => "no",
											"fillEntireposterScreen" => "yes",
											"goFullScreenOnButtonPlay" => "no",
											"use_HEX_colors_for_skin" => "no",
											"normal_HEX_buttons_color" => "#FF0000",
											"subtitles_off_label" => "Subtitle off",
											"playVideoOnlyWhenLoggedIn" => "no",
											"loggedInMessage" => "Please loggin to view this video.",
											"skin_path" => "classic_skin_white",
											"googleAnalyticsMeasurementId" => "",
											"closeLightBoxWhenPlayComplete" => "no",
											"lightBoxBackgroundOpacity" => ".6",
											"lightBoxBackgroundColor" => "#000000",
											"display_type" => "responsive",
											"use_deeplinking" => "yes",
											"right_click_context_menu" => "developer",
											"add_keyboard_support" => "yes",
											"auto_scale" => "yes",
											"show_buttons_tooltips" => "yes",
											"autoplay" => "no",
											"autoPlayText" => "Click To Unmute",
											"loop" => "no",
											"shuffle" => "no",
											"showMainScrubberToolTipLabel" => "yes",
											"scrubbersToolTipLabelBackgroundColor" => "#484848",
											"scrubbersToolTipLabelFontColor" => "#FFFFFF",
											"show_popup_ads_close_button" => "yes",
											"max_width" => 980,
											"max_height" => 552,
											"volume" => .8,
											"rewindTime" => 10,
											"bg_color" => "#eeeeee",
											"video_bg_color" => "#000000",
											"poster_bg_color" => "#000000",
											"buttons_tooltip_hide_delay" => 1.5,
											"buttons_tooltip_font_color" => "#FFFFFF",
											"audioVisualizerLinesColor" => "#CCCCCC",
								   			"audioVisualizerCircleColor" => "#FFFFFF",
											//apw
											"aopwTitle" => "Advertisement",
											"aopwWidth" => 400,
											"aopwHeight" => 240,
											"aopwBorderSize" => 6,
											"aopwTitleColor" => "#FFFFFF",
											//thumbnails preview
											"thumbnails_preview_width" => 196,
											"thumbnails_preview_height" => 110,
											"thumbnails_preview_background_color" => "#000000",
											"thumbnails_preview_border_color" => "#666",
											"thumbnails_preview_label_background_color" => "#666",
											"thumbnails_preview_label_font_color" => "#FFF",
											//a to b loop
											"useAToB" => "no",
											"atbTimeBackgroundColor" => "transparent",
											"atbTimeTextColorNormal" => "#888888",
											"atbTimeTextColorSelected" => "#000000",
											"atbButtonTextNormalColor" => "#FFFFFF",
											"atbButtonTextSelectedColor" => "#FFFFFF",
											"atbButtonBackgroundNormalColor" => "#888888",
											"atbButtonBackgroundSelectedColor" => "#000000",
											//sticky
											"showOpener" => "yes",
											"showOpenerPlayPauseButton" => "yes",
											"verticalPosition" => "bottom",
											"horizontalPosition" => "center",
											"showPlayerByDefault" =>"yes",
											"animatePlayer:" => "yes",
											"openerAlignment" =>"right",
											"mainBackgroundImagePath" =>  plugin_dir_url(dirname(__FILE__)) . "content/minimal_skin_dark/main-background.png",
											"openerEqulizerOffsetTop" => -1,
											"openerEqulizerOffsetLeft" => 3,
											"offsetX" => 0,
											"offsetY" => 0,
											// controller settings
											"showController" => "yes",
											"show_controller_when_video_is_stopped" => "yes",
											"show_next_and_prev_buttons_in_controller" => "no",
											"show_volume_button" => "yes",
											"show_time" => "yes",
											"show_youtube_quality_button" => "yes",
											"show_info_button" => "yes",
											"show_download_button" => "yes",
											"show_share_button" => "yes",
											"show_embed_button" => "yes",
											"showChromecastButton" => "no",
											"show360DegreeVideoVrButton" => "no",
											"showSubtitleButton" => "yes",
											"showRewindButton" => "yes",
											"showAudioTracksButton" => "yes",
											"show_fullscreen_button" => "yes",
											"disable_video_scrubber" => "no",
											"repeat_background" => "no",
											"controller_height" => 42,
											"controller_hide_delay" => 3,
											"start_space_between_buttons" => 7,
											"space_between_buttons" => 8,
											"scrubbers_offset_width" => 2,
											"main_scrubber_offest_top" => 14,
											"time_offset_left_width" => 5,
											"time_offset_right_width" => 3,
											"time_offset_top" => 0,
											"volume_scrubber_height" => 80,
											"volume_scrubber_ofset_height" => 12,
											"time_color" => "#FFFFFF",
											"showYoutubeRelAndInfo"=> "no",
											"youtube_quality_button_normal_color" => "#494949",
											"youtube_quality_button_selected_color" => "#FFFFFF",
											
											// playlists window settings
											"showPlaylistsSearchInput" => "yes",
											"show_playlists_button_and_playlists" => "yes",
											"show_playlists_by_default" => "no",
											"thumbnail_selected_type" => "opacity",
											"youtubePlaylistAPI"	=> "AIzaSyD6LNmbZVbixO1s4ZzQV8odsDZsO2NUsl4",
											"start_at_playlist" => 0,
											"buttons_margins" => 15,
											"thumbnail_max_width" => 350,
											"thumbnail_max_height" => 350,
											"horizontal_space_between_thumbnails" => 40,
											"vertical_space_between_thumbnails" => 40,
											
											// playlist settings
											"randomizePlaylist" => "no",
											"show_playlist_button_and_playlist" => "yes",
											"playlist_position" => "right",
											"show_playlist_by_default" => "yes",
											"show_playlist_name" => "yes",
											"show_search_input" => "yes",
											"show_loop_button" => "yes",
											"show_shuffle_button" => "yes",
											"show_next_and_prev_buttons" => "yes",
											"force_disable_download_button_for_folder" => "yes",
											"add_mouse_wheel_support" => "yes",
											"showThumbnail" => "yes",
											"showOnlyThumbnail" => "no",
											"addScrollOnMouseMove" => "no",
											"showPlaylistOnFullScreen" => "no",
											"executeCuepointsOnlyOnce" => "no",
											"folder_video_label" => "VIDEO",
											"playlist_right_width" => 310,
											"playlist_bottom_height" => 400,
											"start_at_video" => 0,
											"max_playlist_items" => 50,
											"thumbnail_width" => 71,
											"thumbnail_height" => 71,
											"space_between_controller_and_playlist" => 1,
											"space_between_thumbnails" => 1,
											"scrollbar_offest_width" => 8,
											"scollbar_speed_sensitivity" => .5,
											"playlist_background_color" => "#FFFFFF",
											"playlist_name_color" => "#000000",
											"thumbnail_normal_background_color" => "#FFFFFF",
											"thumbnail_hover_background_color" => "#EEEEEE",
											"thumbnail_disabled_background_color" => "#EEEEEE",
											"search_input_background_color" => "#F3F3F3",
											"search_input_color" => "#888888",
											"youtube_and_folder_video_title_color" => "#000000",
											"youtube_owner_color" => "#777777",
											"youtube_description_color" => "#777777",
											
											// logo settings
											"show_logo" => "yes",
											"hide_logo_with_controller" => "yes",
											"logo_position" => "topRight",
											"logo_path" => plugin_dir_url(dirname(__FILE__)) . "content/logo.png",
											"logo_link" => "http://www.webdesign-flash.ro/",
											"logoTarget" => "_blank",
											"logo_margins" => 5,
											
											// embed and info windows settings
											"embed_and_info_window_close_button_margins" => 15,
											"border_color" => "#CDCDCD",
											"main_labels_color" => "#000000",
											"secondary_labels_color" => "#494949",
											"share_and_embed_text_color" => "#777777",
											"input_background_color" => "#b2b2b2",
											"input_color" => "#333333",

											// context menu
											"showContextmenu" => 'yes',
											"showScriptDeveloper" => "no",
											"contextMenuBackgroundColor" => "#ebebeb",
											"contextMenuBorderColor" => "#ebebeb",
											"contextMenuSpacerColor" => "#CCC",
											"contextMenuItemNormalColor" => "#666666",
											"contextMenuItemSelectedColor" => "#000",
											"contextMenuItemDisabledColor" => "#AAA",

											// finger pring stamp
											"useFingerPrintStamp" => "no",
								    		"frequencyOfFingerPrintStamp" => 20000,
								    		"durationOfFingerPrintStamp" => 50
									),
									array(
											// main settings
											"id" => 7,
											"woo_commerce_display_type" => "fwduvp-responsive",
											"name" => "skin_metal_white",
											
											"showErrorInfo" => "yes",
											"initializeOnlyWhenVisible" => "yes",
											"showPlaybackRateButton" => "yes",
											"showPreloader" => "yes",
											"defaultPlaybackRate" => 1,
											"privateVideoPassword" => "428c841430ea18a70f7b06525d4b748a",
											"use_playlists_select_box" => "yes",
											"main_selector_background_selected_color" =>"#000000",
											"main_selector_text_normal_color" =>"#000000",
											"main_selector_text_selected_color" =>"#FFFFFF",
											"main_button_background_normal_color" =>"#FFFFFF",
											"main_button_background_selected_color" =>"#000000",
											"main_button_text_normal_color" =>"#000000",
											"main_button_text_selected_color" =>"#FFFFFF",
											"playAfterVideoStop" => "no",
											"stickyOnScroll" => "no",
											"stickyOnScrollShowOpener" =>"yes",
											"stickyOnScrollWidth" => 700,
											"stickyOnScrollHeight" => 394,
											"preloaderColor1" => "#FFFFFF",
											"preloaderColor2" => "#666666",
											"stopAfterLastVideoHasPlayed" => "no",
											"showDefaultControllerForVimeo" => "yes",
											"useVectorIcons" => "no",
											"showScrubberWhenControllerIsHidden" => "yes",
											"playsinline" => "yes",
											"useResumeOnPlay" => "no",
											"fill_entire_video_screen" => "no",
											"fillEntireposterScreen" => "yes",
											"goFullScreenOnButtonPlay" => "no",
											"use_HEX_colors_for_skin" => "no",
											"normal_HEX_buttons_color" => "#FF0000",
											"playVideoOnlyWhenLoggedIn" => "no",
											"loggedInMessage" => "Please loggin to view this video.",
											"subtitles_off_label" => "Subtitle off",
											"playVideoOnlyWhenLoggedIn" => "no",
											"loggedInMessage" => "Please loggin to view this video.",
											"skin_path" => "metal_skin_white",
											"googleAnalyticsMeasurementId" => "",
											"closeLightBoxWhenPlayComplete" => "no",
											"lightBoxBackgroundOpacity" => ".6",
											"lightBoxBackgroundColor" => "#000000",
											"display_type" => "responsive",
											"use_deeplinking" => "yes",
											"right_click_context_menu" => "developer",
											"add_keyboard_support" => "yes",
											"auto_scale" => "yes",
											"show_buttons_tooltips" => "yes",
											"autoplay" => "no",
											"autoPlayText" => "Click To Unmute",
											"loop" => "no",
											"shuffle" => "no",
											"showMainScrubberToolTipLabel" => "yes",
											"scrubbersToolTipLabelBackgroundColor" => "#000000",
											"scrubbersToolTipLabelFontColor" => "#FFFFFF",
											"show_popup_ads_close_button" => "yes",
											"max_width" => 980,
											"max_height" => 552,
											"volume" => .8,
											"rewindTime" => 10,
											"bg_color" => "#eeeeee",
											"video_bg_color" => "#000000",
											"poster_bg_color" => "#000000",
											"buttons_tooltip_hide_delay" => 1.5,
											"buttons_tooltip_font_color" => "#FFFFFF",
											"audioVisualizerLinesColor" => "#CCCCCC",
								   			"audioVisualizerCircleColor" => "#FFFFFF",
											//apw
											"aopwTitle" => "Advertisement",
											"aopwWidth" => 400,
											"aopwHeight" => 240,
											"aopwBorderSize" => 6,
											"aopwTitleColor" => "#FFFFFF",
											//thumbnails preview
											"thumbnails_preview_width" => 196,
											"thumbnails_preview_height" => 110,
											"thumbnails_preview_background_color" => "#000000",
											"thumbnails_preview_border_color" => "#666",
											"thumbnails_preview_label_background_color" => "#666",
											"thumbnails_preview_label_font_color" => "#FFF",
											//a to b loop
											"useAToB" => "no",
											"atbTimeBackgroundColor" => "transparent",
											"atbTimeTextColorNormal" => "#888888",
											"atbTimeTextColorSelected" => "#000000",
											"atbButtonTextNormalColor" => "#FFFFFF",
											"atbButtonTextSelectedColor" => "#FFFFFF",
											"atbButtonBackgroundNormalColor" => "#888888",
											"atbButtonBackgroundSelectedColor" => "#000000",
											//sticky
											"showOpener" => "yes",
											"showOpenerPlayPauseButton" => "yes",
											"verticalPosition" => "bottom",
											"horizontalPosition" => "center",
											"showPlayerByDefault" =>"yes",
											"animatePlayer:" => "yes",
											"openerAlignment" =>"right",
											"mainBackgroundImagePath" =>  plugin_dir_url(dirname(__FILE__)) . "content/minimal_skin_dark/main-background.png",
											"openerEqulizerOffsetTop" => -1,
											"openerEqulizerOffsetLeft" => 3,
											"offsetX" => 0,
											"offsetY" => 0,
											// controller settings
											"showController" => "yes",
											"show_controller_when_video_is_stopped" => "yes",
											"show_next_and_prev_buttons_in_controller" => "no",
											"show_volume_button" => "yes",
											"show_time" => "yes",
											"show_youtube_quality_button" => "yes",
											"show_info_button" => "yes",
											"show_download_button" => "yes",
											"show_share_button" => "yes",
											"show_embed_button" => "yes",
											"showChromecastButton" => "no",
											"show360DegreeVideoVrButton" => "no",
											"showSubtitleButton" => "yes",
											"showRewindButton" => "yes",
											"showAudioTracksButton" => "yes",
											"show_fullscreen_button" => "yes",
											"disable_video_scrubber" => "no",
											"repeat_background" => "yes",
											"controller_height" => 43,
											"controller_hide_delay" => 3,
											"start_space_between_buttons" => 7,
											"space_between_buttons" => 8,
											"scrubbers_offset_width" => 4,
											"main_scrubber_offest_top" => 14,
											"time_offset_left_width" => 5,
											"time_offset_right_width" => 3,
											"time_offset_top" => 0,
											"volume_scrubber_height" => 80,
											"volume_scrubber_ofset_height" => 12,
											"time_color" => "#919191",
											"showYoutubeRelAndInfo"=> "no",
											"youtube_quality_button_normal_color" => "#919191",
											"youtube_quality_button_selected_color" => "#000000",
											
											// playlists window settings
											"showPlaylistsSearchInput" => "yes",
											"show_playlists_button_and_playlists" => "yes",
											"show_playlists_by_default" => "no",
											"thumbnail_selected_type" => "opacity",
											"youtubePlaylistAPI"	=> "AIzaSyD6LNmbZVbixO1s4ZzQV8odsDZsO2NUsl4",
											"start_at_playlist" => 0,
											"buttons_margins" => 0,
											"thumbnail_max_width" => 350,
											"thumbnail_max_height" => 350,
											"horizontal_space_between_thumbnails" => 40,
											"vertical_space_between_thumbnails" => 40,
											
											// playlist settings
											"randomizePlaylist" => "no",
											"show_playlist_button_and_playlist" => "yes",
											"playlist_position" => "right",
											"show_playlist_by_default" => "yes",
											"show_playlist_name" => "yes",
											"show_search_input" => "yes",
											"show_loop_button" => "yes",
											"show_shuffle_button" => "yes",
											"show_next_and_prev_buttons" => "yes",
											"force_disable_download_button_for_folder" => "yes",
											"add_mouse_wheel_support" => "yes",
											"showThumbnail" => "yes",
											"showOnlyThumbnail" => "no",
											"addScrollOnMouseMove" => "no",
											"showPlaylistOnFullScreen" => "no",
											"executeCuepointsOnlyOnce" => "no",
											"folder_video_label" => "VIDEO",
											"playlist_right_width" => 310,
											"playlist_bottom_height" => 400,
											"start_at_video" => 0,
											"max_playlist_items" => 50,
											"thumbnail_width" => 71,
											"thumbnail_height" => 71,
											"space_between_controller_and_playlist" => 1,
											"space_between_thumbnails" => 1,
											"scrollbar_offest_width" => 8,
											"scollbar_speed_sensitivity" => .5,
											"playlist_background_color" => "#FFFFFF",
											"playlist_name_color" => "#000000",
											"thumbnail_normal_background_color" => "#FFFFFF",
											"thumbnail_hover_background_color" => "#eeeeee",
											"thumbnail_disabled_background_color" => "#eeeeee",
											"search_input_background_color" => "#F3F3F3",
											"search_input_color" => "#888888",
											"youtube_and_folder_video_title_color" => "#000000",
											"youtube_owner_color" => "#919191",
											"youtube_description_color" => "#919191",
											
											// logo settings
											"show_logo" => "yes",
											"hide_logo_with_controller" => "yes",
											"logo_position" => "topRight",
											"logo_path" => plugin_dir_url(dirname(__FILE__)) . "content/logo.png",
											"logo_link" => "http://www.webdesign-flash.ro/",
											"logoTarget" => "_blank",
											"logo_margins" => 5,
											
											// embed and info windows settings
											"embed_and_info_window_close_button_margins" => 15,
											"border_color" => "#CDCDCD",
											"main_labels_color" => "#000000",
											"secondary_labels_color" => "#444444",
											"share_and_embed_text_color" => "#777777",
											"input_background_color" => "#c0c0c0",
											"input_color" => "#333333",

											// context menu
											"showContextmenu" => 'yes',
											"showScriptDeveloper" => "no",
											"contextMenuBackgroundColor" => "#dcdcdc",
											"contextMenuBorderColor" => "#dcdcdc",
											"contextMenuSpacerColor" => "#CCC",
											"contextMenuItemNormalColor" => "#666666",
											"contextMenuItemSelectedColor" => "#000",
											"contextMenuItemDisabledColor" => "#AAA",

											// finger pring stamp
											"useFingerPrintStamp" => "no",
								    		"frequencyOfFingerPrintStamp" => 20000,
								    		"durationOfFingerPrintStamp" => 50
									)
							      );
		}
    	
    }


    // Initialize playlist.
    private function init_playlist(){
    	 $this->main_playlists_ar = array();
		 $this->global_ads_ar = array();
		 $this->global_popup_ads_ar = array();
		 $this->global_vast_url = '';
		 $this->global_vast_target = '_blank';
		 $this->global_vast_start_time = '00:00:00';
		 $this->global_pause_ads_source = '';
		 $this->global_apply_to_youtube_videos = 'no';
    }

    // Get options.
    public function get_data(){
	    $cur_data = get_option("fwduvp_data");
	       
	    $this->settings_ar = $cur_data->settings_ar;
	    $this->main_playlists_ar = $cur_data->main_playlists_ar;
		$this->global_ads_ar = isset($cur_data->global_ads_ar) && is_array($cur_data->global_ads_ar) ? $cur_data->global_ads_ar : array();
		$this->global_popup_ads_ar = isset($cur_data->global_popup_ads_ar) && is_array($cur_data->global_popup_ads_ar) ? $cur_data->global_popup_ads_ar : array();
		$this->global_vast_url = isset($cur_data->global_vast_url) ? $cur_data->global_vast_url : '';
		$this->global_vast_target = isset($cur_data->global_vast_target) ? $cur_data->global_vast_target : '_blank';
		$this->global_vast_start_time = isset($cur_data->global_vast_start_time) ? $cur_data->global_vast_start_time : '00:00:00';
		$this->global_pause_ads_source = isset($cur_data->global_pause_ads_source) ? $cur_data->global_pause_ads_source : '';
		$this->global_apply_to_youtube_videos = isset($cur_data->global_apply_to_youtube_videos) ? $cur_data->global_apply_to_youtube_videos : 'no';
    }
    

    // Set options.
    public function set_data(){
    	update_option("fwduvp_data", $this);
    }
}
?>