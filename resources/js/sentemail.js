(function ($) {
	Craft.SproutSentEmail = Garnish.Base.extend(
		{
			init: function(html)
			{
				// This will disable current file global css styles
				if($('#loaderFrame').length)
				{
					$('#loaderFrame')[0].contentDocument.body.innerHTML = html;
				}
			}
		})

})(jQuery);
