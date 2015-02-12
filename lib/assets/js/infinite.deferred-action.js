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
	var remainingIds = _.keys(self.items);
	jQuery.each(data.items, function(id, item) {
		remainingIds = _.without(remainingIds, id);
		foundItems = true;
		if (self.items[id] === undefined) {
			self.items[id] = {};
			self.items[id].$canvas = $("<div />", {'class': 'list-group-item'}).prependTo(self.$itemList);
			self.items[id].$name = $("<h5 />", {'class': 'list-group-item-heading'}).appendTo(self.items[id].$canvas);
			self.items[id].$duration = $("<span />", {'class': 'label label-primary'}).appendTo(self.items[id].$canvas);
			self.items[id].$status = $("<span />", {'class': 'label'}).appendTo(self.items[id].$canvas);
			self.items[id].$action = $("<a />", {'class': 'btn btn-default btn-xs pull-right'}).hide().html('Download').appendTo(self.items[id].$canvas);
			self.items[id].$cancel = $("<a />", {'class': 'btn btn-warning btn-xs pull-right', 'href': item.data.cancelUrl, 'data-handler': 'background'}).hide().html('Cancel').appendTo(self.items[id].$canvas);
			self.items[id].$dismiss = $("<a />", {'class': 'deferred-action-dismiss close pull-right', 'href': item.data.dismissUrl, 'data-handler': 'background'}).hide().html('<span class="aria-hidden">&times;</span>').prependTo(self.items[id].$canvas);
		}
		if (item.data.dismissUrl) {
			self.items[id].$dismiss.attr('href', item.data.dismissUrl).show();
		} else {
			self.items[id].$dismiss.hide();
		}
		self.items[id].$canvas.show();
		self.items[id].$name.html(item.data.descriptor);
		self.items[id].$status.removeClass('label-default label-primary label-success label-info label-warning label-danger');
		self.items[id].$action.removeClass('btn-default btn-primary btn-success btn-info btn-warning btn-danger');
		self.items[id].$cancel.hide();
		switch (item.status) {
			case 'queued':
				self.items[id].$status.html('Queued');
				self.items[id].$status.addClass('label-default');
				self.items[id].$action.addClass('btn-default');
				self.items[id].$cancel.show();
			break;
			case 'starting':
			case 'running':
				self.items[id].$status.html('Running');
				self.items[id].$status.addClass('label-primary');
				self.items[id].$action.addClass('btn-primary');
			break;
			case 'error':
				self.items[id].$status.html('Error');
				self.items[id].$status.addClass('label-danger');
				self.items[id].$action.addClass('btn-danger');
			break;
			case 'ready':
				self.items[id].$status.html('Done');
				self.items[id].$status.addClass('label-success');
				self.items[id].$action.addClass('btn-success');
			break;
			default:
				self.items[id].$status.html('Unknown');
				self.items[id].$status.addClass('label-warning');
				self.items[id].$action.addClass('btn-warning');
			break;
		}
		self.items[id].$status.attr('title', item.data.result.message);
		if (item.status === 'ready' && item.data.result.download !== undefined) {
			self.items[id].$status.hide();
			self.items[id].$action.html('Download').attr('href', item.data.result.download).show();
			self.items[id].$action.attr('data-handler', '');
			self.items[id].$duration.html(item.date).attr('title', 'Duration: '+ item.duration).show();
		} else {
			self.items[id].$status.show();
			self.items[id].$action.hide();
			if (item.status === 'running') {
				self.items[id].$duration.html(item.duration).attr('title', 'Started: '+ item.date).show();
			} else {
				self.items[id].$duration.hide();
			}

			if (item.data.result.viewLog !== undefined) {
				self.items[id].$duration.hide();
				self.items[id].$action.html('View Log').attr('href', item.data.result.viewLog).show();
				self.items[id].$action.attr('data-handler', 'background');
			}
		}
	});
	jQuery.each(remainingIds, function (index, id) {
		self.items[id].$canvas.hide();
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


function DeferredViewLog($element, params)
{
	this.isInitializing = true;
	InfiniteComponent.call(this);
	var self = this;
	this.log = params.data;
	this.$canvas = $element.addClass('deferrerd-action-log');
	this.config = jQuery.extend(true, {}, this.defaultConfig, params.config);

	this.elements = {};
	this.renderedMessages = {};
	console.log(['viewLog', params]);
	this.renderCanvas();
	this.updateData();
	this.isInitializing = false;
}

DeferredViewLog.prototype = jQuery.extend(true, {}, InfiniteComponent.prototype);
DeferredViewLog.prototype.defaultConfig = {
	'url': false
};

DeferredViewLog.prototype.updateData = function() {
	var self = this;
	if (this.log.ended === null || !this.log.ended) {
		this.updateTimer = setTimeout(function() {
			self.refresh();
		}, 5000);
	}
	if (this.log.data.result.messages !== undefined) {
		this.updateMessages();
	}
	if (this.log.data.result.progress !== undefined && this.log.data.result.progress) {
		this.elements.$topProgress.show();
		if (this.elements.progressBar === undefined) {
			this.elements.progressBar = {};
			this.elements.progressBar.$canvas = $("<div />", {'class': 'list-group-item'}).appendTo(this.elements.$topProgressList);
			this.elements.progressBar.$title = $("<div />", {'class': 'list-group-item-heading'}).html('<strong>Overall Progress</strong>').appendTo(this.elements.progressBar.$canvas);
			this.elements.progressBar.$wrapper = $("<div />", {'class': 'progress'}).appendTo(this.elements.progressBar.$canvas);
			this.elements.progressBar.$progress = $("<div />", {'class': 'progress-bar progress-bar-striped active', 'role': 'progressbar', 'aria-valuenow': 0, 'aria-valuemin': 0, 'aria-valuemax': 100}).appendTo(this.elements.progressBar.$wrapper);
		}
		this.elements.progressBar.percentage = this.log.data.result.progress;
		this.elements.progressBar.$progress.html(this.elements.progressBar.percentage +"%").attr('aria-valuenow', this.elements.progressBar.percentage).css({'width': this.elements.progressBar.percentage+'%'});
		if (this.log.ended !== null) {
			this.elements.progressBar.$progress.removeClass('active');
		}
	} else {
		this.elements.$topProgress.hide();
	}
	this.drawInfo(this.log);
};

DeferredViewLog.prototype.drawInfo = function (info) {
	var self = this;
	var detailConfig = {
		'started': {
			'icon': 'fa fa-play',
			'label': 'Date Started'
		},
		'ended': {
			'icon': 'fa fa-stop',
			'label': 'Date Ended'
		},
		'duration': {
			'icon': 'fa fa-clock-o',
			'label': 'Duration'
		},
		'peak_memory': {
			'icon': 'fa fa-tachometer',
			'label': 'Peak Memory'
		},
		'status': {
			'icon': 'fa fa-cogs',
			'label': 'Status',
			'value': function(values, $detail, $detailValue) {
				var value = values['status'];
				$detail.show();
				$detail.removeClass('list-group-item-success list-group-item-info list-group-item-warning list-group-item-danger');
				switch (value) {
					case 'queued':
						$detail.addClass('list-group-item-warning');
						$detailValue.html('Queued');
					break;
					case 'ready':
						$detail.addClass('list-group-item-success');
						$detailValue.html('Success');
					break;
					case 'running':
						$detail.addClass('list-group-item-info');
						$detailValue.html('Running');
					break;
					case 'error':
						$detail.addClass('list-group-item-danger');
						$detailValue.html('Error');
					break;
					case 'interrupted':
						$detail.addClass('list-group-item-danger');
						$detailValue.html('Error (Interrupted)');
					break;
					default:
						$detail.hide();
					break;
				}
			}
		},
		'log_status': {
			'icon': 'fa fa-cog',
			'label': 'Log Status',
			'value': function(values, $detail, $detailValue) {
				var value = values['log_status'];
				$detail.show();
				$detail.removeClass('list-group-item-success list-group-item-info list-group-item-warning list-group-item-danger');
				switch (value) {
					case 'fine':
						$detail.addClass('list-group-item-success');
						$detailValue.html('No Errors or Warnings');
					break;
					case 'warning':
						$detail.addClass('list-group-item-warning');
						$detailValue.html('Contains Warnings');
					break;
					case 'error':
						$detail.addClass('list-group-item-danger');
						$detailValue.html('Contains Error');
					break;
					default:
						$detail.hide();
					break;
				}
			}
		}
	}
	if (this.elements.$detailsList === undefined) {
		self.elements.$detailsList = $("<div />", {'class': 'list-group'}).appendTo(this.elements.$details.$body);
		self.elements.details = {};
		self.elements.detailValues = {};
		jQuery.each(detailConfig, function(key, config) {
			self.elements.details[key] = $("<div />", {'class': 'list-group-item', 'title': config.label}).appendTo(self.elements.$detailsList);
			var $row = $("<div />", {'class': 'row'}).appendTo(self.elements.details[key]);
			var cssClasses = '';
			if (config.icon) {
				cssClasses = 'col-xs-9';
				$row.append($("<div />").addClass('col-xs-1').append($("<div />", {'class': config.icon + ' detail-icon'})));
			}
			self.elements.detailValues[key] = $("<div />", {'class': 'detail-value ' + cssClasses}).appendTo($row);
		});
	}
	
	jQuery.each(detailConfig, function(key, config) {
		if (info[key] === undefined || !info[key]) {
			self.elements.details[key].hide();
		} else {
			self.elements.details[key].show();
			if (config.value !== undefined) {
				config.value(info, self.elements.details[key], self.elements.detailValues[key])
			} else {
				self.elements.detailValues[key].html(info[key]);
			}
		}
	});

}

DeferredViewLog.prototype.updateMessages = function() {
	var self = this;
	if (this.log.data.result.messages !== undefined && this.log.data.result.messages) {
		this.elements.$messages.show();
		jQuery.each(this.log.data.result.messages, function(index, message) {
			if (self.renderedMessages[index] === undefined) {
				self.renderedMessages[index] = $("<div />", {'class': 'expandable list-group-item'}).prependTo(self.elements.$messageList);
				switch (message.level) {
					case '_e':
						self.renderedMessages[index].addClass('list-group-item-danger');
					break;
					case '_w':
						self.renderedMessages[index].addClass('list-group-item-warning');
					break;
					default:
						self.renderedMessages[index].addClass('list-group-item-info');
					break;
				}
				self.renderedMessages[index].html(message.message);
				if (message.data !== null) {
					$("<code />").addClass('expanded-only log-data-output preformatted').html(message.data).appendTo(self.renderedMessages[index]);
				}
				var timeBadge = $('<span />', {'class': 'badge pull-right'}).html("+"+ message.fromStart);
				timeBadge.attr('title', 'Duration: ' + message.duration +'; Memory: '+ message.memory).prependTo(self.renderedMessages[index]);
				self.renderedMessages[index].checkExpandable();
			}
		});
	} else {
		this.elements.$messages.hide();
	}
};

DeferredViewLog.prototype.renderCanvas = function() {
	this.elements.$grid = $("<div />", {'class': 'row'}).appendTo(this.$canvas);
	this.elements.$topProgress = $("<div />", {'class': 'panel panel-default'}).prependTo(this.$canvas);
	this.elements.$leftContainer = $("<div />", {'class': 'col-sm-4'}).appendTo(this.elements.$grid);
	this.elements.$left = $("<div />", {'class': 'progress-sidebar'}).appendTo(this.elements.$leftContainer);
	//this.elements.$left.data('offset-top', 10).progressAffix();
	this.elements.$right = $("<div />", {'class': 'col-sm-8'}).appendTo(this.elements.$grid);

	this.elements.$details = $("<div />", {'class': 'panel panel-default'}).appendTo(this.elements.$left);
	this.elements.$messages = $("<div />", {'class': 'panel panel-default'}).appendTo(this.elements.$right);

	this.elements.$details.$title = $("<div />", {'class': 'panel-heading'}).appendTo(this.elements.$details);
	$("<div />", {'class': 'panel-title'}).html('Details').appendTo(this.elements.$details.$title);
	this.elements.$details.$body = $("<div />", {'class': 'panel-body'}).appendTo(this.elements.$details);

	this.elements.$topProgressList = $("<div />", {'class': 'list-group'}).appendTo(this.elements.$topProgress);
	this.elements.$messages.$title = $("<div />", {'class': 'panel-heading'}).appendTo(this.elements.$messages);
	$("<div />", {'class': 'panel-title'}).html('Messages').appendTo(this.elements.$messages.$title);
	this.elements.$messages.$body = $("<div />", {'class': 'panel-body'}).appendTo(this.elements.$messages);
	this.elements.$messageList = $("<div />", {'class': 'list-group'}).appendTo(this.elements.$messages.$body);
};

DeferredViewLog.prototype.refresh = function() {
	var self = this;
	var ajax = {};
	ajax['url'] = this.config.url;
	ajax['success'] = function(result) {
		if (result.data === undefined) { return; }
		self.log = result;
		self.updateData();
	}
	jQuery.ajax(ajax);
};


$preparer.add(function(context) {
	$("[data-deferred-action]").each(function() {
		deferredNavItem = new DeferredNavItem($(this), $(this).data('deferred-action'));
		$(this).attr('data-deferred-action', false);
		return false;
	});

	$("[data-deferred-action-log]").each(function() {
		deferredNavItem = new DeferredViewLog($(this), $(this).data('deferred-action-log'));
		$(this).attr('data-deferred-action-log', false);
		return false;
	});
});