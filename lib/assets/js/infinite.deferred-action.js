var deferredNavItem;

InfiniteInstructionHandler.prototype.handleDeferredAction = function() {
	var self = this;
	if (deferredNavItem) {
		deferredNavItem.refresh();
	}
	return true;
};

function DeferredNavItem($element, config)
{
	this.updateTimer = null;
	this.$element = $element;
	this.$span = $element.find('span').first();
	this.config = config['_'];
	this.handleData(config);
}

DeferredNavItem.prototype.handleData = function(data) {

	if (data.running) {
		this.updateTimer = setTimeout(function() {
			self.refresh();
		}, 5000);
	} else {
		this.updateTimer = setTimeout(function() {
			self.refresh();
		}, 60000);
	}
	console.log(data);
};

DeferredNavItem.prototype.refresh = function() {
	var self = this;
	var ajax = {};
	ajax['url'] = this.config['_'].url;
	ajax['success'] = function(result) {
		if (result['_'] === undefined) { return; }
		self.handleData(result);
	}
	jQuery.ajax(ajax);
};

$("[data-deferred-action]").each(function() {
	deferredNavItem = new DeferredNavItem($(this), $(this).data('deferred-action'));
	$(this).attr('data-deferred-action', false);
	return false;
});