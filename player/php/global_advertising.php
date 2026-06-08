<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div id="add-global-ads-dialog" title="<?php esc_html_e('Add new pre-roll,mid-roll,post-roll advertisement', 'fwd-ultimate-video-player'); ?>">
	<p id="add_global_ads_tips"><?php esc_html_e('The source field is required.', 'fwd-ultimate-video-player'); ?></p>
	<fieldset>
	<table class="dialog ads-dialog">
		<tr>
			<td><label><?php esc_html_e('Advertisement label:', 'fwd-ultimate-video-player'); ?></label></td>
			<td><input id="global_ads_label" type="text" class="text ui-widget-content ui-corner-all"></td>
		</tr>
		<tr>
			<td><label><?php esc_html_e('Video or image source:', 'fwd-ultimate-video-player'); ?></label></td>
			<td class="has-button">
				<input id="global_ads_source" type="text" class="text ui-widget-content ui-corner-all">
				<button id="global_ads_source_button" class="ads_source_button"><?php esc_html_e('Add from media library', 'fwd-ultimate-video-player'); ?></button>
				<img class="img-tooltip" src="<?php echo esc_url($tootlTipImgSrc); ?>" title="<?php esc_html_e('This feature support mp4 video files from the media library or external path, youtube videos or images (jpg, jpeg, png).', 'fwd-ultimate-video-player'); ?>">
			</td>
		</tr>
		<tr class="empty-after-button"></tr>
		<tr>
			<td><label>URL:</label></td>
			<td><input type="text" id="global_ads_url" class="text ui-widget-content ui-corner-all"></td>
		</tr>
		<tr>
			<td><label><?php esc_html_e('Target:', 'fwd-ultimate-video-player'); ?></label></td>
			<td>
				<select id="global_ads_target" class="ui-corner-all">
					<option value="_blank">_blank</option>
					<option value="_self">_self</option>
				</select>
			</td>
		</tr>
		<tr>
			<td><label><?php esc_html_e('Start time:', 'fwd-ultimate-video-player'); ?></label></td>
			<td>
				<input type="text" id="global_ads_start_time" class="text ui-widget-content ui-corner-all" value="00:00:00">
				<img class="img-tooltip" src="<?php echo esc_url($tootlTipImgSrc); ?>" title="<?php esc_html_e('The time at which the advertisement will start playing, the format is hours:minutes:seconds, for example 01:20:20.', 'fwd-ultimate-video-player'); ?>">
			</td>
		</tr>
		<tr>
			<td><label><?php esc_html_e('Time to hold add:', 'fwd-ultimate-video-player'); ?></label></td>
			<td>
				<input type="text" id="global_time_to_hold_ad" class="text ui-widget-content ui-corner-all" value="4">
				<img class="img-tooltip" src="<?php echo esc_url($tootlTipImgSrc); ?>" title="<?php esc_html_e('The duration in seconds until the skip button will appear, for example set 10 to show after 10 seconds.', 'fwd-ultimate-video-player'); ?>">
			</td>
		</tr>
		<tr>
			<td><label><?php esc_html_e('Add duration:', 'fwd-ultimate-video-player'); ?></label></td>
			<td>
				<input type="text" id="global_add_duration" class="text ui-widget-content ui-corner-all" value="00:00:10">
				<img class="img-tooltip" src="<?php echo esc_url($tootlTipImgSrc); ?>" title="<?php esc_html_e('The ad duration in hh:mm:ss format if an image is used, for example 00:00:20.', 'fwd-ultimate-video-player'); ?>">
			</td>
		</tr>
	</table>
	</fieldset>
</div>

