/**
 * Defines the SproutModal constructor
 *
 * @constructor
 */
var SproutModal = function ()
{
};

/**
 * Gives us the ability to augment the object in the future
 *
 * @returns {SproutModal}
 */
SproutModal.prototype.init = function ()
{
	var self = this;

	$(".prepare").on("click", function (e)
	{
		e.preventDefault();

		var $t = $(e.target);
		var modalLoader = null;
		if ($t.data('mailer') === 'copypaste')
		{
			modalLoader = self.createLoadingModal();
		}
		
		self.postToControllerAction($t.data(), function handle(error, response)
		{
			if (error)
			{
				return self.createErrorModal(error);
			}

			if (!response.success)
			{
				return self.createErrorModal(response.message);
			}

			// Close error loading modal if no error on request
			if(modalLoader != null)
			{
				modalLoader.hide();
				modalLoader.destroy();
			}


			self.create(response.content);
		});
	});

	return this;
};

/**
 * Gives us the ability to post to a controller action and register a callback a la NodeJS
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

	request.data[Craft.csrfTokenName] = Craft.csrfTokenValue;

	$.ajax(request);
};

/**
 * Creates a modal window instance from content returned from server and does so recursively
 *
 * @param string content
 * @returns {Garnish.Modal}
 */
SproutModal.prototype.create = function (content)
{
	// For later reference within different scopes
	var self = this;

	// Modal setup
	var $modal   = $("#sproutmodal").clone();
	var $content = $("#content", $modal).html(content);
	var $spinner = $(".spinner", $modal);
	var $actions = $(".actions", $modal);

	// Gives mailers a chance to add their own event handlers
	$(document).trigger("sproutModalBeforeRender", $content);

	$modal.removeClass("hidden");

	// Instantiate and show
	var modal = new Garnish.Modal($modal);

	$("#close", $modal).on("click", function ()
	{
		modal.hide();
		modal.destroy();
	});

	$("#cancel", $modal).on("click", function ()
	{
		modal.hide();
		modal.destroy();
	});

	$actions.on("click", function (e)
	{
		e.preventDefault();

		var $self = $(e.target);

		if ($self.hasClass('preventAction'))
		{
			$self.removeClass('preventAction');

			return;
		}

		$spinner.removeClass("hidden");

		self.postToControllerAction($self.data(), function handleResponse(error, response)
		{
			if (error)
			{
				return self.createErrorModal(error);
			}

			if (!response.success)
			{
				return self.createErrorModal(response.message);
			}

			$spinner.addClass("hidden");

			modal.hide();
			modal.destroy();

			modal = self.create(response.content);

			modal.updateSizeAndPosition();
		});
	});

	return modal;
};

SproutModal.prototype.createErrorModal = function(error)
{
	var $content = $('#sproutmodalcontent').clone();

	$('.innercontent', $content).html(error);

	var modal = new SproutModal();

	modal.create($content.html());
};

SproutModal.prototype.createLoadingModal = function()
{
	var $content = $('#sproutmodalloading').clone();

	$('.innercontent', $content);

	var modal = new SproutModal();

	return modal.create($content.html());

};
