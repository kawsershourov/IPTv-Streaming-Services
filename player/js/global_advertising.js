/**
 * Global advertising manager.
 *
 * @package fwduvp
 * @since fwduvp 11.0
 */

(function($){
	'use strict';

	var safeGlobalAdsAr = (typeof fwduvpGlobalAdsAr !== 'undefined') ? fwduvpGlobalAdsAr : [];
	var safeGlobalPopupAdsAr = (typeof fwduvpGlobalPopupAdsAr !== 'undefined') ? fwduvpGlobalPopupAdsAr : [];
	var globalAdsAr = Array.isArray(safeGlobalAdsAr) ? safeGlobalAdsAr : [];
	var globalPopupAdsAr = Array.isArray(safeGlobalPopupAdsAr) ? safeGlobalPopupAdsAr : [];
	var ajaxUrl = (typeof fwduvpAjaxUrl !== 'undefined') ? fwduvpAjaxUrl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
	var ajaxNonce = (typeof fwduvpGlobalAdsAjaxNonce !== 'undefined') ? fwduvpGlobalAdsAjaxNonce : '';
	var popupAjaxNonce = (typeof fwduvpGlobalPopupAdsAjaxNonce !== 'undefined') ? fwduvpGlobalPopupAdsAjaxNonce : '';
	var globalVastURL = (typeof fwduvpGlobalVastURL !== 'undefined') ? fwduvpGlobalVastURL : '';
	var globalVastTarget = (typeof fwduvpGlobalVastTarget !== 'undefined') ? fwduvpGlobalVastTarget : '_blank';
	var globalVastStartTime = (typeof fwduvpGlobalVastStartTime !== 'undefined') ? fwduvpGlobalVastStartTime : '00:00:00';
	var globalPauseAdsSource = (typeof fwduvpGlobalPauseAdsSource !== 'undefined') ? fwduvpGlobalPauseAdsSource : '';
	var globalAdsIdCounter = 0;
	var globalPopupAdsIdCounter = 0;
	var fwduvpMessage;
	var isGlobalAdsRequestRunning = false;
	var isGlobalPopupAdsRequestRunning = false;
	var isCombinedSaveRequestRunning = false;
	var updateGlobalAdsBtn;
	var addDialog;
	var editDialog;
	var deleteDialog;
	var addPopupDialog;
	var editPopupDialog;
	var deletePopupDialog;
	var currentEditId = null;
	var currentDeleteId = null;
	var currentPopupEditId = null;
	var currentPopupDeleteId = null;

	function isProFeatureGateEnabled(){
		if(typeof fwduvpCanShowProMessage === 'undefined' || fwduvpCanShowProMessage === null){
			return false;
		}

		if(fwduvpCanShowProMessage === true || fwduvpCanShowProMessage === 1){
			return true;
		}

		if(typeof fwduvpCanShowProMessage === 'string'){
			var normalizedFlag = $.trim(fwduvpCanShowProMessage).toLowerCase();
			return normalizedFlag === 'true' || normalizedFlag === '1';
		}

		return false;
	}

	function showUpgradeToProPopup(){
		if(!isProFeatureGateEnabled()){
			return false;
		}

		var popupMessage = (typeof fwduvpUpgradeToProMessage__ !== 'undefined') ? fwduvpUpgradeToProMessage__ : 'Upgrade to Pro to use this feature';
		var popupUrl = (typeof fwduvpUpgradeToProUrl !== 'undefined') ? fwduvpUpgradeToProUrl : 'https://fwdapps.net/product/ultimate-video-player-wp/';

		if(typeof FWDUVPProFeaturePopup !== 'undefined'){
			FWDUVPProFeaturePopup.show({
				message: popupMessage,
				buttonLabel: 'Close',
				url: popupUrl
			});
		}else{
			window.open(popupUrl, '_blank');
		}

		return true;
	}

	function bindProLockedInput(selector){
		$(selector).off('.fwduvpProLock');

		$(selector).on('mousedown.fwduvpProLock focus.fwduvpProLock click.fwduvpProLock', function(e){
			if(!showUpgradeToProPopup()) return;
			e.preventDefault();
			$(this).blur();
		});

		$(selector).on('keydown.fwduvpProLock paste.fwduvpProLock input.fwduvpProLock', function(e){
			if(!showUpgradeToProPopup()) return;
			e.preventDefault();
		});
	}

	function bindProLockedSelect(selector){
		$(selector).off('.fwduvpProLock');

		$(selector).on('mousedown.fwduvpProLock focus.fwduvpProLock click.fwduvpProLock change.fwduvpProLock', function(e){
			if(!showUpgradeToProPopup()) return;
			e.preventDefault();
			e.stopImmediatePropagation();
			return false;
		});
	}

	function getNextId(){
		globalAdsIdCounter += 1;
		return globalAdsIdCounter;
	}

	function normalizeIds(){
		for(var i = 0; i < globalAdsAr.length; i++){
			var currentId = parseInt(globalAdsAr[i].id, 10);
			if(!isNaN(currentId) && currentId > globalAdsIdCounter){
				globalAdsIdCounter = currentId;
			}
			if(isNaN(currentId) || currentId <= 0){
				globalAdsAr[i].id = getNextId();
			}else{
				globalAdsAr[i].id = currentId;
			}
		}
	}

	function normalizePopupIds(){
		for(var i = 0; i < globalPopupAdsAr.length; i++){
			var currentId = parseInt(globalPopupAdsAr[i].id, 10);
			if(!isNaN(currentId) && currentId > globalPopupAdsIdCounter){
				globalPopupAdsIdCounter = currentId;
			}
			if(isNaN(currentId) || currentId <= 0){
				globalPopupAdsIdCounter += 1;
				globalPopupAdsAr[i].id = globalPopupAdsIdCounter;
			}else{
				globalPopupAdsAr[i].id = currentId;
			}
		}
	}

	function esc(value){
		return String(value || '').replace(/[&<>'"]/g, function(ch){
			return {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'\'': '&#39;',
				'"': '&quot;'
			}[ch];
		});
	}

	function getFieldValue(id){
		return $(id).val() ? $(id).val().trim() : '';
	}

	function normalizeId(id){
		var parsedId = parseInt(id, 10);
		return isNaN(parsedId) ? null : parsedId;
	}

	function updateTips(tips, text){
		tips.text(text).addClass('ui-state-highlight');
		setTimeout(function(){
			tips.removeClass('ui-state-highlight');
		}, 1200);
		tips.addClass('fwd-error');
	}

	function checkLength(tips, el, prop, min, max){
		if((el.val().length > max) || (el.val().length < min)){
			el.addClass('ui-state-error');
			updateTips(tips, 'Length of ' + prop + ' must be between ' + min + ' and ' + max + '.');
			return false;
		}

		return true;
	}

	function checkIfIntegerAndLength(tips, el, prop, min, max){
		var intRegExp = /-?[0-9]+/;
		var str = el.val();
		var res = str.match(intRegExp);

		if(str.length === 0 && min === 0){
			return true;
		}

		if(res && (res[0] === str)){
			if((str.length > max) || (str.length < min)){
				el.addClass('ui-state-error');
				updateTips(tips, 'Length of ' + prop + ' must be between ' + min + ' and ' + max + '.');
				return false;
			}

			return true;
		}

		el.addClass('ui-state-error');
		updateTips(tips, 'The ' + prop + ' field value must be an integer.');
		return false;
	}

	function checkTimeFormat(tips, el, prop){
		var timeRegExp = /^(?:2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]$/;

		if(!timeRegExp.test(el.val())){
			el.addClass('ui-state-error');
			updateTips(tips, 'The ' + prop + ' field must have the format hh:mm:ss ex:00:10:48.');
			return false;
		}

		return true;
	}

	function checkRequired(value, tips){
		if(!value){
			updateTips(tips, 'The source field is required.');
			return false;
		}
		return true;
	}

	function validateGlobalAdDialog(isEdit){
		var suffix = isEdit ? '_edit' : '';
		var tips = $('#'+ (isEdit ? 'edit_global_ads_tips' : 'add_global_ads_tips'));

		var allAdFields = $([])
			.add($('#global_ads_label' + suffix))
			.add($('#global_ads_source' + suffix))
			.add($('#global_ads_start_time' + suffix))
			.add($('#global_time_to_hold_ad' + suffix))
			.add($('#global_add_duration' + suffix));

		allAdFields.removeClass('ui-state-error');

		var valid = true;
		valid = valid && checkLength(tips, $('#global_ads_label' + suffix), 'advertisement', 1, 300);
		valid = valid && checkLength(tips, $('#global_ads_source' + suffix), 'source', 1, 300);
		valid = valid && checkTimeFormat(tips, $('#global_ads_start_time' + suffix), 'start time');
		valid = valid && checkLength(tips, $('#global_time_to_hold_ad' + suffix), 'time to hold ad', 1, 64);
		valid = valid && checkTimeFormat(tips, $('#global_add_duration' + suffix), 'add duration');

		return valid;
	}

	function validateGlobalVastSettings(){
		var pauseAdsEl = $('#global_pause_ads_source');
		var sourceEl = $('#global_vast_xml_url');
		var startEl = $('#global_vast_start_time');

		pauseAdsEl.removeClass('ui-state-error');
		sourceEl.removeClass('ui-state-error');
		startEl.removeClass('ui-state-error');

		if(getFieldValue('#global_pause_ads_source').length > 0){
			if(pauseAdsEl.val().length > 1000){
				pauseAdsEl.addClass('ui-state-error');
				showGlobalAdsMessage('Length of advertisement on pause must be between 1 and 1000.', true);
				return false;
			}
		}

		if(getFieldValue('#global_vast_xml_url').length > 0){
			if(sourceEl.val().length > 1000){
				sourceEl.addClass('ui-state-error');
				showGlobalAdsMessage('Length of vast source must be between 1 and 1000.', true);
				return false;
			}
		}

		if(!/^(?:2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]$/.test(startEl.val())){
			startEl.addClass('ui-state-error');
			showGlobalAdsMessage('The vast start time field must have the format hh:mm:ss ex:00:10:48.', true);
			return false;
		}

		return true;
	}

	function validatePopupAdDialog(isEdit){
		var suffix = isEdit ? '_edit' : '';
		var tips = $('#global_popupad_tips' + suffix);
		var type = $('#global_popupad_type' + suffix).val() || 'image';

		var allPopupFields = $([])
			.add($('#global_popupads_label' + suffix))
			.add($('#global_popupads_source' + suffix))
			.add($('#global_popupads_start_time' + suffix))
			.add($('#global_popupads_stop_time' + suffix))
			.add($('#global_google_ad_client' + suffix))
			.add($('#global_google_ad_slot' + suffix))
			.add($('#global_google_ad_width' + suffix))
			.add($('#global_google_ad_height' + suffix))
			.add($('#global_google_ad_start_time' + suffix))
			.add($('#global_google_ad_stop_time' + suffix));

		allPopupFields.removeClass('ui-state-error');

		var valid = true;
		if(type === 'image'){
			valid = valid && checkLength(tips, $('#global_popupads_label' + suffix), 'label', 1, 64);
			valid = valid && checkLength(tips, $('#global_popupads_source' + suffix), 'source', 1, 1000);
			valid = valid && checkTimeFormat(tips, $('#global_popupads_start_time' + suffix), 'start time');
			valid = valid && checkTimeFormat(tips, $('#global_popupads_stop_time' + suffix), 'stop time');
		}else{
			valid = valid && checkLength(tips, $('#global_popupads_label' + suffix), 'label', 1, 64);
			valid = valid && checkLength(tips, $('#global_google_ad_client' + suffix), 'google adsense ad client code', 1, 64);
			valid = valid && checkLength(tips, $('#global_google_ad_slot' + suffix), 'google adsense ad client slot', 1, 64);
			valid = valid && checkIfIntegerAndLength(tips, $('#global_google_ad_width' + suffix), 'google adsense ad width', 1, 4);
			valid = valid && checkIfIntegerAndLength(tips, $('#global_google_ad_height' + suffix), 'google adsense ad height', 1, 4);
			valid = valid && checkTimeFormat(tips, $('#global_google_ad_start_time' + suffix), 'start time');
			valid = valid && checkTimeFormat(tips, $('#global_google_ad_stop_time' + suffix), 'stop time');
		}

		return valid;
	}

	function renderGlobalAds(){
		var container = $('#main_global_ads');
		container.empty();
		if(!globalAdsAr.length){
			container.hide();
			return;
		}

		container.show();

		for(var i = 0; i < globalAdsAr.length; i++){
			var item = globalAdsAr[i];
			var row = $('<div class="fwd-item ads global-ad-item" />').attr('data-id', item.id);
			row.append('<h3 class="item-header"><span>' + esc(item.label || 'Advertisement') + '</span></h3>');
			row.append(
				'<div class="extra-buttons-holder">'
					+ '<button class="delete_ads_btn delete-global-ad" type="button">' + fwduvpDelete__ + '</button>'
					+ '<button class="edit_ads_btn edit-global-ad" type="button">' + fwduvpEdit__ + '</button>'
				+ '</div>'
			);
			container.append(row);
		}
	}

	function renderGlobalPopupAds(){
		var container = $('#main_global_popupads');
		container.empty();
		if(!globalPopupAdsAr.length){
			container.hide();
			return;
		}

		container.show();
		for(var i = 0; i < globalPopupAdsAr.length; i++){
			var item = globalPopupAdsAr[i];
			var row = $('<div class="fwd-item global-popupad-item" />').attr('data-id', item.id);
			row.append('<h3 class="item-header"><span>' + esc(item.label || 'Popup Advertisement') + '</span></h3>');
			row.append(
				'<div class="extra-buttons-holder">'
					+ '<button class="delete_popupad_btn delete-global-popupad" type="button">' + fwduvpDelete__ + '</button>'
					+ '<button class="edit_popupad_btn edit-global-popupad" type="button">' + fwduvpEdit__ + '</button>'
				+ '</div>'
			);
			container.append(row);
		}
	}

	function getAdById(id){
		var normalizedId = normalizeId(id);
		if(normalizedId === null) return null;

		for(var i = 0; i < globalAdsAr.length; i++){
			if(parseInt(globalAdsAr[i].id, 10) === normalizedId){
				return globalAdsAr[i];
			}
		}
		return null;
	}

	function getAdIndexById(id){
		var normalizedId = normalizeId(id);
		if(normalizedId === null) return -1;

		for(var i = 0; i < globalAdsAr.length; i++){
			if(parseInt(globalAdsAr[i].id, 10) === normalizedId){
				return i;
			}
		}
		return -1;
	}

	function getPopupAdById(id){
		var normalizedId = normalizeId(id);
		if(normalizedId === null) return null;

		for(var i = 0; i < globalPopupAdsAr.length; i++){
			if(parseInt(globalPopupAdsAr[i].id, 10) === normalizedId){
				return globalPopupAdsAr[i];
			}
		}
		return null;
	}

	function getPopupAdIndexById(id){
		var normalizedId = normalizeId(id);
		if(normalizedId === null) return -1;

		for(var i = 0; i < globalPopupAdsAr.length; i++){
			if(parseInt(globalPopupAdsAr[i].id, 10) === normalizedId){
				return i;
			}
		}
		return -1;
	}

	function buildAdFromAddDialog(){
		return {
			id: getNextId(),
			label: getFieldValue('#global_ads_label'),
			source: getFieldValue('#global_ads_source'),
			url: getFieldValue('#global_ads_url'),
			target: $('#global_ads_target').val() || '_blank',
			startTime: getFieldValue('#global_ads_start_time') || '00:00:00',
			timeToHoldAd: getFieldValue('#global_time_to_hold_ad') || '4',
			addDuration: getFieldValue('#global_add_duration') || '00:00:10'
		};
	}

	function clearAddDialog(){
		$('#global_ads_label').val('');
		$('#global_ads_source').val('');
		$('#global_ads_url').val('');
		$('#global_ads_target').val('_blank');
		$('#global_ads_start_time').val('00:00:00');
		$('#global_time_to_hold_ad').val('4');
		$('#global_add_duration').val('00:00:10');
	}

	function clearAddPopupDialog(){
		$('#global_popupad_type').val('image');
		$('#global_popupads_label').val('');
		$('#global_popupads_source').val('');
		$('#global_popupads_url').val('');
		$('#global_popupads_target').val('_blank');
		$('#global_popupads_start_time').val('00:00:00');
		$('#global_popupads_stop_time').val('00:00:10');
		$('#global_google_ad_client').val('');
		$('#global_google_ad_slot').val('');
		$('#global_google_ad_width').val('300');
		$('#global_google_ad_height').val('250');
		$('#global_google_ad_start_time').val('00:00:00');
		$('#global_google_ad_stop_time').val('00:00:10');
	}

	function openEditDialog(id){
		id = normalizeId(id);
		if(id === null) return;

		var ad = getAdById(id);
		if(!ad){
			return;
		}
		currentEditId = id;
		$('#global_ads_label_edit').val(ad.label || '');
		$('#global_ads_source_edit').val(ad.source || '');
		$('#global_ads_url_edit').val(ad.url || '');
		$('#global_ads_target_edit').val(ad.target || '_blank');
		$('#global_ads_start_time_edit').val(ad.startTime || '00:00:00');
		$('#global_time_to_hold_ad_edit').val(ad.timeToHoldAd || '0');
		$('#global_add_duration_edit').val(ad.addDuration || '00:00:00');
		editDialog.dialog('open');
	}

	function initGlobalVastSettings(){
		$('#global_pause_ads_source').val(globalPauseAdsSource || '');
		$('#global_vast_xml_url').val(globalVastURL || '');
		$('#global_vast_xml_target').val(globalVastTarget || '_blank');
		$('#global_vast_start_time').val(globalVastStartTime || '00:00:00');
	}

	function openEditPopupDialog(id){
		id = normalizeId(id);
		if(id === null) return;

		var ad = getPopupAdById(id);
		if(!ad) return;

		currentPopupEditId = id;
		$('#global_popupad_type_edit').val(ad.type || 'image');
		$('#global_popupads_label_edit').val(ad.label || '');
		$('#global_popupads_source_edit').val(ad.source || '');
		$('#global_popupads_url_edit').val(ad.url || '');
		$('#global_popupads_target_edit').val(ad.target || '_blank');
		$('#global_popupads_start_time_edit').val(ad.startTime || '00:00:00');
		$('#global_popupads_stop_time_edit').val(ad.stopTime || '00:00:10');
		$('#global_google_ad_client_edit').val(ad.google_ad_client || '');
		$('#global_google_ad_slot_edit').val(ad.google_ad_slot || '');
		$('#global_google_ad_width_edit').val(ad.google_ad_width || '300');
		$('#global_google_ad_height_edit').val(ad.google_ad_height || '250');
		$('#global_google_ad_start_time_edit').val(ad.google_ad_start_time || '00:00:00');
		$('#global_google_ad_stop_time_edit').val(ad.google_ad_stop_time || '00:00:10');
		initGlobalPopupAdForm(true);
		editPopupDialog.dialog('open');
	}

	function initSortable(){
		$('#main_global_ads').sortable({
			placeholder: 'ui-state-highlight',
			tolerance: 'pointer',
			stop: function(){
				var ids = [];
				$('#main_global_ads .global-ad-item').each(function(){
					ids.push(normalizeId($(this).attr('data-id')));
				});
				var next = [];
				for(var i = 0; i < ids.length; i++){
					if(ids[i] === null) continue;
					var ad = getAdById(ids[i]);
					if(ad){
						next.push(ad);
					}
				}
				globalAdsAr = next;
			}
		});
	}

	function initPopupSortable(){
		$('#main_global_popupads').sortable({
			placeholder: 'ui-state-highlight',
			tolerance: 'pointer',
			stop: function(){
				var ids = [];
				$('#main_global_popupads .global-popupad-item').each(function(){
					ids.push(normalizeId($(this).attr('data-id')));
				});
				var next = [];
				for(var i = 0; i < ids.length; i++){
					if(ids[i] === null) continue;
					var ad = getPopupAdById(ids[i]);
					if(ad){
						next.push(ad);
					}
				}
				globalPopupAdsAr = next;
			}
		});
	}

	function initGlobalPopupAdForm(isEdit){
		var typeSelector = isEdit ? '#global_popupad_type_edit' : '#global_popupad_type';
		var popupDialogId = isEdit ? '#edit-global-popupad-dialog' : '#add-global-popupad-dialog';
		var popupDialogTable = isEdit ? '.global-popupads-dialog-edit' : '.global-popupads-dialog';
		var adsenseDialogTable = isEdit ? '.global-adsense-dialog-edit' : '.global-adsense-dialog';
		var type = $(typeSelector).val();

		if(type === 'adsense'){
			$(popupDialogTable).hide();
			$(adsenseDialogTable).show();
			$(popupDialogId).dialog({width: 420, height: 510});
		}else{
			$(popupDialogTable).show();
			$(adsenseDialogTable).hide();
			$(popupDialogId).dialog({width: 610, height: 537});
		}
	}

	function buildPopupAdFromAddDialog(){
		globalPopupAdsIdCounter += 1;
		return {
			id: globalPopupAdsIdCounter,
			type: $('#global_popupad_type').val() || 'image',
			label: getFieldValue('#global_popupads_label'),
			source: getFieldValue('#global_popupads_source'),
			url: getFieldValue('#global_popupads_url'),
			target: $('#global_popupads_target').val() || '_blank',
			startTime: getFieldValue('#global_popupads_start_time') || '00:00:00',
			stopTime: getFieldValue('#global_popupads_stop_time') || '00:00:10',
			google_ad_client: getFieldValue('#global_google_ad_client'),
			google_ad_slot: getFieldValue('#global_google_ad_slot'),
			google_ad_width: getFieldValue('#global_google_ad_width') || '300',
			google_ad_height: getFieldValue('#global_google_ad_height') || '250',
			google_ad_start_time: getFieldValue('#global_google_ad_start_time') || '00:00:00',
			google_ad_stop_time: getFieldValue('#global_google_ad_stop_time') || '00:00:10'
		};
	}

	function openMediaPicker(targetInputSelector){
		var frame = wp.media({
			title: 'Select media',
			button: { text: 'Use this media' },
			multiple: false
		});

		frame.on('select', function(){
			var attachment = frame.state().get('selection').first().toJSON();
			$(targetInputSelector).val(attachment.url || '');
		});

		frame.open();
	}

	function saveGlobalAds(options){
		options = options || {};
		var showMessage = options.showMessage !== false;
		var disableButton = options.disableButton !== false;

		// Resolve these at click time in case localization is injected after script parse.
		ajaxUrl = (typeof fwduvpAjaxUrl !== 'undefined') ? fwduvpAjaxUrl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
		ajaxNonce = (typeof fwduvpGlobalAdsAjaxNonce !== 'undefined') ? fwduvpGlobalAdsAjaxNonce : '';

		if(!ajaxUrl || !ajaxNonce){
			if(showMessage){
				showGlobalAdsMessage('Failed to update global advertisements. Missing AJAX nonce or URL.', true);
			}
			if(options.onFail){
				options.onFail('Failed to update global advertisements. Missing AJAX nonce or URL.');
			}
			if(options.onAlways){
				options.onAlways();
			}
			return;
		}

		if(isGlobalAdsRequestRunning){
			if(options.onFail){
				options.onFail('A global advertisements update is already in progress.');
			}
			if(options.onAlways){
				options.onAlways();
			}
			return;
		}

		isGlobalAdsRequestRunning = true;
		if(disableButton){
			updateGlobalAdsBtn.prop('disabled', true);
		}

		$.ajax({
			url: ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'fwduvp_update_global_ads',
				nonce: ajaxNonce,
				ads_data: JSON.stringify(globalAdsAr),
				global_pause_ads_source: getFieldValue('#global_pause_ads_source'),
				global_vast_url: getFieldValue('#global_vast_xml_url'),
				global_vast_target: $('#global_vast_xml_target').val() || '_blank',
				global_vast_start_time: getFieldValue('#global_vast_start_time') || '00:00:00'
			},
		}).done(function(response){
			if(response && response.success){
				if(showMessage){
					showGlobalAdsMessage(response.data && response.data.msg ? response.data.msg : '', false);
				}
				if(options.onSuccess){
					options.onSuccess(response);
				}
			}else{
				var errorMsg = response && response.data && response.data.msg ? response.data.msg : 'Failed to update global advertisements.';
				if(showMessage){
					showGlobalAdsMessage(errorMsg, true);
				}
				if(options.onFail){
					options.onFail(errorMsg);
				}
			}
		}).fail(function(){
			if(showMessage){
				showGlobalAdsMessage('Failed to update global advertisements.', true);
			}
			if(options.onFail){
				options.onFail('Failed to update global advertisements.');
			}
		}).always(function(){
			isGlobalAdsRequestRunning = false;
			if(disableButton){
				updateGlobalAdsBtn.prop('disabled', false);
			}
			if(options.onAlways){
				options.onAlways();
			}
		});
	}

	function showGlobalAdsMessage(text, isError){
		if(!text) return;

		if(typeof FWDUVPMessage !== 'undefined'){
			if(!fwduvpMessage){
				fwduvpMessage = new FWDUVPMessage();
			}
			fwduvpMessage.show(text, isError);
			return;
		}

		if(isError){
			alert(text);
		}
	}

	function saveGlobalPopupAds(options){
		options = options || {};
		var showMessage = options.showMessage !== false;
		var disableButton = options.disableButton !== false;

		ajaxUrl = (typeof fwduvpAjaxUrl !== 'undefined') ? fwduvpAjaxUrl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
		popupAjaxNonce = (typeof fwduvpGlobalPopupAdsAjaxNonce !== 'undefined') ? fwduvpGlobalPopupAdsAjaxNonce : '';

		if(!ajaxUrl || !popupAjaxNonce){
			if(showMessage){
				showGlobalAdsMessage('Failed to update global pop-up advertisements. Missing AJAX nonce or URL.', true);
			}
			if(options.onFail){
				options.onFail('Failed to update global pop-up advertisements. Missing AJAX nonce or URL.');
			}
			if(options.onAlways){
				options.onAlways();
			}
			return;
		}

		if(isGlobalPopupAdsRequestRunning){
			if(options.onFail){
				options.onFail('A global pop-up advertisements update is already in progress.');
			}
			if(options.onAlways){
				options.onAlways();
			}
			return;
		}

		isGlobalPopupAdsRequestRunning = true;
		if(disableButton){
			updateGlobalAdsBtn.prop('disabled', true);
		}

		$.ajax({
			url: ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'fwduvp_update_global_popup_ads',
				nonce: popupAjaxNonce,
				popup_ads_data: JSON.stringify(globalPopupAdsAr)
			}
		}).done(function(response){
			if(response && response.success){
				if(showMessage){
					showGlobalAdsMessage(response.data && response.data.msg ? response.data.msg : '', false);
				}
				if(options.onSuccess){
					options.onSuccess(response);
				}
			}else{
				var errorMsg = response && response.data && response.data.msg ? response.data.msg : 'Failed to update global pop-up advertisements.';
				if(showMessage){
					showGlobalAdsMessage(errorMsg, true);
				}
				if(options.onFail){
					options.onFail(errorMsg);
				}
			}
		}).fail(function(){
			if(showMessage){
				showGlobalAdsMessage('Failed to update global pop-up advertisements.', true);
			}
			if(options.onFail){
				options.onFail('Failed to update global pop-up advertisements.');
			}
		}).always(function(){
			isGlobalPopupAdsRequestRunning = false;
			if(disableButton){
				updateGlobalAdsBtn.prop('disabled', false);
			}
			if(options.onAlways){
				options.onAlways();
			}
		});
	}

	$(function(){
		updateGlobalAdsBtn = $('#update_global_ads_btn, #fwd_update_global_ads_btn');

		if($.isFunction($.fn.fwdTooltip)){
			$('img').fwdTooltip({});
		}

		normalizeIds();
		normalizePopupIds();
		renderGlobalAds();
		renderGlobalPopupAds();
		initSortable();
		initPopupSortable();
		initGlobalVastSettings();

		bindProLockedInput('#global_vast_xml_url');
		bindProLockedInput('#global_pause_ads_source');
		bindProLockedSelect('#global_vast_xml_target');

		addDialog = $('#add-global-ads-dialog').dialog({
			autoOpen: false,
			width: 600,
			height: 553,
			dialogClass: 'fwduvp',
			modal: true,
			beforeOpen: function(){
				if(isProFeatureGateEnabled()){
					showUpgradeToProPopup();
					return false;
				}

				return true;
			},
			buttons: [{
				text: fwduvpAdd__,
				click: function(){
					if(!validateGlobalAdDialog(false)){
						return;
					}
					var ad = buildAdFromAddDialog();
					globalAdsAr.push(ad);
					renderGlobalAds();
					$(this).dialog('close');
					clearAddDialog();
				},
			}, {
				text: fwduvpCancel__,
				click: function(){
					$(this).dialog('close');
				}
			}],
			open: function(){
				$('#add_global_ads_tips').text('The advertisement field is required.').removeClass('fwd-error ui-state-highlight');
				clearAddDialog();
				$('.ui-widget-overlay').addClass('fwduvp');
			}
		});

		editDialog = $('#edit-global-ads-dialog').dialog({
			autoOpen: false,
			width: 600,
			height: 553,
			dialogClass: 'fwduvp',
			modal: true,
			buttons: [{
				text: fwduvpUpdate__,
				click: function(){
					if(!validateGlobalAdDialog(true)){
						return;
					}
					var source = getFieldValue('#global_ads_source_edit');
					var index = getAdIndexById(currentEditId);
					if(index === -1){
						$(this).dialog('close');
						return;
					}
					globalAdsAr[index].label = getFieldValue('#global_ads_label_edit');
					globalAdsAr[index].source = source;
					globalAdsAr[index].url = getFieldValue('#global_ads_url_edit');
					globalAdsAr[index].target = $('#global_ads_target_edit').val() || '_blank';
					globalAdsAr[index].startTime = getFieldValue('#global_ads_start_time_edit') || '00:00:00';
					globalAdsAr[index].timeToHoldAd = getFieldValue('#global_time_to_hold_ad_edit') || '0';
					globalAdsAr[index].addDuration = getFieldValue('#global_add_duration_edit') || '00:00:00';
					renderGlobalAds();
					$(this).dialog('close');
				},
			}, {
				text: fwduvpCancel__,
				click: function(){
					$(this).dialog('close');
				}
			}],
			open: function(){
				$('#edit_global_ads_tips').text('The advertisement field is required.').removeClass('fwd-error ui-state-highlight');
				$('.ui-widget-overlay').addClass('fwduvp');
			}
		});

		deleteDialog = $('#delete-global-ads-dialog').dialog({
			autoOpen: false,
			width: 300,
			height: 150,
			dialogClass: 'fwduvp',
			modal: true,
			buttons: [{
				text: fwduvpYes__,
				click: function(){
					var index = getAdIndexById(currentDeleteId);
					if(index !== -1){
						globalAdsAr.splice(index, 1);
						renderGlobalAds();
					}
					$(this).dialog('close');
				},
			}, {
				text: fwduvpNo__,
				click: function(){
					$(this).dialog('close');
				}
			}],
			open: function(){
				$('.ui-widget-overlay').addClass('fwduvp');
			}
		});

		addPopupDialog = $('#add-global-popupad-dialog').dialog({
			autoOpen: false,
			width: 610,
			height: 537,
			dialogClass: 'fwduvp',
			modal: true,
			beforeOpen: function(){
				if(isProFeatureGateEnabled()){
					showUpgradeToProPopup();
					return false;
				}

				return true;
			},
			buttons: [{
				text: fwduvpAdd__,
				click: function(){
					if(!validatePopupAdDialog(false)){
						return;
					}
					var ad = buildPopupAdFromAddDialog();
					globalPopupAdsAr.push(ad);
					renderGlobalPopupAds();
					$(this).dialog('close');
					clearAddPopupDialog();
				}
			}, {
				text: fwduvpCancel__,
				click: function(){
					$(this).dialog('close');
				}
			}],
			open: function(){
				$('#global_popupad_tips').text('The label field is required.').removeClass('fwd-error ui-state-highlight');
				clearAddPopupDialog();
				initGlobalPopupAdForm(false);
				$('.ui-widget-overlay').addClass('fwduvp');
			}
		});

		editPopupDialog = $('#edit-global-popupad-dialog').dialog({
			autoOpen: false,
			width: 610,
			height: 537,
			dialogClass: 'fwduvp',
			modal: true,
			buttons: [{
				text: fwduvpUpdate__,
				click: function(){
					if(!validatePopupAdDialog(true)){
						return;
					}
					var index = getPopupAdIndexById(currentPopupEditId);
					if(index === -1){
						$(this).dialog('close');
						return;
					}

					var type = $('#global_popupad_type_edit').val() || 'image';
					var label = getFieldValue('#global_popupads_label_edit');

					globalPopupAdsAr[index].type = type;
					globalPopupAdsAr[index].label = label;
					globalPopupAdsAr[index].source = getFieldValue('#global_popupads_source_edit');
					globalPopupAdsAr[index].url = getFieldValue('#global_popupads_url_edit');
					globalPopupAdsAr[index].target = $('#global_popupads_target_edit').val() || '_blank';
					globalPopupAdsAr[index].startTime = getFieldValue('#global_popupads_start_time_edit') || '00:00:00';
					globalPopupAdsAr[index].stopTime = getFieldValue('#global_popupads_stop_time_edit') || '00:00:10';
					globalPopupAdsAr[index].google_ad_client = getFieldValue('#global_google_ad_client_edit');
					globalPopupAdsAr[index].google_ad_slot = getFieldValue('#global_google_ad_slot_edit');
					globalPopupAdsAr[index].google_ad_width = getFieldValue('#global_google_ad_width_edit') || '300';
					globalPopupAdsAr[index].google_ad_height = getFieldValue('#global_google_ad_height_edit') || '250';
					globalPopupAdsAr[index].google_ad_start_time = getFieldValue('#global_google_ad_start_time_edit') || '00:00:00';
					globalPopupAdsAr[index].google_ad_stop_time = getFieldValue('#global_google_ad_stop_time_edit') || '00:00:10';
					renderGlobalPopupAds();
					$(this).dialog('close');
				}
			}, {
				text: fwduvpCancel__,
				click: function(){
					$(this).dialog('close');
				}
			}],
			open: function(){
				$('#global_popupad_tips_edit').text('The label field is required.').removeClass('fwd-error ui-state-highlight');
				initGlobalPopupAdForm(true);
				$('.ui-widget-overlay').addClass('fwduvp');
			}
		});

		deletePopupDialog = $('#delete-global-popupad-dialog').dialog({
			autoOpen: false,
			width: 340,
			height: 150,
			dialogClass: 'fwduvp',
			modal: true,
			buttons: [{
				text: fwduvpYes__,
				click: function(){
					var index = getPopupAdIndexById(currentPopupDeleteId);
					if(index !== -1){
						globalPopupAdsAr.splice(index, 1);
						renderGlobalPopupAds();
					}
					$(this).dialog('close');
				}
			}, {
				text: fwduvpNo__,
				click: function(){
					$(this).dialog('close');
				}
			}],
			open: function(){
				$('.ui-widget-overlay').addClass('fwduvp');
			}
		});

		$('#add_global_ads_button').button().on('click', function(e){
			if(isProFeatureGateEnabled()){
				e.preventDefault();
				e.stopImmediatePropagation();
				showUpgradeToProPopup();
				return false;
			}

			e.preventDefault();
			addDialog.dialog('open');
		});

		$('#main_global_ads').on('click', '.edit-global-ad', function(e){
			e.preventDefault();
			var id = normalizeId($(this).closest('.global-ad-item').attr('data-id'));
			openEditDialog(id);
		});

		$('#main_global_ads').on('click', '.delete-global-ad', function(e){
			e.preventDefault();
			currentDeleteId = normalizeId($(this).closest('.global-ad-item').attr('data-id'));
			deleteDialog.dialog('open');
		});

		$('#global_ads_source_button').button().on('click', function(e){
			e.preventDefault();
			openMediaPicker('#global_ads_source');
		});

		$('#global_ads_source_button_edit').button().on('click', function(e){
			e.preventDefault();
			openMediaPicker('#global_ads_source_edit');
		});

		$('#add_global_popupad_button').button().on('click', function(e){
			if(isProFeatureGateEnabled()){
				e.preventDefault();
				e.stopImmediatePropagation();
				showUpgradeToProPopup();
				return false;
			}

			e.preventDefault();
			addPopupDialog.dialog('open');
		});

		$('#main_global_popupads').on('click', '.edit-global-popupad', function(e){
			e.preventDefault();
			openEditPopupDialog($(this).closest('.global-popupad-item').attr('data-id'));
		});

		$('#main_global_popupads').on('click', '.delete-global-popupad', function(e){
			e.preventDefault();
			currentPopupDeleteId = normalizeId($(this).closest('.global-popupad-item').attr('data-id'));
			deletePopupDialog.dialog('open');
		});

		$('#global_popupads_source_button').button().on('click', function(e){
			e.preventDefault();
			openMediaPicker('#global_popupads_source');
		});

		$('#global_popupads_source_button_edit').button().on('click', function(e){
			e.preventDefault();
			openMediaPicker('#global_popupads_source_edit');
		});

		$('#global_popupad_type').on('change', function(){
			initGlobalPopupAdForm(false);
		});

		$('#global_popupad_type_edit').on('change', function(){
			initGlobalPopupAdForm(true);
		});

		updateGlobalAdsBtn.on('click', function(){
			if(isCombinedSaveRequestRunning){
				return;
			}

			if(!validateGlobalVastSettings()){
				return;
			}

			isCombinedSaveRequestRunning = true;
			updateGlobalAdsBtn.prop('disabled', true);

			saveGlobalAds({
				showMessage: false,
				disableButton: false,
				onSuccess: function(){
					saveGlobalPopupAds({
						showMessage: false,
						disableButton: false,
						onSuccess: function(){
							showGlobalAdsMessage('All advertisements have been updated!', false);
						},
						onFail: function(errorMsg){
							showGlobalAdsMessage(errorMsg || 'Global advertisements were updated, but global pop-up advertisements failed to update.', true);
						},
						onAlways: function(){
							isCombinedSaveRequestRunning = false;
							updateGlobalAdsBtn.prop('disabled', false);
						}
					});
				},
				onFail: function(errorMsg){
					showGlobalAdsMessage(errorMsg || 'Failed to update global advertisements.', true);
					isCombinedSaveRequestRunning = false;
					updateGlobalAdsBtn.prop('disabled', false);
				}
			});
		});
	});

}(jQuery));
