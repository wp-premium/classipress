/*
 * ClassiPress theme jQuery functions
 * Written by AppThemes
 * http://www.appthemes.com
 *
 * Left .js uncompressed so it's easier to customize
 */

jQuery(document).ready(function($) {

	/* style the select dropdown menus */
	if ( jQuery.isFunction( jQuery.fn.selectBox ) ) {
		jQuery('select').selectBox({
			menuTransition: 'fade', // default, slide, fade
			menuSpeed: 'fast'
		});

		/* do not apply in step1 of add new listing form */
		jQuery('.form_step #ad-categories select').selectBox('destroy');
		/* do not apply in report listing form */
		jQuery('.reports_form select').selectBox('destroy');
	}

	/* convert header menu into select list on mobile devices */
	if ( jQuery.isFunction( jQuery.fn.tinyNav ) ) {
		jQuery('.header_menu_res .menu').tinyNav({
			active: 'current-menu-item',
			header: classipress_params.text_mobile_navigation,
			header_href: classipress_params.home_url,
			indent: '-',
			excluded: ['#adv_categories']
		});
	}

	/* mouse over main image fade */
	jQuery('.img-main img, .post-gallery img').mouseover(function() {
		jQuery(this).stop().animate( { opacity:0.6 }, 200 );
	}).mouseout(function() {
		jQuery(this).stop().animate( { opacity:1 }, 200 );
	});

	/* initialize the category selection on add-new page */
	if ( jQuery('#step1 .form_step').length > 0 )
		cp_handle_form_category_select();

	/* initialize the image previewer */
	imagePreview();

	/* initialize tabs control of sidebar and home page */
	cp_tab_control();

	/* move welcome widget out of sidebar to the top of home page */
	jQuery(window).bind( "resize", cp_reposition_widgets );
	cp_reposition_widgets();

	/* auto complete the search field with tags */
	jQuery('#s').autocomplete({
		source: function( request, response ) {
			jQuery.ajax({
				url: classipress_params.ajax_url,
				dataType: 'json',
				data: {
					action: 'ajax-tag-search-front',
					tax: classipress_params.appTaxTag,
					term: request.term
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					//alert('Error: ' + errorThrown + ' - ' + textStatus + ' - ' + XMLHttpRequest);
				},
				success: function( data ) {
					response( jQuery.map( data, function( item ) {
						return {
							term: item,
							value: unescapeHtml(item.name)
						};
					}));
				}
			});
		},
		minLength: 2
	});


	/* add data-rel=colorbox attribute to blog post image links */
	jQuery(".entry-content a, .post a").each(function(i, el) {
		var href_value = el.href;
		if ( /\.(jpg|jpeg|png|gif)$/.test( href_value ) ) {
			jQuery(this).has('img').attr('data-rel', 'colorbox');
		}
	});

	/* assign the ColorBox event to elements */
	if ( jQuery.isFunction(jQuery.colorbox) ) {
		jQuery("a[data-rel='colorbox']").colorbox({
			transition: 'fade',
			rel: 'colorbox',
			current: '',
			scrolling: false,
			slideshow: false,
			maxWidth: '100%',
			maxHeight: '100%',
			slideshowAuto: false,
			title: function() { return jQuery(this).find('img').attr('title'); }
		});
		jQuery("#mainImageLink").click(function() {
			jQuery("#thumb1").click();
		});
	}


	/* hide flash elements on ColorBox load */
	jQuery(document).bind("cbox_open", function() {
		jQuery('object, embed, iframe').css({'visibility':'hidden'});
	});
	jQuery(document).bind("cbox_closed", function() {
		jQuery('object, embed, iframe').css({'visibility':'inherit'});
	});


	/* initialize the form validation */
	if ( jQuery.isFunction(jQuery.fn.validate) ) {
		// validate profile fields
		jQuery("#your-profile").validate({errorClass: 'invalid'});

		// sidebar contact form validation
		jQuery('.form_contact').validate({errorClass: 'invalid'});

		// 'add new' form validation
		jQuery('.form_step, .form_edit').validate({
			ignore: '.ignore',
			errorClass: 'invalid',
			errorPlacement: function(error, element) {

				var is_wp_editor = element.hasClass('wp-editor-area');

				if ( is_wp_editor ) {
					var iframe = jQuery('#'+element.attr('id')+'_ifr');
					jQuery(iframe).addClass('tinymce-invalid');
					jQuery(element).addClass('tinymce-invalid');
					jQuery(element).parent().find('.tinymce-invalid').css( { 'border' : '1px solid #C00' } );
				}

				if ( element.attr('type') == 'checkbox' || element.attr('type') == 'radio' ) {
					element.closest('ol').after(error);
				} else if ( jQuery.isFunction( jQuery.fn.selectBox ) && element.is('select') ) {
					if ( jQuery(window).width() > 600 ) {
						var nextelement = jQuery(element).next();
						error.insertAfter(nextelement);
						error.css('display', 'block');
					} else {
						error.insertBefore(element);
					}
				} else {
					if ( jQuery(window).width() > 600 ) {
						error.insertAfter(element);
						error.css('display', 'block');
					} else {
						error.insertBefore(element);
					}
				}
			},
			highlight: function(element, errorClass, validClass) {
				jQuery(element).addClass(errorClass).removeClass(validClass);
				jQuery(element).parent().find('a.selectBox').addClass(errorClass).removeClass(validClass).focus();
				jQuery(element).parent().find('.tinymce-invalid').css( { 'border' : '1px solid #C00' } );
				jQuery(element).parent().find('.tinymce-invalid').css( { 'background-color' : '#FFEBE8'  } );
			},
			unhighlight: function(element, errorClass, validClass) {
				jQuery(element).removeClass(errorClass).addClass(validClass);
				jQuery(element).parent().find('a.selectBox').removeClass(errorClass).addClass(validClass);
				jQuery(element).parent().find('.tinymce-invalid').css( { 'border' : 'none' } );
			},
			submitHandler: function(form) {
				setTimeout(function () {
					var go_val = jQuery('input[type=submit]').val();
					jQuery('input[type=submit]')
						.attr('disabled', true)
						.attr('value', classipress_params.text_processing )
						.data( 'go_value', go_val )
						.addClass('clicked');
				}, 1);
				form.submit();
			}
		});

		// comment form validation
		jQuery("#commentform").validate({
			errorClass: "invalid",
			errorElement: "div",
			errorPlacement: function(error, element) {
				error.insertAfter(element);
			}
		});
		jQuery("#commentform").fadeIn();

	}

	// displays error if images are required and user did not selected any image
	if ( classipress_params.require_images && typeof window.appFileCount != 'undefined' ) {
		jQuery('input[type=submit]').on('click', function() {
			window.appFileCountBrowser = 0;
			jQuery('input[type="file"]').each(function() {
				if ( jQuery(this).val() != '' ) {
					window.appFileCountBrowser += 1;
				}
			});

			if ( window.appFileCountBrowser == 0 && window.appFileCount == 0 ) {
				jQuery( '#app-attachment-upload-container .app-attachment-info .required-image' ).remove();
				jQuery( '#app-attachment-upload-container .app-attachment-info' ).append( '<p class="error notice required-image">' + classipress_params.text_require_images + '</p>' );
				jQuery( '#app-attachment-upload-container .app-attachment-info .required-image' ).delay(5000).fadeOut('slow');
				return false;
			}
		});
	}

	/* initialize the tooltip */
	if ( jQuery.isFunction(jQuery.fn.easyTooltip) ) {
		// tooltip on 'membership' and 'add new' pages
		jQuery("#mainform a").easyTooltip();
	}

	/* makes the tables responsive */
	if ( jQuery.isFunction( jQuery.fn.footable ) ) {
		jQuery('.footable').footable();
	}

	/* toggle reports form */
	jQuery(".edit a.reports_form_link").on( "click", function() {
		jQuery(this).parent().parent().find(".reports_form").slideToggle( 400 );
		return false;
	});

	/* auto select dropdown category if previously selected elsewhere */
	if ( jQuery('#ad_cat_id').val() > 0 ) {
		jQuery('#ad_cat_id').trigger('change');
	}

	// add a special class for all links that should not be overridable by the WP Customizer
	$('a.button, a.btn_orange, .subcat-list .cat-item a, .post-meta a, .comment-bubble a, .tagcloud a, a[class*=tag-link], .paging a.page-numbers').addClass('cp-fixed-color');

	// override the default paging class
	jQuery('.pages a').addClass('btn_orange');

	// dynamically load ads on the home page
	jQuery('.dynamic-content').on( 'click', function() {

		var content = jQuery(this).attr('id');

		var selector = '.' + content + '-placeholder';

		// load only once
		if ( ! jQuery( selector ).length ) {
			return;
		}

		jQuery( selector ).append('<img class="content-loader" src="'+classipress_params.loader+'">');

		var data = {
			action:'cp_dynamic_content',
			content: content,
			security: classipress_params.nonce,
		}

		jQuery.post( classipress_params.ajax_url, data, function(response) {
			// check if ajax has succeeded
			if ( '-1' != response ) {
				jQuery( selector ).after( response );
				jQuery( selector ).remove();
			}
		});

	});

	// payment gateways
	jQuery('.order-gateway button.button, .order-gateway input[type=submit]').addClass('btn_orange');

	// critic plugin dynamic stylings
	jQuery('#critic-review-wrap').addClass('shadowblock');
	jQuery('#critic-review-wrap input[type=submit]').addClass('btn_orange');
	jQuery('#critic-review-wrap .critic-reviews-title').addClass('dotted')

});

