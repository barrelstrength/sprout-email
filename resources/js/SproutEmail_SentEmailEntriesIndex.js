if (typeof Craft.SproutEmail === typeof undefined) {
	Craft.SproutEmail = {};
}

/**
 * Class Craft.SproutForms.EntriesIndex
 */
Craft.SproutEmail.SentEmailEntriesIndex = Craft.BaseElementIndex.extend({
	getViewClass: function(mode) {
		switch (mode) {
			case 'table':
				return Craft.SproutEmail.SentEmailEntriesTableView;
			default:
				return this.base(mode);
		}
	},
	getDefaultSort: function() {
		return ['dateCreated', 'desc'];
	}
});

// Register the SproutEmail_SentEmail EntriesIndex class
Craft.registerElementIndexClass('SproutEmail_SentEmail', Craft.SproutEmail.SentEmailEntriesIndex);