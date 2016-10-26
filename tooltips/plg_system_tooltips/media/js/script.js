/**
 * @package         Tooltips
 * @version         6.0.2
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */


(function($) {
	"use strict";

	$(document).ready(function() {
		var tt_timeout    = null;
		var tt_timeoutOff = false;

		// hover mode
		$('.rl_tooltips-link.hover').popover({
			trigger  : 'hover',
			container: 'body',
		});


		// close all popovers on click outside
		$('html').click(function() {
			$('.rl_tooltips-link').popover('hide');
		});

		// do stuff differently for touchscreens
		$('html').one('touchstart', function() {
			// add click mode for hovers
			$('.rl_tooltips-link.hover').popover({
				trigger  : 'manual',
				container: 'body'
			}).click(function(evt) {
				tooltipsShow($(this), evt, 'click');
			});
		});

		// close all popovers on click outside
		$('html').on('touchstart', function(e) {
			if ($(e.target).closest('.rl_tooltips').length) {
				return;
			}

			$('.rl_tooltips-link').popover('hide');
		});

		$('.rl_tooltips-link').on('touchstart', function(evt) {
			// prevent click close event
			evt.stopPropagation();
		});

		function tooltipsShow(el, evt, cls) {
			// prevent other click events
			evt.stopPropagation();

			clearTimeout(tt_timeout);

			var popover = typeof(el.data('popover')) != "undefined" ? el.data('popover') : el.data('bs.popover');

			// close all other popovers
			$('.rl_tooltips-link.' + cls).each(function() {
				var popover2 = typeof($(this).data('popover')) != "undefined" ? $(this).data('popover') : $(this).data('bs.popover');

				if (popover2 != popover) {
					$(this).popover('hide');
				}
			});

			// open current
			if (!popover.tip().hasClass('in')) {
				el.popover('show');
			}

			$('.rl_tooltips')
				.click(function(evt) {
					// prevent click close event on popover
					evt.stopPropagation();

					// switch timeout off for this tooltip
					tt_timeoutOff = true;
					clearTimeout(tt_timeout);
				})
			;
		}

	});
})(jQuery);
