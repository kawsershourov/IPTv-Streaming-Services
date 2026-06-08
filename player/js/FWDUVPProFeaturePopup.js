(function(window, document){
	'use strict';

	var overlay;
	var textNode;
	var okButton;
	var learnMoreLink;
	var currentUrl = '';

	function escapeHtml(str){
		return String(str || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	}

	function ensureStyles(){
		if(document.getElementById('fwduvp-pro-popup-style')) return;

		var style = document.createElement('style');
		style.id = 'fwduvp-pro-popup-style';
		style.type = 'text/css';
		style.textContent = '' +
			'.fwduvp-pro-popup-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:2147483647;display:none;align-items:center;justify-content:center;padding:20px;}' +
			'.fwduvp-pro-popup-card{width:100%;max-width:500px;background:linear-gradient(160deg,#ffffff 0%,#f6fbff 100%);border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.35);padding:24px 22px;text-align:center;border:1px solid #d6e8f6;}' +
			'.fwduvp-pro-popup-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;background:#e8f4ff;color:#0a4c78;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;margin:0 auto 12px;}' +
			'.fwduvp-pro-popup-text{font-size:16px;line-height:1.5;color:#1d2327;margin:0 0 10px;}' +
			'.fwduvp-pro-popup-link{display:inline-block;color:#0b5f94;font-size:13px;font-weight:600;text-decoration:none;border-bottom:1px solid rgba(11,95,148,.35);margin-bottom:16px;transition:all .2s ease;}' +
			'.fwduvp-pro-popup-link:hover{color:#083d5f;border-bottom-color:#083d5f;}' +
			'.fwduvp-pro-popup-actions{display:flex;justify-content:center;}' +
			'.fwduvp-pro-popup-ok{min-width:120px;height:38px;border-radius:8px;font-size:14px;cursor:pointer;transition:all .2s ease;}' +
			'.fwduvp-pro-popup-ok{border:0;background:#2271b1;color:#fff;}' +
			'.fwduvp-pro-popup-ok:hover{background:#135e96;}';

		document.head.appendChild(style);
	}

	function ensurePopup(){
		if(overlay) return;

		ensureStyles();

		overlay = document.createElement('div');
		overlay.className = 'fwduvp-pro-popup-overlay';
		overlay.addEventListener('click', function(e){
			if(e.target === overlay) overlay.style.display = 'none';
		});

		var card = document.createElement('div');
		card.className = 'fwduvp-pro-popup-card';
		card.addEventListener('click', function(e){
			e.stopPropagation();
		});

		var badge = document.createElement('div');
		badge.className = 'fwduvp-pro-popup-badge';
		badge.textContent = 'Pro Feature';

		textNode = document.createElement('p');
		textNode.className = 'fwduvp-pro-popup-text';

		var actions = document.createElement('div');
		actions.className = 'fwduvp-pro-popup-actions';

		learnMoreLink = document.createElement('a');
		learnMoreLink.className = 'fwduvp-pro-popup-link';
		learnMoreLink.href = '#';
		learnMoreLink.target = '_blank';
		learnMoreLink.rel = 'noopener noreferrer';
		learnMoreLink.textContent = 'Explore Pro features';
		learnMoreLink.addEventListener('click', function(e){
			e.preventDefault();
			if(currentUrl){
				window.open(currentUrl, '_blank');
			}
		});

		okButton = document.createElement('button');
		okButton.className = 'fwduvp-pro-popup-ok';
		okButton.type = 'button';
		okButton.textContent = 'Close';

		okButton.addEventListener('click', function(){
			overlay.style.display = 'none';
		});

		actions.appendChild(okButton);
		card.appendChild(badge);
		card.appendChild(textNode);
		card.appendChild(learnMoreLink);
		card.appendChild(actions);
		overlay.appendChild(card);
		document.body.appendChild(overlay);
	}

	window.FWDUVPProFeaturePopup = {
		show: function(options){
			ensurePopup();

			var opts = options || {};
			var url = opts.url || '';
			var message = opts.message || 'Upgrade to Pro to use this feature';
			var buttonLabel = opts.buttonLabel || 'Close';
			var detailsLabel = opts.detailsLabel || 'Explore Pro features';

			currentUrl = url;
			okButton.textContent = buttonLabel;
			learnMoreLink.textContent = detailsLabel;
			learnMoreLink.style.display = url ? 'inline-block' : 'none';
			textNode.textContent = message;

			overlay.style.display = 'flex';
		}
	};
})(window, document);
