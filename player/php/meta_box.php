<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="fwduvp-meta-box ui-widget">
	<table>
    	<tr>
			<td>
				<label for="fwduvp_presets_list"><?php esc_html_e('Select preset:', 'fwd-ultimate-video-player'); ?></label>
			</td>
			<td>
				<select id="fwduvp_presets_list" class="ui-widget ui-corner-all"></select>
			</td>
		</tr>
		<tr>
			<td>
				<label for="fwduvp_main_playlists_list"><?php esc_html_e('Select playlist:', 'fwd-ultimate-video-player'); ?></label>
			</td>
			<td>
				<select id="fwduvp_main_playlists_list" class="ui-widget ui-corner-all"></select>
			</td>
		</tr>
		<tr>
			<td>
				<label for="fwduvp_start_at_playlist"><?php esc_html_e('Start at playlist:', 'fwd-ultimate-video-player'); ?></label>
			</td>
			<td>
				<input type="text" id="fwduvp_start_at_playlist" maxlength="3" onkeypress='return event.charCode >= 48 && event.charCode <= 57' class="text ui-widget-content ui-corner-all">
				<img class="img-tooltip" src="<?php echo esc_url($tootlTipImgSrc); ?>" title="<?php esc_html_e('Start at a specific playlist from the selected main playlist, the counting starts from 0, which means that 0 is the fist playlist. Please note that this feature is optional, if left blank UVP will start at the playlist specified in the selected preset.', 'fwd-ultimate-video-player'); ?>">
			</td>
		</tr>
		<tr>
			<td>
				<label for="fwduvp_start_at_video"><?php esc_html_e('Start at video:', 'fwd-ultimate-video-player'); ?></label>
			</td>
			<td>
				<input type="text" id="fwduvp_start_at_video" maxlength="3" onkeypress='return event.charCode >= 48 && event.charCode <= 57' class="text ui-widget-content ui-corner-all">
				<img class="img-tooltip" src="<?php echo esc_url($tootlTipImgSrc); ?>" title="<?php esc_html_e('Start at a specific video from the selected playlist, the counting starts from 0, which means that 0 is the fist video. Please note that this feature is optional, if left blank UVP will start at the video specified in the selected preset.', 'fwd-ultimate-video-player'); ?>">
			</td>
		</tr>
	</table>
	<input type="text" id="fwduvp_shortocde" class="text ui-widget-content ui-corner-all">
	
	<div id="fwduvp_div" class="fwd-updated"><p id="fwduvp_msg"></p></div>
</div>