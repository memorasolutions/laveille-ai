
var Jobick = function(){
	"use strict"
	/* Search Bar ============ */
	var screenWidth = $( window ).width();
	var screenHeight = $( window ).height();
	
	/* NiceSelect Function*/
	var handleNiceSelect = function(){
		if(jQuery('select.nice-select').length > 0){
			jQuery('select.nice-select').niceSelect();  
			var elementArry = document.querySelectorAll('select.nice-select');
			elementArry.forEach((item, index) => {
				var tempArry = [];
				jQuery(item).parents().map((data,_) => {
					if(
						jQuery(jQuery(item).parents()[data]).css('overflow-x') === 'hidden'
						||
						jQuery(jQuery(item).parents()[data]).css('overflow-x') === 'auto'
						||
						jQuery(jQuery(item).parents()[data]).css('overflow') === 'auto'
						||
						jQuery(jQuery(item).parents()[data]).css('overflow') === 'hidden'
						||
						jQuery(jQuery(item).parents()[data]).css('overflow-y') === 'hidden'
						||
						jQuery(jQuery(item).parents()[data]).css('overflow-y') === 'auto'
					){
						tempArry.push(jQuery(item).parents()[data]);
					}
				})
				if(jQuery(tempArry[0]).height() < (jQuery(item).offset().top - jQuery(tempArry[0]).offset()?.top) + jQuery(item).next().height() + 10 + jQuery(item).next().find('ul.list').height()){
					jQuery(item).addClass('dropUp');
				}
			})
			
			$(".status-select").on("change", function(){
				$(this).removeClass('status-complete status-pending status-testing status-progress status-high status-low status-medium')
				var inputVal = $(this).val();
				if(inputVal === "pending") {
					$(this).addClass('status-pending');
				} else if(inputVal === "complete"){
					$(this).addClass('status-complete');
				} else if(inputVal === "testing"){
					$(this).addClass('status-testing');
				} else if(inputVal === "progress"){
					$(this).addClass('status-progress');
				} else if(inputVal === "medium"){
					$(this).addClass('status-medium');
				} else if(inputVal === "high"){
					$(this).addClass('status-high');
				} else if(inputVal === "low"){
					$(this).addClass('status-low');
				}
				$(this).niceSelect('update');
			});
		} 
	}
	
	var handleflatpickr = function(){
		if(jQuery('.flatpickr').length > 0 ){
			flatpickr(".flatpickr", {});
		}
		$('.datetime-flatpickr').flatpickr({
			enableTime: true,
			dateFormat: "Y-m-d H:i",
		  });
		$('.range-flatpickr').flatpickr({
			mode: "range",
		  });
	}	
	var handlePreloader = function(){
		$('#preloader').fadeOut(500);
		$('#main-wrapper').addClass('show');
	}

    var handleMetisMenu = function() {
		if(jQuery('#menu').length > 0 ){
			$("#menu").metisMenu();
		}
		jQuery('.metismenu > .mm-active ').each(function(){
			if(!jQuery(this).children('ul').length > 0)
			{
				jQuery(this).addClass('active-no-child');
			}
		});
	}
   
	var handleAllChecked = function() {
		$("#checkAll").on('change',function() {
			$("td input, .email-list .custom-checkbox input").prop('checked', $(this).prop("checked"));
		});
		$(".checkAll").on('click',function() {
			jQuery(this).closest('.ItemsCheckboxSec').find('input[type="checkbox"]').prop('checked', $(this).prop("checked"));		
		});
		$(".checkAllInput").on('click',function() {
			jQuery(this).closest('.ItemsCheckboxSec').find('input[type="checkbox"]').prop('checked', true);		
		});
		$(".unCheckAllInput").on('click',function() {
			jQuery(this).closest('.ItemsCheckboxSec').find('input[type="checkbox"]').prop('checked', false);		
		});
	}


	var handleNavigation = function() {
		$(".nav-control").on('click', function() {
			$('#main-wrapper').toggleClass("menu-toggle");
			$(".hamburger").toggleClass("is-active");
			handleMinHeight();
		});
	}
  
	var handleCurrentActive = function() {
		for (var nk = window.location,
			o = $("ul#menu a").filter(function() {
				
				return this.href == nk;
				
			})
			.addClass("mm-active")
			.parent()
			.addClass("mm-active");;) 
		{
			
			if (!o.is("li")) break;
			
			o = o.parent()
				.addClass("mm-show")
				.parent()
				.addClass("mm-active");
		}
	}

	var handleMiniSidebar = function() {
		$("ul#menu>li").on('click', function() {
			const sidebarStyle = $('body').attr('data-sidebar-style');
			if (sidebarStyle === 'mini') {
				console.log($(this).find('ul'))
				$(this).find('ul').stop()
			}
		})
	}
   
	var handleMinHeight = function() {
		var win_h = window.innerHeight;
		var win_h = window.innerHeight;
		if (win_h > 0 ? win_h : screen.height) {
			$(".content-body").css("min-height", (window.innerHeight - 70) + "px");	
			setTimeout(() => {
				if($('body').attr('data-layout') === "vertical"){
					if(
						($('body').attr('data-sidebar-style') === "mini") && ($('.dlabnav .metismenu').height() > (window.innerHeight - 60))
						||
						($('body').attr('data-sidebar-style') === "modern") && ($('.dlabnav .metismenu').height() > (window.innerHeight - 60))
						||
						($('body').attr('data-sidebar-style') === "full") && $('#main-wrapper').hasClass('menu-toggle') && ($('.dlabnav .metismenu').height() > (window.innerHeight - 60))
					){	
						$(".content-body").css("min-height", ($('.dlabnav .metismenu').height() + 110) + "px");
					}
				}
			},500);
		};
		
		setTimeout(() => {
			if(
				$('body').attr('data-header-position') === "fixed" 
				&& 
				$('body').attr('data-layout') === "horizontal"
				&&
				$('body').attr('data-sidebar-position') === "fixed"
			){
				$('.content-body').css("padding-top" ,  ($('.dlabnav').height() + $('.header').height()) + 'px');
			}else if(
				$('body').attr('data-header-position') === "fixed" 
				&& 
				$('body').attr('data-layout') === "horizontal"
				&&
				$('body').attr('data-sidebar-position') === "static"
			){
				$('.content-body').css("padding-top" , $('.header').height() + "px" );
			}else if(
				$('body').attr('data-header-position') === "static" 
				&& 
				$('body').attr('data-layout') === "horizontal"
				&&
				$('body').attr('data-sidebar-position') === "fixed"
			){
				$('.content-body').css("padding-top" , "0px" );
			}else {
				$('.content-body').css("padding-top" , "" );
			}
		},400);
		
	}
    
	var handleDataAction = function() {
		$('a[data-action="collapse"]').on("click", function(i) {
			i.preventDefault(),
				$(this).closest(".card").find('[data-action="collapse"] i').toggleClass("mdi-arrow-down mdi-arrow-up"),
				$(this).closest(".card").children(".card-body").collapse("toggle");
		});

		$('a[data-action="expand"]').on("click", function(i) {
			i.preventDefault(),
				$(this).closest(".card").find('[data-action="expand"] i').toggleClass("icon-size-actual icon-size-fullscreen"),
				$(this).closest(".card").toggleClass("card-fullscreen");
		});



		$('[data-action="close"]').on("click", function() {
			$(this).closest(".card").removeClass().slideUp("fast");
		});

		$('[data-action="reload"]').on("click", function() {
			var e = $(this);
			e.parents(".card").addClass("card-load"),
				e.parents(".card").append('<div class="card-loader"><i class=" ti-reload rotate-refresh"></div>'),
				setTimeout(function() {
					e.parents(".card").children(".card-loader").remove(),
						e.parents(".card").removeClass("card-load")
				}, 2000)
		});
	}

    var handleHeaderHight = function() {
		const headerHight = $('.header').innerHeight();
		$(window).scroll(function() {
			if ($('body').attr('data-layout') === "horizontal" && $('body').attr('data-header-position') === "static" && $('body').attr('data-sidebar-position') === "fixed")
				$(this.window).scrollTop() >= headerHight ? $('.dlabnav').addClass('fixed') : $('.dlabnav').removeClass('fixed')
		});
	}
	
	var handleMenuTabs = function() {
		if(screenWidth <= 991 ){
			jQuery('.menu-tabs .nav-link').on('click',function(){
				if(jQuery(this).hasClass('open'))
				{
					jQuery(this).removeClass('open');
					jQuery('.fixed-content-box').removeClass('active');
					jQuery('.hamburger').show();
				}else{
					jQuery('.menu-tabs .nav-link').removeClass('open');
					jQuery(this).addClass('open');
					jQuery('.fixed-content-box').addClass('active');
					jQuery('.hamburger').hide();
				}
			});
			jQuery('.close-fixed-content').on('click',function(){
				jQuery('.fixed-content-box').removeClass('active');
				jQuery('.hamburger').removeClass('is-active');
				jQuery('#main-wrapper').removeClass('menu-toggle');
				jQuery('.hamburger').show();
			});
		}
	}
	/* Header Fixed ============ */
	var headerFix = function(){
		'use strict';
		/* Main navigation fixed on top  when scroll down function custom */		
		jQuery(window).on('scroll', function () {
			
			if(jQuery('.header').length > 0){
				var menu = jQuery('.header');
				$(window).scroll(function(){
				  var sticky = $('.header'),
					  scroll = $(window).scrollTop();

				  if (scroll >= 100){ sticky.addClass('is-fixed');
									}else {sticky.removeClass('is-fixed');}
				});				
			}
			
		});
		/* Main navigation fixed on top  when scroll down function custom end*/
	}
	
	var handleChatbox = function() {
		jQuery('.bell-link').on('click',function(){
			jQuery('.chatbox').addClass('active');
		});
		jQuery('.chatbox-close').on('click',function(){
			jQuery('.chatbox').removeClass('active');
		});
	}
	
	var handlePerfectScrollbar = function() {
		if(jQuery('.dlabnav-scroll').length > 0)
		{
			//const qs = new PerfectScrollbar('.dlabnav-scroll');
			/* const qs = new PerfectScrollbar('.dlabnav-scroll');
			
			qs.isRtl = false; */
		}
	}

	var handleBtnNumber = function() {
		$('.btn-number').on('click', function(e) {
			e.preventDefault();

			fieldName = $(this).attr('data-field');
			type = $(this).attr('data-type');
			var input = $("input[name='" + fieldName + "']");
			var currentVal = parseInt(input.val());
			if (!isNaN(currentVal)) {
				if (type == 'minus')
					input.val(currentVal - 1);
				else if (type == 'plus')
					input.val(currentVal + 1);
			} else {
				input.val(0);
			}
		});
	}
	
	var handleDzChatUser = function() {
		jQuery('.dz-chat-user-box .dz-chat-user').on('click',function(){
			jQuery('.dz-chat-user-box').addClass('hidden');
			jQuery('.dz-chat-history-box').removeClass('hidden');
		}); 
		
		jQuery('.dz-chat-history-back').on('click',function(){
			jQuery('.dz-chat-user-box').removeClass('hidden');
			jQuery('.dz-chat-history-box').addClass('hidden');
		}); 
		
		jQuery('.dz-fullscreen').on('click',function(){
			jQuery('.dz-fullscreen').toggleClass('active');
		});
        
        
        
	}

	var HandleToolip = function() {
		$(document).ready(function(){
			$('.describe').hover(function(event){
				var self = $(this);
			  var titleText = $(this).attr('title');
			  $(this)
			  .data('tipText',titleText)
			  .removeAttr('title');
			  $('<p class="tooltip"></p>')
			  .text(titleText)
			  .appendTo('body')
			  .css('top', (self.offset().top - self.height() - 16) + 'px')
			  .css('left', (self.offset().left - ($('.tooltip').width() / 2) + 10) + 'px')
			  .fadeIn('slow');
			},function(){
			  $(this).attr('title',$(this).data('tipText'));
			  $('.tooltip').remove();
			  
			})
		  });
	}
	
	
	var handleDzFullScreen = function() {
		jQuery('.dz-fullscreen').on('click',function(e){
			if(document.fullscreenElement||document.webkitFullscreenElement||document.mozFullScreenElement||document.msFullscreenElement) { 
				/* Enter fullscreen */
				if(document.exitFullscreen) {
					document.exitFullscreen();
				} else if(document.msExitFullscreen) {
					document.msExitFullscreen(); /* IE/Edge */
				} else if(document.mozCancelFullScreen) {
					document.mozCancelFullScreen(); /* Firefox */
				} else if(document.webkitExitFullscreen) {
					document.webkitExitFullscreen(); /* Chrome, Safari & Opera */
				}
			} 
			else { /* exit fullscreen */
				if(document.documentElement.requestFullscreen) {
					document.documentElement.requestFullscreen();
				} else if(document.documentElement.webkitRequestFullscreen) {
					document.documentElement.webkitRequestFullscreen();
				} else if(document.documentElement.mozRequestFullScreen) {
					document.documentElement.mozRequestFullScreen();
				} else if(document.documentElement.msRequestFullscreen) {
					document.documentElement.msRequestFullscreen();
				}
			}		
		});
	}
	
	var handleshowPass = function(){
		jQuery('.show-pass').on('click', function() {
			jQuery(this).toggleClass('active');
			var passwordField = jQuery(this).siblings('.dz-password'); // Find the related password field
			if (passwordField.attr('type') === 'password') {
				passwordField.attr('type', 'text');
			} else {
				passwordField.attr('type', 'password');
			}
		});
		
	}

	
	var heartBlast = function (){
		$(".heart").on("click", function() {
			$(this).toggleClass("heart-blast");
		});
	}
	
	var handleDzLoadMore = function() {
		$(".dlab-load-more").on('click', function(e)
		{
			e.preventDefault();	//STOP default action
			$(this).append(' <i class="fas fa-sync"></i>');
			
			var dlabLoadMoreUrl = $(this).attr('rel');
			var dlabLoadMoreId = $(this).attr('id');
			
			$.ajax({
				method: "POST",
				url: dlabLoadMoreUrl,
				dataType: 'html',
				success: function(data) {
					$( "#"+dlabLoadMoreId+"Content").append(data);
					$('.dlab-load-more i').remove();
				}
			})
		});
	}

	var imgSelect = function (){
		
			// Get the background image of the selected option
			var backgroundImage = $('.img-select').find('option:selected').css('background-image');
			
			// Apply the same background image to the currently selected option
			$('.img-select').find('.current').css('background-image', backgroundImage);
		
	}
	
	var handleLightgallery = function () {
        if (jQuery('#lightgallery').length > 0) {
            lightGallery(document.getElementById('lightgallery'), {
                plugins: [lgThumbnail, lgZoom],
                selector: '.lg-item',
                thumbnail: true,
                exThumbImage: 'data-src'
            });
        }}
		
	var handleCustomFileInput = function() {
		$(".custom-file-input").on("change", function() {
			var fileName = $(this).val().split("\\").pop();
			$(this).siblings(".custom-file-label").addClass("selected").html(fileName);
		});
	}
    
  	var vHeight = function(){
        var ch = $(window).height() - 206;
        $(".chatbox .msg_card_body").css('height',ch);
    }
	
	var domoPanel = function(){
		if(jQuery(".dlab-demo-content").length>0) {
			$('.dlab-demo-trigger').on('click', function() {
					$('.dlab-demo-panel').addClass('show');
			  });
			  $('.dlab-demo-close, .bg-close').on('click', function() {
					$('.dlab-demo-panel').removeClass('show');
			  });
			  
			  $('.dlab-demo-bx').on('click', function() {
				  $('.dlab-demo-bx').removeClass('demo-active');
				  $(this).addClass('demo-active');
			  });
		}
	} 
	
	var handleCkEditor = function(){
		if(jQuery("#ckeditor").length>0) {
			ClassicEditor
			.create( document.querySelector( '#ckeditor' ), {
				simpleUpload: {
                    uploadUrl: 'ckeditor-upload.php', 
                }
			} )
			.then( editor => {
				window.editor = editor;
			} )
			.catch( err => {
				console.error( err.stack );
			} );
		}
	}
	
	var handleMenuPosition = function(){
		
		if(screenWidth > 1024){
			$(".metismenu  li").unbind().each(function (e) {
				if ($('ul', this).length > 0) {
					var elm = $('ul:first', this).css('display','block');
					var off = elm.offset();
					var l = off.left;
					var w = elm.width();
					var elm = $('ul:first', this).removeAttr('style');
					var docH = $("body").height();
					var docW = $("body").width();
					
					if(jQuery('html').hasClass('rtl')){
						var isEntirelyVisible = (l + w <= docW);	
					}else{
						var isEntirelyVisible = (l > 0)?true:false;	
					}
						
					if (!isEntirelyVisible) {
						$(this).find('ul:first').addClass('left');
					} else {
						$(this).find('ul:first').removeClass('left');
					}
				}
			});
		}
	}	
	
	var handleChartSidebar = function(){
		$('.chat-rightarea-btn').on('click',function(){
			$(this).toggleClass('active');
			$('.chat-right-area').toggleClass('active');
		})
		$('.chat-hamburger').on('click',function(){
			$('.chat-left-area').toggleClass('active');
		})
	}
	
	var MagnificPopup = function(){
		'use strict';	
		if($(".popup-youtube, .popup-vimeo, .popup-gmaps").length > 0 ) {
			/* magnificPopup for paly video function end*/
			$('.popup-youtube, .popup-vimeo, .popup-gmaps').magnificPopup({
				disableOn: 700,
				type: 'iframe',
				mainClass: 'mfp-fade',
				removalDelay: 160,
				preloader: false,

				fixedContentPos: false
			});
		}
	}
	
	var handleDraggableCard = function() {
		var dzCardDraggable = function () {
		 return {
		  //main function to initiate the module
		  init: function () {
		   var containers = document.querySelectorAll('.draggable-zone');

		   if (containers.length === 0) {
			return false;
		   }

		   var swappable = new Sortable.default(containers, {
			draggable: '.draggable',
			handle: '.draggable.draggable-handle',
			mirror: {
			 appendTo: 'body',
			 constrainDimensions: true
			}
			
		   });
		   swappable.on('drag:stop', () => {
				setTimeout(function(){
					setBoxCount();
				}, 200);
				
			})
		  }
		 };
		}();

		jQuery(document).ready(function () {
		 dzCardDraggable.init();
		});
		
		
		function setBoxCount(){
			var cardCount = 0;
			jQuery('.dropzoneContainer').each(function(){
				cardCount = jQuery(this).find('.draggable-handle').length;
				jQuery(this).find('.totalCount').html(cardCount);
			});
		}
	}


	
	/* Masonry Box ============ */
	var masonryBox = function(){
		'use strict';
		/* masonry by  = bootstrap-select.min.js */
		if(jQuery('#masonry, .masonry').length > 0){
			
			setTimeout(function(){
				jQuery('.filters li').removeClass('active');
				jQuery('.filters li:first').addClass('active');
				var self = jQuery("#masonry, .masonry"); 
				var filterValue = $('.filters li:first').attr("data-filter");
				
				function handleIsotope(filterValue){
					self.isotope({ 
						filter: filterValue
					});
				}
				
				if(jQuery('.filters').length){
					jQuery(".filters li:first").addClass('active');
					
					filterValue = $('.filters li:first').attr("data-filter");
					
					handleIsotope(filterValue);
					
					jQuery(".filters").on("click", "li", function() {
						jQuery('.filters li').removeClass('active');
						jQuery(this).addClass('active');
						
						filterValue = $(this).attr("data-filter");
						handleIsotope(filterValue);
					});
				}
			
			}, 500);
			
		}
		/* masonry by = bootstrap-select.min.js end */
	}
	
	var handleConverterTheme = function(){
		if($('.btc-converts').length > 0){
			setTimeout(()=> {
				if($('body').attr('data-theme-version') === "dark"){
					$('.btc-converts').attr('dark-mode', true);
				} 
			},1000);
			$('#theme_version').on('change',function(){
				if($('body').attr('data-theme-version') === "dark"){
					$('.btc-converts').attr('dark-mode', true);
				} else{
					$('.btc-converts').attr('dark-mode', false);
				}
			});
		}
	}
	/* Handle Page On Scroll ============ */
	var handlePageOnScroll = function(event){
		
		'use strict';
		var headerHeight = parseInt($('.header').css('height'), 10);
		
		$('.navbar-nav .scroll').on('click', function(event) 
		{
			event.preventDefault();

			jQuery('.navbar-nav .scroll').parent().removeClass('active');
			jQuery(this).parent().addClass('active');
			
			if (this.hash !== "") {
				var hash = this.hash;	
				var seactionPosition = parseInt($(hash).offset().top, 10);
				var headerHeight =   parseInt($('.header').css('height'), 10);
				
				var scrollTopPosition = seactionPosition - headerHeight;
				$('html, body').animate({
					scrollTop: scrollTopPosition
				}, 800, function(){
					
				});
			}   
		});
		
		pageOnScroll();
	}

	/* Page On Scroll ============ */
	var pageOnScroll = function(event){
		
		if(jQuery('.navbar-nav').length > 0){
			
			var headerHeight = parseInt(jQuery('.header').height(), 10);
			
			jQuery(document).on("scroll", function(){
				
				var scrollPos = jQuery(this).scrollTop();
				jQuery('.navbar-nav .scroll').each(function () {
					var elementLink = jQuery(this);
					
					
					var refElement = jQuery(elementLink.attr("href"));
					
					if(jQuery(this.hash).offset() != undefined){
						var seactionPosition = parseInt(jQuery(this.hash).offset().top, 10);
					}else{
						var seactionPosition = 0;
					}
					var scrollTopPosition = (seactionPosition - headerHeight);

					if (scrollTopPosition <= scrollPos){
						elementLink.parent().addClass("active");
						elementLink.parent().siblings().removeClass("active");
					}
				});
				
			});
		}
	} 
	

	var handlepopovers = function(){
		jQuery('.dz-popover').on('click',function(){
		if(jQuery('#'+jQuery(this).attr('data-dz-popover')).hasClass('hide')){
				jQuery('.dz-popover-content').fadeOut().addClass('hide');
				jQuery('#'+jQuery(this).attr('data-dz-popover')).fadeIn().removeClass('hide');				
			}
			else{
				jQuery('#'+jQuery(this).attr('data-dz-popover')).fadeOut().addClass('hide');					
			}
		});
		$(document).on('click',function(e){
			if(!(($(e.target).closest(".dz-popover-content").length > 0 ) || ($(e.target).closest(".dz-popover").length > 0))){
			$(".dz-popover-content").fadeOut().addClass('hide');
		   }
		});
	}

	
	var handleTab = function(){
		$('.tab-btn').on('click',function(){
			var tab_id = $(this).attr('data-tab');
			$(this).closest('.dz-tab-area').closest_descendent('.nav-tabs,.nav-pills').find('.tab-btn').removeClass('active');
			$(this).closest('.dz-tab-area').closest_descendent('.tab-content-area').children().removeClass('show');
			$(this).addClass('active');
			$(this).closest('.dz-tab-area').find("#"+tab_id).addClass('show');
		})	
	}
	var handleHorizontalDropDown = function(){
		$('.metismenu li a').hover(
			function(event) {
				var $submenu = $(this).parent().children('ul');
				
				var $windowEdge = $('#main-wrapper').width();
				var $leftOffset = ($(window).width() - $('#main-wrapper').width())/2;
				var $menuRightEdge = ($(this).offset().left + $(this).outerWidth() + $submenu.outerWidth()) - $leftOffset;
				if($('html').attr('dir')==='ltr'){
					var $menuRightEdge = ($(this).offset().left + $(this).outerWidth() + $submenu.outerWidth()) - $leftOffset;
					if($menuRightEdge > $windowEdge) {
						if($(this).parent().parent('ul.metismenu').length > 0){
							$submenu.css({ left: 'auto', right: '0' });
						}else{
							$submenu.css({ left: 'auto', right: '100%' });
						}
					}
				}else{
					var $rightOffset = $windowEdge - ($(this).offset().left + $(this).outerWidth() - $leftOffset); 
					var $menuLeftEdge = $rightOffset + $(this).outerWidth() + $submenu.outerWidth();
					console.log($rightOffset);
					if($menuLeftEdge > $windowEdge) {
						if($(this).parent().parent('ul.metismenu').length > 0){
							$submenu.css({ left: '0', right: 'auto' });
						}else{
							$submenu.css({ left: '100%', right: 'auto' });
						}
					}
				}
			},
			function() {
				$(this).children('ul').removeAttr('style');
			}
		);
	}
	
    var handleAlert = function(){
        jQuery('.alert .close-btn').on('click',function(){
            $(this).parent('.alert').remove();
        })
    }
	
	var handleThemeMode = function() {
		if(jQuery(".dz-theme-mode").length>0) {
	
			jQuery('.dz-theme-mode').on('click',function(){
				jQuery(this).toggleClass('active');
				
				if(jQuery(this).hasClass('active')){
					jQuery('body').attr('data-theme-version','dark');
					setCookie('version', 'dark');
				}else{
					jQuery('body').attr('data-theme-version','light');
					setCookie('version', 'light');
				}
			});
			var version = getCookie('version');
			if(version != null){	
				jQuery('body').attr('data-theme-version', version);
			}
			jQuery('.dz-theme-mode').removeClass('active');
			
			jQuery(window).on('resize',function () {
				var version = getCookie('version');
				if(version != null){
					jQuery('body').attr('data-theme-version', version);
				}
			})
			
			setTimeout(function(){
				if(jQuery('body').attr('data-theme-version') === "dark")
				{
					jQuery('.dz-theme-mode').addClass('active');
				}
			},1600)
		}
	}
	
	var handleCardCollapse = function(){
		$('.card-toggle-btn').click(function(){
			if($(this).hasClass('active')){
				$(this).find('.ac-icon').removeClass('ti-minus');
				$(this).find('.ac-icon').addClass('ti-plus');
			}else{
				$(this).find('.ac-icon').removeClass('ti-plus');
				$(this).find('.ac-icon').addClass('ti-minus');
			}
			$(this).toggleClass('active');
			$(this).parent().find('.content').slideToggle(280);
		});
	}
	
	/* Function ============ */
	return {
		init:function(){
			handleMetisMenu();
			handleNiceSelect();
			handleflatpickr();
			handleAllChecked();
			handleNavigation();
			handleCurrentActive();
			handleMiniSidebar();
			handleMinHeight();
			handleDataAction();
			handleHeaderHight();
			handleMenuTabs();
			handleChatbox();
			handlePerfectScrollbar();
			handleBtnNumber();
			handleDzChatUser();
			HandleToolip();
			handleDzFullScreen();
			handleshowPass();
			heartBlast();
			handleDzLoadMore();
			imgSelect();
			handleLightgallery();
			handleCustomFileInput();
			vHeight();
			domoPanel();
			handleCkEditor();
			headerFix();
			handleChartSidebar();
			MagnificPopup();
			handleDraggableCard();
			handleConverterTheme();
			handlePageOnScroll();
			handlepopovers();
			handleTab();
            handleAlert();
            handleThemeMode();
			handleCardCollapse();
			setTimeout(function(){
				handleHorizontalDropDown();
			},500);
		},

		
		load:function(){
			handlePreloader();
			masonryBox();
		},
		
		resize:function(){
			vHeight();
			handleMinHeight();
		},
		
		handleMenuPosition:function(){
			
			handleMenuPosition();
		},
	}
	
}();

(function($) {
	$.fn.closest_descendent = function(filter) {
		var $found = $(),
			$currentSet = this; // Current place
		while ($currentSet.length) {
			$found = $currentSet.filter(filter);
			if ($found.length) break;  // At least one match: break loop
			// Get all children of the current set
			$currentSet = $currentSet.children();
		}
		return $found.first(); // Return first match of the collection
	}    
})(jQuery);

/* Document.ready Start */	
jQuery(document).ready(function() {
    'use strict';
	Jobick.init();
	
});
/* Document.ready END */

/* Window Load START */
jQuery(window).on('load',function () {
	'use strict'; 
	Jobick.load();
	setTimeout(function(){
			Jobick.handleMenuPosition();
	}, 1000);
	
});
/*  Window Load END */
/* Window Resize START */
jQuery(window).on('resize',function () {
	'use strict'; 
	Jobick.resize();
	setTimeout(function(){
		Jobick.handleMenuPosition();
	}, 1000);
});
/*  Window Resize END */