<div id="edit-global-ads-dialog" title="<?php esc_html_e('Edit pre-roll,mid-roll,post-roll advertisement', 'fwd-ultimate-video-player'); ?>">
	<p id="edit_global_ads_tips"><?php esc_html_e('The source field is required.', 'fwd-ultimate-video-player'); ?></p>
	<fieldset>
	<table class="dialog ads-dialog">
		<tr>
			<td><label><?php esc_html_e('Advertisement label:', 'fwd-ultimate-video-player'); ?></label></td>
			<td><input id="global_ads_label_edit" type="text" class="text ui-widget-content ui-corner-all"></td>
		</tr>
		<tr>
			<td><label><?php esc_html_e('Video or image source:', 'fwd-ultimate-video-player'); ?></label></td>
			<td class="has-button">
				<input id="global_ads_source_edit" type="text" class="text ui-widget-content ui-corner-all">
				<button id="global_ads_source_button_edit" class="ads_source_button"><?php esc_html_e('Add from media library', 'fwd-ultimate-video-player'); ?></button>
				<img class="img-tooltip" src="<?php echo esc_url($tootlTipImgSrc); ?>" title="<?php esc_html_e('This feature support mp4 video files from the media library or external path, youtube videos or images (jpg, jpeg, png).', 'fwd-ultimate-video-player'); ?>">
			</td>
		</tr>
		<tr class="empty-after-button"></tr>
		<tr>
			<td><label>URL:</label></td>
			<td><input type="text" id="global_ads_url_edit" class="text ui-widget-content ui-corner-all"></td>
		</tr>
		<tr>
			<td><label><?php esc_html_e('Target:', 'fwd-ultimate-video-player'); ?></label></td>
			<td>
				<select id="global_ads_target_edit" class="ui-corner-all">
					<option value="_blank">_blank</option>
					<option value="_self">_self</option>
				</select>
			</td>
		</tr>
		<tr>
			<td><label><?php esc_html_e('Start time:', 'fwd-ultimate-video-player'); ?></label></td>
			<td>
				<input type="text" id="global_ads_start_time_edit" class="text ui-widget-content ui-corner-all">
				<img class="img-tooltip" src="<?php echo esc_url($tootlTipImgSrc); ?>" title="<?php esc_html_e('The time at which the advertisement will start playing, the format is hours:minutes:seconds, for example 01:20:20.', 'fwd-ultimate-video-player'); ?>">
			</td>
		</tr>
		<tr>
			<td><label><?php esc_html_e('Time to hold add:', 'fwd-ultimate-video-player'); ?></label></td>
			<td>
				<input type="text" id="global_time_to_hold_ad_edit" class="text ui-widget-content ui-corner-all">
				<img class="img-tooltip" src="<?php echo esc_url($tootlTipImgSrc); ?>" title="<?php esc_html_e('The duration in seconds until the skip button will appear, for example set 10 to show after 10 seconds.', 'fwd-ultimate-video-player'); ?>">
			</td>
		</tr>
		<tr>
			<td><label><?php esc_html_e('Add duration:', 'fwd-ultimate-video-player'); ?></label></td>
			<td>
				<input type="text" id="global_add_duration_edit" class="text ui-widget-content ui-corner-all">
				<img class="img-tooltip" src="<?php echo esc_url($tootlTipImgSrc); ?>" title="<?php esc_html_e('The ad duration in hh:mm:ss format if an image is used, for example 00:00:20.', 'fwd-ultimate-video-player'); ?>">
			</td>
		</tr>
	</table>
	</fieldset>
</div>

<div id="delete-global-ads-dialog" title="<?php esc_html_e('Delete advertisement', 'fwd-ultimate-video-player'); ?>">
	<fieldset>
		<label><?php esc_html_e('Are you sure you want to delete this advertisement?', 'fwd-ultimate-video-player'); ?></label>
	</fieldset>
</div>