window.onpageshow = function() {
	jQuery( '#getcat.clicked' )
		.val( jQuery( '#getcat.clicked' ).data( 'go_value' ) )
		.attr( 'disabled', false )
		.removeClass( 'clicked' );
};

/* Tab Control sidebar and home page */
function cp_tab_control() {
	var tabs = [];
	var tabContainers = [];
	jQuery('ul.tabnavig a').each(function() {
		if ( window.location.pathname.match(this.pathname) ) {
			tabs.push(this);
			tabContainers.push( jQuery(this.hash).get(0) );
		}
	});

	//hide all contrainers except execpt for the one from the URL hash or the first container
	if ( window.location.hash !== "" && window.location.hash.search('block') >= 0 ) {
		jQuery(tabContainers).hide().filter(window.location.hash).show();
		//detecting <a> tab using its "href" which should always equal the hash
		jQuery(tabs).filter( function(index) {
			return ( jQuery(this).attr('href') === window.location.hash );
		}).addClass('selected');
		jQuery('html').scrollTop( jQuery(window.location.hash).parent().position().top );
	} else {
		jQuery(tabContainers).hide().filter(':first').show();
		jQuery(tabs).filter(':first').addClass('selected');
	}

	jQuery(tabs).click(function() {
		// hide all tabs
		jQuery(tabContainers).hide().filter(this.hash).show();
		jQuery(tabs).removeClass('selected');
		jQuery(this).addClass('selected');
		return false;
	});
}


