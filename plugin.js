// donor-list admin script
jQuery(function($) {

	// hook edit links
	$('#donor-list td.edit a').click(function(e) {
		return false;
	});

	// hook form
	$('#donor-list-form').ajaxForm({
		success: function(r) {
			alert(r);
		}
	})
	.find(':checkbox')
		.click(function() {
			$('label[for=donor-last-name]').each(function(i, elm) {
				var c = elm.firstChild;
				c.nodeValue = (c.nodeValue.indexOf('Last') > -1)
					? 'Business Name' : 'Last Name';
			});
			$('label[for=donor-first-name]').slideToggle("fast", function() {
				$(this).children('input').val('');
			});
			// TODO: focus next visible input
		})
	.end()
	.find(':submit')
		// .enable()
		.enable(false) // temporary
	.end();

	// IE trollover
	if ($.browser.msie) {
		var troll = function(e) { $(this).toggleClass('troll'); };
		$('#donor-list tbody tr').hover( troll, troll );
	}

});
