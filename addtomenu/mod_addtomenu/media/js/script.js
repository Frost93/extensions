/**
 * @package         Add to Menu
 * @version         5.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

var addtomenu_delay = false;

var addtomenu_setMessage = null;
var addtomenu_show_start = null;
var addtomenu_show_end   = null;

(function($) {
	"use strict";

	$(document).ready(function() {
		$('<span/>', {
			id   : 'addtomenu_msg',
			css  : {'opacity': 0},
			click: function() {
				addtomenu_show_end()
			}
		}).appendTo('body');

		addtomenu_delay = false;
	});

	addtomenu_setMessage = function(msg, succes) {
		"use strict";

		jModalClose();
		if (succes) {
			addtomenu_show_start(msg, 'success');
			addtomenu_show_end(2000);
		} else {
			addtomenu_show_start(msg, 'danger');
			addtomenu_show_end(5000);
		}
	};

	addtomenu_show_start = function(msg, state) {
		$('#addtomenu_msg')
			.html(msg)
			.removeClass('btn-success').removeClass('btn-danger')
			.addClass('btn-' + state).addClass('visible');

		clearInterval(addtomenu_delay);
		$('#addtomenu_msg').fadeTo('fast', 0.8);
	};

	addtomenu_show_end = function(delay) {
		if (delay) {
			setTimeout(function() {
				addtomenu_show_end();
			}, delay);
		} else {
			clearInterval(addtomenu_delay);
			$('#addtomenu_msg').fadeOut();
		}
	};
})(jQuery);
