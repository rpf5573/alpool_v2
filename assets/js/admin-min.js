window.redirect_wait_time = 100;

/**
 * Common AnsPress functions and constructor.
 * @author Rahul Aryan
 * @license GPL 3+
 * @since 4.0
 */

// For preventing global namespace pollution, keep everything in AnsPress object.
window.AnsPress = _.extend({
	models: {},
	views: {},
	collections: {},
	modals: {},
	loadTemplate: function(id){
		if(jQuery('#apTemplate').length==0)
			jQuery('<script id="apTemplate" type="text/html"></script>').appendTo('body');

		jQuery.get(apTemplateUrl + '/' + id + ".html", function(html){
			var tempCont = jQuery('#apTemplate');
			tempCont.text(html + tempCont.text());
			AnsPress.trigger('templateLoaded');
		});
	},
	getTemplate: function(templateId){
		return function(){
			if(jQuery('#apTemplate').length==0)
				return '';

			var regex = new RegExp("#START BLOCK "+templateId+" #([\\S\\s]*?)#END BLOCK "+templateId+" #", "g");
			var match = regex.exec(jQuery('#apTemplate').text());

			if(match == null)
				return '';

			if(match[1]) return match[1];
		}
	},
	isJSONString: function(str) {
		try {
			return jQuery.parseJSON(str);
		} catch (e) {
			return false;
		}
	},
	ajaxResponse: function(data){
		data = jQuery(data);
		if( typeof data.filter('#ap-response') === 'undefined' ){
			console.log('Not a valid AnsPress ajax response.');
			return {};
		}
		var parsedJSON = this.isJSONString(data.filter('#ap-response').html());
		if(!parsedJSON || parsedJSON === 'undefined' || !_.isObject(parsedJSON))
			return {};

		return parsedJSON;
	},
	ajax: function(options){
		var self = this;
		options = _.defaults(options, {
			url: ajaxurl,
			method: 'POST',
		});

		// Convert data to query string if object.
		if(_.isString(options.data))
			options.data = jQuery.apParseParams(options.data);

		if(typeof options.data.action === 'undefined')
			options.data.action = 'ap_ajax';

		var success = options.success;
		delete options.success;
		options.success = function(data){
			console.dir( data );

			var context = options.context||null;
			var parsedData = self.ajaxResponse(data);
			if(parsedData.snackbar){
				AnsPress.trigger('snackbar', parsedData)
			}

			if(typeof success === 'function'){
				data = jQuery.isEmptyObject(parsedData) ? data : parsedData;
				success(data, context);
			}
		};

		console.dir( options );

		return jQuery.ajax(options);
	},
	uniqueId: function() {
		return jQuery('.ap-uid').length;
	},
	showLoading: function(elm) {
		/*hide any existing loading icon*/
		AnsPress.hideLoading(elm);
		var customClass = jQuery(elm).data('loadclass')||'';
		var isText = jQuery(elm).is('input[type="text"]');
		var uid = this.uniqueId();

		if(jQuery(elm).is('button')||jQuery(elm).is('.ap-btn')){
			jQuery(elm).addClass('show-loading');
			$loading = jQuery('<span class="ap-loading-span"></span>');
			$loading.height(jQuery(elm).height());
			$loading.width(jQuery(elm).height());
			jQuery(elm).append($loading);
		} else {
			var el = jQuery('<div class="ap-loading-icon ap-uid '+customClass+ (isText ? ' is-text' : '') +'" id="apuid-' + uid + '"><i></i></div>');
			jQuery('body').append(el);
			var offset = jQuery(elm).offset();
			var height = jQuery(elm).outerHeight();
			var width = isText ? 40 : jQuery(elm).outerWidth();
			el.css({
				top: offset.top,
				left: isText ? offset.left + jQuery(elm).outerWidth() - 40 : offset.left,
				height: height,
				width: width
			});

			jQuery(elm).data('loading', '#apuid-' + uid);
			return '#apuid-' + uid;
		}
	},

	hideLoading: function(elm) {
		if(jQuery(elm).is('button')||jQuery(elm).is('.ap-btn')){
			jQuery(elm).removeClass('show-loading');
			jQuery(elm).find('.ap-loading-span').remove();
			jQuery(elm).prop('disabled', false);
		}else if( 'all' == elm ){
			jQuery('.ap-loading-icon').hide();
		}else{
			jQuery(jQuery(elm).data('loading')).hide();
		}
	},
	getUrlParam: function(key) {
		var qs = jQuery.apParseParams(window.location.href);
		if(typeof key !== 'undefined')
			return typeof qs[key] !== 'undefined' ? qs[key] : null;

		return qs;
	},
	modal: function(name, args){
		args = args||{};
		if(typeof this.modals[name] !== 'undefined'){
			return this.modals[name];
		}

		this.modals[name] = new AnsPress.views.Modal(_.extend({
			id: 'ap-modal-' + name,
			title: aplang.loading,
			content: '',
			size: 'medium'
		}, args));

		jQuery('#anspress').append(this.modals[name].render().$el);
		return this.modals[name];
	},
	hideModal: function(name, runCb){
		if(typeof runCb === 'undefined')
			runCb = true;

		if(typeof this.modals[name] !== 'undefined'){
			this.modals[name].hide(runCb);
			delete this.modals[name];
		}
	},
	removeHash: function(){
		var scrollV, scrollH, loc = window.location;
		// Prevent scrolling by storing the page's current scroll offset
		scrollV = document.body.scrollTop;
		scrollH = document.body.scrollLeft;

    if ('pushState' in history){

			history.pushState('', document.title, loc.pathname + loc.search);
			Backbone.history.navigate('/');
		} else {
			loc.hash = '';
		}
		// Restore the scroll offset, should be flicker free
		document.body.scrollTop = scrollV;
		document.body.scrollLeft = scrollH;

	},

	loadCSS: function(href){
		var cssLink = document.createElement('link');
		cssLink.rel = 'stylesheet';
		cssLink.href = href;
		var head = document.getElementsByTagName('head')[0];
		head.parentNode.insertBefore(cssLink, head);
	}
}, Backbone.Events);

