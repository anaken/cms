(function(){
  var old = $.ui.dialog.prototype._create;
  $.ui.dialog.prototype._create = function(d){
    old.call(this, d);
    var self = this,
      options = self.options,
      oldHeight = options.height,
      oldWidth = options.width,
      uiDialogTitlebarFull = $('<button></button>')
        .addClass(
          'ui-dialog-titlebar-full'
        )
        .toggle(
          function() {
            self._setOptions({
              height : window.innerHeight - 10,
              width : window.innerWidth - 30
            });
            self._position('center');
            return false;
          },
          function() {
            self._setOptions({
              height : oldHeight,
              width : oldWidth
            });
            self._position('center');
            return false;
          }
        )
        .button({
          icons: {
            primary: "ui-icon-newwin"
          },
          text: false
        })
        .appendTo(self.uiDialogTitlebar)

  };
})();