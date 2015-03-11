var deferredNavItem;

TealInstructionHandler.prototype.handleDeferredAction = function() {
	var self = this;
	if (deferredNavItem) {
		deferredNavItem.refresh(true);
	}
	return true;
};

function DeferredNavItem($element, config)
{
	this.isInitializing = true;
	TealComponent.call(this, $element);
	var self = this;
	this.pendingRefresh = false;
	this.firstOpen = true;
	this.updateInterval = null;
	this.updateIntervalResource = null;
	this.opened = false;
	this.hasInteraction = false;
	this.mostRecentEvent = false;
	this.$element = $element;
	$element.addClass('deferred-action-trigger');
	this.$element.hide().removeClass('hidden');
	this.$link = $('a', $element).first();
	this.$span = $('span', this.$link).first();
	this.$canvas = $("<div />", {'class': 'deferred-action-canvas'});
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
	this.renderedInteractionHashes = [];
	this.currentInteractionHash = {};
	this.config = config['_'];
	this.handleData(config);
	this.isInitializing = false;
}
DeferredNavItem.prototype = jQuery.extend(true, {}, TealComponent.prototype);

DeferredNavItem.prototype.setInterval = function (time) {
	var self = this;
	if (time === undefined) {
		time = 5000;
	}
	if (time !== this.updateInterval) {
		clearInterval(this.updateIntervalResource);
		this.updateIntervalResource = setInterval(function() {
			if (!self.pendingRefresh) {
				self.pendingRefresh = true;
				self.refresh();
			}
		}, time);
		this.updateInterval = time;
	}
};

DeferredNavItem.prototype.setInteractionStatus = function(hasInteraction) {
	var self = this;
	if (hasInteraction !== this.hasInteraction) {
		this.hasInteraction = hasInteraction;
		if (this.hasInteractionInterval === undefined) {
			this.hasInteractionInterval = null;
		}
		if (hasInteraction) {
			setTimeout(function() { self.open(); }, 1000);
			this.hasInteractionInterval = setInterval(function() {
				self.$link.animate({'color': '#ffb122', 'margin-left': '-5px', 'margin-right': '5px'}, 1000, function() {
					self.$link.animate({'color': '#551a8b', 'margin-left': '5px', 'margin-right': '-5px'}, 1000);
				});
			}, 2000);
		} else {
			clearInterval(this.hasInteractionInterval);
			self.$link.css({'color': null, 'margin-left': null, 'margin-right': null});
		}
	}
};

