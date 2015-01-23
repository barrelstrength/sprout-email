var SproutModal = function ()
{
};

SproutModal.prototype.init = function ()
{
	return this;
};

/**
 * Allows us to post to a controller action and register a callback a la NodeJS
 *
 * @example
 * var payload = {action: 'plugin/controller/action'};
 * var callback = function(error, data) {};
 *
 * @note
 * The action is required and must be provided in the payload
 *
 * @param object payload
 * @param function callback
 */
SproutModal.prototype.postToControllerAction = function runControllerAction(payload, callback)
{
	var request = {
		url     : window.location,
		type    : "POST",
		data    : payload,
		cache   : false,
		dataType: "json",

		error: function handleFailedRequest(xhr, status, error)
		{
			callback(error);
		},

		success: function handleSuccessfulRequest(response)
		{
			callback(null, response);
		}
	};

	$.ajax(request);
};

SproutModal.prototype.create = function (content)
{
	// For later reference withing different scopes
	var self = this;

	// Modal setup
	var $modal   = $("#sproutmodal").clone();
	var $content = $("#content", $modal).html(content);
	var $spinner = $(".spinner", $modal);
	var $actions = $(".actions", $modal);

	$modal.removeClass("hidden");

	// Instantiate and show
	var modal = new Garnish.Modal($modal);

	$("#close", $modal).off().on("click", function ()
	{
		modal.hide();
	});

	$("#cancel", $modal).off().on("click", function ()
	{
		modal.hide();
	});

	$actions.off().on("click", function (e)
	{
		e.preventDefault();

		var $self = $(e.target);

		$spinner.removeClass("hidden");

		self.postToControllerAction($self.data(), function handleResponse(error, response)
		{
			if (error)
			{
				console.log(error);
				return;
			}

			modal = self.create(response.content);
			$spinner.addClass("hidden");
			modal.updateSizeAndPosition();
		});
	});

	return modal;
};

$(document).ready(function ()
{
	var $buttons = $(".prepare, .preview");

	$buttons.on("click", function (e)
	{
		e.preventDefault();

		var $t = $(e.target);

		var modal = new SproutModal();

		console.log($t.data());

		modal.init().postToControllerAction($t.data(), function handle(error, response)
		{
			if (error)
			{
				console.log(error);

				return;
			}

			modal.create(response.content);
		});
	});
});
