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
	.find(':checkbox:first')
		.click(function() {
			$('#donor-edit fieldset label')
				.filter('[for=donor-last-name]').each(function(i, elm) {
					// change label text when toggled
					var c = elm.firstChild;
					c.nodeValue = (c.nodeValue.indexOf('Last') > -1)
						? 'Business Name' : 'Last Name';
				})
			.end()
				.filter('[for=donor-first-name]').slideToggle("fast", function() {
					// clear value, retain reference for use in timeout
					var self = $(this).children('input').val('').end();

					// focus the first visible element
					setTimeout(function() {
						$('fieldset input')[ self.is(':visible') ? 0 : 1 ].focus();
					}, 50);
				})
			.end();
		})
	.end()
	.find(':submit').enable();

	// IE trollover
	if ($.browser.msie) {
		var troll = function(e) { $(this).toggleClass('troll'); };
		$('#donor-list tbody tr').hover( troll, troll );
	}

});
