(function($) {

  $('.Kudos a').live('click', function(evt) {
    evt.preventDefault();

    var LikeSpan = $(this).parent();
    var row = LikeSpan.parents('li.Comment');
    LikeSpan.html('<span class="TinyProgress">&nbsp;</span>');    

    $.get(this.href, {
      DeliveryType : 'BOOL',
      DeliveryMethod : 'JSON'
    }, function(Data) {
      if (Data.StatusMessage) {
        gdn.inform(Data.StatusMessage);

      }
      
      var item = Data.KudosItem;

      if (Data.KudosNewLink) {
        LikeSpan.html(Data.KudosNewLink);

      }
      if (Data.KudosKudos) {
        $('#'+item+'_'+Data.KudosID+' div.ItemLikes ').replaceWith(Data.KudosKudos);
      }
      
      if (Data.CommentDelete) {      	
      	$(row).slideUp('fast', function() { $(this).remove(); });
      }

    }, 'json');

  });  

})(jQuery);