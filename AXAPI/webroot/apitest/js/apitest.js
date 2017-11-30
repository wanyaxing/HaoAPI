var currentUrl;
var xhrTestingApi = null;
var todayPassedTime = null;
var _keyTypes = {};
var _sortType = null;
var descriptionsList = {};


Date.prototype.format = function(format)
 {
    var o =
    {
        "M+": this.getMonth() + 1,
        //month
        "d+": this.getDate(),
        //day
        "h+": this.getHours(),
        //hour
        "m+": this.getMinutes(),
        //minute
        "s+": this.getSeconds(),
        //second
        "q+": Math.floor((this.getMonth() + 3) / 3),
        //quarter
        "S": this.getMilliseconds()
        //millisecond

    }

    if (/(y+)/.test(format))
    {
        format = format.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));

    }

    for (var k in o)
    {
        if (new RegExp("(" + k + ")").test(format))
        {
            format = format.replace(RegExp.$1, RegExp.$1.length == 1 ? o[k] : ("00" + o[k]).substr(("" + o[k]).length));

        }

    }
    return format;

}

//'2012-11-16 10:36:50';
function datetime_to_unix(datetime) {
    if (datetime == '')
    {
        return 0;

    }
    else if (parseInt(datetime) == datetime)
    {
        return parseInt(datetime);

    }
    var tmp_datetime = datetime.replace(/:/g, '-');
    tmp_datetime = tmp_datetime.replace(/ /g, '-');
    var arr = tmp_datetime.split("-");
    var now = new Date(Date.UTC(arr[0], arr[1] - 1, arr[2], arr[3] - 8, arr[4], arr[5]));
    return parseInt(now.getTime() / 1000);

}

//'2012-11-16 10:36:50';
function unix_to_datetime(unix) {
    var now = new Date(parseInt(unix) * 1000);
    return now.toLocaleString().replace(/年|月/g, "-").replace(/日/g, " ");

}


function JSON_stringify(s, emit_unicode)
 {
    var json = JSON.stringify(s);
    return emit_unicode ? json: json.replace(/[\u007f-\uffff]/g,
    function(c) {
        return '\\u' + ('0000' + c.charCodeAt(0).toString(16)).slice( - 4);

    }
    );

}



function range_to_badge(range)
 {
    var s = '';
    if (range < 0)
    {
        }
    else if (range < 60 * 60)
    {
        s = '<span class="badge badge-soon">刚才</span>';

    }
    else
    {
        if (!todayPassedTime)
        {
            todayPassedTime = (new Date()).getTime() / 1000 - datetime_to_unix((new Date().format('yyyy-MM-dd 00:00:00')));

        }

        range = range + 60 * 60 * 24 - todayPassedTime;

        if (range < 60 * 60 * 24)
        {
            s = '<span class="badge badge-today">今天</span>';

        }
        else if (range < 60 * 60 * 24 * 2)
        {
            s = '<span class="badge badge-yesterday">昨天</span>';

        }
        else if (range < 60 * 60 * 24 * 3)
        {
            s = '<span class="badge badge-beforeyester">前天</span>';

        }
        else if (range < 60 * 60 * 24 * 365)
        {
            s = '<span class="badge">' + (parseInt(range / 60 / 60 / 24)) + '天前</span>';

        }
        // else if (range< 60*60*24*7)
        // {
        //   s = '<span class="badge">本周</span>';
        // }
        // else if (range< 60*60*24*15)
        // {
        //   s = '<span class="badge">半月内</span>';
        // }
        // else if (range< 60*60*24*2)
        // {
        //   s = '<span class="badge">今天</span>';
        // }
        // else if (range< 60*60*24*2)
        // {
        //   s = '<span class="badge">今天</span>';
        // }
        // else if (range< 60*60*24*2)
        // {
        //   s = '<span class="badge">今天</span>';
        // }

    }
    return s;

}

/**
 * 自定义pretty-json展开方案，Local结尾的key不展开
 */
function expandAll(node)
 {
    // console.log(node);
    for (var i in node.childs)
    {
        var child = node.childs[i];
        if (child instanceof PrettyJSON.view.Node)
        {
            if (!child.path.match(/.*Local$/))
            {
                child.show();
                expandAll(child);

            }

        }

    }

}