// creates previews of images
function imagePreview() {
	var xOffset = 10;
	var yOffset = 30;

	jQuery('a.preview').hover(function(e) {
		var adTitle = jQuery(this).find('img').attr('alt');
		jQuery('body').append("<div id='preview'><img src='" + jQuery(this).data('rel') + "' alt='' /><p>" + adTitle + "</p></div>");
		jQuery('#preview').css('top', (e.pageY - xOffset) + 'px').css('left', (e.pageX + yOffset) + 'px').fadeIn('fast');
	}, function() {
		jQuery('#preview').remove();
	});

	jQuery('a.preview').mousemove(function(e) {
		jQuery('#preview').css('top', (e.pageY - xOffset) + 'px').css('left', (e.pageX + yOffset) + 'px');
	});
}


// used to unescape any encoded html passed from ajax json_encode (i.e. &amp;)
function unescapeHtml(html) {
	var temp = document.createElement("div");
	temp.innerHTML = html;
	var result = temp.childNodes[0].nodeValue;
	temp.removeChild(temp.firstChild);
	return result;
}


// highlight search results
jQuery.fn.extend({
	highlight: function(search, insensitive, hclass) {
		var regex = new RegExp("(<[^>]*>)|(\\b"+ search.replace(/([-.*+?^${}()|[\]\/\\])/g,"\\$1") +")", insensitive ? "ig" : "g");
		return this.html(this.html().replace(regex, function(a, b, c) {
			return ( ( a.charAt(0) === "<" ) ? a : "<span class=\""+ hclass +"\">" + c + "</span>" );
		}));
	}
});


