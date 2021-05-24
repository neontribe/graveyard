var statusElement = $('#status');

function getVersionsFromFiletree(tree) {
  var flat = [];
  for (var key in tree) {
    if (!tree.hasOwnProperty(key)) {
      continue;
    }

    var node = {};
    node.text = key;
    node.children = [];
    for (var child in tree[key]) {
      if (!tree[key].hasOwnProperty(child)) {
        continue;
      }
      if (child === 'version') {
        node.children.push('version => ' + tree[key][child]);
      } else {
        node.children.push(getVersionsFromFiletree(tree[key][child]));
      }
    }
    flat.push(node);
  }
  return flat;
}

function formatForJsTree(data) {
  var results = []
  for (var serverCode in data) {
    if (!data.hasOwnProperty(serverCode)) {
      continue;
    }

    var serverResults = data[serverCode];
    if (serverResults.meta.status.toLowerCase() === 'ok') {
      var versions = getVersionsFromFiletree(serverResults.data.path);
      results.push({ 'text': serverCode, 'children': versions });
    } else {
      results.push(serverCode);
    }
  }
  return { 'data': results };
}

function refreshFiletree(refresh) {
  statusElement.text((refresh ? 'Updating' : 'Loading') + ' filetree...');
  $.ajax({
    type: 'GET',
    url: '/get_filetree',
    data: 'for_display=true&refresh=' + refresh,
    success: function (data) {
      var formatted = formatForJsTree(data);

      $('#container').jstree('destroy');
      $('#container').jstree({
        'check_callback': true,
        'core': formatted
      });
      statusElement.text('Successfully ' + (refresh ? 'updated' : 'loaded cached')+ ' filetree.');
    },
    failure: function (data) {
      statusElement.text('Could not ' + (refresh ? 'update' : 'load') + ' the filetree.');
    }
  });
}

$('#refresh').click(function () {
  refreshFiletree(true);
});

refreshFiletree(false);