<div id="add-global-popupad-dialog" title="<?php esc_html_e('Add new pop-up advertisement', 'fwd-ultimate-video-player'); ?>">
	<p id="global_popupad_tips"><?php esc_html_e('The source field is required.', 'fwd-ultimate-video-player'); ?></p>
	<fieldset>
		<table class="dialog">
			<tr>
				<td class="popupad-selector"><label><?php esc_html_e('Type:', 'fwd-ultimate-video-player'); ?></label></td>
				<td>
					<select id="global_popupad_type" class="ui-corner-all">
						<option value="image">image</option>
						<option value="adsense">google adsense</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><label><?php esc_html_e('Advertisement label:', 'fwd-ultimate-video-player'); ?></label></td>
				<td><input id="global_popupads_label" type="text" class="text ui-widget-content ui-corner-all"></td>
			</tr>
		</table>

		<table class="dialog global-popupads-dialog">
			<tr>
				<td><label><?php esc_html_e('Image source', 'fwd-ultimate-video-player'); ?></label></td>
				<td class="has-button">
					<input id="global_popupads_source" type="text" class="text ui-widget-content ui-corner-all">
					<button id="global_popupads_source_button" class="popupads_source_button"><?php esc_html_e('Add from media library', 'fwd-ultimate-video-player'); ?></button>
					<img class="img-tooltip" src="<?php echo esc_url($tootlTipImgSrc); ?>" title="<?php esc_html_e('This feature support mp4 video files from the media library or external path, youtube videos or images (jpg, jpeg, png).', 'fwd-ultimate-video-player'); ?>">
				</td>
			</tr>
			<tr class="empty-after-button"></tr>
			<tr>
				<td><label>URL:</label></td>
				<td><input type="text" id="global_popupads_url" class="text ui-widget-content ui-corner-all"></td>
			</tr>
			<tr>
				<td><label><?php esc_html_e('Target:', 'fwd-ultimate-video-player'); ?></label></td>
				<td>
					<select id="global_popupads_target" class="ui-corner-all">
						<option value="_blank">_blank</option>
						<option value="_self">_self</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><label><?php esc_html_e('Start time:', 'fwd-ultimate-video-player'); ?></label></td>
				<td><input type="text" id="global_popupads_start_time" class="text ui-widget-content ui-corner-all" value="00:00:00"></td>
			</tr>
			<tr>
				<td><label><?php esc_html_e('Stop time:', 'fwd-ultimate-video-player'); ?></label></td>
				<td><input type="text" id="global_popupads_stop_time" class="text ui-widget-content ui-corner-all" value="00:00:10"></td>
			</tr>
		</table>

		<table class="dialog global-adsense-dialog">
			<tr>
				<td><label><?php esc_html_e('Google adsense ad client code:', 'fwd-ultimate-video-player'); ?></label></td>
				<td><input type="text" id="global_google_ad_client" class="text ui-widget-content ui-corner-all"></td>
			</tr>
			<tr>
				<td><label><?php esc_html_e('Google adsense ad slot code:', 'fwd-ultimate-video-player'); ?></label></td>
				<td><input type="text" id="global_google_ad_slot" class="text ui-widget-content ui-corner-all"></td>
			</tr>
			<tr>
				<td><label><?php esc_html_e('Google adsense ad width:', 'fwd-ultimate-video-player'); ?></label></td>
				<td><input type="text" id="global_google_ad_width" class="text ui-widget-content ui-corner-all" value="300"></td>
			</tr>
			<tr>
				<td><label><?php esc_html_e('Google adsense ad height:', 'fwd-ultimate-video-player'); ?></label></td>
				<td><input type="text" id="global_google_ad_height" class="text ui-widget-content ui-corner-all" value="250"></td>
			</tr>
			<tr>
				<td><label><?php esc_html_e('Google adsense ad start time:', 'fwd-ultimate-video-player'); ?></label></td>
				<td><input type="text" id="global_google_ad_start_time" class="text ui-widget-content ui-corner-all" value="00:00:00"></td>
			</tr>
			<tr>
				<td><label><?php esc_html_e('Google adsense ad stop time:', 'fwd-ultimate-video-player'); ?></label></td>
				<td><input type="text" id="global_google_ad_stop_time" class="text ui-widget-content ui-corner-all" value="00:00:10"></td>
			</tr>
		</table>
	</fieldset>
