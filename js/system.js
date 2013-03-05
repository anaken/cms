$(function(){
  $('body').append('<div class="jstemp"></div>');

  handleSystemHTML();
});

function handleSystemHTML(selector) {
  $selector = $(selector ? selector : 'body');
  $selector.find('.objectAddBtn').button({
    icons: {
      primary: "ui-icon-plus"
    },
    text: false
  });
  $selector.find('.objectEditBtn').button({
    icons: {
      primary: "ui-icon-pencil"
    },
    text: false
  });
  $selector.find('.objectDelBtn').button({
    icons: {
      primary: "ui-icon-close"
    },
    text: false
  });

  $('.crudListSortable').sortable({
    stop: function( event, ui ) { crud.resort(this); }
  });

  $selector.find('.datepicker').datepicker({ dateFormat: 'yy-mm-dd' });
}

var crud = {
  form: function(object, id, params){
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
      $('.crudForm'+dialogName).dialog({
        width  : 600,
        height : 'auto',
        modal  : false,
        buttons: {
          'Сохранить': function(){
            $('.crudForm'+dialogName+' .ckeditor').each(function(){
              editorId = $(this).attr('name');
              $(this).val(CKEDITOR.instances[editorId].getData());
            });
            $.json('/crud/save/', $('.crudForm'+dialogName).inputs(), function(data){
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
              $('.crudForm'+dialogName).dialog('close');
            });
          }
        }
      });
      $('.crudForm'+dialogName).dialog('open');
      crud.handleForm(dialogName);
    });
  },
  
  del: function(object, id, params){
    if ( ! confirm('Удалить?')) {
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
  },

  upload: function(number, image_id){
    $('#file', $('#uploadFile'+number).contents()).click();
  },

  uploaded: function(number, image_id, file){
    $('.imageHiddenField'+number).val(image_id);
    $('.imageView'+number).html('<img src="'+file+'"/>');
  },

  handleForm: function(name){
    $('button').button();
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
    $list = $(list);
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
      $('.crudReport'+dialogName).dialog({
        width  : 800,
        height : 'auto',
        modal  : false
      });
      $('.crudReport'+dialogName).dialog('open');
      callback && callback();
    });
  }
}