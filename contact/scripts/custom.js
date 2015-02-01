var previousMenu;

$(document).ready(function() {

    /***************************************************/
    /*   Initialize required functions                 */
    /***************************************************/

    initConfig();

    initPlugins();

    initSocialIcons();

    initMenus();

    /***************************************************/
    /*   Menu's clicking event                         */
    /***************************************************/

    $('ul#nav a').click(function() {

        if (previousMenu.attr('id') != $(this).attr('id')) {
            
            $('ul#nav a').css('opacity', '.5');
            previousMenu.removeClass('activeMenu');
            previousMenu.parent().removeClass('activeMenu');
            setMenuActivatorStatus('block');
            $(this).addClass('activeMenu');
            $(this).parent().addClass('activeMenu');
            $(this).css('opacity', '1');

            var nextMenu = $(this);
            var socialIcons = $('#socialIcons');

            // Set pre-state and position of social icons
            if (nextMenu.attr('id') == 'homeLink' || previousMenu.attr('id') == 'homeLink') {

                socialIcons.stop().animate({
                    opacity : '0'
                }, 400, function() {
                    if (nextMenu.attr('id') == 'homeLink') {
                        socialIcons.css('left', '20px');
                        socialIcons.css('top', '0');
                    } else {
                        socialIcons.css('left', '319px');
                        socialIcons.css('top', '-62px');
                    }
                });
                
                $('#socialIcons a').stop().animate({
                    opacity : '0'
                });
            }

            /***************************************************/
            /*   Change page                                   */
            /***************************************************/

            changePage(previousMenu, nextMenu);
            previousMenu = $(this);
        }
    });

    /***************************************************/
    /*   Sending message event                         */
    /***************************************************/

    var msgForm = $('form#msgForm');

    msgForm.submit(function() {
        if (msgForm.validationEngine('validate')) {
            $.ajax({
                type : "POST",
                url : "php/sendmail.php",
                data : msgForm.serialize(),
                success : function(result) {

                    var message, cssClass;
                    if (result == 'success') {
                        message = 'Your message has been sent. Thank you!';
                        cssClass = 'success';
                    } else {
                        message = 'Duh! Something went wrong. Please try again later.';
                        cssClass = 'fail';
                    }

                    $.colorbox({
                        html : '<span id="sendingStatus" class="' + cssClass + '">' + message + '</span>',
                        transition : 'elastic',
                        width : '450px',
                        height : '150px'
                    });
                }
            });
            return false;
        }
    });

});

function changePage(previousMenu, nextMenu) {
    switch(previousMenu.attr('id')) {

        // Home page
        case 'homeLink' :

            var pIntro = $('p#intro');

            pIntro.stop().animate({
                opacity : '0'
            }, 400, function() {
                pIntro.css('display', 'none');
                $('#photoBox').stop().animate({
                    marginLeft : '-1002px'
                }, 750, 'easeOutQuint', function() {
                    $('#homePage').stop().animate({
                        marginTop : '25px'
                    }, 600, 'easeOutQuint', function() {
                        setActivePage(nextMenu);
                    })
                });
            });
            break;

        // Profile page
        case 'profileLink' :

            var profilePage = $('#profilePage');
            var skills = $('#skills');
            var experiences = $('#experiences');

            experiences.stop().animate({
                opacity : '0'
            }, 400, function() {
                skills.stop().animate({
                    marginLeft : '-260px'
                }, 750, 'easeOutQuint', function() {
                    profilePage.stop().animate({
                        opacity : '0'
                    }, 10, function() {
                        profilePage.css('display', 'none');
                        setActivePage(nextMenu);
                    });
                });
            });
            break;

        // Portfolio page
        case 'portfolioLink' :

            var portfolioPage = $('#portfolioPage');
            var portfolioDesc = $('.portfolioItems .description');
            var portfolioItem = $('.portfolioItems li');

            portfolioDesc.stop().animate({
                opacity : '0'
            });
            portfolioItem.stop().animate({
                height : '145px'
            }, 800, 'easeOutQuint', function() {
                portfolioPage.stop().animate({
                    left : '-690px'
                }, 800, 'easeOutQuint', function() {
                    portfolioPage.addClass('hide');
                    setActivePage(nextMenu);
                });
            });
            break;

        // Contact page
        case 'contactLink' :

            var googleMap = $('#googleMap');
            var contactInfo = $('#contactInfo');
            var contactForm = $('#contactForm');

            contactInfo.stop().animate({
                opacity : '0'
            }, 400);
            contactForm.stop().animate({
                opacity : '0'
            }, 400, function() {
                googleMap.stop().animate({
                    marginLeft : '-330px'
                }, 750, 'easeOutQuint', function() {
                    $('#contactPage').addClass('hide');
                    setActivePage(nextMenu);
                });
            });
            break;
    }
}

