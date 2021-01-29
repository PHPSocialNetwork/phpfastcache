$(document).ready(function () {
  var ShowdownOptions = {
    headerLevelStart: 2,
    emoji: true,
    tables: true,
    ghMentions: true,
    disableForced4SpacesIndentedSublists: true,
    simplifiedAutoLink: true
  };
  var ShowdownConverter = new showdown.Converter(ShowdownOptions);
  var MutationObserver = window.MutationObserver || window.WebKitMutationObserver;


  var hydrateElementAsynchronously = function (source, target, format) {
    var baseMarkupRelativeUrls = 'https://github.com/PHPSocialNetwork/phpfastcache/blob/master/'
    if (!target.hasClass('hydrated'))
    {
      target.addClass('hydrated', true);
      $.ajax({
        async: true,
        url: source,
        beforeSend: function () {
          $(target).html('<div class="text-center"><i class="fa fa-refresh fa-spin fa-3x fa-fw"></i></div>');
        }
      }).done(function (data) {
        switch (format)
        {
          case 'markdown':
            $(target).html(ShowdownConverter.makeHtml(data.replace(/\](\(\.\/)(.*)\)/, "](" + baseMarkupRelativeUrls + "$2)")));
            break;

          case 'html':
            $(target).html(data);
            break;

          case 'text':
          default:
            $(target).empty().append('<pre>').find('pre').text(data);
            break;
        }
      }).fail(function (jqXHR, textStatus, errorThrown) {
        $(target).html('<div class="alert alert-danger">\n' +
          '  <strong>[' + jqXHR.status + '] Ajax Error</strong><br>' +
          jqXHR.responseText +
          '</div>');
      }).always(function () {
        $('pre code').each(function (i, block) {
          hljs.highlightBlock(block);
        });
        $('.panel-collapse').addClass('fpad2');
      });
    }
  };

  var observer = new MutationObserver(function (mutations, observer) {
    for (var key in mutations)
    {
      $($(mutations[key].target)).find('[data-content-source="remote"][data-src]').each(function () {
        hydrateElementAsynchronously(
          $(this).data('src'),
          $(this),
          $(this).attr('data-format')
        );
      });
    }
  });
  /**
   * Define what element should be observed by the observer
   * and what types of mutations trigger the callback
   */
  observer.observe(document, {
    childList: true,
    subtree: true,
  });

  /**
   * Ajax Tabs
   */
  $(document).on('shown.bs.tab shown.bs.collapse', '.panel-collapse, a[data-toggle="tab"]', function (e) {
    var $this = ($(this).prop('tagName') === 'A' ? $(this) : $(this).siblings('.panel-heading').find('a[data-toggle="collapse"]'));

    hydrateElementAsynchronously(
      $this.data('href'),
      $($this.attr('href') + ', ' + $this.attr('href') + '-collapse'),
      $this.attr('data-format')
    );
  });

  /**
   * Get the latest version
   */
  $.ajax({
    async: true,
    url: $('[data-object="pfc-version"]').data('object-src'),
    beforeSend: function () {
      $('[data-object="pfc-version"]').html('<i class="fa fa-circle-o-notch fa-spin fa-fw"></i>');
    }
  }).done(function (data) {
    var lines = data.split("\n");
    for(var key in lines){
      if(lines[key].indexOf('## ') > -1){
        $('[data-object="pfc-version"]').text(lines[key].replace('## ', ''));
        break;
      }
    }
  }).fail(function () {
    $(target).text('Ajax error');
  });


  /**
   * Improving user navigation
   * for very long pages
   */
  $(window).scroll(function () {
    if ($(this).scrollTop() >= 400)
    {
      $('#return-to-top').addClass('visible');
    } else
    {
      $('#return-to-top').removeClass('visible');
    }
  });

  $('#return-to-top').click(function () {
    $('body,html').animate({
      scrollTop: 0
    }, 500);
  });

  /**
   * Bind tooltips
   */
  $('[data-toggle="tooltip"]').tooltip();

  /**
   * Reusable component
   */
  var triggerTabsLoading = function(anchor){
    if(anchor && $('#main-tabs li[data-anchor="' + anchor + '"]').length){
      $('#main-tabs li[data-anchor="' + anchor + '"] a').click();
      history.replaceState({}, document.title, ".");
    }
    $('#main-tabs').tabCollapse();
    $('#main-tabs li.active a').trigger('shown.bs.tab');
    $('.panel-collapse.collapse.in').trigger('shown.bs.collapse');
  };

  /**
   * Prevent resizing from mobile to desktop
   * viewport to display empty tabs
   */
  $(window).resize(function() {
    clearTimeout(window.resizedFinished);
    window.resizedFinished = setTimeout(function(){
      $('.hydrated:empty').removeClass('hydrated');
      triggerTabsLoading();
    }, 250);
  });

  triggerTabsLoading(window.location.hash);
});