function searchApiKey(_this, _isSelect)
 {
    if (_isSelect)
    {
        _this.select();

    }
    var _keyType = $(_this).val();
    if (_keyType == '')
    {
        return;

    }
    $(_this).addClass("btn-primary").siblings().removeClass("btn-primary");
    $(this).attr('keytype', _keyType);
    setTimeout(function() {
        if ($(this).attr('keytype') == $(_this).val())
        {
            filterApiList($(_this).val());

        }

    },
    500);

}

function changeKeyType(_this)
 {
    $(_this).addClass("btn-primary").siblings().removeClass("btn-primary");
    var _keyType = $(_this).attr('keytype');
    filterApiList(_keyType);

}

function filterApiList(_keyType)
 {
    _keyType = _keyType.toLowerCase();
    if (_keyType == '所有')
    {
        $('#list_api_btns').children().show();

    }
    else
    {
        $('#list_api_btns').children().each(function() {
            var htmlText = $(this).html().replace(/<.*?>/g, '').toLowerCase();
            if (htmlText.indexOf(_keyType) >= 0)
            {
                $(this).show(0);

            }
            else
            {
                $(this).hide(0);

            }

        });

    }

}

function getHeaders()
 {
    var _headers = null;
    $('form').find('[form-type=header]').each(function() {
        var _key = $(this).val();
        if (_key != '')
        {
            if (_headers == null) {
                _headers = {};
            }
            _headers[_key] = $(this).parent().siblings().find("input,textarea").val();
        }

    });
    return _headers;

}
function getValues()
 {
    var _getValues = [];
    $('form').find('[form-type=field]').each(function() {
        var _key = $(this).val();
        var _input = $(this).parent().siblings().find("input,textarea");
        if (_key != '' && _input.attr('type')!='file')
        {
            var _val = $(this).parent().siblings().find("input,textarea").val();
            _getValues.push(_key + '=' + encodeURIComponent(_val));
        }

    });
    return _getValues;

}
function getPosts()
 {
    var _data = {};
    $('form').find('[form-type=field]').each(function() {
        var _key = $(this).val();
        var _input = $(this).parent().siblings().find("input,textarea");
        if (_key != '' && _input.attr('type')!='file')
        {
            var _val = $(this).parent().siblings().find("input,textarea").val();
            _data[_key] = _val;
        }
    });
    return _data;

}

function reFormGroup(_formType, _formRequest)
 {
    var _formGroup = null;
    $('form').find('[form-type=' + _formType + ']').each(function() {
        if ($(this).val() == _formRequest['key'])
        {
            _formGroup = $(this).closest(".form-group");

        }

    });
    if (_formGroup == null)
    {
        var _firstNode = $('form').find('[form-type=' + _formType + ']').first().closest(".form-group");
        $('form').find('[form-type=' + _formType + ']').last().closest(".form-group").after(_firstNode.clone(true));
        _formGroup = $('form').find('[form-type=' + _formType + ']').last().closest(".form-group");

    }

    _formGroup.attr('is-required', _formRequest['required'] ? 'true': 'false').trigger('mouseenter').trigger('mouseleave').attr('title', _formRequest['title'] + "<br/>" + _formRequest['desc']).attr('data-html', "true").tooltip().show();
    var _inputs = _formGroup.find('input,textarea');
    _inputs.eq(0).val(_formRequest['key'])
    .attr('field-type', _formRequest['type'])
    .attr('field-required', _formRequest['required'])
    .attr('field-title', _formRequest['title'])
    .attr('field-desc', _formRequest['desc']);
    if (_formRequest['type'] == 'file')
    {
        _inputs.eq(1).replaceWith('<input type="file"' + (_formType == 'field' ? ' name="' + _formRequest['key'] + '"': '') + (_formRequest['key'].indexOf('[]') > 0 ? ' multiple="multiple"': '') + '/>')

    }
    else if (_formRequest['type'] == 'text')
    {
        _inputs.eq(1).replaceWith('<textarea  name="' + _formRequest['key'] + '">'+_formRequest['test-value']+'</textarea>');
    }
    else if (_formRequest['type'] == 'md5')
    {
        _inputs.eq(1).val(hex_md5(_formRequest['test-value'])).unbind().change(function() {
            $(this).val(hex_md5($(this).val()));
        });

    }
    else
    {
        if (_formRequest['test-value'] == '')
        {
            if (_formRequest['type'] == 'datetime')
            {
                _formRequest['test-value'] = (new Date().format('yyyy-MM-dd hh:mm:ss'));

            }
            else if (_formRequest['type'] == 'date')
            {
                _formRequest['test-value'] = (new Date().format('yyyy-MM-dd'));

            }

        }
        _inputs.eq(1).val(_formRequest['test-value']);

    }
    if (_formType == 'field')
    {
        _inputs.eq(1).attr('name', _formRequest['key']);

    }
    if (_formRequest['click'])
    {
        _inputs.eq(1).siblings('.input-group-addon').show().unbind().bind('click', _formRequest['click']).parent().addClass('input-group');

    }
    else
    {
        // _inputs.eq(1).closest('.input-group').removeClass('input-group');
        }
    $('#div_headerfield').scrollTop(99999);

}