/* Form Checkboxes Values Function */
function addRemoveCheckboxValues(cbval, cbGroupVals) {
	var a;
	if ( cbval.checked === true ) {
		a = document.getElementById(cbGroupVals);
		a.value += ',' + cbval.value;
		a.value = a.value.replace(/^\,/, '');
	} else {
		a = document.getElementById(cbGroupVals);
		a.value = a.value.replace(cbval.value + ',', '');
		a.value = a.value.replace(cbval.value, '');
		a.value = a.value.replace(/\,$/, '');
	}
}


/* General Trim Function  */
function trim(str) {
	var	str = str.replace(/^\s\s*/, '');
	var	ws = /\s/;
	var	i = str.length;

	while (ws.test(str.charAt(--i)));
	return str.slice(0, i + 1);
}


/* Used for enabling the image for uploads */
function enableNextImage(a, i) {
	jQuery('#upload' + i).removeAttr('disabled');
}


/* Position price currency */
function cp_currency_position( price ) {
	var position = classipress_params.currency_position;
	var currency = classipress_params.ad_currency;

	switch ( position ) {
		case 'left':
			return currency + price;
		case 'left_space':
			return currency + ' ' + price;
		case 'right':
			return price + currency;
		default: // right_space
			return price + ' ' + currency;
	}

}


/* Handle price slider in refine results widget */
function cp_show_price_slider( min_price, max_price, min_value, max_value, precise_price ) {
	max_value = ( ( ! precise_price && max_value <= 1000 ) ? max_price : ( ( precise_price && max_value >= 1000 ) ? 1000 : max_value ) );

	jQuery('#slider-range').slider( {
		range: true,
		min: min_price,
		max: ( ( ! precise_price ) ? max_price : 1000 ),
		step: 1,
		values: [ min_value, max_value ],
		slide: function(event, ui) {
			jQuery('#amount').val( cp_currency_position( ui.values[0] ) + ' - ' + cp_currency_position( ui.values[1] ) );
		}
	});

	jQuery('#amount').val( cp_currency_position( jQuery('#slider-range').slider('values', 0) ) + ' - ' + cp_currency_position( jQuery('#slider-range').slider('values', 1) ) );

}


/* Moves welcome widget out of sidebar */
function cp_reposition_widgets() {
	if ( jQuery(window).width() > 800 ) {
		jQuery('.content_left #welcome_widget').prependTo('.content_right');
		jQuery('.content_left #refine_widget').prependTo('.content_right');
	} else {
		jQuery('.content_right #welcome_widget').prependTo('.content_left');
		jQuery('.content_right #refine_widget').prependTo('.content_left');
	}
}


/* Used for deleting ad on customer dashboard */
function confirmBeforeDeleteAd() {
	return confirm(classipress_params.text_before_delete_ad);
}


