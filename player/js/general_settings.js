/**
 * General settings.
 *
 * @package fwduvp
 * @since fwduvp 1.0
 */
 
jQuery(document).ready(function($){

  'use strict';

  var DEFAULT_SKINS_NR = 8;
    var fwduvpMessage;
    var isPresetRequestRunning = false;

  fwduvpSettingsAr = unescapeHtml(fwduvpSettingsAr);
  fwduvpSettingsAr = JSON.parse(fwduvpSettingsAr);

  fwduvpVideoStartBehaviour = unescapeHtml(fwduvpVideoStartBehaviour);
  fwduvpVideoStartBehaviour = JSON.parse(fwduvpVideoStartBehaviour);

  if(fwduvpTextDomain.indexOf('acora') != -1){
      DEFAULT_SKINS_NR = 1;
  } 

  function togglePresetButtons(disabled){
      $("#add_btn, #update_btn, #del_btn").prop("disabled", !!disabled);
  }

  function showPresetMessage(text, isError){
      if(!text) return;

      if(typeof FWDUVPMessage !== "undefined"){
          if(!fwduvpMessage){
              fwduvpMessage = new FWDUVPMessage();
          }
          fwduvpMessage.show(text, isError);
          return;
      }

      if(isError){
          updateTips(text);
      }
  }

  function updateButtonsByPresetType(){
      if(fwduvpCurOrderId < DEFAULT_SKINS_NR){
          $("#update_btn").hide();
          $("#del_btn").hide();
      }else{
          $("#update_btn").show();
          $("#del_btn").show();
      }
  }

  function rebuildPresetSelect(selectedSetId){
      $("#skins").empty();

      $.each(fwduvpSettingsAr, function(i, el){
          $("#skins").append("<option value='" + el.id + "'>" + el.name + "</option>");
      });

      if(!fwduvpSettingsAr.length){
          fwduvpSetId = 0;
          fwduvpCurOrderId = 0;
          return;
      }

      var nextId = typeof selectedSetId !== "undefined" ? parseInt(selectedSetId, 10) : parseInt(fwduvpSettingsAr[0].id, 10);
      var exists = false;

      $("#skins option").each(function(){
          if(parseInt($(this).val(), 10) === nextId){
              exists = true;
              return false;
          }
      });

      if(!exists){
          nextId = parseInt(fwduvpSettingsAr[0].id, 10);
      }

      $("#skins").val(String(nextId));
      fwduvpSetId = nextId;

      $.each(fwduvpSettingsAr, function(i, el){
          if(parseInt(el.id, 10) === nextId){
              fwduvpCurOrderId = i;
              return false;
          }
      });
  }

  function savePresetWithAjax(dataObj, onSuccess){
      if(isPresetRequestRunning){
          return;
      }

      isPresetRequestRunning = true;
      togglePresetButtons(true);

      $.ajax({
          url: fwduvpAjaxUrl,
          type: "POST",
          dataType: "json",
          data: {
              action: "fwduvp_update_presets",
              nonce: fwduvpGeneralSettingsAjaxNonce,
              settings_data: JSON.stringify(dataObj)
          }
      }).done(function(response){
          if(response && response.success){
              if(response.data && dataObj.action !== "del"){
                  if(typeof response.data.set_id !== "undefined") fwduvpSetId = parseInt(response.data.set_id);
                  if(typeof response.data.set_order_id !== "undefined") fwduvpCurOrderId = parseInt(response.data.set_order_id);
                  if(typeof response.data.tab_init_id !== "undefined") fwduvpCurTabId = parseInt(response.data.tab_init_id);
              }

              if(typeof onSuccess === "function") onSuccess(response);
              showPresetMessage(response && response.data ? response.data.msg : "", false);
          }else{
              var msg = response && response.data && response.data.msg ? response.data.msg : "Failed to save preset settings.";
              showPresetMessage(msg, true);
          }
      }).fail(function(){
          showPresetMessage("Failed to save preset settings.", true);
      }).always(function(){
          isPresetRequestRunning = false;
          togglePresetButtons(false);
      });
  }
  
  var cur_settings_obj;
    var defaultTipsText = "";
  
  $("#tabs").tabs();
        
  $("#contextMenuBackgroundColor, #audioVisualizerLinesColor, #audioVisualizerCircleColor, #contextMenuBorderColor,#contextMenuSpacerColor, #contextMenuItemNormalColor, #contextMenuItemSelectedColor,#contextMenuItemDisabledColor,#aopwTitleColor,#thumbnails_preview_background_color,#thumbnails_preview_border_color, #thumbnails_preview_label_background_color,#thumbnails_preview_label_font_color,#atbTimeTextColorNormal,#atbTimeTextColorSelected,#atbButtonTextNormalColor,#atbButtonTextSelectedColor,#atbButtonBackgroundNormalColor, #atbButtonBackgroundSelectedColor,#bg_color,#scrubbersToolTipLabelBackgroundColor,#scrubbersToolTipLabelFontColor,#normal_HEX_buttons_color,#lightBoxBackgroundColor,#video_bg_color,#poster_bg_color,#buttons_tooltip_font_color,#time_color,#preloaderColor1,#preloaderColor2,#youtube_quality_button_normal_color,#youtube_quality_button_selected_color,#playlist_background_color,#playlist_name_color, #thumbnail_normal_background_color,#thumbnail_hover_background_color,#thumbnail_disabled_background_color,#search_input_background_color,#search_input_color,#youtube_and_folder_video_title_color,#youtube_owner_color,#youtube_description_color,#borderColor,#main_labels_color,#secondary_labels_color,#share_and_embed_text_color, #input_background_color,#input_color,#ads_text_normal_color,#ads_text_selected_color,#ads_border_normal_color,#ads_border_selected_color,  #main_selector_background_selected_color,#main_selector_text_normal_color,#main_selector_text_selected_color,#main_button_background_normal_color,#main_button_background_selected_color,#main_button_text_normal_color,#main_button_text_selected_color").spectrum({
    color: "#888888",
    chooseText: "Update",
    showInitial: true,
    showInput: true,
    allowEmpty:true,
    preferredFormat: "hex6"
  });
    
  $("#tabs img, .fwdtooltip").fwdTooltip({
    
  });

    defaultTipsText = $("#tips").text();
  
  $("#start_space_between_buttons_img").fwdTooltip({
      content: "<img src='" + fwduvpSpacesUrl + "startSpaceBetweenButtons.jpg' width='600' height='41'>",
      tooltipClass: "ui-tooltip-img"
  });
  
  $("#space_between_buttons_img").fwdTooltip({
      content: "<img src='" + fwduvpSpacesUrl + "spaceBetweenButtons.jpg' width='600' height='41'>",
      tooltipClass: "ui-tooltip-img",
  });
  

  // Set settings.
  function setSettings() {
      $("#startBH").val(fwduvpVideoStartBehaviour);
      var settings_obj = fwduvpSettingsAr[fwduvpCurOrderId];
      
      // Main settings.
      $("#name").val(settings_obj.name);
      $("#woo_commerce_display_type").val(settings_obj.woo_commerce_display_type);
      $("#display_type").val(settings_obj.display_type);
      $("#skin_path").val(settings_obj.skin_path);
      $("#initializeOnlyWhenVisible").val(settings_obj.initializeOnlyWhenVisible);
      $("#stickyOnScroll").val(settings_obj.stickyOnScroll);
      $("#stickyOnScrollShowOpener").val(settings_obj.stickyOnScrollShowOpener);
      $("#stickyOnScrollWidth").val(parseInt(settings_obj.stickyOnScrollWidth));
      $("#stickyOnScrollHeight").val(parseInt(settings_obj.stickyOnScrollHeight));
      $("#fill_entire_video_screen").val(settings_obj.fill_entire_video_screen);
      $("#fillEntireposterScreen").val(settings_obj.fillEntireposterScreen);
      $("#goFullScreenOnButtonPlay").val(settings_obj.goFullScreenOnButtonPlay);
      $("#subtitles_off_label").val(settings_obj.subtitles_off_label);
      $("#playsinline").val(settings_obj.playsinline);
      $("#useResumeOnPlay").val(settings_obj.useResumeOnPlay);
      $("#use_HEX_colors_for_skin").val(settings_obj.use_HEX_colors_for_skin);
      $("#use_deeplinking").val(settings_obj.use_deeplinking);
      $("#add_keyboard_support").val(settings_obj.add_keyboard_support);
      $("#auto_scale").val(settings_obj.auto_scale);
      $("#show_buttons_tooltips").val(settings_obj.show_buttons_tooltips);
      $("#stop_video_when_play_complete").val(settings_obj.stop_video_when_play_complete);
      $("#autoplay").val(settings_obj.autoplay);
      $("#autoPlayText").val(settings_obj.autoPlayText);
      $("#loop").val(settings_obj.loop);
      $("#googleAnalyticsMeasurementId").val(settings_obj.googleAnalyticsMeasurementId);
      $("#useVectorIcons").val(settings_obj.useVectorIcons);
      $("#shuffle").val(settings_obj.shuffle);
      $("#max_width").val(settings_obj.max_width);
      $("#max_height").val(settings_obj.max_height);
      $("#volume").val(settings_obj.volume);
      $("#rewindTime").val(settings_obj.rewindTime);
      $("#show_popup_ads_close_button").val(settings_obj.show_popup_ads_close_button);
      $("#bg_color").spectrum("set", settings_obj.bg_color != "transparent" ? settings_obj.bg_color : null);
      $("#video_bg_color").spectrum("set", settings_obj.video_bg_color != "transparent" ? settings_obj.video_bg_color : null);
      $("#poster_bg_color").spectrum("set", settings_obj.poster_bg_color != "transparent" ? settings_obj.poster_bg_color : null);
      $("#buttons_tooltip_hide_delay").val(settings_obj.buttons_tooltip_hide_delay);
      $("#buttons_tooltip_font_color").spectrum("set", settings_obj.buttons_tooltip_font_color != "transparent" ? settings_obj.buttons_tooltip_font_color : null); 
      $("#atbTimeTextColorNormal").spectrum("set", settings_obj.atbTimeTextColorNormal != "transparent" ? settings_obj.atbTimeTextColorNormal : null);
      $("#atbTimeTextColorSelected").spectrum("set", settings_obj.atbTimeTextColorSelected != "transparent" ? settings_obj.atbTimeTextColorSelected : null);
      $("#atbButtonTextNormalColor").spectrum("set", settings_obj.atbButtonTextNormalColor != "transparent" ? settings_obj.atbButtonTextNormalColor : null);
      $("#atbButtonTextSelectedColor").spectrum("set", settings_obj.atbButtonTextSelectedColor != "transparent" ? settings_obj.atbButtonTextSelectedColor : null);
      $("#atbButtonBackgroundNormalColor").spectrum("set", settings_obj.atbButtonBackgroundNormalColor != "transparent" ? settings_obj.atbButtonBackgroundNormalColor : null);  
      $("#atbButtonBackgroundSelectedColor").spectrum("set", settings_obj.atbButtonBackgroundSelectedColor != "transparent" ? settings_obj.atbButtonBackgroundSelectedColor : null);    
      $("#main_selector_background_selected_color").spectrum("set", settings_obj.main_selector_background_selected_color != "transparent" ? settings_obj.main_selector_background_selected_color : null);
      $("#main_selector_text_normal_color").spectrum("set", settings_obj.main_selector_text_normal_color != "transparent" ? settings_obj.main_selector_text_normal_color : null);
      $("#main_selector_text_selected_color").spectrum("set", settings_obj.main_selector_text_selected_color != "transparent" ? settings_obj.main_selector_text_selected_color : null);
      $("#main_button_background_normal_color").spectrum("set", settings_obj.main_button_background_normal_color != "transparent" ? settings_obj.main_button_background_normal_color : null);
      $("#main_button_background_selected_color").spectrum("set", settings_obj.main_button_background_selected_color != "transparent" ? settings_obj.main_button_background_selected_color : null);
      $("#main_button_text_normal_color").spectrum("set", settings_obj.main_button_text_normal_color != "transparent" ? settings_obj.main_button_text_normal_color : null);
      $("#main_button_text_selected_color").spectrum("set", settings_obj.main_button_text_selected_color != "transparent" ? settings_obj.main_button_text_selected_color : null);
      $("#audioVisualizerLinesColor").spectrum("set", settings_obj.audioVisualizerLinesColor != "transparent" ? settings_obj.audioVisualizerLinesColor : null);
      $("#audioVisualizerCircleColor").spectrum("set", settings_obj.audioVisualizerCircleColor != "transparent" ? settings_obj.audioVisualizerCircleColor : null);

      // Sticky.
      $("#showPlayerByDefault").val(settings_obj.showPlayerByDefault);
      $("#animatePlayer").val(settings_obj.animatePlayer);
      $("#googleAnalyticsMeasurementId").val(settings_obj.googleAnalyticsMeasurementId);
      $("#showOpener").val(settings_obj.showOpener);
      $("#showOpenerPlayPauseButton").val(settings_obj.showOpenerPlayPauseButton);
      $("#openerAlignment").val(settings_obj.openerAlignment);
      $("#verticalPosition").val(settings_obj.verticalPosition);
      $("#horizontalPosition").val(settings_obj.horizontalPosition);
      $("#openerEqulizerOffsetTop").val(settings_obj.openerEqulizerOffsetTop);
      $("#openerEqulizerOffsetLeft").val(settings_obj.openerEqulizerOffsetLeft);
      $("#offsetX").val(settings_obj.offsetX);
      $("#offsetY").val(settings_obj.offsetY);
      $("#uvp_mainBackgroundImagePath").val(settings_obj.mainBackgroundImagePath);
      $("#uvp_bg_upload_img").attr("src", settings_obj.mainBackgroundImagePath);
      
      // Controller settings.
      $("#show_controller_when_video_is_stopped").val(settings_obj.show_controller_when_video_is_stopped);
      $("#showController").val(settings_obj.showController);
      $("#show_next_and_prev_buttons_in_controller").val(settings_obj.show_next_and_prev_buttons_in_controller);
      $("#show_volume_button").val(settings_obj.show_volume_button);
      $("#showErrorInfo").val(settings_obj.showErrorInfo);
      $("#useAToB").val(settings_obj.useAToB);
      $("#show_time").val(settings_obj.show_time);
      $("#show_youtube_quality_button").val(settings_obj.show_youtube_quality_button);
      $("#showPlaybackRateButton").val(settings_obj.showPlaybackRateButton);
      $("#defaultPlaybackRate").val(settings_obj.defaultPlaybackRate);
      $("#showRewindButton").val(settings_obj.showRewindButton);
      $("#privateVideoPassword").val(settings_obj.privateVideoPassword);
      $("#showPreloader").val(settings_obj.showPreloader);
      $("#show_info_button").val(settings_obj.show_info_button);
      $("#show_download_button").val(settings_obj.show_download_button);
      $("#show_share_button").val(settings_obj.show_share_button);
      $("#show_embed_button").val(settings_obj.show_embed_button);
      $("#showChromecastButton").val(settings_obj.showChromecastButton);
      $("#show360DegreeVideoVrButton").val(settings_obj.show360DegreeVideoVrButton);
      $("#showSubtitleButton").val(settings_obj.showSubtitleButton);
      $("#show_fullscreen_button").val(settings_obj.show_fullscreen_button);
      $("#showAudioTracksButton").val(settings_obj.showAudioTracksButton);
      $("#repeat_background").val(settings_obj.repeat_background);
      $("#disable_video_scrubber").val(settings_obj.disable_video_scrubber);
      $("#controller_height").val(settings_obj.controller_height);
      $("#showMainScrubberToolTipLabel").val(settings_obj.showMainScrubberToolTipLabel);
      $("#showScrubberWhenControllerIsHidden").val(settings_obj.showScrubberWhenControllerIsHidden);
      $("#controller_hide_delay").val(settings_obj.controller_hide_delay);
      $("#start_space_between_buttons").val(settings_obj.start_space_between_buttons);
      $("#space_between_buttons").val(settings_obj.space_between_buttons);
      $("#scrubbers_offset_width").val(settings_obj.scrubbers_offset_width);
      $("#main_scrubber_offest_top").val(settings_obj.main_scrubber_offest_top);
      $("#loggedInMessage").val(unescapeHtml(settings_obj.loggedInMessage));
      $("#playIfLoggedIn").val(settings_obj.playIfLoggedIn);
      $("#lightBoxBackgroundOpacity").val(settings_obj.lightBoxBackgroundOpacity);
      $("#closeLightBoxWhenPlayComplete").val(settings_obj.closeLightBoxWhenPlayComplete);
      $("#showYoutubeRelAndInfo").val(settings_obj.showYoutubeRelAndInfo);
      $("#showDefaultControllerForVimeo").val(settings_obj.showDefaultControllerForVimeo);
      $("#executeCuepointsOnlyOnce").val(settings_obj.executeCuepointsOnlyOnce);
      $("#aopwTitle").val(settings_obj.aopwTitle);
      $("#aopwWidth").val(settings_obj.aopwWidth);
      $("#aopwHeight").val(settings_obj.aopwHeight);
      $("#aopwBorderSize").val(settings_obj.aopwBorderSize);
      $("#aopwTitleColor").spectrum("set", settings_obj.aopwTitleColor != "transparent" ? settings_obj.aopwTitleColor : null);
      $("#time_offset_left_width").val(settings_obj.time_offset_left_width);
      $("#time_offset_right_width").val(settings_obj.time_offset_right_width);
      $("#time_offset_top").val(settings_obj.time_offset_top);
      $("#volume_scrubber_height").val(settings_obj.volume_scrubber_height);
      $("#volume_scrubber_ofset_height").val(settings_obj.volume_scrubber_ofset_height);
      $("#time_color").spectrum("set", settings_obj.time_color != "transparent" ? settings_obj.time_color : null);
      $("#preloaderColor1").spectrum("set", settings_obj.preloaderColor1 != "transparent" ? settings_obj.preloaderColor1 : null);
      $("#preloaderColor2").spectrum("set", settings_obj.preloaderColor2 != "transparent" ? settings_obj.preloaderColor2 : null);
      $("#youtube_quality_button_normal_color").spectrum("set", settings_obj.youtube_quality_button_normal_color != "transparent" ? settings_obj.youtube_quality_button_normal_color : null);
      $("#youtube_quality_button_selected_color").spectrum("set", settings_obj.youtube_quality_button_selected_color != "transparent" ? settings_obj.youtube_quality_button_selected_color : null);
      $("#normal_HEX_buttons_color").spectrum("set", settings_obj.normal_HEX_buttons_color != "transparent" ? settings_obj.normal_HEX_buttons_color : null);
    
      // Playlists window settings.
      $("#show_playlists_button_and_playlists").val(settings_obj.show_playlists_button_and_playlists);
      $("#showPlaylistsSearchInput").val(settings_obj.showPlaylistsSearchInput);
      $("#use_playlists_select_box").val(settings_obj.use_playlists_select_box);
      $("#show_playlists_by_default").val(settings_obj.show_playlists_by_default);
      $("#thumbnail_selected_type").val(settings_obj.thumbnail_selected_type);
      $("#start_at_playlist").val(settings_obj.start_at_playlist);
      $("#youtubePlaylistAPI").val(settings_obj.youtubePlaylistAPI);
      $("#buttons_margins").val(settings_obj.buttons_margins);
      $("#thumbnail_max_width").val(settings_obj.thumbnail_max_width);
      $("#thumbnail_max_height").val(settings_obj.thumbnail_max_height);
      $("#horizontal_space_between_thumbnails").val(settings_obj.horizontal_space_between_thumbnails);
      $("#vertical_space_between_thumbnails").val(settings_obj.vertical_space_between_thumbnails);
      
      // Playlist settings.
      $("#show_playlist_button_and_playlist").val(settings_obj.show_playlist_button_and_playlist);
      $("#playlist_position").val(settings_obj.playlist_position);
      $("#show_playlist_by_default").val(settings_obj.show_playlist_by_default);
      $("#showPlaylistOnFullScreen").val(settings_obj.showPlaylistOnFullScreen);
      $("#show_playlist_name").val(settings_obj.show_playlist_name);
      $("#showThumbnail").val(settings_obj.showThumbnail);
      $("#showOnlyThumbnail").val(settings_obj.showOnlyThumbnail);
      $("#addScrollOnMouseMove").val(settings_obj.addScrollOnMouseMove);
      $("#randomizePlaylist").val(settings_obj.randomizePlaylist);
      $("#show_search_input").val(settings_obj.show_search_input);
      $("#show_loop_button").val(settings_obj.show_loop_button);
      $("#show_shuffle_button").val(settings_obj.show_shuffle_button);
      $("#show_next_and_prev_buttons").val(settings_obj.show_next_and_prev_buttons);
      $("#force_disable_download_button_for_folder").val(settings_obj.force_disable_download_button_for_folder);
      $("#add_mouse_wheel_support").val(settings_obj.add_mouse_wheel_support);
      $("#start_at_random_video").val(settings_obj.start_at_random_video);
      $("#playAfterVideoStop").val(settings_obj.playAfterVideoStop);
      $("#stopAfterLastVideoHasPlayed").val(settings_obj.stopAfterLastVideoHasPlayed);
      $("#showContextmenu").val(settings_obj.showContextmenu);
      $("#showScriptDeveloper").val(settings_obj.showScriptDeveloper);
      $("#playlist_right_width").val(settings_obj.playlist_right_width);
      $("#playlist_bottom_height").val(settings_obj.playlist_bottom_height);
      $("#start_at_video").val(settings_obj.start_at_video);
      $("#max_playlist_items").val(settings_obj.max_playlist_items);
      $("#thumbnail_width").val(settings_obj.thumbnail_width);
      $("#thumbnail_height").val(settings_obj.thumbnail_height);
      $("#space_between_controller_and_playlist").val(settings_obj.space_between_controller_and_playlist);
      $("#space_between_thumbnails").val(settings_obj.space_between_thumbnails);
      $("#scrollbar_offest_width").val(settings_obj.scrollbar_offest_width);
      $("#scollbar_speed_sensitivity").val(settings_obj.scollbar_speed_sensitivity);
      $("#playlist_background_color").spectrum("set", settings_obj.playlist_background_color != "transparent" ? settings_obj.playlist_background_color : null);
      $("#playlist_name_color").spectrum("set", settings_obj.playlist_name_color != "transparent" ? settings_obj.playlist_name_color : null);
      $("#thumbnail_normal_background_color").spectrum("set", settings_obj.thumbnail_normal_background_color != "transparent" ? settings_obj.thumbnail_normal_background_color : null);
      $("#thumbnail_hover_background_color").spectrum("set", settings_obj.thumbnail_hover_background_color != "transparent" ? settings_obj.thumbnail_hover_background_color : null);
      $("#thumbnail_disabled_background_color").spectrum("set", settings_obj.thumbnail_disabled_background_color != "transparent" ? settings_obj.thumbnail_disabled_background_color : null);
      $("#search_input_background_color").spectrum("set", settings_obj.search_input_background_color != "transparent" ? settings_obj.search_input_background_color : null);
      $("#search_input_color").spectrum("set", settings_obj.search_input_color != "transparent" ? settings_obj.search_input_color : null);
      $("#youtube_and_folder_video_title_color").spectrum("set", settings_obj.youtube_and_folder_video_title_color != "transparent" ? settings_obj.youtube_and_folder_video_title_color : null);
      $("#youtube_owner_color").spectrum("set", settings_obj.youtube_owner_color != "transparent" ? settings_obj.youtube_owner_color : null);
      $("#youtube_description_color").spectrum("set", settings_obj.youtube_description_color != "transparent" ? settings_obj.youtube_description_color : null);
      $("#scrubbersToolTipLabelBackgroundColor").spectrum("set", settings_obj.scrubbersToolTipLabelBackgroundColor != "transparent" ? settings_obj.scrubbersToolTipLabelBackgroundColor : null);
      $("#scrubbersToolTipLabelFontColor").spectrum("set", settings_obj.scrubbersToolTipLabelFontColor != "transparent" ? settings_obj.scrubbersToolTipLabelFontColor : null);
      
      // Thumbnails preview settings.
      $("#thumbnails_preview_width").val(settings_obj.thumbnails_preview_width);
      $("#thumbnails_preview_height").val(settings_obj.thumbnails_preview_height);

      // logo settings.
      $("#show_logo").val(settings_obj.show_logo);
      $("#hide_logo_with_controller").val(settings_obj.hide_logo_with_controller);
      $("#logo_position").val(settings_obj.logo_position);
      $("#logo_path").val(settings_obj.logo_path);
      $("#uvp_logo_upload_img").attr("src", settings_obj.logo_path);
      $("#logo_link").val(settings_obj.logo_link);
      $("#logoTarget").val(settings_obj.logoTarget);
      $("#logo_margins").val(settings_obj.logo_margins);
      
      // Embed and info windows settings.
      $("#embed_and_info_window_close_button_margins").val(settings_obj.embed_and_info_window_close_button_margins);
      $("#borderColor").spectrum("set", settings_obj.borderColor != "transparent" ? settings_obj.borderColor : null);
      $("#main_labels_color").spectrum("set", settings_obj.main_labels_color != "transparent" ? settings_obj.main_labels_color : null);
      $("#secondary_labels_color").spectrum("set", settings_obj.secondary_labels_color != "transparent" ? settings_obj.secondary_labels_color : null);
      $("#share_and_embed_text_color").spectrum("set", settings_obj.share_and_embed_text_color != "transparent" ? settings_obj.share_and_embed_text_color : null);
      $("#input_background_color").spectrum("set", settings_obj.input_background_color != "transparent" ? settings_obj.input_background_color : null);
      
      $("#lightBoxBackgroundColor").spectrum("set", settings_obj.lightBoxBackgroundColor != "transparent" ? settings_obj.lightBoxBackgroundColor : null);
      $("#input_color").spectrum("set", settings_obj.input_color != "transparent" ? settings_obj.input_color : null);
      
      // Ads settings.
      $("#open_new_page_at_the_end_of_the_ads").val(settings_obj.open_new_page_at_the_end_of_the_ads);
      $("#play_ads_only_once").val(settings_obj.play_ads_only_once);
      $("#ads_buttons_position").val(settings_obj.ads_buttons_position);
      $("#skip_to_video_text").val(settings_obj.skip_to_video_text);
      $("#skip_to_video_button_text").val(settings_obj.skip_to_video_button_text);
      $("#ads_text_normal_color").spectrum("set", settings_obj.ads_text_normal_color != "transparent" ? settings_obj.ads_text_normal_color : null);
      $("#ads_text_selected_color").spectrum("set", settings_obj.ads_text_selected_color != "transparent" ? settings_obj.ads_text_selected_color : null);
      $("#ads_border_normal_color").spectrum("set", settings_obj.ads_border_normal_color != "transparent" ? settings_obj.ads_border_normal_color : null);
      $("#ads_border_selected_color").spectrum("set", settings_obj.ads_border_selected_color != "transparent" ? settings_obj.ads_border_selected_color : null);
      $("#thumbnails_preview_background_color").spectrum("set", settings_obj.thumbnails_preview_background_color != "transparent" ? settings_obj.thumbnails_preview_background_color : null);    
      $("#thumbnails_preview_border_color").spectrum("set", settings_obj.thumbnails_preview_border_color != "transparent" ? settings_obj.thumbnails_preview_border_color : null);    
      $("#thumbnails_preview_label_background_color").spectrum("set", settings_obj.thumbnails_preview_label_background_color != "transparent" ? settings_obj.thumbnails_preview_label_background_color : null);    
      $("#thumbnails_preview_label_font_color").spectrum("set", settings_obj.thumbnails_preview_label_font_color != "transparent" ? settings_obj.thumbnails_preview_label_font_color : null); 
      $("#contextMenuBackgroundColor").spectrum("set", settings_obj.contextMenuBackgroundColor != "transparent" ? settings_obj.contextMenuBackgroundColor : null);
      $("#contextMenuBorderColor").spectrum("set", settings_obj.contextMenuBorderColor != "transparent" ? settings_obj.contextMenuBorderColor : null);
      $("#contextMenuSpacerColor").spectrum("set", settings_obj.contextMenuSpacerColor != "transparent" ? settings_obj.contextMenuSpacerColor : null);
      $("#contextMenuItemNormalColor").spectrum("set", settings_obj.contextMenuItemNormalColor != "transparent" ? settings_obj.contextMenuItemNormalColor : null);
      $("#contextMenuItemSelectedColor").spectrum("set", settings_obj.contextMenuItemSelectedColor != "transparent" ? settings_obj.contextMenuItemSelectedColor : null);
      $("#contextMenuItemDisabledColor").spectrum("set", settings_obj.contextMenuItemDisabledColor != "transparent" ? settings_obj.contextMenuItemDisabledColor : null);

      // Fingerprint stamp.
      $("#useFingerPrintStamp").val(settings_obj.useFingerPrintStamp);
      $("#frequencyOfFingerPrintStamp").val(parseInt(settings_obj.frequencyOfFingerPrintStamp));
      $("#durationOfFingerPrintStamp").val(parseInt(settings_obj.durationOfFingerPrintStamp));
  }
  

  // Init function.
  function init(){   
  
      $("#startBH").change(function(){
          if(fwduvpVideoStartBehaviour != $("#startBH").val()){
            $("#update_btn").show();
          }else{
            $("#update_btn").hide();
          }
      });
      
            rebuildPresetSelect(fwduvpSetId);
      
      if (fwduvpCurOrderId < DEFAULT_SKINS_NR){
        $("#update_btn").hide();
            $("#del_btn").hide();
      }else{
        $("#update_btn").show();
            $("#del_btn").show();
      }
      
      setSettings();
      
      $("#preset_id").html("ID : " + fwduvpSetId);
      
      $("#tabs").tabs("option", "active", fwduvpCurTabId);
  }  
  init();
    
  $("#skins").change(function(){
      fwduvpSetId = parseInt($("#skins").val());
      
      $.each(fwduvpSettingsAr, function(i, el){
      if (fwduvpSetId == el.id){
        fwduvpCurOrderId = i;
      }
  });    
  setSettings();
      
  if(fwduvpCurOrderId < DEFAULT_SKINS_NR){
      $("#update_btn").hide();
      $("#del_btn").hide();
  }else{
      $("#update_btn").show();
      $("#del_btn").show();
  }
      
  allFields.removeClass("ui-state-error");
  $("#tips").text("All form fields are required.");
  
  $("#preset_id").html("ID : " + fwduvpSetId);});
  

  // Update settings.  
  function updateSettings() {

      // Main settings.
      cur_settings_obj.id = fwduvpSetId;
      cur_settings_obj.mainBackgroundImagePath = $("#uvp_mainBackgroundImagePath").val().replace(/"/g, "'");
      cur_settings_obj.name = $("#name").val().replace(/"/g, "'");
      cur_settings_obj.woo_commerce_display_type = $("#woo_commerce_display_type").val();
      cur_settings_obj.display_type = $("#display_type").val();
      cur_settings_obj.initializeOnlyWhenVisible = $("#initializeOnlyWhenVisible").val();
      cur_settings_obj.stickyOnScroll = $("#stickyOnScroll").val();
      cur_settings_obj.stickyOnScrollShowOpener = $("#stickyOnScrollShowOpener").val();
      cur_settings_obj.stickyOnScrollWidth = parseInt($("#stickyOnScrollWidth").val());
      cur_settings_obj.stickyOnScrollHeight = parseInt($("#stickyOnScrollHeight").val());
      cur_settings_obj.fill_entire_video_screen = $("#fill_entire_video_screen").val();
      cur_settings_obj.fillEntireposterScreen = $("#fillEntireposterScreen").val();
      cur_settings_obj.goFullScreenOnButtonPlay = $("#goFullScreenOnButtonPlay").val();
      cur_settings_obj.subtitles_off_label = $("#subtitles_off_label").val().replace(/"/g, "'");
      cur_settings_obj.playsinline = $("#playsinline").val();
      cur_settings_obj.useResumeOnPlay = $("#useResumeOnPlay").val();
      cur_settings_obj.use_HEX_colors_for_skin = $("#use_HEX_colors_for_skin").val();
      cur_settings_obj.showErrorInfo = $("#showErrorInfo").val();
      cur_settings_obj.useAToB = $("#useAToB").val();
      cur_settings_obj.skin_path = $("#skin_path").val();
      cur_settings_obj.use_deeplinking = $("#use_deeplinking").val();
      cur_settings_obj.add_keyboard_support = $("#add_keyboard_support").val();
      cur_settings_obj.auto_scale = $("#auto_scale").val();
      cur_settings_obj.show_buttons_tooltips = $("#show_buttons_tooltips").val();
      cur_settings_obj.stop_video_when_play_complete = $("#stop_video_when_play_complete").val();
      cur_settings_obj.autoplay = $("#autoplay").val();
      cur_settings_obj.autoPlayText = $("#autoPlayText").val().replace(/"/g, "'");
      cur_settings_obj.loop = $("#loop").val();
      cur_settings_obj.useVectorIcons = $("#useVectorIcons").val();
      cur_settings_obj.googleAnalyticsMeasurementId = $("#googleAnalyticsMeasurementId").val().replace(/"/g, "'");
      cur_settings_obj.shuffle = $("#shuffle").val();
      cur_settings_obj.max_width = parseInt($("#max_width").val());
      cur_settings_obj.max_height = parseInt($("#max_height").val());
      cur_settings_obj.volume = parseFloat($("#volume").val());
      cur_settings_obj.rewindTime = parseInt($("#rewindTime").val());
      cur_settings_obj.show_popup_ads_close_button = $("#show_popup_ads_close_button").val();
      cur_settings_obj.loggedInMessage = $("#loggedInMessage").val().replace(/"/g, "'");
      cur_settings_obj.playIfLoggedIn = $("#playIfLoggedIn").val();
      cur_settings_obj.bg_color = $("#bg_color").spectrum("get") ? $("#bg_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.video_bg_color = $("#video_bg_color").spectrum("get") ? $("#video_bg_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.poster_bg_color = $("#poster_bg_color").spectrum("get") ? $("#poster_bg_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.buttons_tooltip_hide_delay = parseFloat($("#buttons_tooltip_hide_delay").val());
      cur_settings_obj.buttons_tooltip_font_color = $("#buttons_tooltip_font_color").spectrum("get") ? $("#buttons_tooltip_font_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.main_selector_background_selected_color = $("#main_selector_background_selected_color").spectrum("get") ? $("#main_selector_background_selected_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.atbTimeTextColorNormal = $("#atbTimeTextColorNormal").spectrum("get") ? $("#atbTimeTextColorNormal").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.atbTimeTextColorSelected = $("#atbTimeTextColorSelected").spectrum("get") ? $("#atbTimeTextColorSelected").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.atbButtonTextNormalColor = $("#atbButtonTextNormalColor").spectrum("get") ? $("#atbButtonTextNormalColor").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.atbButtonTextSelectedColor = $("#atbButtonTextSelectedColor").spectrum("get") ? $("#atbButtonTextSelectedColor").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.atbButtonBackgroundNormalColor = $("#atbButtonBackgroundNormalColor").spectrum("get") ? $("#atbButtonBackgroundNormalColor").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.atbButtonBackgroundSelectedColor = $("#atbButtonBackgroundSelectedColor").spectrum("get") ? $("#atbButtonBackgroundSelectedColor").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.main_selector_text_normal_color = $("#main_selector_text_normal_color").spectrum("get") ? $("#main_selector_text_normal_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.main_selector_text_selected_color = $("#main_selector_text_selected_color").spectrum("get") ? $("#main_selector_text_selected_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.main_button_background_normal_color = $("#main_button_background_normal_color").spectrum("get") ? $("#main_button_background_normal_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.main_button_background_selected_color = $("#main_button_background_selected_color").spectrum("get") ? $("#main_button_background_selected_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.main_button_text_normal_color = $("#main_button_text_normal_color").spectrum("get") ? $("#main_button_text_normal_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.main_button_text_selected_color = $("#main_button_text_selected_color").spectrum("get") ? $("#main_button_text_selected_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.audioVisualizerLinesColor = $("#audioVisualizerLinesColor").spectrum("get") ? $("#audioVisualizerLinesColor").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.audioVisualizerCircleColor = $("#audioVisualizerCircleColor").spectrum("get") ? $("#audioVisualizerCircleColor").spectrum("get").toHexString() : "transparent";
      
      // Controller settings.
      cur_settings_obj.show_controller_when_video_is_stopped = $("#show_controller_when_video_is_stopped").val();
      cur_settings_obj.showController = $("#showController").val();
      cur_settings_obj.show_next_and_prev_buttons_in_controller = $("#show_next_and_prev_buttons_in_controller").val();
      cur_settings_obj.show_volume_button = $("#show_volume_button").val();
      cur_settings_obj.show_time = $("#show_time").val();
      cur_settings_obj.show_youtube_quality_button = $("#show_youtube_quality_button").val();
      cur_settings_obj.showPlaybackRateButton = $("#showPlaybackRateButton").val();
      cur_settings_obj.defaultPlaybackRate = $("#defaultPlaybackRate").val();
      cur_settings_obj.showRewindButton = $("#showRewindButton").val();
      cur_settings_obj.privateVideoPassword = $("#privateVideoPassword").val().replace(/"/g, "'");
      cur_settings_obj.showPreloader = $("#showPreloader").val();
      cur_settings_obj.show_info_button = $("#show_info_button").val();
      cur_settings_obj.show_download_button = $("#show_download_button").val();
      cur_settings_obj.show_share_button = $("#show_share_button").val();
      cur_settings_obj.show_embed_button = $("#show_embed_button").val();
      cur_settings_obj.showChromecastButton = $("#showChromecastButton").val();
      cur_settings_obj.show360DegreeVideoVrButton = $("#show360DegreeVideoVrButton").val();
      cur_settings_obj.showSubtitleButton = $("#showSubtitleButton").val();
      cur_settings_obj.show_fullscreen_button = $("#show_fullscreen_button").val();
      cur_settings_obj.showAudioTracksButton = $("#showAudioTracksButton").val();
      cur_settings_obj.repeat_background = $("#repeat_background").val();
      cur_settings_obj.disable_video_scrubber = $("#disable_video_scrubber").val();
      cur_settings_obj.controller_height = parseInt($("#controller_height").val());
      cur_settings_obj.showMainScrubberToolTipLabel = $("#showMainScrubberToolTipLabel").val();
      cur_settings_obj.showScrubberWhenControllerIsHidden = $("#showScrubberWhenControllerIsHidden").val();
      cur_settings_obj.controller_hide_delay = parseFloat($("#controller_hide_delay").val());
      cur_settings_obj.start_space_between_buttons = parseInt($("#start_space_between_buttons").val());
      cur_settings_obj.space_between_buttons = parseInt($("#space_between_buttons").val());
      cur_settings_obj.scrubbers_offset_width = parseInt($("#scrubbers_offset_width").val());
      cur_settings_obj.main_scrubber_offest_top = parseInt($("#main_scrubber_offest_top").val());
      cur_settings_obj.time_offset_left_width = parseInt($("#time_offset_left_width").val());
      cur_settings_obj.time_offset_right_width = parseInt($("#time_offset_right_width").val());
      cur_settings_obj.time_offset_top = parseInt($("#time_offset_top").val());
      cur_settings_obj.volume_scrubber_height = parseInt($("#volume_scrubber_height").val());
      cur_settings_obj.lightBoxBackgroundOpacity = $("#lightBoxBackgroundOpacity").val();
      cur_settings_obj.closeLightBoxWhenPlayComplete = $("#closeLightBoxWhenPlayComplete").val();
      cur_settings_obj.showYoutubeRelAndInfo = $("#showYoutubeRelAndInfo").val();
      cur_settings_obj.showDefaultControllerForVimeo = $("#showDefaultControllerForVimeo").val();
      cur_settings_obj.executeCuepointsOnlyOnce = $("#executeCuepointsOnlyOnce").val();
      cur_settings_obj.aopwTitle = $("#aopwTitle").val().replace(/"/g, "'");
      cur_settings_obj.aopwWidth = parseInt($("#aopwWidth").val());
      cur_settings_obj.aopwHeight = parseInt($("#aopwHeight").val());
      cur_settings_obj.aopwBorderSize = parseInt($("#aopwBorderSize").val());
      cur_settings_obj.aopwTitleColor = $("#aopwTitleColor").spectrum("get") ? $("#aopwTitleColor").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.volume_scrubber_ofset_height = parseInt($("#volume_scrubber_ofset_height").val());
      cur_settings_obj.time_color = $("#time_color").spectrum("get") ? $("#time_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.preloaderColor1 = $("#preloaderColor1").spectrum("get") ? $("#preloaderColor1").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.preloaderColor2 = $("#preloaderColor2").spectrum("get") ? $("#preloaderColor2").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.lightBoxBackgroundColor = $("#lightBoxBackgroundColor").spectrum("get") ? $("#lightBoxBackgroundColor").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.youtube_quality_button_normal_color = $("#youtube_quality_button_normal_color").spectrum("get") ? $("#youtube_quality_button_normal_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.youtube_quality_button_selected_color = $("#youtube_quality_button_selected_color").spectrum("get") ? $("#youtube_quality_button_selected_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.normal_HEX_buttons_color = $("#normal_HEX_buttons_color").spectrum("get") ? $("#normal_HEX_buttons_color").spectrum("get").toHexString() : "transparent";
      
      // Playlists window settings.
      cur_settings_obj.show_playlists_button_and_playlists = $("#show_playlists_button_and_playlists").val();
      cur_settings_obj.use_playlists_select_box = $("#use_playlists_select_box").val();
      cur_settings_obj.showPlaylistsSearchInput = $("#showPlaylistsSearchInput").val();
      cur_settings_obj.show_playlists_by_default = $("#show_playlists_by_default").val();
      cur_settings_obj.thumbnail_selected_type = $("#thumbnail_selected_type").val();
      cur_settings_obj.start_at_playlist = parseInt($("#start_at_playlist").val());
      cur_settings_obj.youtubePlaylistAPI = $("#youtubePlaylistAPI").val();
      cur_settings_obj.buttons_margins = parseInt($("#buttons_margins").val());
      cur_settings_obj.thumbnail_max_width = parseInt($("#thumbnail_max_width").val());
      cur_settings_obj.thumbnail_max_height = parseInt($("#thumbnail_max_height").val());
      cur_settings_obj.horizontal_space_between_thumbnails = parseInt($("#horizontal_space_between_thumbnails").val());
      cur_settings_obj.vertical_space_between_thumbnails = parseInt($("#vertical_space_between_thumbnails").val());
      
      // Playlist settings.
      cur_settings_obj.show_playlist_button_and_playlist = $("#show_playlist_button_and_playlist").val();
      cur_settings_obj.playlist_position = $("#playlist_position").val();
      cur_settings_obj.show_playlist_by_default = $("#show_playlist_by_default").val();
      cur_settings_obj.showPlaylistOnFullScreen = $("#showPlaylistOnFullScreen").val();
      cur_settings_obj.show_playlist_name = $("#show_playlist_name").val();
      cur_settings_obj.showThumbnail = $("#showThumbnail").val();
      cur_settings_obj.showOnlyThumbnail = $("#showOnlyThumbnail").val();
      cur_settings_obj.addScrollOnMouseMove = $("#addScrollOnMouseMove").val();
      cur_settings_obj.randomizePlaylist = $("#randomizePlaylist").val();
      cur_settings_obj.show_search_input = $("#show_search_input").val();
      cur_settings_obj.show_loop_button = $("#show_loop_button").val();
      cur_settings_obj.show_shuffle_button = $("#show_shuffle_button").val();
      cur_settings_obj.show_next_and_prev_buttons = $("#show_next_and_prev_buttons").val();
      cur_settings_obj.force_disable_download_button_for_folder = $("#force_disable_download_button_for_folder").val();
      cur_settings_obj.add_mouse_wheel_support = $("#add_mouse_wheel_support").val();
      cur_settings_obj.start_at_random_video = $("#start_at_random_video").val();
      cur_settings_obj.playAfterVideoStop = $("#playAfterVideoStop").val();
      cur_settings_obj.stopAfterLastVideoHasPlayed = $("#stopAfterLastVideoHasPlayed").val();
      cur_settings_obj.showContextmenu = $("#showContextmenu").val();
      cur_settings_obj.showScriptDeveloper = $("#showScriptDeveloper").val();
      cur_settings_obj.playlist_right_width = parseInt($("#playlist_right_width").val());
      cur_settings_obj.playlist_bottom_height = parseInt($("#playlist_bottom_height").val());
      cur_settings_obj.start_at_video = parseInt($("#start_at_video").val());
      cur_settings_obj.max_playlist_items = parseInt($("#max_playlist_items").val());
      cur_settings_obj.thumbnail_width = parseInt($("#thumbnail_width").val());
      cur_settings_obj.thumbnail_height = parseInt($("#thumbnail_height").val());
      cur_settings_obj.space_between_controller_and_playlist = parseInt($("#space_between_controller_and_playlist").val());
      cur_settings_obj.space_between_thumbnails = parseInt($("#space_between_thumbnails").val());
      cur_settings_obj.scrollbar_offest_width = parseInt($("#scrollbar_offest_width").val());
      cur_settings_obj.scollbar_speed_sensitivity = parseFloat($("#scollbar_speed_sensitivity").val());
      cur_settings_obj.playlist_background_color = $("#playlist_background_color").spectrum("get") ? $("#playlist_background_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.playlist_name_color = $("#playlist_name_color").spectrum("get") ? $("#playlist_name_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.thumbnail_normal_background_color = $("#thumbnail_normal_background_color").spectrum("get") ? $("#thumbnail_normal_background_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.thumbnail_hover_background_color = $("#thumbnail_hover_background_color").spectrum("get") ? $("#thumbnail_hover_background_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.thumbnail_disabled_background_color = $("#thumbnail_disabled_background_color").spectrum("get") ? $("#thumbnail_disabled_background_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.search_input_background_color = $("#search_input_background_color").spectrum("get") ? $("#search_input_background_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.search_input_color = $("#search_input_color").spectrum("get") ? $("#search_input_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.youtube_and_folder_video_title_color = $("#youtube_and_folder_video_title_color").spectrum("get") ? $("#youtube_and_folder_video_title_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.youtube_owner_color = $("#youtube_owner_color").spectrum("get") ? $("#youtube_owner_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.youtube_description_color = $("#youtube_description_color").spectrum("get") ? $("#youtube_description_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.scrubbersToolTipLabelBackgroundColor = $("#scrubbersToolTipLabelBackgroundColor").spectrum("get") ? $("#scrubbersToolTipLabelBackgroundColor").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.scrubbersToolTipLabelFontColor = $("#scrubbersToolTipLabelFontColor").spectrum("get") ? $("#scrubbersToolTipLabelFontColor").spectrum("get").toHexString() : "transparent";
      
      // Sticky.
      cur_settings_obj.showPlayerByDefault = $("#showPlayerByDefault").val();
      cur_settings_obj.animatePlayer = $("#animatePlayer").val();
      cur_settings_obj.googleAnalyticsMeasurementId = $("#googleAnalyticsMeasurementId").val().replace(/"/g, "'");
      cur_settings_obj.showOpener = $("#showOpener").val();
      cur_settings_obj.showOpenerPlayPauseButton = $("#showOpenerPlayPauseButton").val();
      cur_settings_obj.openerAlignment = $("#openerAlignment").val();
      cur_settings_obj.verticalPosition = $("#verticalPosition").val();
      cur_settings_obj.horizontalPosition = $("#horizontalPosition").val();
      cur_settings_obj.openerEqulizerOffsetTop = parseInt($("#openerEqulizerOffsetTop").val());
      cur_settings_obj.openerEqulizerOffsetLeft = parseInt($("#openerEqulizerOffsetLeft").val());
      cur_settings_obj.offsetX = parseInt($("#offsetX").val());
      cur_settings_obj.offsetY = parseInt($("#offsetY").val());

      // Thumbnails preview settings.
      cur_settings_obj.thumbnails_preview_width = $("#thumbnails_preview_width").val();
      cur_settings_obj.thumbnails_preview_height = $("#thumbnails_preview_height").val();

      // logo settings.
      cur_settings_obj.show_logo = $("#show_logo").val();
      cur_settings_obj.hide_logo_with_controller = $("#hide_logo_with_controller").val();
      cur_settings_obj.logo_position = $("#logo_position").val();
      cur_settings_obj.logo_path = $("#logo_path").val().replace(/"/g, "'");
      cur_settings_obj.logo_link = $("#logo_link").val().replace(/"/g, "'");
      cur_settings_obj.logoTarget = $("#logoTarget").val().replace(/"/g, "'");
      cur_settings_obj.logo_margins = parseInt($("#logo_margins").val());
      
      // Embed and info windows settings.
      cur_settings_obj.embed_and_info_window_close_button_margins = parseInt($("#embed_and_info_window_close_button_margins").val());
      cur_settings_obj.borderColor = $("#borderColor").spectrum("get") ? $("#borderColor").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.main_labels_color = $("#main_labels_color").spectrum("get") ? $("#main_labels_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.secondary_labels_color = $("#secondary_labels_color").spectrum("get") ? $("#secondary_labels_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.share_and_embed_text_color = $("#share_and_embed_text_color").spectrum("get") ? $("#share_and_embed_text_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.input_background_color = $("#input_background_color").spectrum("get") ? $("#input_background_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.input_color = $("#input_color").spectrum("get") ? $("#input_color").spectrum("get").toHexString() : "transparent";
      
      // Ads settings.
      cur_settings_obj.open_new_page_at_the_end_of_the_ads = $("#open_new_page_at_the_end_of_the_ads").val();
      cur_settings_obj.play_ads_only_once = $("#play_ads_only_once").val();
      cur_settings_obj.ads_buttons_position = $("#ads_buttons_position").val();
      cur_settings_obj.skip_to_video_text = $("#skip_to_video_text").val().replace(/"/g, "'");
      cur_settings_obj.skip_to_video_button_text = $("#skip_to_video_button_text").val().replace(/"/g, "'");
      cur_settings_obj.ads_text_normal_color = $("#ads_text_normal_color").spectrum("get") ? $("#ads_text_normal_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.ads_text_selected_color = $("#ads_text_selected_color").spectrum("get") ? $("#ads_text_selected_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.ads_border_normal_color = $("#ads_border_normal_color").spectrum("get") ? $("#ads_border_normal_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.ads_border_selected_color = $("#ads_border_selected_color").spectrum("get") ? $("#ads_border_selected_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.thumbnails_preview_background_color = $("#thumbnails_preview_background_color").spectrum("get") ? $("#thumbnails_preview_background_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.thumbnails_preview_label_background_color = $("#thumbnails_preview_label_background_color").spectrum("get") ? $("#thumbnails_preview_label_background_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.thumbnails_preview_border_color = $("#thumbnails_preview_border_color").spectrum("get") ? $("#thumbnails_preview_border_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.thumbnails_preview_label_font_color = $("#thumbnails_preview_label_font_color").spectrum("get") ? $("#thumbnails_preview_label_font_color").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.contextMenuBackgroundColor = $("#contextMenuBackgroundColor").spectrum("get") ? $("#contextMenuBackgroundColor").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.contextMenuBorderColor = $("#contextMenuBorderColor").spectrum("get") ? $("#contextMenuBorderColor").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.contextMenuSpacerColor = $("#contextMenuSpacerColor").spectrum("get") ? $("#contextMenuSpacerColor").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.contextMenuItemNormalColor = $("#contextMenuItemNormalColor").spectrum("get") ? $("#contextMenuItemNormalColor").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.contextMenuItemSelectedColor = $("#contextMenuItemSelectedColor").spectrum("get") ? $("#contextMenuItemSelectedColor").spectrum("get").toHexString() : "transparent";
      cur_settings_obj.contextMenuItemDisabledColor = $("#contextMenuItemDisabledColor").spectrum("get") ? $("#contextMenuItemDisabledColor").spectrum("get").toHexString() : "transparent";
    
      // Fingerprint stamp.
      cur_settings_obj.useFingerPrintStamp = $("#useFingerPrintStamp").val();
      cur_settings_obj.frequencyOfFingerPrintStamp = parseInt($("#frequencyOfFingerPrintStamp").val());
      cur_settings_obj.durationOfFingerPrintStamp = parseInt($("#durationOfFingerPrintStamp").val());
  }
    

  // Check length.
  function checkLength(el, prop, min, max){
      var silent = arguments.length > 4 ? arguments[4] : false;
      if((el.val().length > max) || (el.val().length < min)){
          el.addClass("ui-state-error");
          if(!silent) updateTips("Length of " + prop + " must be between " + min + " and " + max + ".");
          return false;
      }else{
          el.removeClass("ui-state-error");
          return true;
      }
  }
    

  // Check if integer and length.
  function checkIfIntegerAndLength(el, prop, min, max){
      var silent = arguments.length > 4 ? arguments[4] : false;
      var int_reg_exp = /-?[0-9]+/;
      var str = el.val();
      var res = str.match(int_reg_exp);
      
      if (res && (res[0] == str)){
          if ((el.val().length > max) || (el.val().length < min)){
              el.addClass("ui-state-error");
              if(!silent) updateTips("Length of " + prop + " must be between " + min + " and " + max + ".");
              return false;
          }else{
              el.removeClass("ui-state-error");
              return true;
           }
      }else{
          el.addClass("ui-state-error");
          if(!silent) updateTips("The " + prop + " field value must be an integer.");
          return false;
      }
  }
  

  // Check float an length.
  function checkIfFloatAndLength(el, prop, min, max){
      var silent = arguments.length > 4 ? arguments[4] : false;
      var float_reg_exp = /1\.0|0?\.[0-9]+|[01]/;
      var str = el.val();
      var res = str.match(float_reg_exp);
      
      if (res && (res[0] == str)){
          if ((el.val().length > max) || (el.val().length < min)){
              el.addClass("ui-state-error");
              if(!silent) updateTips("Length of " + prop + " must be between " + min + " and " + max + ".");
                
              return false;
          }else{
              el.removeClass("ui-state-error");
              return true;
          }
      }else{
          el.addClass("ui-state-error");
          if(!silent) updateTips("The " + prop + " field value must be a float number.");
          return false;
      }
  }
  

  // Check float an length2.
  function checkIfFloatAndLength2(el, prop, min, max){
      var silent = arguments.length > 4 ? arguments[4] : false;
      var float_reg_exp = /[0-9]*\.?[0-9]+/;
      var str = el.val();
      var res = str.match(float_reg_exp);
      
      if (res && (res[0] == str)){
          if ((el.val().length > max) || (el.val().length < min)){
              el.addClass("ui-state-error");
              if(!silent) updateTips("Length of " + prop + " must be between " + min + " and " + max + ".");
              return false;
          }else{
              el.removeClass("ui-state-error");
              return true;
          }
      }else{
          el.addClass("ui-state-error");
          if(!silent) updateTips("The " + prop + " field value must be a float number.");
          return false;
      }
  }

  function clearTopErrorIfNoInvalidFields(){
      if(allFields.filter(".ui-state-error").length === 0){
          $("#tips").removeClass("fwd-error ui-state-highlight").text(defaultTipsText || "All form fields are required.");
      }
  }

  function validateSingleField(el){
      var id = el.attr("id");
      if(!id) return true;

      // length checks
      if(id === "name") return checkLength(el, "name", 1, 64, true);
      if(id === "logo_path") return checkLength(el, "logo path", 0, 256, true);
      if(id === "logo_link") return checkLength(el, "logo link", 0, 256, true);
      if(id === "skip_to_video_text") return checkLength(el, "skip to video text", 0, 256, true);
      if(id === "skip_to_video_button_text") return checkLength(el, "skip to video button text", 0, 256, true);
      if(id === "aopwTitle") return checkLength(el, "title", 0, 256, true);

      // integer checks
      var integerRules = {
          max_width:["maximum width",1,8], max_height:["maximum height",1,8], stickyOnScrollWidth:["sticky on scroll player maximum width:",1,8], stickyOnScrollHeight:["sticky on scroll player maximum height:",1,8],
          rewindTime:["rewind time",1,3], controller_height:["controller height",1,8], start_space_between_buttons:["start space between buttons",1,8], space_between_buttons:["space between buttons",1,8],
          scrubbers_offset_width:["scrubbers offset width",1,8], main_scrubber_offest_top:["main scrubber offset top",1,8], time_offset_left_width:["time offset left width",1,8], time_offset_right_width:["time offset right width",1,8],
          time_offset_top:["time offset top",1,8], volume_scrubber_height:["volume scrubber height",1,8], volume_scrubber_ofset_height:["volume scrubber offset height",1,8],
          start_at_playlist:["start at playlist",1,8], buttons_margins:["buttons margins",1,8], thumbnail_max_width:["thumbnail maximum width",1,8], thumbnail_max_height:["thumbnail maximum height",1,8],
          horizontal_space_between_thumbnails:["horizontal space between thumbnails",1,8], vertical_space_between_thumbnails:["vertical space between thumbnails",1,8],
          playlist_right_width:["playlist right width",1,8], playlist_bottom_height:["playlist bottom height",1,8], start_at_video:["start at video",1,8], max_playlist_items:["maximum playlist items",1,8],
          thumbnail_width:["thumbnail_width",1,8], thumbnail_height:["thumbnail height",1,8], space_between_controller_and_playlist:["space between controller and playlist",1,8], space_between_thumbnails:["space between thumbnails",1,8],
          scrollbar_offest_width:["scrollbar offset width",1,8], logo_margins:["logo margins",1,8], embed_and_info_window_close_button_margins:["embed and info window close button margins",1,8],
          aopwWidth:["maximum width",1,10], aopwHeight:["maximum height",1,10], aopwBorderSize:["advertisement border size",1,10],
          openerEqulizerOffsetTop:["equalizer offset top",1,10], openerEqulizerOffsetLeft:["equalizer offset left",1,10], offsetX:["offset X",1,10], offsetY:["offset Y",1,10],
          thumbnails_preview_width:["thumbnail width",1,3], thumbnails_preview_height:["thumbnail height",1,3],
          frequencyOfFingerPrintStamp:["fingerprint stamp frequency:",1,8], durationOfFingerPrintStamp:["Fingerprint stamp duration",1,8]
      };
      if(integerRules[id]) return checkIfIntegerAndLength(el, integerRules[id][0], integerRules[id][1], integerRules[id][2], true);

      // float checks
      var floatRules = {
          volume:["volume",1,8], scollbar_speed_sensitivity:["scrollbar speed sensitivity",1,8], lightBoxBackgroundOpacity:["background opacity",1,3]
      };
      if(floatRules[id]) return checkIfFloatAndLength(el, floatRules[id][0], floatRules[id][1], floatRules[id][2], true);

      // float2 checks
      var float2Rules = {
          buttons_tooltip_hide_delay:["buttons tooltip hide delay",1,8], controller_hide_delay:["controller hide delay",1,8]
      };
      if(float2Rules[id]) return checkIfFloatAndLength2(el, float2Rules[id][0], float2Rules[id][1], float2Rules[id][2], true);

      return true;
  }

  $(document).on("input change blur", "#name, #max_width, #max_height, #stickyOnScrollWidth, #stickyOnScrollHeight, #volume, #rewindTime, #buttons_tooltip_hide_delay, #controller_height, #controller_hide_delay, #start_space_between_buttons, #space_between_buttons, #scrubbers_offset_width, #main_scrubber_offest_top, #time_offset_left_width, #time_offset_right_width, #time_offset_top, #volume_scrubber_height, #volume_scrubber_ofset_height, #start_at_playlist, #buttons_margins, #thumbnail_max_width, #thumbnail_max_height, #horizontal_space_between_thumbnails, #vertical_space_between_thumbnails, #playlist_right_width, #playlist_bottom_height, #start_at_video, #max_playlist_items, #thumbnail_width, #thumbnail_height, #space_between_controller_and_playlist, #space_between_thumbnails, #scrollbar_offest_width, #scollbar_speed_sensitivity, #logo_path, #logo_link, #logo_margins, #embed_and_info_window_close_button_margins, #skip_to_video_text, #skip_to_video_button_text, #aopwTitle, #aopwWidth, #aopwHeight, #aopwBorderSize, #openerEqulizerOffsetTop, #openerEqulizerOffsetLeft, #offsetX, #offsetY, #lightBoxBackgroundOpacity, #thumbnails_preview_width, #thumbnails_preview_height, #frequencyOfFingerPrintStamp, #durationOfFingerPrintStamp", function(){
      var el = $(this);
      var valid = validateSingleField(el);
      if(valid){
          el.removeClass("ui-state-error");
          clearTopErrorIfNoInvalidFields();
      }
  });


  // Update tips.
  function updateTips(txt){
      $("#tips").text(txt).addClass("ui-state-highlight");
          setTimeout(function(){
            $("#tips").removeClass("ui-state-highlight", 1500);
          }, 500);  
      $("#tips").addClass("fwd-error");
  }


  // Add nputs for validation.
  var allFields = $([]).add($("#name")).add($("#max_width")).add($("#max_height")).add($("#volume")).add($('#rewindTime')).add($("#buttons_tooltip_hide_delay")).add($("#controller_height")).add($("#controller_hide_delay"))
              .add($("#start_space_between_buttons")).add($("#space_between_buttons")).add($("#scrubbers_offset_width")).add($("#main_scrubber_offest_top")).add($("#time_offset_left_width")).add($("#time_offset_right_width"))
              .add($("#time_offset_top")).add($("#volume_scrubber_height")).add($("#volume_scrubber_ofset_height")).add($("#start_at_playlist")).add($("#buttons_margins")).add($("#thumbnail_max_width")).add($("#thumbnail_max_height"))
              .add($("#horizontal_space_between_thumbnails")).add($("#vertical_space_between_thumbnails")).add($("#playlist_right_width")).add($("#playlist_bottom_height")).add($("#start_at_video")).add($("#max_playlist_items"))
              .add($("#thumbnail_width")).add($("#thumbnail_height")).add($("#space_between_controller_and_playlist")).add($("#space_between_thumbnails")).add($("#scrollbar_offest_width")).add($("#scollbar_speed_sensitivity"))
              .add($("#logo_path")).add($("#logo_link")).add($("#logo_margins")).add($("#embed_and_info_window_close_button_margins")).add($("#skip_to_video_text")).add($("#skip_to_video_button_text")).add($("#aopwTitle")).add($("#aopwWidth")).add($("#aopwHeight")).add($("#aopwBorderSize")).add($("#openerEqulizerOffsetTop")).add($("#openerEqulizerOffsetLeft")).add($("#offsetX")).add($("#offsetY")).add($("#lightBoxBackgroundOpacity")).add($("#thumbnails_preview_width")).add($("#thumbnails_preview_height")).add($("#frequencyOfFingerPrintStamp")).add($("#durationOfFingerPrintStamp"));
  var fValid = false;
  

  // Validate fields.
  function validateFields(){
      fValid = true;
      allFields.removeClass("ui-state-error");

      // Main settings.
      fValid = fValid && checkLength($("#name"), "name", 1, 64);
      fValid = fValid && checkIfIntegerAndLength($("#max_width"), "maximum width", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#max_height"), "maximum height", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#stickyOnScrollWidth"), "sticky on scroll player maximum width:", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#stickyOnScrollHeight"), "sticky on scroll player maximum height:", 1, 8);
      fValid = fValid && checkIfFloatAndLength($("#volume"), "volume", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#rewindTime"), "rewind time", 1, 3);
      fValid = fValid && checkIfFloatAndLength2($("#buttons_tooltip_hide_delay"), "buttons tooltip hide delay", 1, 8);
      
      if (!fValid){
        $("#tabs").tabs("option", "active", 0);
        window.scrollTo(0,0); 
        return false;
      }
  
      // Controller settings.
      fValid = fValid && checkIfIntegerAndLength($("#controller_height"), "controller height", 1, 8);
      fValid = fValid && checkIfFloatAndLength2($("#controller_hide_delay"), "controller hide delay", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#start_space_between_buttons"), "start space between buttons", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#space_between_buttons"), "space between buttons", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#scrubbers_offset_width"), "scrubbers offset width", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#main_scrubber_offest_top"), "main scrubber offset top", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#time_offset_left_width"), "time offset left width", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#time_offset_right_width"), "time offset right width", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#time_offset_top"), "time offset top", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#volume_scrubber_height"), "volume scrubber height", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#volume_scrubber_ofset_height"), "volume scrubber offset height", 1, 8);
      
      if (!fValid){
          $("#tabs").tabs("option", "active", 1);
          window.scrollTo(0,0);
          return false;
      }
      
      // Playlists window settings settings.
      fValid = fValid && checkIfIntegerAndLength($("#start_at_playlist"), "start at playlist", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#buttons_margins"), "buttons margins", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#thumbnail_max_width"), "thumbnail maximum width", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#thumbnail_max_height"), "thumbnail maximum height", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#horizontal_space_between_thumbnails"), "horizontal space between thumbnails", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#vertical_space_between_thumbnails"), "vertical space between thumbnails", 1, 8);
      
      if (!fValid){
          $("#tabs").tabs("option", "active", 2);
          window.scrollTo(0,0);
          return false;
      }
      
      // Playlist settings.
      fValid = fValid && checkIfIntegerAndLength($("#playlist_right_width"), "playlist right width", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#playlist_bottom_height"), "playlist bottom height", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#start_at_video"), "start at video", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#max_playlist_items"), "maximum playlist items", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#thumbnail_width"), "thumbnail_width", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#thumbnail_height"), "thumbnail height", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#space_between_controller_and_playlist"), "space between controller and playlist", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#space_between_thumbnails"), "space between thumbnails", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#scrollbar_offest_width"), "scrollbar offset width", 1, 8);
      fValid = fValid && checkIfFloatAndLength($("#scollbar_speed_sensitivity"), "scrollbar speed sensitivity", 1, 8);
      
      if (!fValid){
          $("#tabs").tabs("option", "active", 3);
          window.scrollTo(0,0);
          return false;
      }
    
      // logo settings.
      fValid = fValid && checkLength($("#logo_path"), "logo path", 0, 256);
      fValid = fValid && checkLength($("#logo_link"), "logo link", 0, 256);
      fValid = fValid && checkIfIntegerAndLength($("#logo_margins"), "logo margins", 1, 8);
        
      if (!fValid){
          $("#tabs").tabs("option", "active", 4);
          window.scrollTo(0,0);
          return false;
      }
    
      // Embed and info windows settings.
      fValid = fValid && checkIfIntegerAndLength($("#embed_and_info_window_close_button_margins"), "embed and info window close button margins", 1, 8);
        
      if (!fValid){
          $("#tabs").tabs("option", "active", 5);
          window.scrollTo(0,0);
          return false;
      }
    
      // Ads settings.
      fValid = fValid && checkLength($("#skip_to_video_text"), "skip to video text", 0, 256);
      fValid = fValid && checkLength($("#skip_to_video_button_text"), "skip to video button text", 0, 256);
        
      if (!fValid){
          $("#tabs").tabs("option", "active", 6);
          window.scrollTo(0,0);
          return false;
      }
    
      // Popup on pause settings.
      fValid = fValid && checkLength($("#aopwTitle"), "title", 0, 256);
      fValid = fValid && checkIfIntegerAndLength($("#aopwWidth"), "maximum width", 1, 10);
      fValid = fValid && checkIfIntegerAndLength($("#aopwHeight"), "maximum height", 1, 10);
      fValid = fValid && checkIfIntegerAndLength($("#aopwBorderSize"), "advertisement border size", 1, 10);
      if (!fValid){
          $("#tabs").tabs("option", "active", 7);
          window.scrollTo(0,0);
          return false;
      }
  
      // Sticky.
      fValid = fValid && checkIfIntegerAndLength($("#openerEqulizerOffsetTop"), "equalizer offset top", 1, 10);
      fValid = fValid && checkIfIntegerAndLength($("#openerEqulizerOffsetLeft"), "equalizer offset left", 1, 10);
      fValid = fValid && checkIfIntegerAndLength($("#offsetX"), "offset X", 1, 10);
      fValid = fValid && checkIfIntegerAndLength($("#offsetY"), "offset Y", 1, 10);
    
      if (!fValid){
          $("#tabs").tabs("option", "active", 8);
          window.scrollTo(0,0);
          return false;
      }
    
      // lightbox.
      fValid = fValid && checkIfFloatAndLength($("#lightBoxBackgroundOpacity"), "background opacity", 1, 3);
      if (!fValid){
            $("#tabs").tabs("option", "active", 9);
            window.scrollTo(0,0);
            return false;
        }

      // Thubnails preview.
      fValid = fValid && checkIfIntegerAndLength($("#thumbnails_preview_width"), "thumbnail width", 1, 3);
      fValid = fValid && checkIfIntegerAndLength($("#thumbnails_preview_height"), "thumbnail height", 1, 3);
      if (!fValid){
          $("#tabs").tabs("option", "active", 11);
          window.scrollTo(0,0);
          return false;
      }

      // Fingerprint stamp.
      fValid = fValid && checkIfIntegerAndLength($("#frequencyOfFingerPrintStamp"), "fingerprint stamp frequency:", 1, 8);
      fValid = fValid && checkIfIntegerAndLength($("#durationOfFingerPrintStamp"), "Fingerprint stamp duration", 1, 8);
      if (!fValid){
          $("#tabs").tabs("option", "active", 13);
          window.scrollTo(0,0);
          return false;
      }
  }
    
  $("#add_btn").click(function(e){
      e.preventDefault();

      if($("#name").val() == fwduvpSettingsAr[fwduvpCurOrderId]["name"]){
          updateTips("Please make sure that the preset name is unique");
          $("#name").addClass("ui-state-error");
          $("#tabs").tabs("option", "active", 0);
          window.scrollTo(0,0);
          return false;
      };
    
      validateFields();
      if (fValid) {
            cur_settings_obj = {};
            
            fwduvpSetId = $("#skins option").length;
            fwduvpCurOrderId = $("#skins option").length;
            
            var idsAr = [];
            
            if (fwduvpSetId > DEFAULT_SKINS_NR){
              $.each(fwduvpSettingsAr, function(i, el){
              idsAr.push(el.id);
            });
              
              for (var i=DEFAULT_SKINS_NR; i<fwduvpSettingsAr.length; i++){
                if ($.inArray(i, idsAr) == -1){
                  fwduvpSetId = i;
                  break;
                }
            }
      }
        
      updateSettings();
    
      fwduvpSettingsAr.push(cur_settings_obj);
      var data_obj = {
          action: "add",
          set_id: fwduvpSetId,
          set_order_id: fwduvpCurOrderId,
          fwduvpCurTabId: $("#tabs").tabs("option", "active"),
    
       fwduvpVideoStartBehaviour:$("#startBH").val(),
          settings_ar: fwduvpSettingsAr
       };
    
        $("#settings_data").val(JSON.stringify(data_obj));

        savePresetWithAjax(data_obj, function(){
            $("#skins").append("<option value='" + fwduvpSetId + "'>" + cur_settings_obj.name + "</option>");
            $("#skins").val(fwduvpSetId);
            $("#preset_id").html("ID : " + fwduvpSetId);
            updateButtonsByPresetType();
            allFields.removeClass("ui-state-error");
            $("#tips").text("All form fields are required.");
        });

        return false;
      }else{
          return false;
      }
  });

  // logo image.
  var cov_logo_custom_uploader;
    
  $("#uvp_logo_image_btn").click(function(e){
      e.preventDefault();

      // If the uploader object has already been created, reopen the dialog.
      if (cov_logo_custom_uploader){
        cov_logo_custom_uploader.open();
          return;
      }
      
      // Extend the wp.media object.
      cov_logo_custom_uploader = wp.media.frames.file_frame = wp.media({
          title: "Choose Image",
          button:{
              text: "Add Image"
          },
          library:{
            type: "image"
          },
          multiple: false
      });

      // When a file is selected, grab the URL and set it as the text field's value.
      cov_logo_custom_uploader.on("select", function(){
          var attachment = cov_logo_custom_uploader.state().get("selection").first().toJSON();
          
          $("#logo_path").val(attachment.url);
          $("#uvp_logo_upload_img").attr("src", attachment.url);
      });

      // Open the uploader dialog.
      cov_logo_custom_uploader.open();
  });

  // Bg image custom uploader.
  var cov_bg_custom_uploader;
    
    $("#uvp_bg_image_btn").click(function(e){
        e.preventDefault();
 
        // If the uploader object has already been created, reopen the dialog.
        if (cov_bg_custom_uploader){
          cov_bg_custom_uploader.open();
            return;
        }
        
        // Extend the wp.media object.
        cov_bg_custom_uploader = wp.media.frames.file_frame = wp.media({
            title: "Choose Image",
            button:{
                text: "Add Image"
            },
            library:{
              type: "image"
            },
            multiple: false
        });
 
        // When a file is selected, grab the URL and set it as the text field's value.
        cov_bg_custom_uploader.on("select", function(){
            var attachment = cov_bg_custom_uploader.state().get("selection").first().toJSON();
            
            $("#uvp_mainBackgroundImagePath").val(attachment.url);
            $("#uvp_bg_upload_img").attr("src", attachment.url);
        });
 
        // Open the uploader dialog.
        cov_bg_custom_uploader.open();
    });
    

    // Update settings.
    $("#update_btn").click(function(e){
        e.preventDefault();

        validateFields();
        if(fValid){
            cur_settings_obj = {};
            updateSettings();
        
            fwduvpSettingsAr[fwduvpCurOrderId] = cur_settings_obj;
        
          var data_obj = {
              action: "save",
              set_id: fwduvpSetId,
              set_order_id: fwduvpCurOrderId,
              fwduvpCurTabId: $("#tabs").tabs("option", "active"),
              fwduvpVideoStartBehaviour:$("#startBH").val(),
              settings_ar: fwduvpSettingsAr
          };
        
          $("#settings_data").val(JSON.stringify(data_obj));

          savePresetWithAjax(data_obj, function(){
              $("#skins option:selected").text(cur_settings_obj.name);
              updateButtonsByPresetType();
          });

          return false;
      }else{
          return false;
      }
  });
    
  // Delete settings.
  $("#del_btn").click(function(e){
      e.preventDefault();

      if(fwduvpCurOrderId < DEFAULT_SKINS_NR){
          return false;
      }

    var deletedSetId = fwduvpSetId;
    fwduvpSettingsAr.splice(fwduvpCurOrderId, 1);
      
      var data_obj ={
          action: "del",
          fwduvpVideoStartBehaviour:$("#startBH").val(),
          settings_ar: fwduvpSettingsAr
      };
      
      $("#settings_data").val(JSON.stringify(data_obj));

      savePresetWithAjax(data_obj, function(){
          if(fwduvpSettingsAr.length > 0){
              rebuildPresetSelect(fwduvpSettingsAr[0].id);
              setSettings();
          }else{
              rebuildPresetSelect(deletedSetId);
          }

          updateButtonsByPresetType();
          $("#preset_id").html("ID : " + fwduvpSetId);
      });

      return false;
    });

    // Utils.
    function escapeHtml(str) {
        var map = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            "\"": "&quot;",
            "'": "&#039;"
        };
        str = str.replace(/'/g, "\"");
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function unescapeHtml(str) {
        var map = {
            "&amp;": "&",
            "&lt;": "<",
            "&gt;": ">",
            "&quot;": "\"",
            "&#039;": "'"
        };
        //str = str.replace(//g, "'");
        return str.replace(/(&amp;|&lt;|&gt;|&quot;|&#039;)/g, function(m) { return map[m]; });
    }

    function removeFirstAndLastChar(str){
        str = str.substring(1);
        str = str.slice(0, -1);
        return str;
    }
});