function setActivePage(activeMenu) {

    /***************************************************/
    /*   Showing active page                           */
    /***************************************************/

    switch(activeMenu.attr('id')) {

        // Home page
        case 'homeLink' :

            $('#homePage').stop().animate({
                height : '230px',
                marginTop : '130px'
            }, 800, 'easeInOutQuint', function() {
                $('#photoBox').stop().animate({
                    marginLeft : '-751px'
                }, 750, 'easeOutQuint', function() {

                    showSocialIcons();

                    var pIntro = $('p#intro');

                    pIntro.css('display', 'block');
                    pIntro.stop().animate({
                        opacity : '1'
                    }, 400, function() {
                        setMenuActivatorStatus('none');
                    });
                });
            });
            break;

        // Profile page
        case 'profileLink' :

            var profilePage = $('#profilePage');
            profilePage.css('opacity', '0');
            profilePage.css('display', 'block');

            var skills = $('#skills');
            skills.css('margin-left', '-260px');

            var experiences = $('#experiences');
            experiences.css('opacity', '0');

            var skillScoreWrapper = $('span.skillScoreWrapper');
            skillScoreWrapper.css('width', '0');

            profilePage.stop().animate({
                opacity : '1'
            }, 10, 'easeOutQuint', function() {
                skills.stop().animate({
                    marginLeft : '0'
                }, 750, 'easeInOutQuint', function() {
                    experiences.stop().animate({
                        opacity : '1'
                    }, 400, function() {
                        showSocialIcons();
                        skillScoreWrapper.stop().animate({
                            width : '100%'
                        }, 2300, 'easeOutQuint');
                    });
                    setMenuActivatorStatus('none');
                });
            });
            break;

        // Portfolio page
        case 'portfolioLink' :

            var portfolioPage = $('#portfolioPage');
            var portfolioDesc = $('.portfolioItems .description');
            var portfolioItem = $('.portfolioItems li');

            portfolioPage.removeClass('hide');
            portfolioPage.css('left', '-690px');
            portfolioDesc.css('opacity', '0');
            portfolioItem.css('height', '145px');

            portfolioPage.stop().animate({
                left : '0'
            }, 1000, 'easeInOutQuint', function() {
                showSocialIcons();

                portfolioItem.stop().animate({
                    height : '330px'
                }, 900, 'easeOutQuint');

                portfolioDesc.stop().animate({
                    opacity : '1'
                });
                setMenuActivatorStatus('none');
            });
            break;

        // Contact page
        case 'contactLink' :

            var googleMap = $('#googleMap');
            googleMap.css('margin-left', '-330px')

            var contactInfo = $('#contactInfo');
            contactInfo.css('opacity', '0');

            var contactForm = $('#contactForm');
            contactForm.css('opacity', '0');

            $('#contactPage').removeClass('hide');

            googleMap.stop().animate({
                marginLeft : '0'
            }, 750, 'easeInOutQuint', function() {
                contactInfo.stop().animate({
                    opacity : '1'
                }, 400);
                contactForm.stop().animate({
                    opacity : '1'
                }, 400);
                setMenuActivatorStatus('none');
                showSocialIcons();
            });
            break;
    }
}