_.templateSettings = {
	evaluate:    /<#([\s\S]+?)#>/g,
	interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
	escape:      /\{\{([^\}]+?)\}\}(?!\})/g,
};

(function($){
	//pass in just the context as a $(obj) or a settings JS object
	$.fn.autogrow = function(opts) {
		var that = $(this).css({
			overflow: 'hidden',
			resize: 'none'
		}) //prevent scrollies
		,
		selector = that.selector,
		defaults = {
				context: $(document) //what to wire events to
				,
				animate: true //if you want the size change to animate
				,
				speed: 50 //speed of animation
				,
				fixMinHeight: true //if you don't want the box to shrink below its initial size
				,
				cloneClass: 'autogrowclone' //helper CSS class for clone if you need to add special rules
				,
				onInitialize: false //resizes the textareas when the plugin is initialized
			};
			opts = $.isPlainObject(opts) ? opts : {
				context: opts ? opts : $(document)
			};
			opts = $.extend({}, defaults, opts);
			that.each(function(i, elem) {
				var min, clone;
				elem = $(elem);
			//if the element is "invisible", we get an incorrect height value
			//to get correct value, clone and append to the body.
			if (elem.is(':visible') || parseInt(elem.css('height'), 10) > 0) {
				min = parseInt(elem.css('height'), 10) || elem.innerHeight();
			} else {
				clone = elem.clone().addClass(opts.cloneClass).val(elem.val()).css({
					position: 'absolute',
					visibility: 'hidden',
					display: 'block'
				});
				$('body').append(clone);
				min = clone.innerHeight();
				clone.remove();
			}
			if (opts.fixMinHeight) {
				elem.data('autogrow-start-height', min); //set min height
			}
			elem.css('height', min);
			if (opts.onInitialize && elem.length) {
				resize.call(elem[0]);
			}
		});
			opts.context.on('keyup paste focus', selector, resize);

			function resize(e) {
				var box = $(this),
				oldHeight = box.innerHeight(),
				newHeight = this.scrollHeight,
				minHeight = box.data('autogrow-start-height') || 0,
				clone;
			if (oldHeight < newHeight) { //user is typing
				this.scrollTop = 0; //try to reduce the top of the content hiding for a second
				opts.animate ? box.stop().animate({
					height: newHeight
				}, opts.speed) : box.innerHeight(newHeight);
			} else if (!e || e.which == 8 || e.which == 46 || (e.ctrlKey && e.which == 88)) { //user is deleting, backspacing, or cutting
				if (oldHeight > minHeight) { //shrink!
					//this cloning part is not particularly necessary. however, it helps with animation
					//since the only way to cleanly calculate where to shrink the box to is to incrementally
					//reduce the height of the box until the $.innerHeight() and the scrollHeight differ.
					//doing this on an exact clone to figure out the height first and then applying it to the
					//actual box makes it look cleaner to the user
					clone = box.clone()
					//add clone class for extra css rules
					.addClass(opts.cloneClass)
					//make "invisible", remove height restriction potentially imposed by existing CSS
					.css({
						position: 'absolute',
						zIndex: -10,
						height: ''
					})
					//populate with content for consistent measuring
					.val(box.val());
					box.after(clone); //append as close to the box as possible for best CSS matching for clone
					do { //reduce height until they don't match
					newHeight = clone[0].scrollHeight - 1;
					clone.innerHeight(newHeight);
				} while (newHeight === clone[0].scrollHeight);
					newHeight++; //adding one back eliminates a wiggle on deletion
					clone.remove();
					box.focus(); // Fix issue with Chrome losing focus from the textarea.
					//if user selects all and deletes or holds down delete til beginning
					//user could get here and shrink whole box
					newHeight < minHeight && (newHeight = minHeight);
					oldHeight > newHeight && opts.animate ? box.stop().animate({
						height: newHeight
					}, opts.speed) : box.innerHeight(newHeight);
				} else { //just set to the minHeight
					box.innerHeight(minHeight);
				}
			}
		}
		return that;
	};

	jQuery.fn.apScrollTo = function(elem, toBottom, speed) {
		toBottom = toBottom||false;
		var parentPos = $(this).scrollTop() - $(this).offset().top;
		var top = toBottom ? $(this).offset().top + $(this).height() : $(this).offset().top;
		$('html, body').stop();
		$('html, body').animate({
			scrollTop: top
		}, speed == undefined ? 1000 : speed);

		if(elem != undefined)
			$(this).animate({
				scrollTop: parentPos + $(elem).offset().top
			}, speed == undefined ? 1000 : speed);

		return this;
	};

	AnsPress.views.Snackbar = Backbone.View.extend({
		id: 'ap-snackbar',
		template: '<div class="ap-snackbar<# if(success){ #> success<# } #>">{{message}}</div>',
		hover: false,
		initialize: function(){
			AnsPress.on('snackbar', this.show, this);
		},
		events: {
			'mouseover': 'toggleHover',
			'mouseout': 'toggleHover',
		},
		show: function(data){
			var self = this;
			this.data = data.snackbar;
			this.data.success = data.success;
			this.$el.removeClass('snackbar-show');
			this.render();
			setTimeout(function(){
				self.$el.addClass('snackbar-show');
			}, 0);
			this.hide();
		},
		toggleHover:function(){
			clearTimeout(this.hoveTimeOut);
			this.hover = !this.hover;
			if(!this.hover)
				this.hide();
		},
		hide: function(){
			var self = this;
			if(!self.hover)
				this.hoveTimeOut = setTimeout(function(){
					self.$el.removeClass('snackbar-show');
				}, 5000);
		},
		render: function(){
			if(this.data){
				var t = _.template(this.template);
				this.$el.html(t(this.data));
			}
			return this;
		}
	});

	AnsPress.views.Modal = Backbone.View.extend({
		className: 'ap-modal',
		template: "<div class=\"ap-modal-body<# if(typeof size !== 'undefined'){ #> ap-modal-{{size}}<# } #>\"><div class=\"ap-modal-header\"><# if(typeof title !== 'undefined' ){ #><strong>{{title}}</strong><# } #><a href=\"#\" ap=\"close-modal\" class=\"ap-modal-close\"><i class=\"apicon-x\"></i></a></div><div class=\"ap-modal-content\"><# if(typeof content !== 'undefined'){ #>{{{content}}}<# } #></div><div class=\"ap-modal-footer\"><# if(typeof buttons !== 'undefined'){ #><# _.each(buttons, function(btn){ #><a class=\"ap-modal-btn <# if(typeof btn.class !== 'undefined') { #>{{btn.class}}<# } #>\" href=\"#\" <# if(typeof btn.cb !== 'undefined') { #>ap=\"{{btn.cb}}\" apquery=\"{{btn.query}}\"<# } #>>{{btn.label}}</a><# }); #><# } #></div></div><div class=\"ap-modal-backdrop\"></div>",
		events: {
			'click [ap="close-modal"]': 'clickHide',
			'click [ap="modal-click"]': 'clickAction',
		},
		initialize: function(opt){
			opt.title = opt.title||aplang.loading;
			this.data = opt;
		},
		render: function(){
			$('html').css('overflow', 'hidden');
			var t = _.template(this.template);
			this.$el.html(t(this.data));
			return this;
		},
		clickHide: function(e){
			e.preventDefault();
			this.hide();
		},
		hide: function(runCb){
			if(typeof runCb === 'undefined')
				runCb = true;
			this.remove();
			$('html').css('overflow', '');
			if(this.data.hideCb&&runCb) this.data.hideCb(this); // Callback
			var name = this.data.id.replace('ap-modal-', '');
			if(typeof AnsPress.modals[name] !== 'undefined')
				delete AnsPress.modals[name];
		},
		setContent: function(html){
			this.$el.find('.ap-modal-content').html(html);
		},
		setTitle: function(title){
			this.$el.find('.ap-modal-header strong').text(title);
		},
		setFooter: function(content){
			this.$el.find('.ap-modal-footer').html(content);
		},
		clickAction: function(e){
			e.preventDefault();
			var targ = $(e.target);
			q = targ.data('apquery');

			if(q.cb){
				q.element = targ;
				AnsPress.trigger(q.cb, q);
			}
		}
	});

	var re = /([^&=]+)=?([^&]*)/g;
	var decode = function (str) {
			return decodeURIComponent(str.replace(/\+/g, ' '));
	};
	$.apParseParams = function (query) {
		// recursive function to construct the result object
		function createElement(params, key, value) {
			key = key + '';
			// if the key is a property
			if (key.indexOf('.') !== -1) {
				// extract the first part with the name of the object
				var list = key.split('.');
				// the rest of the key
				var new_key = key.split(/\.(.+)?/)[1];
				// create the object if it doesnt exist
				if (!params[list[0]]) params[list[0]] = {};
				// if the key is not empty, create it in the object
				if (new_key !== '') {
						createElement(params[list[0]], new_key, value);
				} else console.warn('parseParams :: empty property in key "' + key + '"');
			} else
			// if the key is an array
			if (key.indexOf('[') !== -1) {
				// extract the array name
				var list = key.split('[');
				key = list[0];
				// extract the index of the array
				var list = list[1].split(']');
				var index = list[0]
				// if index is empty, just push the value at the end of the array
				if (index == '') {
					if (!params) params = {};
					if (!params[key] || !$.isArray(params[key])) params[key] = [];
					params[key].push(value);
				} else
				// add the value at the index (must be an integer)
				{
					if (!params) params = {};
					if (!params[key] || !$.isArray(params[key])) params[key] = [];
					params[key][parseInt(index)] = value;
				}
			} else
			// just normal key
			{
					if (!params) params = {};
					params[key] = value;
			}
		}
		// be sure the query is a string
		query = query + '';
		if (query === '') query = window.location + '';
		var params = {}, e;
		if (query) {
			// remove # from end of query
			if (query.indexOf('#') !== -1) {
					query = query.substr(0, query.indexOf('#'));
			}

			// remove ? at the begining of the query
			if (query.indexOf('?') !== -1) {
					query = query.substr(query.indexOf('?') + 1, query.length);
			} else return {};
			// empty parameters
			if (query == '') return {};
			// execute a createElement on every key and value
			while (e = re.exec(query)) {
				var key = decode(e[1]);
				var value = decode(e[2]);
				createElement(params, key, value);
			}
		}
		return params;
	};
})(jQuery);