function changePlan(p_plan) {
    var planList = p_plan.split('|');
    switch (planList[0]) {
        case '#api':
        var i = parseInt(planList[1]);
        if (i >= 0)
        {
            if (apiList[i])
            {
                $('#btn_api_title_' + i).trigger('click');

            }

        }
        break;
        case '#request':
        if (planList.length >= 3)
        {
            if (planList.length == 6 && planList[5]!='')
            {
                _genreUnSelected = JSON.parse(planList[5]);
                for (var i in _genreUnSelected)
                {
                    $('#genres_list .input_api_genre[genre='+_genreUnSelected[i]+']').attr('checked',false);
                }
                if (_genreUnSelected.length>0)
                {
                    apiListInit();
                }
            }
            setTimeout(function(){
                if (planList[1] != 'undefined')
                {
                    $('#div_switchgroup button[unique-id=' + planList[1] + ']').trigger('click');
                }
                if (planList[2] != 'undefined')
                {
                    $('#div_apilist .panel[unique-id=' + planList[2] + '] h4 a').trigger('click');
                }
                setTimeout(function(){
                    if (planList.length >= 4)
                    {
                        json = JSON.parse(planList[3]);
                        for (var i in json)
                        {
                            reFormGroup('field', json[i]);

                        }
                    }
                    if (planList.length == 5)
                    {
                        json = JSON.parse(planList[4]);
                        for (var i in json)
                        {
                            reFormGroup('header', json[i]);

                        }
                    }
                },50);
            },50);


        }

        break;

        case 'help':
        break;

    }

}

function updatePlan()
 {
    var buttonUniqueId = $('#div_switchgroup .btn-primary').attr('unique-id');
    var apiUniqueId = $('.panel-collapse.in').closest('.panel').attr('unique-id');
    var _posts = [];
    $('form').find('[form-type=field]').each(function() {
        var _key = $(this).val();
        var _input = $(this).parent().siblings().find("input,textarea");
        if (_key != '' && _input.attr('type')!='file')
        {
            var _val = $(this).parent().siblings().find("input,textarea").val();
            _posts.push({
                'key': _key,
                'type': $(this).attr('field-type'),
                'required': $(this).attr('field-required'),
                'test-value': _val,
                'title': $(this).attr('field-title'),
                'desc': $(this).attr('field-desc')
            });

        }

    });
    var _headers = [];
    $('form').find('[form-type=header]').each(function() {
        var _key = $(this).val();
        if (_key != '')
        {
            var _val = $(this).parent().siblings().find("input,textarea").val();
            _headers.push({
                'key': _key,
                'type': $(this).attr('field-type'),
                'required': $(this).attr('field-required'),
                'test-value': _val,
                'title': $(this).attr('field-title'),
                'desc': $(this).attr('field-desc')
            });

        }

    });

    var _genreUnSelected = $('#genres_list .input_api_genre').not(':checked').map(function(){
        return $(this).attr('genre');
    }).get();

    _href = '#request' + '|' + buttonUniqueId + '|' + apiUniqueId + '|' + JSON_stringify(_posts, false) + '|' + JSON_stringify(_headers, false)+ '|'  + JSON_stringify(_genreUnSelected, false);
    setCurrentUrl(_href);

}

