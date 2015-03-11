
function DeferredViewLog($element, params)
{
	this.isInitializing = true;
	TealComponent.call(this);
	var self = this;
	this.log = params.data;
	this.$canvas = $element.addClass('deferrerd-action-log');
	this.config = jQuery.extend(true, {}, this.defaultConfig, params.config);

	this.elements = {};
	this.renderedMessages = {};
	this.renderCanvas();
	this.updateData();
	this.isInitializing = false;
}

DeferredViewLog.prototype = jQuery.extend(true, {}, TealComponent.prototype);
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
					case 'success':
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
	$("[data-deferred-action-log]", context).each(function() {
		deferredNavItem = new DeferredViewLog($(this), $(this).data('deferred-action-log'));
		$(this).attr('data-deferred-action-log', false);
		return false;
	});
});