(function($){
	AnsPress.Common = {
		init: function(){
			AnsPress.on('showImgPreview', this.showImgPreview);
			AnsPress.on('formPosted', this.imageUploaded);
			AnsPress.on('ajaxBtnDone', this.uploadModal);
			AnsPress.on('ajaxBtnDone', this.commentModal);

			AnsPress.on('showModal', this.showModal);
		},
		readUrl: function(input, el) {
			if (input.files && input.files[0]) {
				var reader = new FileReader();
				reader.onload = function(e) {
					AnsPress.trigger('showImgPreview', e.target.result, el.find('.ap-upload-list'));
				}
				reader.readAsDataURL(input.files[0]);
			}
		},
		uploadModal: function(data){
			if(data.action != 'ap_upload_modal' || ! data.html)
				return;

			$modal = AnsPress.modal('imageUpload', {
				title: data.title,
				content: data.html,
				size: 'small',
			});

			var file = $modal.$el.find('input[type="file"]');
			file.on('change', function(){
				$modal.$el.find('.ap-img-preview').remove();
				AnsPress.Common.readUrl(this, $modal.$el);
			});
		},
		showImgPreview: function(src, el){
			$('<img class="ap-img-preview" src="'+src+'" />').appendTo(el);
		},
		imageUploaded: function(data){
			if(data.action!=='ap_image_upload' || typeof tinymce === 'undefined')
				return;

			if(data.files)
				$.each(data.files, function(old, newFile){
					tinymce.activeEditor.insertContent('<img src="'+newFile+'" />');
				});

			AnsPress.hideModal('imageUpload');
		},
		showModal: function(modal){
			modal.size = modal.size||'medium';
			AnsPress.modal(modal.name, {
				title: modal.title,
				content: modal.content,
				size: modal.size,
			});
		}
	};
})(jQuery);