function setCurrentUrl(_href)
 {
    self.location = _href;
    currentUrl = self.location.href;

}

function reFormApi(i)
 {
    var _api = apiList[i];
    document.getElementsByTagName('title')[0].innerHTML = _api['title'] + '　　/　　API:测试工具';
    // setCurrentUrl('#api'+'|'+i+'|'+apiList[i]['title']);
    if (_api['action'].indexOf('http') < 0)
    {
        _api['action'] = window.location.protocol + '//' + window.location.host
        + (_api['action'].indexOf('/') == 0
        ? ''
        : window.location.pathname.replace(/(\/apitest\/|\/)[^\/]*$/g, '') + '/')
        + _api['action'];
        _api['action'] = _api['action'].replace(/\/[^\/]+\/\.\.\//g, '/');

    }
    $('#link_api_url').val(_api['action']);

    if (_api['method'] == 'get')
    {
        $('#switch_method button').eq(0).trigger('click');

    }
    else if (_api['method'] == 'post')
    {
        $('#switch_method button').eq(1).trigger('click');

    }
    $('form').find('[form-type=field]').each(function(i) {
        if (i > 0)
        {
            $(this).closest(".form-group").remove();

        }

    });

    for (var j in _api['request'])
    {
        if (_api['request'][j]['required'] || _api['request'][j]['test-value'] != '')
        {
            reFormGroup('field', _api['request'][j]);

        }

    }
    // $('form').find('.input-group-addon').trigger('click');
    // setTimeout(function() {updatePlan();}, 1000);

}

function reFormGroupApi(i, j)
 {
    reFormGroup('field', apiList[i]['request'][j]);

}

function reFormHeader()
 {
    for (var j in headerList)
    {
        reFormGroup('header', headerList[j]);

    }

}

function sortKeyBar(_newSortType)
 {
    if (_newSortType)
    {
        _sortType = _newSortType;

    }
    else
    {
        switch (_sortType)
        {
            case 'name':
            _sortType = 'time';
            break;

            case 'time':
            _sortType = 'name';
            break;

        }

    }
    apiKeyBarInit();
    return false;

}

function apiKeyBarInit()
 {
    var _now = (new Date()).getTime() / 1000;
    var _keyTypeArray = [];
    var _timeLast = 0;
    for (var _keyType in _keyTypes)
    {
        _keyTypeArray.push({
            'keyType': _keyType,
            'keyPY': makeFirstPy(_keyType),
            'timeunix': _keyTypes[_keyType]
        });
        _timeLast = Math.max(_timeLast, _keyTypes[_keyType]);

    }
    if (_sortType == 'name' || (_sortType == null && (_now - _timeLast > 60 * 60 * 24 * 3)))
    {
        if (!_sortType) {
            sortKeyBar('name');
            return false;
        }
        console.log('按名称排序');
        _keyTypeArray.sort(function compare(a, b) {
            // return a['keyPY'].localeCompare(b['keyPY']);
            return (a['keyPY']+a['keyType']).localeCompare(b['keyPY']+b['keyType'],'zh');
        });
    }
    else
    {
        if (!_sortType) {
            sortKeyBar('time');
            return false;
        }
        console.log('按时间排序');
        _keyTypeArray.sort(function compare(a, b) {
            return a['timeunix'] <= b['timeunix'] ? 1: -1;

        });
    }

    $('#switch_examples').empty();
    var _lastKeyPY = null;
    for (var i in _keyTypeArray)
    {
        var _keyType = _keyTypeArray[i]['keyType'];
        var _keyPY = _keyTypeArray[i]['keyPY'];
        var _timeunix = _keyTypeArray[i]['timeunix'];
        $('#switch_examples').append('<button type="button" class="btn btn-default" onclick="changeKeyType(this);" keytype="' + _keyType + '" unique-id="' + hex_md5(_keyType) + '" >'
        + _keyType
        + (_sortType == 'time' ? range_to_badge(_now - _timeunix) : (_lastKeyPY != _keyPY ? '<span class="badge">' + _keyPY + '</span>': ''))
        + '</button>');
        _lastKeyPY = _keyPY;

    }

    var _sortStr = '排序';
    switch (_sortType)
    {
        case 'name':
        _sortStr = '按名称排序';
        break;

        case 'time':
        _sortStr = '按时间排序';
        break;

    }
    $('<button type="button" class="btn btn-default" onclick="changeKeyType(this);" keytype="所有">所有<span class="badge key_bar_sort" onclick="return sortKeyBar();" >' + _sortStr + '</span></button>').insertBefore($('#switch_examples button').eq(0));
    $('<input id="input_search" type="text" class="btn btn-default" onkeyup="searchApiKey(this);" onclick="searchApiKey(this,1);" placeholder="search"/>').insertAfter($('#switch_examples button').eq(0));


}

function apiListInit()
 {
    if ($('#genres_list').html() == '')
    {
        var _genreList = {};
        for (var i in apiList)
        {
            var _api = apiList[i];
            if (_api['genre'] && _api['genre']!='')
            {
                var _genres = _api['genre'].split(',');
                for (var k in _genres)
                {
                    var _genre = $.trim(_genres[k]);
                    if (!_genreList[_genre])
                    {
                        _genreList[_genre] = 0;
                    }
                    _genreList[_genre] ++;
                }
            }
        }
        $('#genres_list').hide();
        for (var _genre in _genreList)
        {
            $('#genres_list').append('<label><input type="checkbox" class="input_api_genre" genre="'+_genre+'" checked onclick="apiListInit()"/>'+_genre+'</label>');
            $('#genres_list').show();
        }
    }

    var _genresSelected = $('#genres_list .input_api_genre:checked').map(function(){
        return $(this).attr('genre');
    }).get();


    var _listNode = $('#list_api_btns').empty();
    var _now = (new Date()).getTime() / 1000;
    _keyTypes = {};

    for (var i in apiList)
    {
        var _api = apiList[i];

        if (_api['time'])
        {
            _api['timeunix'] = datetime_to_unix(_api['time']);

        }
        else
        {
            _api['timeunix'] = 1;
        }
        for (var j in _api['request'])
        {
            var requestTime = 1;
            if (_api['request'][j]['time'])
            {
                _api['request'][j]['timeunix'] = datetime_to_unix(_api['request'][j]['time']);
                if (_api['request'][j]['timeunix'] > _api['timeunix'])
                {
                    _api['timeunix'] = _api['request'][j]['timeunix'];
                }
            }
        }

    }

    apiList.sort(function compare(a, b) {
        return a['timeunix'] <= b['timeunix'] ? 1: -1;
    });

    for (var i in apiList)
    {
        var _api = apiList[i];

        var _keyString = '';
        _keyString = '';
        if (_api['desc'] != '')
        {
            _keyString = _api['desc'].replace('\n', '<br/>').replace(/[\n\r]/g, '<br/>') + '<br/><br/>';

        }
        _keyString += 'url    : ' + _api['action'];
        if (_api['time'] && _api['time'] != '')
        {
            _keyString += '<span class="span-time">' + _api['time'] + '</span>';

        }
        _keyString += '<br/>';
        if (_api['request'].length > 0)
        {
            _keyString += '<table class="table table-striped">';
            _keyString += '<tr><th>字段名</th><th>必须</th><th>格式</th><th>字段描述</th><th>测试值</th></tr>';
            for (var j in _api['request'])
            {
                var requestTime = 1;
                if (_api['request'][j]['timeunix'])
                {
                    requestTime = _api['request'][j]['timeunix'];

                }
                _keyString += '<tr onclick="reFormGroupApi(' + i + ',' + j + ');"><td>' + _api['request'][j]['key'] + (range_to_badge(_now - requestTime)) + '</td><td>' + (_api['request'][j]['required'] ? '是': '否') + '</td><td>' + _api['request'][j]['type'] + '</td><td><span>' + _api['request'][j]['title'] + '</span><span style="color:red;">' + _api['request'][j]['desc'] + '</span></td><td>' + _api['request'][j]['test-value'] + '</td></tr>'

            }
            _keyString += '</table>';

        }

        var _panelString = '<div class="panel panel-default" unique-id="' + hex_md5(apiList[i]['title']) + '" >\
    <div class="panel-heading">\
    <h4 class="panel-title">\
    <a id="btn_api_title_' + i + '" href="javascript:;" data-toggle="collapse" data-parent="#list_api_btns" data-target="#collapseDiv' + i + '" onclick="reFormApi(' + i + ');" style="width: 100%;display: inline-block;">'
        + (range_to_badge(_now - _api['timeunix']))
        + _api['title']
        + '<span class="span-method">' + _api['method'] + '</span>'
        + '</a>'
        + '\
    </h4>\
    </div>\
    <div id="collapseDiv' + i + '" class="panel-collapse collapse">\
    <div class="panel-body">' + _keyString + '</div>\
    </div>\
    </div>';

        if (_api['genre'] && _api['genre']!='')
        {
            var _isGenreSelected = false;
            var _genres = _api['genre'].split(',');
            for (var k in _genres)
            {
                var _genre = $.trim(_genres[k]);
                // console.log(_genre,_genresSelected,$.inArray(_genre,_genresSelected));
                if ($.inArray(_genre,_genresSelected)>=0)
                {
                    _isGenreSelected = true;
                    continue;
                }
            }
            if (!_isGenreSelected)
            {
                continue;
            }
        }
        if (_api['title'].match(/[：:]/g))
        {
            var _keyType = _api['title'].replace(/([：:]).*/g, '$1');
            if (!_keyTypes[_keyType])
            {
                _keyTypes[_keyType] = _api['timeunix'];
            }
        }
        _listNode.append(_panelString);
    }
    _listNode.collapse();



    reFormHeader();

    apiKeyBarInit();

}

function configJsonInit()
 {
    if (typeof(configJsonFileList) == 'object' && configJsonFileList.length > 0)
    {
        $.ajax({
            type: 'GET',
            url: configJsonFileList.pop(),
            dataType: 'json',
            async: true,
            //是否使用异步
            success: function(result) {
                apiList = apiList.concat(result);
                configJsonInit();

            }

        });

    }
    else
    {
        apiListInit();

    }

}

function getDescriptionsInModel(modelName)
{
    if (!descriptionsList[modelName] && descriptionsList!==false)
    {

        var _link = 'js/desc_of_model.php?model_name='+encodeURIComponent(modelName);

        var _method = 'get'

        $.ajax({

            type: _method

            ,
            url: _link

            ,
            dataType: "json"

            ,async:false//是否使用异步

            ,
            success: function(result) {
                if (result['errorCode'] && result['errorCode']!=0)
                {
                    descriptionsList = false;
                }
                else
                {
                    descriptionsList[modelName] = result;
                }
            }

            ,error :function(){
                descriptionsList = false;
            }

            //Options to tell jQuery not to process data or worry about content-type.
            ,
            cache: (_method == 'get')
            ,
            contentType: false
            //必须false才会自动加上正确的Content-Type 告诉jQuery不要去设置Content-Type请求头
            ,
            processData: false
            //必须false才会避开jQuery对 formdata 的默认处理  告诉jQuery不要去处理发送的数据  XMLHttpRequest会对 formdata 进行正确的处理
        });

    }
    if (descriptionsList[modelName])
    {
        return descriptionsList[modelName];
    }
    return {};
}

$(function() {
    configJsonInit();


    $('#switch_examples').children().eq(0).trigger('click');

    $('#btn_add_header').click(function() {
        var _firstNode = $('form').find('[form-type=header]').first().closest(".form-group");
        $('form').find('[form-type=header]').last().closest(".form-group").after(_firstNode.clone(true));
        $('form').find('[form-type=header]').last().closest(".form-group").show();

    });
    $('#btn_add_field').click(function() {
        var _firstNode = $('form').find('[form-type=field]').first().closest(".form-group");
        $('form').find('[form-type=field]').last().closest(".form-group").after(_firstNode.clone(true));
        $('form').find('[form-type=field]').last().closest(".form-group").show();

    });
    $('.form-group').bind({
        mouseenter: function() {
            if ($(this).attr('is-required') && $(this).attr('is-required') == 'true')
            {

                }
            else
            {
                if ($(this).find('.btn_del').length == 0)
                {
                    $(this).find('[form-type]').before('<div class="btn_del" > x </div>').prev().click(function() {
                        $(this).closest(".form-group").trigger('mouseleave').remove();

                    });

                }
                $(this).find('.btn_del').show();

            }

        }
        ,
        mouseleave: function() {
            if ($(this).attr('is-required'))
            {

                }
            else
            {
                $(this).find('.btn_del').hide();

            }

        }

    });
    $('form').find('[form-type=field]').bind({
        change: function() {
            $(this).parent().siblings().find('input,textarea').attr('name', $(this).val());

        }
    });

    $('#switch_method button').click(function() {
        $(this).addClass("btn-primary").siblings().removeClass("btn-primary");

    });

    $('#div_frames>ul>li').eq(0).find('a').bind({
        click: function() {
            var json,
            data;
            data = $('#textarea_results').val();
            try {
                json = JSON.parse(data);
            }
            catch(e) {
                // console.log('json 解析失败');
                if (data.match(/^<.*?>[\s\S]*<.*?>$/g))
                {
                    $('#div_json_view').html(data);
                }
                else
                {
                    setTimeout(function() {
                        $('#div_frames>ul>li').eq(1).find('a').trigger("click");
                    },
                    100);
                }

                return;

            }

            var node = new PrettyJSON.view.Node({
                el: $('#div_json_view'),
                data: json,
                dateFormat: "DD/MM/YYYY - HH24:MI:SS"

            });
            // console.log(node);
            // node.expandAll();
            expandAll(node);

        }
    });

    // $('#textarea_results')[0].onchange = function()
    // {

// }

$('#btn_test_url').click(function() {

        if ($('#checkbox_is_autosign').prop('checked'))
        {
            $('form').find('.input-group-addon').trigger('click');
            //每次都自动重算

        }

        if (xhrTestingApi) {
            xhrTestingApi.abort();
        }

        var _headers = getHeaders();

        var _link = $('#link_api_url').val();

        if (_link == '')
        {
            alert('API URL NO FOUND');
            $('#link_api_url').focus();
            return false;

        }

        var _method = $('#switch_method .btn-primary').html().toLowerCase();

        var _data = null;
        if (_method == 'get')
        {
            var _getValues = getValues();
            _link = _link + (_link.indexOf('?') > 0 ? '&': '?') + _getValues.join('&');

        }
        else if (_method == 'post')
        {
            if (_headers == null)
            {
                _data = getPosts();

            }
            else
            {
                _data = new FormData($('form')[0]);

            }

        }

        $('#textarea_results').val('waiting....');
        $('#div_json_view').html('waiting....');
        // console.log(_getValues)
        console.log(_headers)
        if (_method == 'post' && _headers == null)
        {
            xhrTestingApi = $.post(_link, _data,
            function(data) {
                xhrTestingApi = null;
                $('#textarea_results').val(data);
                $('#textarea_results').trigger("change");
                $('#div_frames>ul>li').eq(0).find('a').trigger("click");

            },
            'text');

        }
        else
        {
            xhrTestingApi = $.ajax({

                type: _method

                ,
                url: _link

                ,
                headers: _headers

                ,
                data: _data

                ,
                dataType: "text"

                ,
                error: function(XHR, textStatus, errorThrown) {
                    console.log(XHR, textStatus, errorThrown);
                    $('#textarea_results').val(XHR.responseText);
                    alert("XHR=" + XHR + "\ntextStatus=" + textStatus + "\nerrorThrown=" + errorThrown);

                }

                ,
                success: function(data, textStatus) {
                    xhrTestingApi = null;
                    $('#textarea_results').val(data);
                    $('#textarea_results').trigger("change");
                    $('#div_frames>ul>li').eq(0).find('a').trigger("click");

                }

                //Options to tell jQuery not to process data or worry about content-type.
                ,
                cache: (_method == 'get')
                ,
                contentType: false
                //必须false才会自动加上正确的Content-Type 告诉jQuery不要去设置Content-Type请求头
                ,
                processData: false
                //必须false才会避开jQuery对 formdata 的默认处理  告诉jQuery不要去处理发送的数据  XMLHttpRequest会对 formdata 进行正确的处理

            });

        }
        setTimeout(function() {
            updatePlan()
        },
        100);

    });
    // //url 监控
    setInterval(function() {
        var _currentUrl = self.location.href;
        if (currentUrl != _currentUrl) {
            currentUrl = _currentUrl;
            changePlan(self.location.hash);

        };

    },
    100);

});
