var statusElement = $('#status');

function refreshFiletree(refresh)
{
  statusElement.text((refresh ? 'Updating' : 'Loading') + ' filetree...');
  $.ajax({
    type:'GET',
    url	:'/get-filetree',
    data:'forDisplay=true&refresh=' + refresh,
    success: function(data){
      $('#container').jstree({
        'core': data
      });
      statusElement.text('Successfully ' + (refresh ? 'updated' : 'loaded cached')+ ' filetree.');
    },
    failure: function(data){
      statusElement.text('Could not ' + (refresh ? 'update' : 'load') + ' the filetree.');
    }
  });
}

$("#refresh").click(function(){
  refreshFiletree(true);
});

refreshFiletree(false);
