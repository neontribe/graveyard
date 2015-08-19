/***********
Exposed API.
************/

var colors = {
  'FATAL': 'red',
  'FAIL': 'orange',
  'INFO': 'blue',
  'STATUS': 'black',
  'SAME': 'green',
  'DIFFERENT': 'gold'
}

function formatEvent(event)
{
  // get formatter for event type
  var eventFormatter = _formatters[event.event];

  var logged = [];
  function log(template, parameters, level)
  {
    var msg = ['<' + level + '>', _formatString(template, parameters)];
    var color = level in colors ? colors[level] : colors.NEUTRAL;
    logged.push({ 'msg': msg, 'color': color });
  }

  eventFormatter(log, event)

  return logged;
}


/***************************************************
Ported python util functions to make porting easier.
***************************************************/

function _formatString(toFormat, replacements)
{
  for (var toReplace in replacements)
  {
    if (!replacements.hasOwnProperty(toReplace))
    {
      continue;
    }

    var value = replacements[toReplace];
    toReplace = '%' + toReplace + '%';

    var regex = new RegExp(toReplace, 'g');
    toFormat = toFormat.replace(regex, value);
  }

  return toFormat;
}

/**
A function used in place of python's dict.get function.
*/
function _get(dict, property, defValue)
{
  if (property in dict)
  {
    return dict[property];
  }

  return defValue;
}

/**
A function used in place of python's dict.pop function.
*/
function _pop(dict, property, defValue)
{
  var value = _get(dict, property, defValue);
  delete dict[property];
  return value;
}


/***************************************************************************************************************
Direct source port of:
  https://github.com/ansible/ansible/blob/62a1efa0c6712bfea794d870f22bd178b1452e20/lib/ansible/callbacks.py#L443
    &
  https://github.com/ansible/ansible/blob/62a1efa0c6712bfea794d870f22bd178b1452e20/lib/ansible/callbacks.py#L593
That's why it sucks so much.
***************************************************************************************************************/

var _formatters = {
  /************
  Runner Events
  ************/

  'failed': function (log, event) {
    var host = event.host;
    var res = event.res;
    var ignoreErrors = event.ignore_errors;

    _pop(res, 'invocation', null);

    var item = _get(res, 'item', null);
    var parsed = _get(res, 'parsed', true);
    var moduleMsg = '';
    if (!parsed)
    {
      moduleMsg = _pop(res, 'msg', null);
    }
    var stderr = _pop(res, 'stderr', null);
    var stdout = _pop(res, 'stdout', null);
    var returnedMsg = _pop(res, 'msg', null);

    var message;
    if (item)
    {
      message = '[%host%] => (item=%item%) => %results%';
      log(message, { host: host, item: item, results: JSON.stringify(res) }, 'FAIL');
    }
    else
    {
      message = '[%host%] => %results%';
      log(message, { host: host, results: JSON.stringify(res) }, 'FAIL');
    }

    if (stderr)
    {
      log('stderr: %output%', { output: stderr }, 'FAIL');
    }

    if (stdout)
    {
      log('stdout: %output%', { output: stdout }, 'FAIL');
    }

    if (returnedMsg)
    {
      log('msg: %text%', { text: returnedMsg }, 'FAIL');
    }

    if (!parsed && moduleMsg)
    {
      log(moduleMsg, {}, 'FAIL');
    }

    if (ignoreErrors)
    {
      log('...ignoring', {}, 'INFO');
    }
  },

  'ok': function (log, event) {
    var host = event.host;
    var res = event.res;

    var item = _get(res, 'item', null);
    _pop(res, 'invocation', null);
    var changed = _get(res, 'changed', false);

    var message = '';
    if (item)
    {
      message = '[%host%] => (item=%item%)';
      log(message, { host: host, item: item }, changed ? 'DIFFERENT' : 'SAME');
    }
    else if ((!('ansible_job_id' in res)) || 'finished' in res)
    {
      message = '[%host%]';
      log(message, { host: host }, changed ? 'DIFFERENT' : 'SAME');
    }
  },

  'skipped': function (log, event) {
    var host = event.host;
    var item = event.item;

    var message;
    if (item)
    {
      message = 'Skipping [%host%] => (item=%item%)';
      log(message, { host: host, item: item }, 'INFO');
    }
    else
    {
      message = 'Skipping [%host%]';
      log(message, { host: host }, 'INFO');
    }

    return;
  },

  'unreachable': function (log, event) {
    var host = event.host;
    var res = event.res;

    var item = null;

    if (typeof(res) === 'object')
    {
      item = _get(results, 'item', null);
    }

    if (item)
    {
      log('[%host%] => (item=%item%) => %res%', { host: host, item: item, res: res }, 'FATAL');
    }
    else
    {
      log('[%host%] => %res%', { host: host, res: res }, 'FATAL');
    }
  },

  'no_hosts': function (log, event) {
    log('No hosts matched or all hosts have already failed, aborting', {}, 'FATAL');
  },

  /**************
  PlayBook Events
  **************/

  'start': function (log, event) {
    log('PlayBook execution started', {}, 'STATUS');
  },

  'notify': function (log, event) {
    var host = event.host;
    var handler = event.handler;

    // ansible does not deem this important enough to log
    return;
  },

  'no_hosts_matched': function (log, event) {
    log('Skipping: no hosts matched', {}, 'INFO');
  },

  'no_hosts_remaining': function (log, event) {
    log('All hosts have already failed, aborting', {}, 'FATAL');
  },

  'task_start': function (log, event) {
    var name = event.name;
    var conditional = event.conditional;

    var message = "TASK: [%name%]";
    if (conditional)
    {
      message = "NOTIFIED: [%name%]";
    }

    log(message, { name: name }, 'STATUS');
  },

  'prompt': function (log, event) {
    var varname = event.varname;
    var isPrivate = event.private;
    var prompt = event.prompt;
    var encrypt = event.encrypt;
    var confirm = event.confirm;
    var saltSize = event.salt_size;
    var salt = event.salt;
    var defaultValue = event.default;

    var messge = prompt ? prompt : varname;

    log('Script asked for a prompt, with message "%msg%"', { msg: message }, 'FATAL');
  },

  'setup': function (log, event) {
    log('Gathering facts...', {}, 'STATUS')
  },

  'import_for_host': function (log, event) {
    var host = event.host;
    var importedFile = event.importedFile;

    log('"%host%": importing "%importedFile%"', { host: host, importedFile: importedFile }, 'INFO');
  },

  'not_import_for_host': function (log, event) {
    var host = event.host;
    var missingFile = event.missing_file;

    log('"%host%": NOT importing "%missingFile%"', { host: host, missingFile: missingFile }, 'INFO');
  },

  'play_start': function (log, event) {
    var name = event.name;

    log('Starting play "%name%"', { name: name }, 'STATUS');
  },

  'stats': function (log, event) {
    var stats = event.stats;

    // ansible does not deem this important enough to log
    return;
  },

  'complete': function (log, event) {
    log('Completed PlayBook execution.', {}, 'STATUS');
  }
}