</div>

<div id="edit-global-popupad-dialog" title="<?php esc_html_e('Edit pop-up advertisement', 'fwd-ultimate-video-player'); ?>">
	<p id="global_popupad_tips_edit"><?php esc_html_e('The source field is required.', 'fwd-ultimate-video-player'); ?></p>
	<fieldset>
		<table class="dialog">
			<tr>
				<td class="popupad-selector"><label><?php esc_html_e('Type:', 'fwd-ultimate-video-player'); ?></label></td>
				<td>
					<select id="global_popupad_type_edit" class="ui-corner-all">
						<option value="image">image</option>
						<option value="adsense">google adsense</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><label><?php esc_html_e('Advertisement label:', 'fwd-ultimate-video-player'); ?></label></td>
				<td><input id="global_popupads_label_edit" type="text" class="text ui-widget-content ui-corner-all"></td>
			</tr>
		</table>

		<table class="dialog global-popupads-dialog-edit">
			<tr>
				<td><label><?php esc_html_e('Image source', 'fwd-ultimate-video-player'); ?></label></td>
				<td class="has-button">
					<input id="global_popupads_source_edit" type="text" class="text ui-widget-content ui-corner-all">
					<button id="global_popupads_source_button_edit" class="popupads_source_button"><?php esc_html_e('Add from media library', 'fwd-ultimate-video-player'); ?></button>
				</td>
			</tr>
			<tr class="empty-after-button"></tr>
			<tr>
				<td><label>URL:</label></td>
				<td><input type="text" id="global_popupads_url_edit" class="text ui-widget-content ui-corner-all"></td>
			</tr>
			<tr>
				<td><label><?php esc_html_e('Target:', 'fwd-ultimate-video-player'); ?></label></td>
				<td>
					<select id="global_popupads_target_edit" class="ui-corner-all">
						<option value="_blank">_blank</option>
						<option value="_self">_self</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><label><?php esc_html_e('Start time:', 'fwd-ultimate-video-player'); ?></label></td>
				<td><input type="text" id="global_popupads_start_time_edit" class="text ui-widget-content ui-corner-all"></td>
			</tr>
			<tr>
				<td><label><?php esc_html_e('Stop time:', 'fwd-ultimate-video-player'); ?></label></td>
				<td><input type="text" id="global_popupads_stop_time_edit" class="text ui-widget-content ui-corner-all"></td>
			</tr>
		</table>

		<table class="dialog global-adsense-dialog-edit">
			<tr><td><label><?php esc_html_e('Google adsense ad client code:', 'fwd-ultimate-video-player'); ?></label></td><td><input type="text" id="global_google_ad_client_edit" class="text ui-widget-content ui-corner-all"></td></tr>
			<tr><td><label><?php esc_html_e('Google adsense ad slot code:', 'fwd-ultimate-video-player'); ?></label></td><td><input type="text" id="global_google_ad_slot_edit" class="text ui-widget-content ui-corner-all"></td></tr>
			<tr><td><label><?php esc_html_e('Google adsense ad width:', 'fwd-ultimate-video-player'); ?></label></td><td><input type="text" id="global_google_ad_width_edit" class="text ui-widget-content ui-corner-all"></td></tr>
			<tr><td><label><?php esc_html_e('Google adsense ad height:', 'fwd-ultimate-video-player'); ?></label></td><td><input type="text" id="global_google_ad_height_edit" class="text ui-widget-content ui-corner-all"></td></tr>
			<tr><td><label><?php esc_html_e('Google adsense ad start time:', 'fwd-ultimate-video-player'); ?></label></td><td><input type="text" id="global_google_ad_start_time_edit" class="text ui-widget-content ui-corner-all"></td></tr>
			<tr><td><label><?php esc_html_e('Google adsense ad stop time:', 'fwd-ultimate-video-player'); ?></label></td><td><input type="text" id="global_google_ad_stop_time_edit" class="text ui-widget-content ui-corner-all"></td></tr>
		</table>
	</fieldset>