jQuery(document).ready(function($){
	AnsPress.Common.init();

	var apSnackbarView = new AnsPress.views.Snackbar();
	$('body').append(apSnackbarView.render().$el);

	$( document ).click(function (e) {
		e.stopPropagation();
		if (!$(e.target).is('.ap-dropdown-toggle') && !$(e.target).closest('.open').is('.open') && !$(e.target).closest('form').is('form')) {
				$('.ap-dropdown').removeClass('open');
		}
	});

	// Dropdown toggle
	$('body').on('click', '.ap-dropdown-toggle, .ap-dropdown-menu > a', function(e){
		e.preventDefault();
		$('.ap-dropdown').not($(this).closest('.ap-dropdown')).removeClass('open');
		$(this).closest('.ap-dropdown').toggleClass('open');
	});

	$('body').on('click', '.ap-droptogg', function(e){
		e.preventDefault();
		$(this).closest('.ap-dropdown').removeClass('open');
		$(this).closest('#noti-dp').hide();
	});

	// Ajax button.
	$('body').on('click', '[apajaxbtn]', function(e){
		var self = this;
		e.preventDefault();

		if($(this).attr('aponce') != 'false' && $(this).is('.loaded'))
			return;

		var self = $(this);
		var query = JSON.parse(self.attr('apquery'));

		AnsPress.showLoading(self);
		AnsPress.ajax({
			data: query,
			success: function(data){
				if($(this).attr('aponce')!= 'false')
					$(self).addClass('loaded');

				AnsPress.hideLoading(e.target);

				AnsPress.trigger('ajaxBtnDone', data);

				if(typeof data.btn !== 'undefined')
					if(data.btn.hide) self.hide();

				if(typeof data.cb !== 'undefined')
					AnsPress.trigger(data.cb, data, e.target);

				// Open modal.
				if(data.modal){
					AnsPress.trigger('showModal', data.modal);
				}
			}
		})
	});

	function apAddRepeatField(el, values){
		values = values||false;
		var args = $(el).data('args');
		args['index'] = $(el).find('[datarepeatid]').length;
		var template = $('#'+args.key+'-template').text();

		var t = _.template(template);
		t = t(args);
		var regex = /(class|id|for)="([^"]+)"/g;

		var t = t.replace(regex, function(match, group) {
			return match.replace(/[[\]]/g, '');
		});

		var html = $('<div class="ap-repeatable-item" datarepeatid="'+args.index+'">'+ t +'<a href="#" class="ap-repeatable-delete">'+args.label_delete+'</a></div>');
		$.each(values, function(childName, v){
			html.find('[name="'+args.key+'['+args.index+']['+childName+']"]').val(v);
		});

		var errors = $('#'+args.key+'-errors');

		if ( errors.length > 0 ) {
			var errors_json = JSON.parse(errors.html());
			$.each(errors_json, function(i, err){
				$.each(err, function(field, messages){
					var fieldWrap = html.find('[name="'+args.key+'['+i+']['+field+']"]').closest('.ap-form-group');
					fieldWrap.addClass('ap-have-errors');
					var errContain = $('<div class="ap-field-errors"></div>');
					$.each(messages, function(code, msg){
						errContain.append('<span class="ap-field-error code-'+code+'">'+msg+'</span>');
					})
					$(errContain).insertAfter(fieldWrap.find('label'));
				});
			});
		}

		$(el).find('.ap-fieldrepeatable-item').append(html);
	}

	$('[data-role="ap-repeatable"]').each(function(){
		var self = this;


		$(this).find('.ap-repeatable-add').on('click', function(e){
			e.preventDefault();

			var self = $(this);
			var query = JSON.parse(self.attr('apquery'));
			AnsPress.showLoading(self);

			$count = $('[name="'+query.id+'-groups"]');
			query.current_groups = $count.val();
			$count.val(parseInt(query.current_groups)+1);

			$nonce = $('[name="'+query.id+'-nonce"]');
			query.current_nonce = $nonce.val();

			AnsPress.ajax({
				data: query,
				success: function(data){
					AnsPress.hideLoading(e.target);
					$(data.html).insertBefore(self);
					$nonce.val(data.nonce);
				}
			})
		});

		$(this).on('click', '.ap-repeatable-delete', function(e){
			e.preventDefault();
			$(this).closest('.ap-form-group').remove();
		});

	});

	$('body').on('click', '.ap-form-group', function(){
		$(this).removeClass('ap-have-errors');
	});

	$('body').on('click', 'button.show-loading', function(e){
		e.preventDefault();
	});

	$('body').on( 'submit', '[apform]', function(e){
		e.preventDefault();
		var self = $(this);
		var submitBtn = $(this).find('button[type="submit"]');

		if(submitBtn.length>0)
			AnsPress.showLoading(submitBtn);

    $(this).ajaxSubmit({
			url: ajaxurl,
			beforeSerialize: function() {
				if(typeof tinymce !== 'undefined')
					tinymce.triggerSave();

				$('.ap-form-errors, .ap-field-errors').remove();
				$('.ap-have-errors').removeClass('ap-have-errors');
			},
			success: function(data) {
				setTimeout(function(){
					if(submitBtn.length>0)
						AnsPress.hideLoading(submitBtn);

					data = AnsPress.ajaxResponse(data);
					if(data.snackbar){
						AnsPress.trigger('snackbar', data)
					}

					if(typeof grecaptcha !== 'undefined' && typeof widgetId1 !== 'undefined')
						grecaptcha.reset(widgetId1);

					AnsPress.trigger('formPosted', data);

					if(typeof data.form_errors !== 'undefined'){
						$formError = $('<div class="ap-form-errors"></div>').prependTo(self);

						$.each(data.form_errors, function(i, err){
							$formError.append('<span class="ap-form-error ecode-'+i+'">'+err+'</div>');
						});

						$.each(data.fields_errors, function(i, errs){
							$('.ap-field-'+i).addClass('ap-have-errors');
							$('.ap-field-'+i).find('.ap-field-errorsc').html('<div class="ap-field-errors"></div>');

							$.each(errs.error, function(code, err){
								$('.ap-field-' + i).find('.ap-field-errors').append('<span class="ap-field-error ecode-'+code+'">'+err+'</span>');
							});
						});

						self.apScrollTo();
					} else if(typeof data.hide_modal !== undefined){
						// Hide modal
						AnsPress.hideModal(data.hide_modal);
					}

					if(typeof data.redirect !== 'undefined'){
						window.location = data.redirect;
					}
				}, window.redirect_wait_time);
			}
		});
	});
	$(document).keyup(function(e) {
		if (e.keyCode == 27) {
			$lastModal = $('.ap-modal').last();
			if ( $lastModal.length>0 ){
				$name = $lastModal.attr('id').replace('ap-modal-', '');
				AnsPress.hideModal($name);
			}
		}
	});

	AnsPress.on( 'loadedMoreActivities', function(data, e){
		$(data.html).insertAfter($('.ap-activities:last-child'));
		$(e).closest('.ap-activity-item').remove();
	});

	AnsPress.tagsPreset = {
		tags: {
			delimiter: ',',
			valueField: 'term_id',
			labelField: 'name',
			searchField: 'name',
			persist: false,
			render: {
				option: function(item, escape) {
					return '<div class="ap-tag-sugitem">' +
						'<span class="name">' + escape(item.name) + '</span>' +
						'<span class="count">' + escape(item.count) + '</span>' +
						'<span class="description">' + escape(item.description) + '</span>' +
					'</div>';
				}
			},
			create: false,
			maxItems: 4
		}
	}

	AnsPress.tagElements = function ($el){
		var type = $el.data('type');
		var jsoptions = $el.data('options');
		var options = $('#'+jsoptions.id+'-options').length > 0 ? JSON.parse($('#'+jsoptions.id+'-options').html()) : {};
		var defaults = AnsPress.tagsPreset[type];
		defaults.options = options;
		defaults.maxItems = jsoptions.maxItems;

		if(false !== jsoptions.create){
			defaults.create = function(input) {
				return {
					term_id: input,
					name: input,
					description: '',
					count: 0,
				}
			};
		}

		defaults.load = function(query, callback) {
			if (!query.length) return callback();
			jQuery.ajax({
				url: ajaxurl,
				type: 'GET',
				dataType: 'json',
				data: {
					action: 'ap_search_tags',
					q: query,
					__nonce: jsoptions.nonce,
					form: jsoptions.form,
					field: jsoptions.field,
				},
				error: function() {
					callback();
				},
				success: function(res) {
					callback(res);
				}
			});
		};
		$el.selectize(defaults);
	}

	$('[aptagfield]').each(function(){
		AnsPress.tagElements($(this));
	});

	$('#anspress').on('click', '.ap-remove-parent', function(e){
		e.preventDefault();
		$(this).parent().remove();
	})
});

