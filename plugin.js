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
		var self = $(this);
		// config
		var box  = {
			checked: !!this.checked,
			label: ((this.checked ? 'Business' : 'Last') + ' Name'),
			slide: ('slide' + (this.checked ? 'Up' : 'Down'))
		};

		$('#donor-edit fieldset label')

		.filter('[for=donor-last-name]').each(function(i, elm) {
			// change label text when toggled
			elm.firstChild.nodeValue = box.label;
		}).end()

		.filter('[for=donor-first-name]')[ box.slide ](99, function() {
			// retain reference for use in timeout
			var label = $(this);

			// disable first_name if business
			label.children('input').enable(!box.checked)

			// focus the first visible element
			setTimeout(function() {
				self.focus(); // help IE get back to the edit box
				$('fieldset input')[ label.is(':visible') ? 0 : 1 ].select();
			}, 100);
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