function initMenus() {

    $('ul#nav a').hover(function() {
        if (previousMenu.attr('id') != $(this).attr('id')) {
            $(this).stop().animate({
                opacity : '1'
            }, 550);
        }

    }, function() {
        if (previousMenu.attr('id') != $(this).attr('id')) {
            $(this).stop().animate({
                opacity : '.5'
            }, 250);
        }
    });
}

function initSocialIcons() {

    $('#socialIcons a').hover(function() {
        $(this).stop().animate({
            bottom : '6px'
        }, 400, 'easeOutQuart');
    }, function() {
        $(this).animate({
            bottom : '0px'
        }, 400, 'easeOutQuart');
    });
}

function showSocialIcons() {
    $('#socialIcons').stop().animate({
        opacity : '1'
    }, 400);
    
    $('#socialIcons a').stop().animate({
        opacity : '1'
    }, 400);
}

function initConfig() {
    var config = getConfig();

    $('p#address').html(config.address);

    // Render Google Map for Contact page
    loadGoogleMap(config.useAutoMap, config.address);
}

function initPlugins() {

    // jPreLoader plugin
    $('body').jpreLoader({}, function() {
        $('#navBox').animate({
            opacity : '1'
        }, 400);
        
        $('#nav').animate({
            opacity : '1'
        }, 400);

        $('#homePage').animate({
            opacity : '1'
        }, 400, function() {
            
            // Additional code just only for IE8 and FireFox 3 to show the corner graphic
            var ua = $.browser;
            if(jQuery.browser.version.substring(0, 2) == "8." || (ua.mozilla && ua.version.slice(0,3) == "1.9")) {
                $('img#ie8Corner').css('display', 'block');
            }
            
            initPages();
        });
    });

    // Poshytip plugin
    $('#socialIcons img').poshytip({
        className : 'tip-twitter',
        alignTo : 'target',
        alignY : 'bottom',
        offsetY : 25,
        allowTipHover : false,
        slide : false
    });

    // Mosaic plugin
    $('.circle').mosaic({
        opacity : 0.8,
        speed : 800
    });

    // UItoTop Plugin
    $().UItoTop({
        easingType : 'easeOutQuart'
    });

    // ColorBox Plugin
    $('.portfolioBox').colorbox({
        rel : 'portfolioBox',
        fixed : true
    });
    $('.youtube').colorbox({
        height : '480px',
        iframe : true,
        innerWidth : 640,
        innerHeight : 480,
        fixed : true
    });

    $('#msgForm').validationEngine('attach', {
        autoHidePrompt : 'true',
        autoHideDelay : '2000',
        fixed : true
    });
}

function initPages() {

    // Portfolio hovering animation
    $('.portfolioBox').hover(function() {
        $(this).next().animate({
            opacity : '.6'
        });
    }, function() {
        $(this).next().animate({
            opacity : '1'
        });
    });

    previousMenu = $('#homeLink');
    previousMenu.attr('class', 'activeMenu');

    setMenuActivatorStatus('block');
    setActivePage(previousMenu);
}

function loadGoogleMap(isActive, address) {
    if (isActive) {
        var geocoder = new google.maps.Geocoder();
        var latlng = new google.maps.LatLng(-34.397, 150.644);
        var myOptions = {
            zoom : 13,
            center : latlng,
            mapTypeId : google.maps.MapTypeId.ROADMAP
        }
        var map = new google.maps.Map($('#googleMap')[0], myOptions);

        geocoder.geocode({
            'address' : address
        }, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                map.setCenter(results[0].geometry.location);
                var marker = new google.maps.Marker({
                    map : map,
                    position : results[0].geometry.location
                });
            } else {
                alert("Geocode was not successful for the following reason: " + status);
            }
        });
    }
}

function setMenuActivatorStatus(css) {
    var navActivator = $('#navActivator');
    navActivator.stop().animate({
        opacity : '0'
    }, 5, function() {
        navActivator.css('display', css);
    });
}
