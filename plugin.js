// donor-list admin script
jQuery(function($) {

	// width hacks (grr)
	var list_width = parseInt($('#donor-list').width()) + ($.browser.msie ? 65 : 50) + 'px',
		cell_width = parseInt($('#donor-list tbody td').width()) + ($.browser.msie ? 8 : 4) + 'px';

	$('#donor-edit').css({ 'left': list_width, 'display': 'block' });
	// "display: block" for IE

	// hook edit links
	var parse_row = function(anchor) {
		var hash  = { },
			$row  = $(anchor).parents('tr').eq(0);
			splat = anchor.href.split('#').pop().split('_');

		hash['id']    = parseInt(splat[0]);
		hash['state'] = parseInt(splat[1]);

		$row.find('span').each(function(i, s) {
			var n = jQuery.trim($(s).text()).split(', ');
			if (i === 0) {
				hash[ 'last_name'] = n[0];
				hash['first_name'] = n[1];
			} else {
				hash['city'] = n[0];
			}
		}).end()
		.find('th a').each(function(i, a) {
			hash['email'] = a.href.split(':')[1]; // mailto:<blah>
		});

		return [ hash, !hash.first_name ];
	};

	$('#donor-list a.edit').click(function(e) {
		var row = parse_row(this),
			has = row[0],
			biz = row[1];

		$('#donor-business').attr('checked', !!biz).triggerHandler('click');

		$('#donor-list-form fieldset :input[name^=donor]').each(function(i, f) {
			var key = f.name.match(/\[([^\]]+)\]/)[1];
			$(f).val( has[key] || '' );
		});

		return false;
	})
	.css('margin-left', cell_width); // hack hack hack hack

	// hook form
	$('#donor-list-form').ajaxForm({

		beforeSubmit: function(data, $form, options) {
			var invalid = $form.find('input.required:enabled').filter(function() {
				return 0 === $.trim($(this).val()).length;
			});
			if (invalid.length) {
				var named = $.trim(
					invalid.eq(0).parent('label').contents().filter("[nodeType=3]")[0].nodeValue
				);
				alert('Please enter a ' + $.trim( named ) + ' to continue.');
				invalid[0].select();
				return false;
			}
			$form.find(':submit').enable(false);
		},

		resetForm: true,

		success: function(r) {
			alert(r.toString());
			$('#donor-list-form :submit').enable();
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

		.filter('[for=donor-first-name]')[ box.slide ]("fast", function() {
			// retain reference for use in timeout
			var label = $(this);

			// disable first_name if business
			label.children('input').enable(!box.checked)

			// focus the first visible element
			setTimeout(function() {
				self.focus(); // help IE get back to the edit box
				$('fieldset input')[ label.is(':visible') ? 0 : 1 ].select();
			}, 50);
		}).end();

	}).end()

	.find(':submit').enable();

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