DeferredNavItem.prototype.handleData = function(data) {
	var self = this;
	self.pendingRefresh = false;
	var foundItems = false;
	var hasInteraction = false;
	var remainingIds = _.keys(self.items);
	if (_.isEmpty(data.items)) {
		this.setInterval(60000);
	} else {
		this.setInterval(5000);
	}
	jQuery.each(data.items, function(id, item) {
		remainingIds = _.without(remainingIds, id);
		foundItems = true;
		if (self.items[id] === undefined) {
			self.items[id] = {};
			self.items[id].$canvas = $("<div />", {'class': 'list-group-item'}).prependTo(self.$itemList);
			self.items[id].$name = $("<h5 />", {'class': 'list-group-item-heading'}).appendTo(self.items[id].$canvas);
			self.items[id].$duration = $("<span />", {'class': 'label label-primary'}).appendTo(self.items[id].$canvas);
			self.items[id].$status = $("<span />", {'class': 'label'}).appendTo(self.items[id].$canvas);
			self.items[id].$actions = $("<div />", {'class': 'btn-group btn-group-xs pull-right'}).hide().appendTo(self.items[id].$canvas);
			self.items[id].$dismiss = $("<a />", {'class': 'deferred-action-dismiss close pull-right', 'href': item.data.dismissUrl, 'data-handler': 'background'}).hide().html('<span class="aria-hidden">&times;</span>').prependTo(self.items[id].$canvas);
			self.items[id].interactions = self.generatePanel(self.items[id].$canvas, {'label': '<span class="fa fa-exclamation"></span> Attention Required', 'level': 3}, 'warning');
			self.items[id].interactions.$canvas.hide();
		}
		if (item.data.dismissUrl) {
			self.items[id].$dismiss.attr('href', item.data.dismissUrl).show();
		} else {
			self.items[id].$dismiss.hide();
		}
		var itemHasInteraction = false;
		if (item.status === 'running' && self.hasInteractions(item)) {
			hasInteraction = itemHasInteraction = true;
		}
		self.items[id].$canvas.show();
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
			case 'success':
				self.items[id].$status.html('Done');
				self.items[id].$status.addClass('label-success');
			break;
			default:
				self.items[id].$status.html('Unknown');
				self.items[id].$status.addClass('label-warning');
			break;
		}
		self.items[id].$status.attr('title', item.data.result.message);
		if (itemHasInteraction) {
				self.items[id].interactions.$canvas.show();
				self.renderInteractions(self.items[id].interactions, item);
		} else {
			self.items[id].interactions.$canvas.hide();
		}
		if (!_.isEmpty(item.data.actions)) {
			self.items[id].$actions.show();
			self.items[id].$actions.html('');
			jQuery.each(item.data.actions, function(index, action) {
				var $action = $("<a />", {'href': action.url, 'class': 'btn'}).html(action.label).appendTo(self.items[id].$actions);
				if (action.state === undefined) {
					action.state = 'default';
				}
				$action.addClass('btn-' + action.state);

				delete action.url;
				delete action.label;
				delete action.state;
				$action.attr(action);

			});
		} else {
			self.items[id].$actions.hide();
		}
	});
	
	this.setInteractionStatus(hasInteraction);
	jQuery.each(remainingIds, function (index, id) {
		self.items[id].$canvas.remove();
		delete self.items[id];
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
DeferredNavItem.prototype.hasInteractions = function(item) {
	var self = this;
	if (!item.data.interactions) {
		return false;
	}
	var hasNewInteraction = false;
	if (self.currentInteractionHash[item.id] === undefined) {
		self.currentInteractionHash[item.id] = false;
	}
	jQuery.each(item.data.interactions, function(index, interaction) {
		if (jQuery.inArray(interaction.hash, self.renderedInteractionHashes) !== -1
			&& self.currentInteractionHash[item.id] !== interaction.hash) {
			return true;
		}
		hasNewInteraction = true;
		return false;
	});
	return hasNewInteraction;
};

DeferredNavItem.prototype.renderInteractions = function(interactionPanel, item) {
	var self = this;
	var renderedInteractions = false;
	interactionPanel.$canvas.show();
	jQuery.each(item.data.interactions, function(index, interaction) {
		if (jQuery.inArray(interaction.hash, self.renderedInteractionHashes) !== -1
			&& self.currentInteractionHash[item.id] !== interaction.hash
			) {
			return true;
		}
		self.renderedInteractionHashes.push(interaction.hash);
		renderedInteractions = true;
		if (self.currentInteractionHash[item.id] !== undefined && interaction.hash === self.currentInteractionHash[item.id]) {
			return false;
		}
		self.currentInteractionHash[item.id] = interaction.hash;
		interactionPanel.$body.html('');
		self.renderInteraction(interactionPanel.$body, item, interaction);
		return false;
	});
	if (!renderedInteractions) {
		interactionPanel.$canvas.hide();
	}
};

DeferredNavItem.prototype.renderInteraction = function($canvas, item, interaction) {
	var self = this;
	var $input = false;
	var $form = $("<form />").appendTo($canvas).on('submit', function() {
		var ajax = [];
		ajax.url = self.config.resolveUrl;
		ajax.type = 'POST';
		ajax.data = {
			'id': interaction.id,
			'value': $input.val()
		};
		jQuery.ajax(ajax);
		$canvas.html('');
		self.currentInteractionHash[item.id] = false;
		$("<div />", {'class': 'alert alert-info'}).html('Saving resolution...').appendTo($canvas);
		return false;
	});
	var $item = $("<div />", {'class': 'form-group'}).appendTo($form);
	

	var $label = $("<label />", {'for': 'interaction-value'}).html(interaction.label).appendTo($item);

	switch (interaction.inputType) {
		case 'select': 
			$input = $("<select />", {'name': 'interaction-value', 'id': 'interaction-value'}).renderSelect(interaction.options.options);
		break;
		default:
			$input = $("<input />", {'type': 'text', 'name': 'interaction-value', 'id': 'interaction-value'});
		break;
	}
	$input.appendTo($item);
	var $submit = $("<input />", {'type': 'submit', 'class': 'btn btn-primary btn-sm', 'value': 'Resolve', 'name': 'Submit'}).appendTo($form);
	// $canvas.html('boom: ' +  JSON.stringify(interaction));
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
	this.updateTimer = null;
	if (openAfter === undefined) {
		openAfter = false;
	}
	ajax['url'] = this.config.refreshUrl;
	ajax['success'] = function(result) {
		if (result['_'] === undefined) { return; }
		self.handleData(result);
		if (openAfter) {
			self.open();
		}
	}
	jQuery.ajax(ajax);
};


$preparer.add(function(context) {
	$("[data-deferred-action]", context).each(function() {
		deferredNavItem = new DeferredNavItem($(this), $(this).data('deferred-action'));
		$(this).attr('data-deferred-action', false);
		return false;
	});
});
