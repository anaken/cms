var LOADER = '<div class="loading"></div>';

$(document).ready(function(){
  $('body').append('<div class="jstemp"></div>');

  (function($){
    $.expr[':'].renders = function(a, k, m, r) {
      var i = $(a).pos();
      var w = {
        left   : window.scrollX,
        top    : window.scrollY,
        right  : window.innerWidth + window.scrollX,
        bottom : window.innerHeight + window.scrollY
      };
      return ! ( i.bottom < w.top || i.top > w.bottom || i.right < w.left || i.left > w.right );
    };

    $.originalPostFunc = $.post;
    $.post = function(a1, a2, a3, a4) {
      var af, f;
      $('body').append(LOADER);
      if (typeof a3 == 'function') {
        af = a3;
      }
      else if (typeof a2 == 'function') {
        af = a2;
        a4 = a3;
      }
      f = function(data, textStatus, jqXHR){
        $('.loading').remove();
        return af(data, textStatus, jqXHR);
      };
      return $.originalPostFunc(a1, a2, f, a4);
    };

    $.fn.originalButtonFunc = $.fn.button;
    $.fn.button = function(params) {
      var result = $(this.get()).originalButtonFunc(params);
      $(this.get()).filter('.hide').hide();
      return result;
    };
  })(jQuery);

  $.extend({
    json: function(url, params, callback) {
      $.post(url, params, function(result){
        try {
          var json = $.parseJSON(result);
          if (json.e != 0) {
            alert(json.msg);
            return;
          }
          callback && callback(json);
        }
        catch (e) {
          alert(result);
          return;
        }
      });
    }
  });

  jQuery.fn.extend({
    inputs: function() {
      var inputs = $(this).find(":input");
      inputs.each(function() {
        if ($(this).attr('type') == 'checkbox') {
          $(this).val($(this).attr('checked'));
        }
      });
      return inputs;
    },

    pull: function(url, post, callback) {
      var self = this.get(0);
      $.post(url, post, function(html){
        $(self).html(html);
        handleHTML(self);
        callback && callback();
      });
    },

    pos: function() {
      var left = 0;
    	var top  = 0;
    	var elem = this.get(0);
      if ( ! elem) {
        return {};
      }
    	var width = elem.offsetWidth;
    	var height = elem.offsetHeight;
    	var scrollTop = window.pageYOffset == undefined ? document.documentElement.scrollTop : window.pageYOffset;

    	if (elem.getBoundingClientRect) {
    		var bounds = elem.getBoundingClientRect();
    		left = bounds.left;
    		top = bounds.top + scrollTop;
    	} else {
    		while (elem != null) {
    			left += elem.offsetLeft;
    			top  += elem.offsetTop;
    			elem  = elem.offsetParent;
    		}
    	}
    	return {left: left, top: top, right: left+width, bottom: top+height};
    }
  });

  handleHTML();
});

function handleHTML(selector) {
  var $selector = $(selector ? selector : 'body');

  setTimeout(function(){ // <-- fuckin jqueryui
    $selector.find('.window').dialog({
      height: 'auto',
      modal: false
    });
  }, 1);

  $.fancybox && $selector.find('a[rel="fancybox"]').fancybox({
		'titleShow'     : false,
		'transitionIn'	: 'elastic',
		'transitionOut'	: 'elastic',
		'easingIn'      : 'easeOutBack',
		'easingOut'     : 'easeInBack'
	});

  $selector.find('button').each(function(){
    var params = {};
    if ($(this).attr('icon')) {
      params.icons = { primary: "ui-icon-" + $(this).attr('icon') };
    }
    params.text = $(this).attr('button-type') != 2;
    $(this).button(params);
  });

  $selector.find('.radio').not('.mwcmsh').addClass('mwcmsh').click(function(){
    mwcms.radio(this);
  });
  $selector.find('.checkbox').not('.mwcmsh').addClass('mwcmsh').click(function(){
    mwcms.checkbox(this);
  });

  handleHTML.initialized = true;
}
handleHTML.initialized = false;

var mwcms = { /* middle way content management system */
  radio: function(self){
    var name = $(self).find('input').attr('name');
    $('.radio input[name="'+name+'"]').closest('.radio').find('span').removeClass('radioActive');
    $(self).find('span').addClass('radioActive');
  },

  checkbox: function(self){
    var func = $(self).find('input:checked').length ? 'addClass' : 'removeClass';
    $(self).find('span')[func]('checkboxActive');
  }
}

var cart = {
  add: function(goodId, cnt){
    $.cookie('goods['+goodId+']', cnt, { path: '/', expires: 365 });
    $('.buyGoodsAdd'+goodId).addClass('hide');
    $('.buyGoodsDel'+goodId).removeClass('hide');
    cart.reload();
  },
  del: function(goodId, params){
    $.cookie('goods['+goodId+']', '', { path: '/', expires: -1 });
    $('.buyGoodsAdd'+goodId).removeClass('hide');
    $('.buyGoodsDel'+goodId).addClass('hide');
    var reloadParams = {};
    if (params && params.callback) {
      reloadParams.callback = params.callback;
    }
    cart.reload(reloadParams);
  },
  reload: function(params){
    $('.cartBlock').pull('/cartBlock/');
    params && params.callback && params.callback();
  }
}