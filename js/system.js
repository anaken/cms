$(function(){
  (function($){
    $.fn.crudDialog = function(params) {
      params = $.extend({
        show: {
          effect: "fade", // fade | puff | scale
          duration: 400
        },
        hide: {
          effect: "fade",
          duration: 400
        },
        resizable: true
      }, params);
      return $(this.get()).dialog(params);
    };
  })(jQuery);

  handleSystemHTML();
});

function handleSystemHTML(selector) {
  var $selector = $(selector ? selector : 'body');

  $('.crudListSortable').sortable({
    stop: function( event, ui ) { crud.resort(this); }
  });

  $selector.find('.datepicker').datepicker({ dateFormat: 'yy-mm-dd' });

  $selector.find(".crudMenu ul").menu();
  $selector.find(".showCrudMenu").click(function(){
    if ($('.crudMenu:visible').length) {
      $('.crudMenu').hide();
    }
    else {
      $('.crudMenu').show();
    }
  });
  $selector.find(".crudMenu ul li").click(function(){
    $('.crudMenu').hide();
  });
}

var crud = {
  form: function(self, params){
    var object = self && $(self).attr('data-table') ? $(self).attr('data-table') : params.table;
    var id     = self && $(self).attr('data-id')    ? $(self).attr('data-id')    : (typeof params == 'undefined' ? null : params.id);
    var dialogName = object;
    $('.crudForm'+dialogName).dialog("destroy");
    $('.crudForm'+dialogName).remove();
    var post = {
      object : object,
      id     : id
    };
    if (params && params.defaults) {
      post['defaults'] = params.defaults;
    }
    $.post('/crud/form/', post, function(data){
      $('.jstemp').append(data);
      $('.crudForm'+dialogName).crudDialog({
        width  : 600,
        height : 'auto',
        modal  : false,
        buttons: {
          'Сохранить': function(){
            crud.save($('.crudForm'+dialogName).get(0), params);
          }
        }
      });
      $('.crudForm'+dialogName).dialog('open');
      crud.handleForm(dialogName);
    });
    return false;
  },

  save: function(self, params) {
    $(self).find('.ckeditor').each(function(){
      $(this).val(CKEDITOR.instances[$(this).attr('name')].getData());
    });
    $.json('/crud/save/', $(self).inputs(), function(data){
      var relocate = true;
      if (params && params.callback) {
        relocate = params.callback();
      }
      if (params && params.appendToList && data.id) {
        relocate = false;
        $(params.appendToList).append('<option value="' + data.id + '">' + data.name + '</option>');
      }
      if (relocate !== false) {
        document.location = document.location.toString();
      }
      $(self).dialog('close');
    });
  },

  del: function(self, params){
    var object = self && $(self).attr('data-table') ? $(self).attr('data-table') : params.table;
    var id     = self && $(self).attr('data-id')    ? $(self).attr('data-id') : params.id;
    if ( ( typeof params == "undefined" || ! params.force) && ! confirm('Удалить?')) {
      return;
    }
    $.json('/crud/del/', {
      id     : id,
      object : object
    }, function(){
      var relocate = true;
      if (params && params.removeFrom) {
        relocate = false;
        $(params.removeFrom + ' option[value="' + id + '"]').remove();
      }
      if (relocate !== false) {
        document.location = document.location.toString();
      }
    });
    return false;
  },

  childs: function(self, params){
    var $menu = $(self).closest('.crudChildsPlace').find('.crudChilds');
    if ($menu.length) {
      $menu.toggle();
      return;
    }
    var object = self && $(self).attr('data-table') ? $(self).attr('data-table') : params.table;
    var id     = self && $(self).attr('data-id')    ? $(self).attr('data-id') : params.id;
    $.json('/crud/childs/', {
      id     : id,
      object : object
    }, function(result){
      var childs = result.childs;
      var childsHTML = [];
      for (var i in childs) {
        childsHTML.push('<li><a onclick="crud.report(\''+childs[i].name+'\', {params: {\''+i+'\': \''+id+'\'}, edit: 1, order: \'id desc\', limit: 50});$(this).closest(\'.crudChilds\').hide();return false" href="#">'+childs[i].caption+'</a></li>');
      }
      var menu = '<ul class="crudChilds">'+childsHTML.join('')+'</ul>';
      $(self).closest('.crudChildsPlace').append(menu);
      $(self).closest('.crudChildsPlace').find('.crudChilds').menu();
    });
    return false;
  },

  upload: function(ident){
    $('#file', $('#crudImageView'+ident+' iframe').contents()).click();
  },

  uploaded: function(ident, files){
    var inputs = '';
    var inputName = $('#crudImageView'+ident).attr('data-name');
    for (var i in files) {
      inputs += '<div class="crudImagesInput"><button button-type="2" icon="close" class="formatImageRemove objectDelBtn" onclick="crud.removeImage(this)">удалить</button><input onclick="crud.removeImage(this)" type="hidden" name="'+inputName+'" value="'+files[i].id+'"/><img src="'+files[i].file+'"/></div>';
    }
    $('#crudImageView'+ident+' .crudImagesInputs').append(inputs);
    handleHTML('#crudImageView'+ident+' .crudImagesInputs');
  },

  handleForm: function(name){
    handleHTML('.crudForm'+name);
    handleSystemHTML('.crudForm'+name);
    $('.crudForm'+name+' .crudFormTable .formatInList').change(function(){
      var $tableInput = $(this).closest('.crudFormTable').find('input[name="_table_"]');
      if ( ! $tableInput.length) {
        return;
      }
      $.cookie('crud.'+$tableInput.val()+'.'+$(this).attr('name'), $(this).val(), { path: '/', expires: 365 });
    });

    var $tableInput = $('.crudForm'+name+' .crudFormTable').find('input[name="_table_"]');
    if ($tableInput.length) {
      $('.crudForm'+name+' .crudFormTable .formatInList').each(function(){
        if ($(this).val()) {
          return;
        }
        var cookieValue = $.cookie('crud.'+$tableInput.val()+'.'+$(this).attr('name'));
        $(this).find('option').removeAttr('selected');
        $(this).find('option[value="'+cookieValue+'"]').attr('selected', true);
      });
    }
  },

  resort: function(list){
    var items = [];
    var $list = $(list);
    $list.find('> *').each(function(){
      items.push($(this).attr('crud-id'));
    });
    $.json('/crud/resort/', {
      order  : items,
      object : $list.attr('crud-object')
    });
  },

  report: function(object, params, callback){
    var dialogName = object;
    $('.crudReport'+dialogName).dialog("destroy");
    $('.crudReport'+dialogName).remove();
    params = $.extend(params, {table: object});
    $.post('/crud/report/', params, function(data){
      $('.jstemp').append(data);
      $('.crudReport'+dialogName).crudDialog({
        width  : 920,
        height : 'auto',
        modal  : false
      });
      $('.crudReport'+dialogName).dialog('open');
      handleSystemHTML('.crudReport'+dialogName);
      handleHTML('.crudReport'+dialogName);
      callback && callback();
    });
  },

  removeImage: function(btn) {
    $(btn).closest('.crudImagesInput').remove();
  }
}