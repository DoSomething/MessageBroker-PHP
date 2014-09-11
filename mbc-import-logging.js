var amqp = require('amqp')
    , PHPUnserialize = require('php-unserialize')
    , NodeCurl = require('node-curl')
    , php = require('phpjs')
    ;

// Config settings
var mb_secure_config = require(__dirname + 'mb_secure_config.json');
var mb_config = require(__dirname + '/mb_config.json');

// mb_config = JSON.stringify(mb_config, null, 4);
// console.log(mb_config);

// RabbitMQ
var conn = amqp.createConnection(
  {
    host: mb_secure_config.host,
    port: mb_secure_config.port,
    login: mb_secure_config.login,
    password: mb_secure_config.password,
    vhost: mb_secure_config.vhost,
    noDelay: mb_config.noDelay
  },
  {
    defaultExchangeName: mb_config.rabbit.exchanges
  1.
    logging_timestamp: 1410227722
    media: users-imported
    status: signupCount: 1, skipped: 2, targetCSVFile: bla
    
    a:3:{s:12:"mobile-error";s:16:"Existing account";s:6:"mobile";s:10:"3479886705";s:6:"logged";i:1410228162;}
    a:6:{s:12:"email-status";s:16:"Existing account";s:5:"email";s:23:"gonzalezj1382@yahoo.com";s:8:"acquired";s:19:"2013-07-15 01:46:07";s:12:"mobile-error";s:16:"Existing account";s:6:"mobile";s:10:"3237121646";s:6:"logged";i:1408997542;}
    
  2.
    logging_timestamp: 1410227722
    media: phone
    address: 3479886705
    status: Existing account
    
  3.
    logging_timestamp: 1410227722
    media: email
    address: test@test.com
    status: Existing account
    acquired: 2013-07-15 01:46:07
    
    
      if (this.request.body.logging_timestamp !== undefined) {
    // Convert timestamp string to Date object
    var timestamp = parseInt(this.request.body.logging_timestamp);
    addArgs.logged_date = convertToDate(timestamp);
  }
  if (this.request.body.phone !== undefined) {
    addArgs.phone.number = this.request.body.phone;
    addArgs.phone.status = this.request.body.phone-status;
  }
  if (this.request.body.email !== undefined) {
    addArgs.email.number = this.request.body.email;
    addArgs.email.status = this.request.body.email-status;
    addArgs.email.acquired = this.request.body.email-acquired;
  }
  if (this.request.body.drupal !== undefined) {
    addArgs.drupal.email = this.request.body.email;
    addArgs.drupal.uid = this.request.body.drupal-uid;
  }
  
  
  // quick start
  curl('localhost:4744', function(err) {
    console.info(this.status);
    console.info('-----');
    console.info(this.body);
    console.info('-----');
    console.info(this.info('SIZE_DOWNLOAD'));
  });
  
  // with options
  curl('www.google.com', {VERBOSE: 1, RAW: 1, CURLOPT_POST: 1, CURLOPT_POSTFIELDS: }, function(err) {
    console.info(this);
  });
  
        curl_setopt($ch, CURLOPT_URL, $userApiUrl);
      curl_setopt($ch, CURLOPT_POST, count($post));
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      
/*
 * discuss at: http://phpjs.org/functions/http_build_query/
 *
 * note: If the value is null, key and value are skipped in the http_build_query of PHP while in phpjs they are not.
 * depends on: urlencode
 * example 1: http_build_query({foo: 'bar', php: 'hypertext processor', baz: 'boom', cow: 'milk'}, '', '&amp;');
 * returns 1: 'foo=bar&amp;php=hypertext+processor&amp;baz=boom&amp;cow=milk'
 * example 2: http_build_query({'php': 'hypertext processor', 0: 'foo', 1: 'bar', 2: 'baz', 3: 'boom', 'cow': 'milk'}, 'myvar_');
 * returns 2: 'myvar_0=foo&myvar_1=bar&myvar_2=baz&myvar_3=boom&php=hypertext+processor&cow=milk'
 */
function http_build_query(formdata, numeric_prefix, arg_separator) {

  var value, key, tmp = [],
    that = this;

  var _http_build_query_helper = function(key, val, arg_separator) {
    var k, tmp = [];
    if (val === true) {
      val = '1';
    } else if (val === false) {
      val = '0';
    }
    if (val != null) {
      if (typeof val === 'object') {
        for (k in val) {
          if (val[k] != null) {
            tmp.push(_http_build_query_helper(key + '[' + k + ']', val[k], arg_separator));
          }
        }
        return tmp.join(arg_separator);
      } else if (typeof val !== 'function') {
        return that.urlencode(key) + '=' + that.urlencode(val);
      } else {
        throw new Error('There was an error processing for http_build_query().');
      }
    } else {
      return '';
    }
  };

  if (!arg_separator) {
    arg_separator = '&';
  }
  for (key in formdata) {
    value = formdata[key];
    if (numeric_prefix && !isNaN(key)) {
      key = String(numeric_prefix) + key;
    }
    var query = _http_build_query_helper(key, value, arg_separator);
    if (query !== '') {
      tmp.push(query);
    }
  }

  return tmp.join(arg_separator);
}
      
/*
 * discuss at: http://phpjs.org/functions/unserialize/
 *
 * note: We feel the main purpose of this function should be to ease the transport of data between php & js
 * note: Aiming for PHP-compatibility, we have to translate objects to arrays
 * example 1: unserialize('a:3:{i:0;s:5:"Kevin";i:1;s:3:"van";i:2;s:9:"Zonneveld";}');
 * returns 1: ['Kevin', 'van', 'Zonneveld']
 * example 2: unserialize('a:3:{s:9:"firstName";s:5:"Kevin";s:7:"midName";s:3:"van";s:7:"surName";s:9:"Zonneveld";}');
 * returns 2: {firstName: 'Kevin', midName: 'van', surName: 'Zonneveld'}
 */
function unserialize(data) {

  var that = this,
    utf8Overhead = function(chr) {
      // http://phpjs.org/functions/unserialize:571#comment_95906
      var code = chr.charCodeAt(0);
      if (code < 0x0080) {
        return 0;
      }
      if (code < 0x0800) {
        return 1;
      }
      return 2;
    };
  error = function(type, msg, filename, line) {
    throw new that.window[type](msg, filename, line);
  };
  read_until = function(data, offset, stopchr) {
    var i = 2,
      buf = [],
      chr = data.slice(offset, offset + 1);

    while (chr != stopchr) {
      if ((i + offset) > data.length) {
        error('Error', 'Invalid');
      }
      buf.push(chr);
      chr = data.slice(offset + (i - 1), offset + i);
      i += 1;
    }
    return [buf.length, buf.join('')];
  };
  read_chrs = function(data, offset, length) {
    var i, chr, buf;

    buf = [];
    for (i = 0; i < length; i++) {
      chr = data.slice(offset + (i - 1), offset + i);
      buf.push(chr);
      length -= utf8Overhead(chr);
    }
    return [buf.length, buf.join('')];
  };
  _unserialize = function(data, offset) {
    var dtype, dataoffset, keyandchrs, keys, contig,
      length, array, readdata, readData, ccount,
      stringlength, i, key, kprops, kchrs, vprops,
      vchrs, value, chrs = 0,
      typeconvert = function(x) {
        return x;
      };

    if (!offset) {
      offset = 0;
    }
    dtype = (data.slice(offset, offset + 1))
      .toLowerCase();

    dataoffset = offset + 2;

    switch (dtype) {
      case 'i':
        typeconvert = function(x) {
          return parseInt(x, 10);
        };
        readData = read_until(data, dataoffset, ';');
        chrs = readData[0];
        readdata = readData[1];
        dataoffset += chrs + 1;
        break;
      case 'b':
        typeconvert = function(x) {
          return parseInt(x, 10) !== 0;
        };
        readData = read_until(data, dataoffset, ';');
        chrs = readData[0];
        readdata = readData[1];
        dataoffset += chrs + 1;
        break;
      case 'd':
        typeconvert = function(x) {
          return parseFloat(x);
        };
        readData = read_until(data, dataoffset, ';');
        chrs = readData[0];
        readdata = readData[1];
        dataoffset += chrs + 1;
        break;
      case 'n':
        readdata = null;
        break;
      case 's':
        ccount = read_until(data, dataoffset, ':');
        chrs = ccount[0];
        stringlength = ccount[1];
        dataoffset += chrs + 2;

        readData = read_chrs(data, dataoffset + 1, parseInt(stringlength, 10));
        chrs = readData[0];
        readdata = readData[1];
        dataoffset += chrs + 2;
        if (chrs != parseInt(stringlength, 10) && chrs != readdata.length) {
          error('SyntaxError', 'String length mismatch');
        }
        break;
      case 'a':
        readdata = {};

        keyandchrs = read_until(data, dataoffset, ':');
        chrs = keyandchrs[0];
        keys = keyandchrs[1];
        dataoffset += chrs + 2;

        length = parseInt(keys, 10);
        contig = true;

        for (i = 0; i < length; i++) {
          kprops = _unserialize(data, dataoffset);
          kchrs = kprops[1];
          key = kprops[2];
          dataoffset += kchrs;

          vprops = _unserialize(data, dataoffset);
          vchrs = vprops[1];
          value = vprops[2];
          dataoffset += vchrs;

          if (key !== i)
            contig = false;

          readdata[key] = value;
        }

        if (contig) {
          array = new Array(length);
          for (i = 0; i < length; i++)
            array[i] = readdata[i];
          readdata = array;
        }

        dataoffset += 1;
        break;
      default:
        error('SyntaxError', 'Unknown / Unhandled data type(s): ' + dtype);
        break;
    }
    return [dtype, dataoffset - offset, typeconvert(readdata)];
  };

  return _unserialize((data + ''), 0)[2];
}

/*
 * discuss at: http://phpjs.org/functions/urlencode/
 *
 * note: This reflects PHP 5.3/6.0+ behavior
 * note: Please be aware that this function expects to encode into UTF-8 encoded strings, as found on
 * note: pages served as UTF-8
 * example 1: urlencode('Kevin van Zonneveld!');
 * returns 1: 'Kevin+van+Zonneveld%21'
 * example 2: urlencode('http://kevin.vanzonneveld.net/');
 * returns 2: 'http%3A%2F%2Fkevin.vanzonneveld.net%2F'
 * example 3: urlencode('http://www.google.nl/search?q=php.js&ie=utf-8&oe=utf-8&aq=t&rls=com.ubuntu:en-US:unofficial&client=firefox-a');
 * returns 3: 'http%3A%2F%2Fwww.google.nl%2Fsearch%3Fq%3Dphp.js%26ie%3Dutf-8%26oe%3Dutf-8%26aq%3Dt%26rls%3Dcom.ubuntu%3Aen-US%3Aunofficial%26client%3Dfirefox-a'
 */
function urlencode(str) {

  str = (str + '')
    .toString();

  // Tilde should be allowed unescaped in future versions of PHP (as reflected below), but if you want to reflect current
  // PHP behavior, you would need to add ".replace(/~/g, '%7E');" to the following.
  return encodeURIComponent(str)
    .replace(/!/g, '%21')
    .replace(/'/g, '%27')
    .replace(/\(/g, '%28')
    .
  replace(/\)/g, '%29')
    .replace(/\*/g, '%2A')
    .replace(/%20/g, '+');
}