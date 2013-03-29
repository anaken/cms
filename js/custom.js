var LOADER = '<div class="loader"></div>';

$(document).ready(function(){
  $.extend({
    json: function(url, params, callback) {
      $.post(url, params, function(result){
        try {
          json = $.parseJSON(result);
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
      $(self).html(LOADER);
      $(self).load(url, post, function(){
        handleHTML(self);
        handleSystemHTML && handleSystemHTML(self);
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
  $selector = $(selector ? selector : 'body');
  $selector.find('.window').dialog({
    height: 'auto',
    modal: true
  });

  $.fancybox && $selector.find('a[rel="fancybox"]').fancybox({
		'titleShow'     : false,
		'transitionIn'	: 'elastic',
		'transitionOut'	: 'elastic',
		'easingIn'      : 'easeOutBack',
		'easingOut'     : 'easeInBack'
	});

  $selector.find('button').button();

  $selector.find('.button').wrapInner('<span class="buttonBgMain"></span>').wrapInner('<span class="buttonBgX"></span>');

  /*
  var leftHeight = $('.layout.left-side').height();
  var bodyHeight = $('.layout.body').height();
  var pos = $('.layout.footer').pos();
  var bpos = $('body').pos();
  var bottom = bpos.bottom > pos.bottom ? bpos.bottom - pos.bottom : 0;
  if (leftHeight > bodyHeight) {
    var height = leftHeight;
  }
  else {
    var height = bodyHeight;
  }
  $('.layout.body').css('min-height', height + bottom);
  $('.layout.left-side').css('min-height', height + bottom);
  */

  handleHTML.initialized = true;
}
handleHTML.initialized = false;

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
    $('.cartBlock').load('/cartBlock/');
    params && params.callback && params.callback();
  }
}