window.AnsPress.Helper = {
	toggleNextClass: function(el){
		jQuery(el).closest('.ap-field-type-group').find('.ap-fieldgroup-c').toggleClass('show');
	}
};

// @codekit-prepend "common.js";

/* on start */

'use strict';

(function($) {
  AnsPress.views.Answer = Backbone.Model.extend({
    defaults: {
      ID: '',
      content: '',
      deleteNonce: '',
      comments: '',
      activity : '',
      author: '',
      editLink: '',
			trashLink: '',
      status: '',
      selected: '',
			avatar: '',
    }
  });

  AnsPress.collections.Answers = Backbone.Collection.extend({
    url: ajaxurl+'?action=ap_ajax&ap_ajax_action=get_all_answers&question_id='+currentQuestionID,
    model: AnsPress.views.Answer
  });

  AnsPress.views.Answer = Backbone.View.extend({
    className: 'ap-ansm clearfix',
    id: function(){
      return this.model.get('ID');
    },
    initialize: function(options){
      if(options.model)
        this.model = options.model;
    },
    template: function(){
      return $('#ap-answer-template').html()
    },
    render: function(){
      if(this.model){
				console.dir( this.model );
				// console.dir( this.$el );
				var t = _.template(this.template());
				this.$el.html(t(this.model.toJSON()));
				if ( this.model.attributes.status == 'trash' ) {
					//console.dir( this.$el.find('.answer-actions').remove() );
					this.$el.find('.answer-actions > .edit').remove();
					this.$el.find('.answer-actions > .trash').remove();
					this.$el.find('.answer-actions > .select_answer').remove();
					this.$el.find('.answer-actions > .clone').remove();
				} else {
					this.$el.find('.answer-actions > .delete').remove();
					this.$el.find('.answer-actions > .untrash').remove();
				}

				if ( this.model.attributes.status == 'private' ) {
					this.$el.find('.answer-actions > .clone').remove();
					this.$el.find('.answer-actions .select_answer').remove();
				}

				if ( this.model.attributes.selected !== "0" ) {
					this.$el.addClass('selected');
					// var checkbox = this.$el.find('.answer-actions .select_answer_checkbox')
					this.$el.find('.answer-actions .select_answer_checkbox').prop('checked', true);
				}
      }
      return this;
    }
  });

  AnsPress.views.Answers = Backbone.View.extend({
    initialize: function(options){
      this.model = options.model;
      this.model.on('add', this.answerFetched, this);
    },
    renderItem: function(ans){
      var view = new AnsPress.views.Answer({model: ans});
      this.$el.append(view.render().$el);
    },
    render: function(){
      var self = this;
      if(this.model){
        this.model.each(function(ans){
          self.renderItem(ans);
        });
      }

      return this;
    },
    answerFetched: function(answer){
      this.renderItem(answer);
    }
  });

  if( currentQuestionID ) {
    var answers = new AnsPress.collections.Answers();
    var answersView = new AnsPress.views.Answers({model: answers, el: '#answers-list'});
    answersView.render();
    answers.fetch();
  }

})(jQuery);

