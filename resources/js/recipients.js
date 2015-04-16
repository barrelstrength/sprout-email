/**
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.resources
 */

(function($) {


var RecipientAdmin = Garnish.Base.extend(
{
	$lists: null,
	$selectedList: null,

	init: function()
	{
		this.$lists = $('#lists');
		this.$selectedList = this.$lists.find('a.sel:first');
		this.addListener($('#newlistbtn'), 'activate', 'addNewList');

		var $listSettingsBtn = $('#listsettingsbtn');

		if ($listSettingsBtn.length)
		{
			var menuBtn = $listSettingsBtn.data('menubtn');

			menuBtn.settings.onOptionSelect = $.proxy(function(elem)
			{
				var action = $(elem).data('action');

				switch (action)
				{
					case 'rename':
					{
						this.renameSelectedList();
						break;
					}
					case 'delete':
					{
						this.deleteSelectedList();
						break;
					}
				}
			}, this);
		}
	},

	addNewList: function()
	{
		var name = this.promptForListName('');

		if (name)
		{
			var data = {
				name: name
			};

			Craft.postActionRequest('sproutEmail/defaultMailer/saveRecipientList', data, $.proxy(function(response, textStatus)
			{
				if (textStatus == 'success')
				{
					if (response.success)
					{
						location.href = Craft.getUrl('sproutemail/recipients/'+response.list.id);
					}
					else if (response.errors)
					{
						var errors = this.flattenErrors(response.errors);
						alert(Craft.t('Could not create the list:')+"\n\n"+errors.join("\n"));
					}
					else
					{
						Craft.cp.displayError();
					}
				}

			}, this));
		}
	},

	renameSelectedList: function()
	{
		var oldName = this.$selectedList.data('name'),
			newName = this.promptForListName(oldName);

		if (newName && newName != oldName)
		{
			var data = {
				id:   this.$selectedList.data('id'),
				name: newName
			};

			Craft.postActionRequest('sproutEmail/defaultMailer/saveRecipientList', data, $.proxy(function(response, textStatus)
			{
				if (textStatus == 'success')
				{
					if (response.success)
					{
						location.href = Craft.getUrl('sproutemail/recipients/'+response.list.id);
					}
					else if (response.errors)
					{
						var errors = this.flattenErrors(response.errors);
						alert(Craft.t('Could not rename the list:')+"\n\n"+errors.join("\n"));
					}
					else
					{
						Craft.cp.displayError();
					}
				}
			}, this));
		}
	},

	promptForListName: function(oldName)
	{
		return prompt(Craft.t('What do you want to name your list?'), oldName);
	},

	deleteSelectedList: function()
	{
		if (confirm(Craft.t('Are you sure you want to delete this list and all its recipients?')))
		{
			var data = {
				id: this.$selectedList.data('id')
			};

			Craft.postActionRequest('sproutEmail/defaultMailer/deleteRecipientList', data, $.proxy(function(response, textStatus)
			{
				if (textStatus == 'success')
				{
					if (response.success)
					{
						location.href = Craft.getUrl('sproutemail/recipients');
					}
					else
					{
						Craft.cp.displayError();
					}
				}
			}, this));
		}
	},

	flattenErrors: function(responseErrors)
	{
		var errors = [];

		for (var attribute in responseErrors)
		{
			errors = errors.concat(responseErrors[attribute]);
		}

		return errors;
	}
});


Garnish.$doc.ready(function()
{
	Craft.RecipientAdmin = new RecipientAdmin();
});


})(jQuery);