/* Used for selecting category on add-new form */
function cp_handle_form_category_select() {
	//if on page load the parent category is already selected, load up the child categories
	jQuery('#catlvl0').attr('level', 0);

	//bind the ajax lookup event to #ad_cat_id object
	jQuery(document).on('change', '#ad_cat_id', function() {
		currentLevel = parseInt(jQuery(this).parent().attr('level'), 10);
		cp_get_subcategories(jQuery(this), 'catlvl', currentLevel + 1, classipress_params.ad_parent_posting);

		//rebuild the entire set of dropdowns based on which dropdown was changed
		jQuery.each(jQuery(this).parent().parent().children(), function(childLevel, childElement) {
			if ( currentLevel + 1 < childLevel )
				jQuery(childElement).remove();

			if ( currentLevel + 1 === childLevel )
				jQuery(childElement).removeClass('hasChild');
		});

		//find the deepest selected category and assign the value to the "chosenCateory" field
		if ( jQuery(this).val() > 0 ) {
			jQuery('#chosenCategory input:first').val(jQuery(this).val());
		} else if ( jQuery('#catlvl' + ( currentLevel - 1 ) + ' select').val() > 0) {
			jQuery('#chosenCategory input:first').val(jQuery('#catlvl' + ( currentLevel - 1 ) + ' select').val());
		} else {
			jQuery('#chosenCategory input:first').val('-1');
		}
	});
}


function cp_get_subcategories(dropdown, results_div_id, level, allow_parent_posting) {
	parent_dropdown = jQuery(dropdown).parent();
	category_ID = jQuery(dropdown).val();
	results_div = results_div_id + level;
	if ( ! jQuery(parent_dropdown).hasClass('hasChild') ) {
		jQuery(parent_dropdown).addClass('hasChild').parent().append('<div id="' + results_div + '" level="' + level + '" class="childCategory"></div>');
	}

	jQuery.ajax({
		type: 'POST',
		url: classipress_params.ajax_url,
		dataType: "json",
		data: {
			action: 'dropdown-child-categories',
			cat_id : category_ID,
			listing_id: classipress_params.listing_id,
			level: level
		},
		//show loading just when dropdown changed
		beforeSend: function() {
			jQuery('#getcat').hide();
			jQuery(dropdown).addClass('ui-autocomplete-loading').slideDown("fast");
		},
		//stop showing loading when the process is complete
		complete: function() {
			jQuery(dropdown).removeClass('ui-autocomplete-loading');
		},
		error: function(XMLHttpRequest, textStatus, errorThrown){
			//alert( 'Error: ' + errorThrown + ' - ' + textStatus + ' - ' + JSON.stringify( XMLHttpRequest ) );
		},
		// if data is retrieved, store it in html
		success: function( data ) {

			// child categories found so build and display them
			if ( data.success === true ) {
				jQuery('#' + results_div).html( data.html ).slideDown("fast"); //build html from ajax post

				// Trigger the 'change' event for the sub-categories.
				if ( jQuery('#' + results_div + ' select').val() ) {
					jQuery('#' + results_div + ' select').trigger('change');
				}

				if ( level === 1 ) {
					whenEmpty = false;
				} else {
					whenEmpty = true;
				}
			// if no categories are found
			} else {
				jQuery('#' + results_div).slideUp("fast");
				if ( jQuery(dropdown).val() === -1 && level === 2 ) {
					whenEmpty = false;
				} else {
					whenEmpty = true;
				}
			}

			// always check if go button should be on or off, jQuery parent is used for traveling backup the category heirarchy
			if ( ( allow_parent_posting === 'yes' && jQuery('#chosenCategory input:first').val() > 0) ) {
				jQuery('#getcat').fadeIn();
			//check for empty category option
			} else if ( whenEmpty && allow_parent_posting === 'whenEmpty' && jQuery('#chosenCategory input:first').val() > 0 ) {
				jQuery('#getcat').fadeIn();
			//if child category exists, is set, and allow_parent_posting not set to "when empty"
			} else if ( jQuery('#' + results_div_id + (level-1)).hasClass('childCategory') && jQuery(dropdown).val() > -1 && allow_parent_posting === 'no' ) {
				jQuery('#getcat').fadeIn();
			} else {
				jQuery('#getcat').fadeOut();
			}

		}
	});
}