</div>

<div id="delete-global-popupad-dialog" title="<?php esc_html_e('Delete pop-up advertisement', 'fwd-ultimate-video-player'); ?>">
	<fieldset>
		<label><?php esc_html_e('Are you sure you want to delete this pop-up advertisement?', 'fwd-ultimate-video-player'); ?></label>
	</fieldset>
</div>

<div class="wrap fwduvp fwduvp-playlist-manager">

    <div class="main-holder">
	
        <div class="fwd-option">
            <label for="add_global_ads_button"><?php esc_html_e('Advertisement pre-roll,mid-roll,post-roll:', 'fwd-ultimate-video-player'); ?></label>
            <div id="main_global_ads"></div>
            <button id="add_global_ads_button"><?php esc_html_e('Add advertisement', 'fwd-ultimate-video-player'); ?></button>
        </div>

		<div class="fwd-option" style="margin-top:20px;">
			<label for="add_global_popupad_button"><?php esc_html_e('Pop-up image / adsense advertisement:', 'fwd-ultimate-video-player'); ?></label>
			<div id="main_global_popupads"></div>
			<button id="add_global_popupad_button"><?php esc_html_e('Add advertisement', 'fwd-ultimate-video-player'); ?></button>
		</div>

		<div class="fwd-option" style="margin-top:20px;">
			<table id="global_vast_settings_table" class="dialog video-final">
				<tr>
					<td><label><?php esc_html_e('Advertisement on pause:', 'fwd-ultimate-video-player'); ?></label></td>
					<td>
						<input type="text" id="global_pause_ads_source" class="text ui-widget-content ui-corner-all">
						<img class="img-tooltip" src="<?php echo esc_url($tootlTipImgSrc); ?>" title="<?php esc_html_e('Add here the URL / page source of the webpage to be displayed in the advertisement on pause window (ex:http://www.webdesign-flash.ro/iframe.html), if you do not want this window to appear when the video is paused leave this input blank.', 'fwd-ultimate-video-player'); ?>">
					</td>
				</tr>
				<tr>
					<td><label><?php esc_html_e('Vast source:', 'fwd-ultimate-video-player'); ?></label></td>
					<td>
						<input type="text" id="global_vast_xml_url" class="text ui-widget-content ui-corner-all">
						<img class="img-tooltip" src="<?php echo esc_url($tootlTipImgSrc); ?>" title="<?php esc_html_e('The absolute path of a valid VAST, VMAP or Google IMA file. This field is optional.', 'fwd-ultimate-video-player'); ?>">
					</td>
				</tr>
				<tr>
					<td><label><?php esc_html_e('Vast target:', 'fwd-ultimate-video-player'); ?></label></td>
					<td>
						<select id="global_vast_xml_target" class="ui-corner-all">
							<option value="_blank">_blank</option>
							<option value="_self">_self</option>
						</select>
					</td>
				</tr>
				<tr>
					<td><label><?php esc_html_e('Vast start time:', 'fwd-ultimate-video-player'); ?></label></td>
					<td>
						<input type="text" id="global_vast_start_time" class="text ui-widget-content ui-corner-all" value="00:00:00">
						<img class="img-tooltip" src="<?php echo esc_url($tootlTipImgSrc); ?>" title="<?php esc_html_e('The start time for VAST in hh:mm:ss format, for example 00:10:48.', 'fwd-ultimate-video-player'); ?>">
					</td>
				</tr>
			</table>
		</div>
    </div>

    <p>
		<input id="fwd_update_global_ads_btn" type="button" value="<?php esc_attr_e('Update advertisements', 'fwd-ultimate-video-player'); ?>" />
    </p>
</div>
