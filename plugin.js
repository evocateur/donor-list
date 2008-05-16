// donor-list admin script
jQuery(function($) {

	// hook edit links
	var parse_row = function(anchor) {
		var hash  = { },
			$row  = $(anchor).parents('tr').eq(0);
			splat = anchor.href.split('#').pop().split(',');

		hash['id']    = parseInt(splat[0]);
		hash['state'] = parseInt(splat[1]);

		$row.find('th, td:first').each(function(i, s) {
			var n = jQuery.trim($(s).text()).split(', ');
			if (i === 0) {
				hash[ 'last_name'] = n[0];
				hash['first_name'] = n[1];
			} else {
				hash['city'] = n[0];
			}
		});

		return [ hash, !hash.first_name ];
	};

	$('#donor-list td.edit a').click(function(e) {
		var row = parse_row(this),
			has = row[0],
			biz = row[1];

		$('#donor-business').attr('checked', !!biz).triggerHandler('click');

		$('#donor-list-form fieldset :input[name^=donor]').each(function(i, f) {
			var key = f.name.match(/\[([^\]]+)\]/)[1];
			$(f).val( has[key] || '' );
		});

		return false;
	});

	// hook form
	$('#donor-list-form').ajaxForm({
		success: function(r) {
			alert(r);
		}
	})

	.find(':checkbox:first').click(function() {

		$('#donor-edit fieldset label')

		.filter('[for=donor-last-name]').each(function(i, elm) {
			// change label text when toggled
			var c = elm.firstChild;
			c.nodeValue = (c.nodeValue.indexOf('Last') > -1)
				? 'Business Name' : 'Last Name';
		}).end()

		.filter('[for=donor-first-name]').slideToggle("fast", function() {
			// clear value, retain reference for use in timeout
			var self = $(this).children('input').val('').end();

			// focus the first visible element
			setTimeout(function() {
				$('fieldset input')[ self.is(':visible') ? 0 : 1 ].focus();
			}, 50);
		}).end();

	}).end()

	// .find(':submit').enable();
	.find(':submit').enable(false);

	// cancel & delete bindings
	$('#donor-cancel').click(function() {
		$('#donor-list-form fieldset :input').clearFields().filter('[name="donor[id]"]').val('');
		$('#donor-business').attr('checked', false).triggerHandler('click');
	});

	$('#donor-delete').click(function() {
		return false;
	});

	// IE trollover
	if ($.browser.msie) {
		var troll = function(e) { $(this).toggleClass('troll'); };
		$('#donor-list tbody tr').hover( troll, troll );
	}

});
