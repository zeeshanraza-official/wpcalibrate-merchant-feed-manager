/**
 * WPCalibrate Merchant Feed Manager - Admin JavaScript
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		// 1. Copy Feed URL to clipboard
		$(document).on('click', '.wpcmfm-copy-url', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var url = $btn.data('url') || $btn.siblings('.wpcmfm-feed-url-input').val();

			if (!url) {
				return;
			}

			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(url).then(function() {
					showCopiedFeedback($btn);
				}).catch(function() {
					fallbackCopy(url, $btn);
				});
			} else {
				fallbackCopy(url, $btn);
			}
		});

		function fallbackCopy(text, $btn) {
			var tempInput = document.createElement('input');
			tempInput.value = text;
			document.body.appendChild(tempInput);
			tempInput.select();
			try {
				document.execCommand('copy');
				showCopiedFeedback($btn);
			} catch (err) {
				alert(wpcmfm_vars.strings.copy_failed || 'Failed to copy');
			}
			document.body.removeChild(tempInput);
		}

		function showCopiedFeedback($btn) {
			var origText = $btn.text();
			$btn.text(wpcmfm_vars.strings.copied || 'Copied!').prop('disabled', true);
			setTimeout(function() {
				$btn.text(origText).prop('disabled', false);
			}, 2000);
		}

		// 2. Tab Navigation in Feed Edit Form
		$(document).on('click', '.wpcmfm-tab-btn', function() {
			var $btn = $(this);
			var targetId = $btn.data('target');

			$('.wpcmfm-tab-btn').removeClass('active');
			$btn.addClass('active');

			$('.wpcmfm-tab-pane').removeClass('active');
			$('#' + targetId).addClass('active');
		});

		// 3. Selection rules dynamic row adding if needed
		// Accessible keydown handlers
		$('.wpcmfm-tab-btn').on('keydown', function(e) {
			if (e.key === 'Enter' || e.key === ' ') {
				$(this).trigger('click');
			}
		});
	});

})(jQuery);