jQuery(function () {
	jQuery.fn.apAjaxQueryString = function () {
		var query = jQuery(this).data('query').split("::");

		var newQuery = {};

		newQuery['action'] = 'ap_ajax';
		newQuery['ap_ajax_action'] = query[0];
		newQuery['__nonce'] = query[1];
		newQuery['args'] = {};

		var newi = 0;
		jQuery.each(query,function(i){
			if(i != 0 && i != 1){
				newQuery['args'][newi] = query[i];
				newi++;
			}
		});

		return newQuery;
	};

	/* create document */
	APjs.admin = new APjs.admin();
	/* need to call init manually with jQuery */
	APjs.admin.initialize();
});

/* namespace */
window.APjs = {};
APjs.admin = function () {};

(function($){
	APjs.admin.prototype = {

		/* automatically called */
		initialize: function () {
			this.renameTaxo();
			this.editPoints();
			this.savePoints();
			this.deleteFlag();
			this.ajaxBtn();
			this.statistic();
		},

		renameTaxo: function () {
			jQuery('.ap-rename-taxo').click(function (e) {
				e.preventDefault();
				jQuery.ajax({
					url: ajaxurl,
					data: {
						action: 'ap_taxo_rename'
					},
					context: this,
					success: function (data) {
						jQuery(this).closest('.error').remove();
						location.reload();
					}
				});
				return false;
			});
		},
		editPoints: function () {
			jQuery('.wp-admin').on('click', '[data-action="ap-edit-reputation"]', function (e) {
				e.preventDefault();
				var id = jQuery(this).attr('href');
				jQuery.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'ap_edit_reputation',
						id: id
					},
					context: this,
					dataType: 'json',
					success: function (data) {
						if (data['status']) {
							jQuery('#ap-reputation-edit').remove();
							jQuery('#anspress-reputation-table').hide();
							jQuery('#anspress-reputation-table').after(data['html']);
						}
					}
				});
			});
		},
		savePoints: function () {
			jQuery('.wp-admin').on('submit', '[data-action="ap-save-reputation"]', function (e) {
				e.preventDefault();
				jQuery('.button-primary', this).attr('disabled', 'disabled');
				var id = jQuery(this).attr('href');
				jQuery.ajax({
					type: 'POST',
					url: ajaxurl,
					cache: false,
					data: jQuery(this).serialize({
						checkboxesAsBools: true
					}),
					context: this,
					dataType: 'json',
					success: function (data) {
						if (data['status']) {
							jQuery('.wrap').empty().html(data['html']);
						}
					}
				});

				return false;
			});
		},
		deleteFlag: function () {
			jQuery('[data-action="ap-delete-flag"]').click(function (e) {
				e.preventDefault();
				jQuery.ajax({
					type: 'POST',
					url: ajaxurl,
					data: jQuery(this).attr('href'),
					context: this,
					success: function (data) {
						jQuery(this).closest('.flag-item').remove();
					}
				});
			});
		},
		ajaxBtn: function () {
			$('.ap-ajax-btn').on('click', function (e) {
				e.preventDefault();
				var q = $(this).apAjaxQueryString();
				console.dir( q );
				$.ajax({
					url: ajaxurl,
					data: q,
					context: this,
					type: 'POST',
					success: function (data) {
						if (typeof $(this).data('cb') !== 'undefined') {
							var cb = $(this).data("cb");
							if (typeof APjs.admin[cb] === 'function') {
								APjs.admin[cb](data, this);
							}
						}
					}
				});

			});
		},
		replaceText: function (data, elm) {
			$(elm).closest('li').find('strong').text(data);
		},
		statistic: function() {
			// top : sticky_header_bar_offset
			// init sticky header
			// position: 'absolute',
    	// 	scrollContainer: function($table){
			// 		var the = $table.closest('.wrapper');
			// 		console.dir( the );
			// 		return the;
			// 	}
			var sticky_header_bar_offset = $('#wpadminbar')[0].clientHeight;
			$(".ap-list-table.terms").floatThead({
				position: 'absolute',
    		scrollContainer: function($table){
					return $table.closest('.wrapper');
				}
			});

			var btns = $('.yas-table-open-btn');
			if ( btns.length > 0 ) {
				btns.on('click', function(e){
					var self = $(this);
					var query = JSON.parse(self.attr('apquery'));
					
					var loading = new Loading({
						discription:	query.term_name,
						defaultApply: true,
					});

					function scrollFromLeft( position ) {
						$('#wpbody').animate({
							scrollLeft : position
						}, 1000);
					}

					$.ajax({
						type: "POST",
						url: ajaxurl,
						data: query,
						success: function (response) {

							// remove existing yas table first
							var group_table_container = self.closest('.statistic-group-table-container');
							var table_container = group_table_container.children( '.statistic-table-container' );
							if ( table_container.length == 2 ) {
								$(table_container[1]).remove();
							}

							// append yas table to right
							group_table_container.addClass('width-double');
							group_table_container.append( response );

							var yas_form = group_table_container.find('.list-table-form.yas');

							// ready for navigation
							var btn_go_to_yas = group_table_container.find('.go-to-yas');
							btn_go_to_yas.css( 'display', 'block' );
							btn_go_to_yas.on( 'click', function(){
								scrollFromLeft(yas_form.offset().left);
							} );

							var btn_back_to_terms = group_table_container.find('.back-to-terms');
							btn_back_to_terms.css( 'display', 'block' );
							btn_back_to_terms.on( 'click', function(){
								scrollFromLeft(0);
							} );

							// scroll to right automatically
							setTimeout( function(){
								loading.out();
								scrollFromLeft(yas_form.offset().left);
							}, 300 );

							group_table_container.find(".ap-list-table.yas").floatThead({
								position: 'absolute',
								scrollContainer: function($table){
									return $table.closest('.wrapper');
								}
							});
							
						}
					});
				});
			}
		}
	}

	$(document).ready(function() {
		$('#select-question-for-answer').on('keyup', function () {
			if (jQuery.trim(jQuery(this).val()) == '')
				return;
			jQuery.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'ap_ajax',
					ap_ajax_action: 'suggest_similar_questions',
					value: jQuery(this).val(),
					is_admin: true
				},
				success: function (data) {
					var textJSON = jQuery(data).filter('#ap-response').html();
					if (typeof textJSON !== 'undefined' && textJSON.length > 2) {
						data = JSON.parse(textJSON);
					}
					console.log(data);
					if (typeof data['html'] !== 'undefined')
						jQuery('#similar_suggestions').html(data['html']);
				},
				context: this,
			});
		});

		$('[data-action="ap_media_uplaod"]').click(function (e) {
			e.preventDefault();
			$btn = jQuery(this);
			var image = wp.media({
				title: jQuery(this).data('title'),
				// mutiple: true if you want to upload multiple files at once
				multiple: false
			}).open().on('select', function (e) {
				// This will return the selected image from the Media Uploader, the result is an object
				var uploaded_image = image.state().get('selection').first();
				// We convert uploaded_image to a JSON object to make accessing it easier
				// Output to the console uploaded_image
				var image_url = uploaded_image.toJSON().url;
				var image_id = uploaded_image.toJSON().id;

				// Let's assign the url value to the input field
				jQuery($btn.data('urlc')).val(image_url);
				jQuery($btn.data('idc')).val(image_id);

				if (!jQuery($btn.data('urlc')).prev().is('img'))
					jQuery($btn.data('urlc')).before('<img id="ap_category_media_preview" src="' + image_url + '" />');
				else
					jQuery($btn.data('urlc')).prev().attr('src', image_url);
			});
		});

		$('[data-action="ap_media_remove"]').click(function (e) {
			e.preventDefault();
			$('input[data-action="ap_media_value"]').val('');
			$('img[data-action="ap_media_value"]').remove();
		});

		$('.checkall').click(function () {
			var checkbox = $(this).closest('.ap-tools-ck').find('input[type="checkbox"]:not(.checkall)');
			checkbox.prop('checked', $(this).prop("checked"));
		})

		$('#' + $('#ap-tools-selectroles').val()).slideDown();

		$('#ap-tools-selectroles').change(function () {
			var id = '#' + $(this).val();
			$('.ap-tools-roleitem').hide();
			$(id).fadeIn(300);
		})

	});

})(jQuery);

// Question Category Disable
(function($){
	if ( typeof expert_categories !== 'undefined' ) {
		var checkboxes = $('#taxonomy-question_category li input[type=checkbox]');
		if ( checkboxes.length > 0 ) {
			checkboxes.attr('disabled', 'true');
			// expert_categories = Object.values( expert_categories );
			if ( expert_categories.length > 0 ) {
				expert_categories.forEach(function(id){
					checkboxes.each(function(){
						if ( this.id == ('in-question_category-' + id ) || this.id == ('in-popular-question_category-' + id ) ) {
							$(this).removeAttr('disabled');
						}
					});
				});
			}
		}
	}
})(jQuery);

function select_answer(checkbox) {
	var checkboxes = jQuery('.answer-actions .select_answer_checkbox');
	if ( checkbox.checked ) {
		jQuery.each( checkboxes, function(key, el){
			el.checked = false;
		} );
		checkbox.checked = true;
	}
}


