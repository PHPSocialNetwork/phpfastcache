/*
 * jQuery Echo Slider v1.0
 * http://two2twelve.com
 *
 * Copyright 2012, Eric Alli
 * Free to use and reproduce under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 * 
 * April 2012
 */
(function($) {
	var EchoSlider = function(element, options){
		
		// Set a few vars
		vars = {
			currentSlide: 0,
	    totalSlides: 0,
	    running: false,
	    paused: false,
	    stop: false
		};
		
		// Grab our settings
		var settings = $.extend({}, $.fn.echoSlider.defaults, options);
		
		// Retrieve this slider
		var slider = $(element);
	  slider.data('echo:vars', vars)
			.css('position','relative')
			.addClass('echo-slider');

		// Find the slides
	  var slides = slider.children();
	  slides.each(function() {
		  var slide = $(this);
		  slide.hide().children("div").hide();
		  vars.totalSlides++;
	  });

		// Show initial slide
		$(slides[vars.currentSlide]).show().children("div").show();

		// Start the show!
		var timer = 0;
    if(!settings.manualAdvance && slides.length > 1){
        timer = setInterval(function(){ startEcho(slider, slides, settings); }, settings.pauseTime);
    }

		// Add directional nav
    if(settings.directionNav){
        slider.append('<div class="echo-directional-nav"><a class="echo-prev_nav">'+ settings.prevText +'</a><a class="echo-next_nav">'+ settings.nextText +'</a></div>');

      // Hide directional nav
      if(settings.directionNavHide){
      	$('.echo-directional-nav', slider).hide();
        slider.hover(function(){
         	$('.echo-directional-nav', slider).show();
        }, function(){
         	$('.echo-directional-nav', slider).hide();
      	});
      }

			$('a.echo-prev_nav', slider).live('click', function(){
				if(vars.running) return false;
				clearInterval(timer);
				timer = '';
				var prev_slide = vars.currentSlide;
				if(prev_slide === 0) { prev_slide += 1; }
				vars.currentSlide -= 2;
				startEcho(slider, slides, settings, prev_slide);
			});

			$('a.echo-next_nav', slider).live('click', function(){
				if(vars.running) return false;
				clearInterval(timer);
				timer = '';
				var prev_slide = vars.currentSlide;
				if(prev_slide === 0) { prev_slide += 1; }
				startEcho(slider, slides, settings, prev_slide);
			});
    }

		if(settings.swipeNav){
		
			$(slider).swipe({ 
				swipeLeft: function() {
					if(vars.running) return false;
					clearInterval(timer);
					timer = '';
					var prev_slide = vars.currentSlide;
					if(prev_slide === 0) { prev_slide += 1; }
					startEcho(slider, slides, settings, prev_slide);
				},
				swipeRight: function() {
					if(vars.running) return false;
					clearInterval(timer);
					timer = '';
					var prev_slide = vars.currentSlide;
					if(prev_slide === 0) { prev_slide += 1; }
					vars.currentSlide -= 2;
					startEcho(slider, slides, settings, prev_slide);
				}
			});
			
		}

    // Add navigation links
    if(settings.controlNav){
	    var echoNav = $('<div class="echo-nav"></div>');
	    slider.append(echoNav);
	    for(var i = 0; i < slides.length; i++){
	    	echoNav.append('<a href="javascript:;" class="echo-control" rel="'+ i +'">'+ (i + 1) +'</a>');            
	    }
	    //Set initial active link
	    $('.echo-nav a:eq('+ vars.currentSlide +')', slider).addClass('active');
    
	    $('.echo-nav a', slider).live('click', function(){
				
				 if(vars.running) return false;
         if($(this).hasClass('active')) return false;
         clearInterval(timer);
         timer = '';
				 var prev_slide = vars.currentSlide;
				 if(prev_slide === 0) { prev_slide += 1; }
         vars.currentSlide = $(this).attr('rel') - 1;
         startEcho(slider, slides, settings, prev_slide);
	    });
    }
	
    // Allow pausing on hover
    if(settings.pauseOnHover){
        slider.hover(function(){
            vars.paused = true;
            clearInterval(timer);
            timer = '';
        }, function(){
            vars.paused = false;
            if(timer == '' && !settings.manualAdvance){
                timer = setInterval(function(){ startEcho(slider, slides, settings); }, settings.pauseTime);
            }
        });
    }
	
		// After slide event
    slider.bind('echo:animFinished', function(){ 
    	vars.running = false; 
			if(timer == '' && !vars.paused && !settings.manualAdvance){
          timer = setInterval(function(){ startEcho(slider, slides, settings); }, settings.pauseTime);
      }
      settings.afterChange.call(this);
    });
	
		var startEcho = function(slider, kids, settings, previous_slide){	
			
			// Get the vars
			var vars = slider.data('echo:vars');
			
			// Don't continue if vars.stop is set or slider is out of focus
			if(!vars || vars.stop) return false;
			if(!slider.is(":visible")) return false;
			
			// Add beforeChange callback
			settings.beforeChange.call(this);
			
			vars.currentSlide++;
			
			if(vars.currentSlide == vars.totalSlides){ 
				vars.currentSlide = 0;
				// Add slideshowEnd callback
				settings.slideshowEnd.call(this);
			}
			if(vars.currentSlide < 0) vars.currentSlide = (vars.totalSlides - 1);
			
			vars.running = true;
			
			//Set active links in nav
			if(settings.controlNav){
				$('.echo-nav a', slider).removeClass('active');
				$('.echo-nav a:eq('+ vars.currentSlide +')', slider).addClass('active');
			}
			
			// Find the last slide
			if(slides.first().is(":visible")) {
				var prevSlide = slides.first();
			} else {
				if(previous_slide) {
					var prevSlide = $(slides[previous_slide]);
					if(!prevSlide.length) {
						var prevSlide = slides.last();
					}
				} else {
					var prevSlide = $(slides[vars.currentSlide]).prev(".slide");
					if(!prevSlide.length) {
						var prevSlide = slides.last();
					}
				}
			}
			
			// Hide last slide
			var delay_time = 0;				
			p = 0;
			if(prevSlide.data("effect-out")) {
				var effect_out = prevSlide.data("effect-out");
			} else {
				var effect_out = settings.effect;
			}
			if(settings.easing != false) {
				var prev_effect = "easeInBack";
			} else {
				var prev_effect = "";
			}
			prevSlide.show().children("div").each(function() {
					
					// If not first element, delay transition
					if(p != 0) {
						$(this).delay(settings.animSpeed);
						//delay_time += settings.animSpeed;
					}
					
					var current_left = $(this).position().left;
					var slide_distance = slider.width() - $(this).position().left;
					
					// if last element, hide slide container and elements
					if(prevSlide.children("div").length - 1 == p) {
						if(effect_out == "slide") {
							$(this).animate({ 'left' : '-'+slide_distance+'px' }, settings.animSpeed, prev_effect, function() { $(this).hide().css({ 'left' : current_left }); prevSlide.hide(); });
						} else {
							$(this).fadeOut(settings.animSpeed, function() { prevSlide.hide().children("div").hide(); });
						}
					} else {
						if(effect_out == "slide") {
							$(this).animate({ 'left' : '-'+slide_distance+'px' }, settings.animSpeed, prev_effect, function() {  $(this).hide().css({ 'left' : current_left }); });
						} else {
							$(this).fadeOut(settings.animSpeed);
						}
					}
					delay_time += settings.animSpeed;
					
					p++;
			});
			
			// Show next slide
			n = 0;
			if($(slides[vars.currentSlide]).data("effect-in")) {
				var effect_in = $(slides[vars.currentSlide]).data("effect-in");
			} else {
				var effect_in = settings.effect;
			}
			if(settings.easing != false) {
				var next_effect = "easeOutBack";
			} else {
				var next_effect = "";
			}
			$(slides[vars.currentSlide]).show().children("div").each(function() {
				
				if(n == 0) {
					$(this).delay(delay_time);
				} else {
					$(this).delay(delay_time + settings.animSpeed);
				}
				
				var current_left = $(this).css("left");
				var slide_distance = slider.width() - $(this).position().left;
				
				if($(slides[vars.currentSlide]).children("div").length - 1 == n) {
					if(effect_in == "slide") {
						$(this).css({ 'left' : slide_distance+'px' }).show();
						$(this).animate({ 'left' : current_left }, settings.animSpeed, next_effect, function() { slider.trigger('echo:animFinished'); });
					} else {
						$(this).fadeIn(settings.animSpeed, function() { slider.trigger('echo:animFinished'); });
					}						
				} else {
					if(effect_in == "slide") {
						$(this).css({ 'left' : slide_distance+'px' }).show();
						$(this).animate({ 'left' : current_left }, settings.animSpeed, next_effect, function() { });
					} else {
						$(this).fadeIn(settings.animSpeed);
					}
				}

				n++;
				
			});	
			
		};
	
	};

	$.fn.echoSlider = function(options) {
	  return this.each(function(){
	  	var element = $(this);
	    if (element.data('echoslider')) return;
	    var echoslider = new EchoSlider(this, options);
	    element.data('echoslider', echoslider);
	  });
	};

	//Default settings
	$.fn.echoSlider.defaults = {
		effect: 'slide',
		easing: true, // Requires jQuery easing plugin
		pauseTime: 3000,
		animSpeed: 500,
		manualAdvance: true,
		pauseOnHover: true,
		controlNav: true,
		directionNav: false,
		swipeNav: true,
		prevText: "Previous",
		nextText: "Next",
		beforeChange: function(){},
		afterChange:  function(){},
		slideshowEnd: function(){}
	};

	$.fn._reverse = [].reverse;

})(jQuery);