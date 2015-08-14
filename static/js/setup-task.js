document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('playbookForm');
  var log = document.getElementById('playbookLog');

  var logger = new Logger(log);

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    var values = extractFormValues(form);
    var url = buildUrl('/run_playbook', values);
    var source = new EventSource(url);

    source.addEventListener('open', function (event) {
      logger.log([new Date().toLocaleTimeString(), 'Connected']);
      // TODO: this is literally the worst hack I have ever written. It stops
      //       the web browser from trying to repeatedly connect after the
      //       connection has been closed. I don't know why. I'm sorry if you
      //       have to read this or - worse - change this.
      throw Error();
    });

    source.addEventListener('message', function (event) {
      var jsonEvent = JSON.parse(event.data);
      var toLog = [new Date().toLocaleTimeString(), jsonEvent.event];
      if (jsonEvent.res)
      {
        toLog.push(JSON.stringify(jsonEvent.res));
      }
      logger.log(toLog);
    }, false);

    source.addEventListener('error', function (event) {
      logger.log([new Date().toLocaleTimeString(), 'Disconnected']);
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
