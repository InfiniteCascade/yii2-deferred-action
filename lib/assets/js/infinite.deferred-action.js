var deferredNavItem;

InfiniteInstructionHandler.prototype.handleDeferredAction = function() {
	var self = this;
	if (deferredNavItem) {
		deferredNavItem.refresh(true);
	}
	return true;
};

function DeferredNavItem($element, config)
{
	var self = this;
	this.firstOpen = true;
	this.updateTimer = null;
	this.opened = false;
	this.mostRecentEvent = false;
	console.log(config);
	this.$element = $element;
	$element.addClass('deferred-action-trigger');
	this.$element.hide().removeClass('hidden');
	this.$link = $('a', $element).first();
	this.$span = $('span', this.$link).first();
	this.$canvas = $("<div />");
	this.$itemList = $("<div />", {'class': 'list-group'}).appendTo(this.$canvas);
	this.$link.popover({
		'placement': 'bottom',
		'content': this.$canvas,
		'title': 'Background Tasks',
		'html': true,
		'trigger': 'manual'
	});
	this.$link.click(function() {
		if (self.opened) {
			self.close();
		} else {
			self.open();
		}
		return false;
	});
	$(document).on('click', function(e) {
		if ($(e.target).is(self.$link) || $(e.target).is(self.$canvas) || self.$canvas.find(e.target).length !== 0) {
			return;
		}
		self.close();
	});
	$(document).keyup(function(e) {
		if (e.keyCode == 27) {   // esc
			self.close();
		}
	});
	this.items = {};
	this.config = config['_'];
	this.handleData(config);
}

DeferredNavItem.prototype.handleData = function(data) {
	var self = this;
	clearTimeout(this.updateTimer);
	if (data.running) {
		this.updateTimer = setTimeout(function() {
			self.refresh();
		}, 5000);
	} else {
		this.updateTimer = setTimeout(function() {
			self.refresh();
		}, 60000);
	}
	var foundItems = false;
	jQuery.each(data.items, function(id, item) {
		foundItems = true;
		if (self.items[id] === undefined) {
			self.items[id] = {};
			self.items[id].$canvas = $("<div />", {'class': 'list-group-item'}).prependTo(self.$itemList);
			self.items[id].$name = $("<h5 />", {'class': 'list-group-item-heading'}).appendTo(self.items[id].$canvas);
			self.items[id].$duration = $("<span />", {'class': 'label label-default pull-left'}).appendTo(self.items[id].$canvas);
			self.items[id].$status = $("<span />", {'class': 'label'}).appendTo(self.items[id].$canvas);
			self.items[id].$download = $("<a />", {'class': 'label btn btn-primary btn-sm pull-right'}).hide().html('Download').appendTo(self.items[id].$canvas);
		}
		self.items[id].$name.html(item.data.descriptor);
		self.items[id].$status.removeClass('label-default label-primary label-success label-info label-warning label-danger');
		switch (item.status) {
			case 'queued':
				self.items[id].$status.html('Queued');
				self.items[id].$status.addClass('label-default');
			break;
			case 'starting':
			case 'running':
				self.items[id].$status.html('Running');
				self.items[id].$status.addClass('label-primary');
			break;
			case 'error':
				self.items[id].$status.html('Error');
				self.items[id].$status.addClass('label-danger');
			break;
			case 'ready':
				self.items[id].$status.html('Done');
				self.items[id].$status.addClass('label-success');
			break;
			default:
				self.items[id].$status.html('Unknown');
				self.items[id].$status.addClass('label-warning');
			break;
		}
		self.items[id].$status.attr('title', item.data.result.message);
		if (item.status === 'ready' && item.data.result.download !== undefined) {
			self.items[id].$status.hide();
			self.items[id].$download.attr('href', item.data.result.download).show();
			self.items[id].$duration.html(item.date).attr('title', 'Duration: '+ item.duration);
		} else {
			self.items[id].$status.show();
			self.items[id].$download.hide();
			if (item.status === 'running') {
				self.items[id].$duration.html(item.duration).attr('title', 'Started: '+ item.date);
			} else {
				self.items[id].$duration.hide();
			}
		}
	});

	if (foundItems) {
		this.$element.show();
		if (!self.firstOpen && data.mostRecentEvent && self.mostRecentEvent !== data.mostRecentEvent) {
			self.open();
		}
		self.mostRecentEvent = data.mostRecentEvent;
	} else {
		this.$element.hide();
	}
	if (data.running) {
		this.$span.addClass('fa-spin-slow');
	} else {
		this.$span.removeClass('fa-spin-slow');
	}
	self.firstOpen = false;
};

DeferredNavItem.prototype.open = function() {
	this.$link.popover('show');
	this.opened = true;
};

DeferredNavItem.prototype.close = function() {
	this.$link.popover('hide');
	this.opened = false;
};

DeferredNavItem.prototype.refresh = function(openAfter) {
	var self = this;
	var ajax = {};
	if (openAfter === undefined) {
		openAfter = false;
	}
	ajax['url'] = this.config.url;
	ajax['success'] = function(result) {
		if (result['_'] === undefined) { return; }
		self.handleData(result);
		if (openAfter) {
			self.open();
		}
	}
	jQuery.ajax(ajax);
};

$("[data-deferred-action]").each(function() {
	deferredNavItem = new DeferredNavItem($(this), $(this).data('deferred-action'));
	$(this).attr('data-deferred-action', false);
	return false;
});