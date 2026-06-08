jQuery(document).ready(function($) {

	'use strict';

	fwduvpPresetsObj = unescapeHtml(fwduvpPresetsObj);
  	fwduvpPresetsObj = JSON.parse(fwduvpPresetsObj);

  	fwduvpMainPlaylistsObj = unescapeHtml(fwduvpMainPlaylistsObj);
  	fwduvpMainPlaylistsObj = JSON.parse(fwduvpMainPlaylistsObj);

	$("#fwduvp-shortcode-generator img").fwdTooltip({
    });
	
	$.each(fwduvpPresetsObj, function(i, el){
		$("#fwduvp_presets_list").append('<option value="' + el.name + '">' + el.name + '</option>');
		setShortodeIntext();
	});

	$("#fwduvp_presets_list").change(function(){
		presetId = $("#fwduvp_presets_list").val();
		setShortodeIntext();
	});

	$('#fwduvp_start_at_playlist, #fwduvp_start_at_video').on('keyup', function(){
		setShortodeIntext();
	});
	
	function setShortodeIntext(){
		var presetId = $("#fwduvp_presets_list").val();
		var playlistId = $("#fwduvp_main_playlists_list").val();
		var startAtPlaylist = $("#fwduvp_start_at_playlist").val();
		var startAtVideo = $("#fwduvp_start_at_video").val();

		if(fwduvpMainPlaylistsObj.length > 0){
			$("#fwduvp_shortocde").val('[fwduvp preset_id="' + presetId + '" playlist_id="' + playlistId  + '" start_at_playlist="' + startAtPlaylist +  '" start_at_video="' + startAtVideo + '"]');
			$("#fwduvp_shortocde").show();
		}else{
			$("#fwduvp_shortocde").hide();
		}
	}
	
	$("#fwduvp_div").hide();
		
	if (fwduvpMainPlaylistsObj.length > 0){
		
		$.each(fwduvpMainPlaylistsObj, function(i, el){
			$("#fwduvp_main_playlists_list").append('<option value="' + el.name + '">' + el.name + '</option>');
		});
		$("#fwduvp_main_playlists_list").change(function(){
			playlistId = $("#fwduvp_main_playlists_list").val();
			setShortodeIntext();
		});
		
		var presetId = $("#fwduvp_presets_list").val();
		var playlistId = $("#fwduvp_main_playlists_list").val();
		var startAtPlaylist = $("#fwduvp_start_at_playlist").val();
		var startAtVideo = $("#fwduvp_start_at_video").val();
		
		$("#fwduvp_shortcode_btn").click(function(){		
			var shortcode = '[fwduvp preset_id="' + presetId + '" playlist_id="' + playlistId  + '" start_at_playlist="' + startAtPlaylist +  '" start_at_video="' + startAtVideo + '"]';
		
			if (typeof tinymce != "undefined"){
			    var editor = tinymce.get("content");
			    
			    if (editor && (editor instanceof tinymce.Editor) && ($("textarea#content:hidden").length != 0)) {
			        editor.selection.setContent(shortcode);
			        editor.save({no_events: true});
			    }else{
					var text = $("textarea#content").val();
					var select_pos1 = $("textarea#content").prop("selectionStart");
					var select_pos2 = $("textarea#content").prop("selectionEnd");
					
					var new_text = text.slice(0, select_pos1) + shortcode + text.slice(select_pos2);
					
					$("textarea#content").val(new_text);
			    } 
			}else{
				var text = $("textarea#content").val();
				var select_pos1 = $("textarea#content").prop("selectionStart");
				var select_pos2 = $("textarea#content").prop("selectionEnd");
				
				var new_text = text.slice(0, select_pos1) + shortcode + text.slice(select_pos2);
				
				$("textarea#content").val(new_text);
			}
			
			$("#fwduvp_div").hide();
			$("#fwduvp_div").fadeIn(600);
			$("#fwduvp_msg").html("The shortcode has been added!");
			
			return false;
		});
	}else{
		var td = $("#fwduvp_main_playlists_list").parent();
		
		$("#fwduvp_main_playlists_list").remove();
		td.append("<em style='margin-left:8px;'>No playlists are available.</em>");
		
		$("#fwduvp_shortcode_btn").prop("disabled", true);
		$("#fwduvp_shortcode_btn").css("cursor", "default");
	}
	
	setShortodeIntext();

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
        return str.replace(/(&amp;|&lt;|&gt;|&quot;|&#039;)/g, function(m) { return map[m]; });
    }

    function removeFirstAndLastChar(str){
        str = str.substring(1);
        str = str.slice(0, -1);
        return str;
    }
});