document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('playbookForm');
  var log = document.getElementById('playbookLog');

  var logger = new Logger(log, 4);

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    var values = extractFormValues(form);
    var url = buildUrl('/run_playbook', values);
    var source = new EventSource(url);

    source.addEventListener('open', function (event) {
      logger.log([new Date().toLocaleTimeString(), 'Connected']);
    });

    source.addEventListener('message', function (event) {
      var jsonEvent = JSON.parse(event.data);
      var logTemplate = [new Date().toLocaleTimeString(), jsonEvent.event];

      var eventOutput = formatEvent(jsonEvent);
      for (var lineIndex = 0; lineIndex < eventOutput.length; lineIndex++)
      {
        var line = eventOutput[lineIndex];
        var toLog = logTemplate.slice();

        for (var linePartIndex = 0; linePartIndex < line.msg.length; linePartIndex++)
        {
          var linePart = line.msg[linePartIndex];
          toLog.push(linePart);
        }

        logger.log(toLog, line.level);
      }

      if (jsonEvent.event === 'complete')
      {
        source.close();
      }
    }, false);

    source.addEventListener('error', function (event) {
      logger.log([new Date().toLocaleTimeString(), 'Error: Disconnected']);
    });
  });
});

function buildUrl(path, params)
{
  path += '?';

  var pairs = [];
  for (var key in params)
  {
    if (!params.hasOwnProperty(key))
    {
      continue;
    }

    var value = params[key];
    pairs.push(key + '=' + value)
  }

  path += pairs.join('&');

  return path;
}

function extractFormValues(form)
{
  var valuesFound = {};
  var children = Array.prototype.slice.call(form.children)

  if (children.length > 0)
  {
    for (var childrenIndex = 0; childrenIndex < children.length; childrenIndex++)
    {
      var child = children[childrenIndex];

      if (child.name && child.value)
      {
        var value = null;

        if (child.type && child.type === 'checkbox')
        {
          value = child.checked;
        }
        else
        {
          value = child.value;
        }

        valuesFound[child.name] = value;
      }
      else
      {
        var childValues = extractFormValues(child);

        for (var key in childValues)
        {
          if (!childValues.hasOwnProperty(key))
          {
            continue;
          }

          valuesFound[key] = childValues[key];
        }
      }
    }
  }

  return valuesFound;
}
