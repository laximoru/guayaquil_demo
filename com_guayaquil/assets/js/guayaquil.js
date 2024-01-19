(function ObjectWatchPolyfill() {
    if (!Object.prototype.watch) {
        Object.defineProperty(Object.prototype, "watch", {
            enumerable: false
            , configurable: true
            , writable: false
            , value: function (prop, handler) {
                var
                    oldval = this[prop]
                    , newval = oldval
                    , getter = function () {
                        return newval;
                    }
                    , setter = function (val) {
                        oldval = newval;
                        return newval = handler.call(this, prop, oldval, val);
                    }
                ;

                if (delete this[prop]) { // can't watch constants
                    Object.defineProperty(this, prop, {
                        get: getter
                        , set: setter
                        , enumerable: true
                        , configurable: true
                    });
                }
            }
        });
    }

    if (!Object.prototype.unwatch) {
        Object.defineProperty(Object.prototype, "unwatch", {
            enumerable: false
            , configurable: true
            , writable: false
            , value: function (prop) {
                var val = this[prop];
                delete this[prop]; // remove accessors
                this[prop] = val;
            }
        });
    }
})();

var polyfill = {

    /**
     *	Watch Property
     *	Given an object, property, and handler, replace it with a custom getter/setter
     */
    watchProperty: function(obj, prop, handler) {
        var oldval = obj[prop],
            newval = oldval,
            getter = function() {
                return newval;
            },
            setter = function(val) {
                oldval = newval;
                if (oldval !== val) {
                    handler([{
                        type: 'update',
                        object: obj,
                        name: prop,
                        oldValue: oldval
                    }]);
                }
                return (newval = val);
            };
        if (delete obj[prop]) {
            Object.defineProperty(obj, prop, {
                get: getter,
                set: setter,
                enumerable: true,
                configurable: true
            });
        }
    },

    /**
     *	Given a delta object, update the watched properties and fire a callback
     */
    updateProperties: function(delta, obj, handler) {
        var added = delta.added,
            deleted = delta.deleted,
            hasAdded = !!added.length,
            hasDeleted = !!deleted.length,
            all = delta.all,
            allL = all.length,
            response = [];

        for (var i = 0; i < allL; i++) {
            this.watchProperty(obj, all[i], handler);

            if (hasAdded && i <= added.length) {
                response.push({
                    type: 'add',
                    object: obj,
                    name: added[i]
                });
            }
            if (hasDeleted && i <= deleted.length) {
                response.push({
                    type: 'deleted',
                    object: obj,
                    name: deleted[i]
                });
            }
        }
        handler(response);
    },

    /**
     *	Unwatch Property
     *	Given an object and property, retrieve the old value, delete the prop, and set it
     */
    unWatchProperty: function(obj, prop) {
        var val = obj[prop];
        delete obj[prop];
        obj[prop] = val;
    },

    /**
     *	Set Dirty Check
     *	Define a 'hidden' dirty check on the object so that it can be cleared later
     */
    setDirtyCheck: function(obj, time, fn) {
        Object.defineProperty(obj, '__interval__', {
            enumerable: false,
            configurable: true,
            writeable: false,
            value: setInterval(fn, time)
        });
    },

    /**
     *	Clear Dirty Check
     *	Given an object, clear the dirty-check interval and delete the property.
     */
    clearDirtyCheck: function(obj) {
        clearInterval(obj.__interval__);
        delete obj.__interval__;
    }
};

var utils = {
    compare: function(arr1, arr2) {
        if (!(arr1 instanceof Array) || !(arr2 instanceof Array)) {
            throw new TypeError('#compare accepts two parameters, both being Arrays.');
        }
        if (arr1.length !== arr2.length) {
            return false;
        }
        for (var i = 0, l = arr1.length; i < l; i++) {
            if (arr1[i] instanceof Array && arr2[i] instanceof Array) {
                if (!this.compare(arr1[i], arr2[i])) {
                    return false;
                }
            } else if (arr1[i] !== arr2[i]) {
                return false;
            }
        }
        return true;
    },

    diff: function(arr1, arr2) {
        if (!arr1 || !arr2 || !(arr1 instanceof Array) || !(arr2 instanceof Array)) {
            throw new TypeError('#diff accepts two parameters, both being Arrays.');
        }
        var a = [],
            diff = {},
            a1L = arr1.length,
            a2L = arr2.length;

        diff.added = [];
        diff.deleted = [];
        diff.all = [];

        for (var i = 0; i < a1L; i++) {
            a[arr1[i]] = 1;
        }
        for (var j = 0; j < a2L; j++) {
            if (a[arr2[j]]) {
                delete a[arr2[j]];
            } else {
                a[arr2[j]] = 2;
            }
        }
        for (var k in a) {
            diff.all.push(k);
            if (a[k] === 1) {
                diff.deleted.push(k);
            } else {
                diff.added.push(k);
            }
        }
        return diff;
    },

    keys: function(obj) {
        if (Object.prototype.toString.call(obj) !== '[object Object]') {
            throw new TypeError('#keys only accepts objects');
        }
        var props = [];
        for (var prop in obj) {
            props.push(prop);
        }
        return props;
    },

    clone: function(obj) {
        var a = [];
        for (var prop in obj) {
            a[prop] = obj[prop];
        }
        return a;
    }
};

function getCookie(name) {
    var matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

function setCookie(key, value) {
    document.cookie = key + '=' + value;
}

String.prototype.replaceAll = function (search, replacement) {
    var target = this;
    return target.split(search).join(replacement);
};

function autoReload() {
    var goal = self.location;
    location.href = goal;
}

function initServiceMessages() {
    var requestMessageState = getCookie('request_message');
    var responseMessageState = getCookie('response_message');

    if (requestMessageState && requestMessageState === 'active') {
        jQuery(document).find('[data-toggle-class=".request_message"]').addClass('active');
        jQuery(document).find('.request_message').removeClass('hidden');
    }

    if (responseMessageState && responseMessageState === 'active') {
        jQuery(document).find('[data-toggle-class=".response_message"]').addClass('active');
        jQuery(document).find('.response_message').removeClass('hidden');
    }
}

function validateForm(form) {
    var reqiredFields = jQuery(form).find('.required');

    var empty = 0;

    jQuery(reqiredFields).each(function (index, field) {

        jQuery(field).on('keyup', function () {
            if (!field.value || field.value === '') {
                empty++;
                jQuery(field).addClass('g_input_error');
            } else {
                jQuery(field).removeClass('g_input_error');
            }
        });

        if (!field.value || field.value === '') {
            empty++;
            jQuery(field).addClass('g_input_error');
        } else {
            jQuery(field).removeClass('g_input_error');
        }
    });

    return !empty;
}

function login(button) {
    var form = jQuery(button).closest('form');
    if (validateForm(form)) {
        jQuery(button).closest('form').submit();
    }
}

function formatXml(xml) {
    var formatted = '';
    var reg = /(>)(<)(\/*)/g;
    xml = xml.replace(reg, '$1\r\n$2$3');
    var pad = 0;
    jQuery.each(xml.split('\r\n'), function (index, node) {
        var indent = 0;
        if (node.match(/.+<\/\w[^>]*>$/)) {
            indent = 0;
        } else if (node.match(/^<\/\w/)) {
            if (pad != 0) {
                pad -= 1;
            }
        } else if (node.match(/^<\w([^>]*[^\/])?>.*$/)) {
            indent = 1;
        } else {
            indent = 0;
        }

        var padding = '';
        for (var i = 0; i < pad; i++) {
            padding += '    ';
        }

        formatted += padding + node + '\r\n';
        pad += indent;
    });

    return formatted;
}

function highLightXml(element) {
    var parser = new DOMParser();
    // var responseElem = parser.parseFromString(jQuery('.response_message').text(), "text/xml");
    var responseElem = parser.parseFromString(jQuery(element).text(), "text/xml");

    if (document.body.getAttribute('data-mark') === '0') {

        if (jQuery(responseElem).find('row[oem]').length || jQuery(responseElem).find('Detail[oem]').length) {
            jQuery(responseElem).find('row[oem]').attr('oem', '*****');
            jQuery(responseElem).find('Detail[oem]').attr('oem', '*****');
        }
    }

    var xmlText = new XMLSerializer().serializeToString(responseElem);
    var xml = formatXml(xmlText);
    if (xml && element) {
        element.innerText = (xml);
    }
}

jQuery(document).ready(function () {
    initServiceMessages();

    hljs.configure({
        tabReplace: '    ',
        useBR: true
    });

    jQuery('.response_message code').each(function (i, block) {
        highLightXml(block);
        hljs.highlightBlock(block);
        jQuery(block).css('white-space', 'pre');
    });

    jQuery(document).on('change', '#language', function () {
        document.cookie = 'interface_language=' + jQuery(this).val();
        autoReload();
    });

    var loginContent = jQuery(document).find('#login-form-content div.login-container').first();

    jQuery('#login-button, a.logging').colorbox({
        html: function () {
            return loginContent;
        },
        height: 255,
        width: 280,
        close: '',
        onComplete: function () {
            jQuery('#cboxTitle').hide();
            jQuery('form.with-validate [type="submit"]').click(function (e) {
                e.preventDefault();
                e.stopPropagation();
                login(this);
            });
        }
    });

    var alertMessage = jQuery('#message');
    var cUrl = window.location.href;

    var aa = cUrl.replaceAll('&auth=false', '');
    var newUrl = aa.replaceAll('&auth=true', '');
    window.history.replaceState('', '', newUrl);

    if (alertMessage.length) {

        setTimeout(function () {
            jQuery(alertMessage).hide();
        }, 2000)
    }

    jQuery('form.with-validate [type="submit"]').click(function (e) {
        e.preventDefault();
        e.stopPropagation();
        login(this);
    });

    jQuery(document).on('click', '[data-toggle-class]', function () {
        var toToggle = jQuery(this).data('toggle-class');
        jQuery(toToggle).toggleClass('hidden');
        jQuery(this).toggleClass('active');
        jQuery(this).trigger('afterToggle');
        var cookieName = jQuery(this).attr('data-toggle-class').replace('.', '');

        setCookie(cookieName, 'closed');

        if (jQuery(this).hasClass('active')) {
            setCookie(cookieName, 'active');
        }
    });
});

function checkVinValue(value, submit_btn) {
    value = value.replace(/[^0-9A-Za-z]/g, '');
    var expr = new RegExp('\^[A-z0-9]{12}[0-9|o|i|q]{5}\$', 'i');
    if (expr.test(value)) {
        value = value.replace(/[^0-9A-Za-z]/g, '');
        jQuery('#VINInput').attr('class', 'g_input');

        var form = jQuery(document).find('form[name="findByVIN"]');
        var catalog = form.find('input[name="c"]').val();
        if (catalog) {
            window.location = 'index.php?task=vehicles&ft=findByVIN&c=' + catalog + '&vin=$vin$&ssd='.replace('\$vin\$', value);
        } else {
            window.location = 'index.php?task=vehicles&ft=findByVIN&c=&vin=$vin$&ssd='.replace('\$vin\$', value);
        }
    } else {
        jQuery('#VINInput').attr('class', 'g_input_error');
    }

}

function checkFrameValue(frameno, submit_btn) {
    var frnexpr = new RegExp('\^[A-z0-9- ]{3,7}[0-9- ]{3,7}\$', 'i');
    var result = true;
    var form = jQuery(document).find('form[name="findByFrame"]');
    var catalog = jQuery(form).find('input[name="c"]').attr('value');
    if (frnexpr.test(frameno.replace(/[^0-9A-Za-z]/g, ''))) {
        frameno = frameno.replace(/[^0-9A-Za-z]/g, '');
        jQuery(form).find('#FrameNoInput').removeClass('g_input_error');
        result = true;
    } else {
        jQuery(form).find('#FrameNoInput').addClass('g_input_error');
        result = false;
    }
    if (result) {
        window.location = 'index.php?task=vehicles&ft=findByFrame&ssd=&c=' + catalog + '&frameNo=' + frameno;
    }
}

function checkOem(oem, block, ssd) {
    var value;
    var form = jQuery(document).find('form[name="findByOEM"]');
    var inputWrapper = jQuery(form).find('#OEMInput');

    value = oem.replace(/[^0-9A-Za-z]/g, '');
    jQuery(inputWrapper).find('input[name="oem"]').val(value);
    var serrialized = form.serialize();
    var expr = new RegExp('\^[A-z0-9]{1,}\$', 'i');
    if (expr.test(value)) {
        form.find('input[name="OEM"]').val(value);
        jQuery(inputWrapper).attr('class', 'g_input');
        var catalog = form.find('input[name="c"]').val();
        window.location = 'index.php?' + serrialized;
    } else {
        jQuery(inputWrapper).attr('class', 'g_input_error');
    }
}

function checkPlate(oem, block, ssd) {
    var value;
    var form = jQuery(document).find('form[name="findByPlate"]');
    var inputWrapper = jQuery(form).find('#PlateInput');

    value = oem.replace(/[^0-9А-я]/g, '');
    jQuery(inputWrapper).find('input[name="plate"]').val(value);
    var serrialized = form.serialize();
    var expr = new RegExp('\^[А-я0-9]{1,}\$', 'i');
    if (expr.test(value)) {
        form.find('input[name="Plate"]').val(value);
        jQuery(inputWrapper).attr('class', 'g_input');
        window.location = 'index.php?' + serrialized;
    } else {
        jQuery(inputWrapper).attr('class', 'g_input_error');
    }
}

function checkName(nameField) {
    value = nameField.value.trim();
    if (value.length > 0) {
        jQuery(nameField).parent().attr('class', 'g_input');
        return true;
    } else {
        jQuery(nameField).parent().attr('class', 'g_input_error');
        return false;
    }
}

function openWizard(ssd, catalogCode) {
    if (ssd == 'null') {
        return false;
    }
    var url = 'index.php?task=wizard2&c=' + catalogCode + '&ssd=$ssd$'.replace('\$ssd\$', ssd);
    window.location = url;
}

function checkCustomForm(form, submit_btn) {
    var testResult = true;

    jQuery(form).find(":input").each(function () {
        if (jQuery(this).data("regexp")) {
            var regexp = jQuery(this).data("regexp");
            var value = jQuery(this).val().trim().toUpperCase();
            jQuery(this).val(value);
            var expr = new RegExp(regexp, 'i');
            if (expr.test(value)) {
                jQuery(form).find('.g_input').removeClass('g_input_error');
                jQuery(form).find('.g_input').addClass('g_input');
            } else {
                jQuery(form).find('.g_input').addClass('g_input_error');
                testResult = false;
            }
        }
    });

    if (testResult) {
        jQuery(submit_btn).attr('disabled', '1');
    }

    return testResult;
}

function showPreloader(jBlock) {

    var preloader = jQuery('<div class="preloader"></div>');
    var target = jBlock;
    if (!target) {
        target = jQuery('body');
        preloader.addClass('fixed');
    } else {
        target = jQuery(target);
    }

    if (target.hasClass('preloader-container')) {
        return;
    }

    target.addClass('preloader-container').append(preloader);
    var $preloader = target.find('.preloader');

    jQuery(window).resize();
}

function hidePreloader(jBlock) {
    if (!jBlock) {
        jBlock = jQuery('.preloader-container');
    }
    jBlock.children('.preloader').remove();
    jBlock.removeClass('preloader-container');
}

function insertParam(key, value) {
    var url = document.location.search.substr(1).split('&');
    var i = url.length;
    var x;

    key = encodeURI(key);
    value = encodeURI(value);

    while (i--) {
        x = url[i].split('=');

        if (x[0] === key) {
            x[1] = value;
            url[i] = x.join('=');
            break;
        }
    }
    if (i < 0) {
        url[url.length] = [key, value].join('=');
    }
    return url.join('&');
}

function getUrlParameter(url, sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1));

    if (url) {
        sPageURL = url;
    }

    var sURLVariables = sPageURL.split('&');
    var sParameterName;
    var i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }

    return false;
}

jQuery(document).on('click', 'a#list-parts-link', function () {
    var $this = jQuery(this);
    jQuery.colorbox({
        'href': $this.data('url'),
        'opacity': 0.3,
        'innerWidth': '800px',
        'maxHeight': '80%',
        close: '',
        onComplete: function () {
            jQuery('#cboxTitle').hide();
            jQuery('#cboxLoadedContent').css("margin-top", "0px");
        }
    });
});

function tree_toggle(event, elem) {
    event = event || window.event;
    if (!elem) {
        var clickedElem = event.target || event.srcElement;
    } else {
        clickedElem = elem;
    }

    if (!hasClass(clickedElem, 'qgExpand')) {
        return;
    }

    var node = clickedElem.parentNode;
    if (hasClass(node, 'qgExpandLeaf')) {
        return;
    }

    var newClass = hasClass(node, 'qgExpandOpen') ? 'qgExpandClosed' : 'qgExpandOpen'
    var re = /(^|\s)(qgExpandOpen|qgExpandClosed)(\s|$)/
    node.className = node.className.replace(re, '$1' + newClass + '$3')
}


function hasClass(elem, className) {
    return new RegExp("(^|\\s)" + className + "(\\s|$)").test(elem.className)
}

var QuickGroups = {};
QuickGroups.Search = function (value) {
    var filteredGroups = jQuery('#qgFilteredGroups');
    var tree = jQuery('#gqGroupsWrapper');

    if (value.length < 3) {
        filteredGroups.css("display", "none");
        tree.css("display", "block");
    }
    else {
        filteredGroups.css("display", "block");
        tree.css("display", "none");
        filteredGroups.html('');

        QuickGroups.InnerSearch(value, '', tree, filteredGroups)
    }
};

jQuery.expr[":"].Contains = jQuery.expr.createPseudo(function (arg) {
    return function (elem) {
        return jQuery(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
    };
});

QuickGroups.InnerSearch = function (value, current_path, item, filtered_groups) {
    var items = item.children();

    items.each(function () {
        var el = jQuery(this);

        if (el.hasClass('qgContent')) {
            var $name = el.find('.group-name');
            var $aliases = el.find('.qg-aliases .synonym-name');
            var $detailName = el.find('.qg-detail-names .qg-detail-name:Contains(' + value + '):eq(0)');

            var inName = $name.find('a:Contains(' + value + ')').length || $aliases.is(':Contains(' + value + ')');

            if (el.find('a').length && (inName || $detailName.length)) {
                jQuery('<div class="qgFilteredGroup">' +
                    '<div class="qgCurrentPath">' + current_path + '</div>' +
                    '<div class="qgFilteredName">' + $name.html() +
                    (inName ? '' : ' / ' + $detailName) + '</div>' +
                    '</div>').appendTo(filtered_groups);
            }

            current_path = current_path + ' / ' + $name.text();
        }
        QuickGroups.InnerSearch(value, current_path, el, filtered_groups);
    });
};

function prepareImage() {
    var img = jQuery('img.dragger');

    var width = img.innerWidth();
    var height = img.innerHeight();

    img.attr('owidth', width);
    img.attr('oheight', height);

    jQuery('div.dragger').each(function (idx) {
        var el = jQuery(this);
        el.attr('owidth', parseInt(el.css('width')));
        el.attr('oheight', parseInt(el.css('height')));
        el.attr('oleft', parseInt(el.css('margin-left')));
        el.attr('otop', parseInt(el.css('margin-top')));
    });
}

function rescaleImage(delta, mousePosition) {

    var img = jQuery('img.dragger');

    var original_width = img.attr('owidth');
    var original_height = img.attr('oheight');

    if (!original_width) {
        prepareImage();

        original_width = img.attr('owidth');
        original_height = img.attr('oheight');
    }

    var current_width = img.innerWidth();
    var current_height = img.innerHeight();

    var scale = current_width / original_width;

    var cont = jQuery('#viewport');

    var view_width = parseInt(cont.css('width'));
    var view_height = parseInt(cont.css('height'));

    var minScale = Math.min(view_width / original_width, view_height / original_height);

    var newscale = scale + (delta / 10);
    if (newscale < minScale)
        newscale = minScale;

    if (newscale > 1)
        newscale = 1;

    var correctX = Math.max(0, (view_width - original_width * newscale) / 2);
    var correctY = Math.max(0, (view_height - original_height * newscale) / 2);

    img.attr('width', original_width * newscale);
    img.attr('height', original_height * newscale);
    img.css('margin-left', correctX + 'px');
    img.css('margin-top', correctY + 'px');

    jQuery('div.dragger').each(function (idx) {
        var el = jQuery(this);
        el.css('margin-left', (el.attr('oleft') * newscale + correctX) + 'px');
        el.css('margin-top', (el.attr('otop') * newscale + correctY) + 'px');
        el.css('width', el.attr('owidth') * newscale + 'px');
        el.css('height', el.attr('oheight') * newscale + 'px');
    });

    return newscale;
}

function fitToWindow() {
    var t = jQuery('#g_container');
    var width = t.innerWidth() - (parseInt(t.css('padding-right')) || 0) - (parseInt(t.css('padding-left')) || 0);
    jQuery('#viewport, #viewtable').css('width', Math.ceil(width * 0.48));
}

var el_name;

function SubscribeDblClick(selector) {
    jQuery(selector).dblclick(function () {
        var el = jQuery(this);
        var elName = el.attr('name');

        var items = jQuery('tr[name="' + elName + '"]');

        if (items.length == 0)
            return false;

        if (items.length == 1) {
            var id = jQuery(items[0]).attr('id');
            items = jQuery('#' + id + ' a.follow');

            if (items.length == 0) {
                return false;
            }

            var url = jQuery(items[0]).attr('href');
            url += (url.indexOf('?') >= 0 ? '&' : '?') + 'format=raw';
            jQuery.colorbox({
                'href': url,
                'opacity': 0.3,
                'innerWidth': '1000px',
                'maxHeight': '98%',
                close: ''
            })
        } else {
            jQuery.colorbox({
                'html': function () {
                    var items = jQuery('tr[name="' + elName + '"] td[name=c_name]');
                    var name = jQuery(items[0]).text();

                    var html = '<h2><span>' + name + '</span></h2>' + '<table>';

                    var oems = jQuery('tr[name="' + elName + '"] td[name=c_oem]');
                    var notes = jQuery('tr[name="' + elName + '"] td[name=c_note]');
                    var urls = jQuery('tr[name="' + elName + '"] td[name=c_oem] a.follow');

                    var count = oems.length;
                    if (count == 0) {
                        count = notes.length;
                    }

                    for (var idx = 0; idx < count; idx++) {
                        var url = jQuery(urls[idx]).attr('href');
                        url += (url.indexOf('?') >= 0 ? '&' : '?') + 'format=raw';
                        html += '<tr><td><a href="#" onclick="jQuery.colorbox({\'href\': \'' + url + '\',\'opacity\': 0.3, \'innerWidth\' : \'1000px\',\'maxHeight\' : \'98%\'}); return false;">' + jQuery(oems[idx]).text() + '</a></td><td>' + jQuery(notes[idx]).text() + '</td></tr>';
                    }

                    html += '</table>';

                    return html;
                },
                close: '',
                'opacity': 0.3,
                'maxHeight': '98%'
            })
        }
    })
}

jQuery(document).ready(function () {

    jQuery('.dragger, #viewport').bind('mousewheel', function (event, delta) {
        event.preventDefault();
        var mousePosition = {x: event.pageX, y: event.pageY};
        var scale = rescaleImage(delta, mousePosition);

        if (scale < 1) {
            jQuery(this).scrollLeft(mousePosition.x - jQuery(this).offset().left);
            jQuery(this).scrollTop(mousePosition.y - jQuery(this).offset().top);
        }
    });

    jQuery('#viewport').dragscrollable({dragSelector: '.dragger, #viewport', acceptPropagatedEvent: false});

    jQuery('#viewport div.unit-tooltip').tooltip({
        track: true,
        delay: 0,
        showURL: false,
        fade: 250,
        bodyHandler: function () {
            var name = jQuery(this).attr('name');
            var item = jQuery('tr[name="' + name + '"] td[name=c_name]').first();
            if (item.length === 0) {
                jQuery(this).tooltip('destroy');
                return false;
            } else {
                return jQuery(item[0]).html();
            }
        }
    });

    jQuery(document).on('mouseover', '.detail-link-tooltip', function () {
        var $this = jQuery(this);

        $this.tooltip({
            track: true,
            delay: 0,
            showURL: false,
            fade: 250,
            bodyHandler: function () {
                return this.dataset.label
            }
        });
    });

    jQuery('tr.g_highlight').click(function () {
        var name = jQuery(this).attr('name');
        jQuery('.g_highlight[name="' + name + '"]').toggleClass('g_highlight_lock');
        jQuery('.g_highlight_over[name="' + name + '"]').toggleClass('g_highlight_lock');
    });

    jQuery('.qdetails tr').click(function () {
        var $this = jQuery(this);
        $this.toggleClass('g_highlight_lock');
    });

    jQuery('tr.g_highlight').hover(
        function () {
            unitHl(this, 'in');
        },
        function () {
            unitHl(this, 'out');
        }
    );

    jQuery('#viewport div').click(function () {

        var name = jQuery(this).attr('name');
        jQuery('[name=' + name + '].g_highlight').toggleClass('g_highlight_lock');
        jQuery('[name=' + name + '].g_highlight_over').toggleClass('g_highlight_lock');

        var tr = jQuery('tr.g_highlight_lock[name="' + name + '"]');
        if (tr.length == 0) {
            return;
        }
        jQuery('.column').animate({
            scrollTop: jQuery('#viewtable tr[name="' + name + '"]').position().top
        }, 1000);
    });

    jQuery('#viewport div').hover(
        function () {
            unitHl(this, 'in');
        },
        function () {
            unitHl(this, 'out');
        }
    );
    jQuery(window).bind("resize", function () {
        fitToWindow();
    });

    fitToWindow();

    if ((document.all) ? false : true)
        jQuery('#g_container div table').attr('width', '100%');

    jQuery('.guayaquil_zoom').colorbox({
        'href': function () {
            return jQuery(this).attr('full');
        },
        'title': function () {
            var title = jQuery(this).attr('title');
            return title;
        },
        'maxWidth': '98%',
        'maxHeight': '98%',
        'width': '49%',
        'opacity': 0.3,
        close: '',
        html: function () {
            return jQuery('#viewport').html();
        },
        onComplete: function () {
            jQuery('#cboxLoadedContent').css('overflow', 'hidden');
            jQuery('#cboxLoadedContent div').click(function () {

                var name = jQuery(this).attr('name');
                jQuery('[name=' + name + '].g_highlight').toggleClass('g_highlight_lock');
                jQuery('[name=' + name + '].g_highlight_over').toggleClass('g_highlight_lock');

                var tr = jQuery('tr.g_highlight_lock[name="' + name + '"]');
                if (tr.length == 0) {
                    return;
                }
                jQuery('.column').animate({
                    scrollTop: jQuery('#viewtable tr[name="' + name + '"]').position().top
                }, 1000);
            });

            jQuery('#cboxLoadedContent div').hover(
                function () {
                    unitHl(this, 'in');
                },
                function () {
                    unitHl(this, 'out');
                }
            );

            jQuery('#cboxLoadedContent div.unit-tooltip').tooltip({
                track: true,
                delay: 0,
                showURL: false,
                fade: 250,
                bodyHandler: function () {
                    var name = jQuery(this).attr('name');
                    var item = jQuery('tr[name="' + name + '"] td[name=c_name]').first();
                    if (item.length === 0) {
                        jQuery(this).tooltip('destroy');
                        return false;
                    } else {
                        return jQuery(item[0]).html();
                    }
                }
            });
        }
    });
});

var glow_name = '';

function glow(name) {

    if (name) {
        glow_name = name.toUpperCase();

        jQuery('.guayaquil_floatunitlist_box').removeClass('g_highlight_glow');

        var units = jQuery('div[name="' + glow_name + '"]').parent();

        units.addClass('g_highlight_glow');

        if (units.length > 0) {
            window.location = '#_' + name;

            jQuery('html, body').animate({
                scrollTop: jQuery('div[name="' + glow_name + '"]').offset().top
            }, 500);

            return true;
        } else {
            return false;
        }
    }
}

function hl(el, type) {
    var name = el.name;
    if (name == null) {
        name = el.getProperty('name');
    }

    if (glow_name == name) {
        if (type == 'in')
            jQuery('.g_highlight_glow[name="' + name + '"]').attr('class', 'g_highlight_over');
        else
            jQuery('.g_highlight_over[name="' + name + '"]').attr('class', 'g_highlight_glow');
    }
    else {
        if (type == 'in')
            jQuery('.g_highlight[name="' + name + '"]').attr('class', 'g_highlight_over');
        else
            jQuery('.g_highlight_over[name="' + name + '"]').attr('class', 'g_highlight');
    }
}

jQuery(document).ready(function ($) {
    jQuery('.guayaquil_floatunitlist_box div').hover(
        function () {
            jQuery('div[name="' + jQuery(this).attr('name') + '"]').parent().addClass('guayaquil_floatunitlist_box_hover');
        },
        function () {
            jQuery('div[name="' + jQuery(this).attr('name') + '"]').parent().removeClass('guayaquil_floatunitlist_box_hover');
        }
    );

    jQuery(document).ready(function () {
        jQuery('.unitlist-zoom').colorbox({
                rel: 'gal',
                href: function () {
                    var url = jQuery(this).attr('full');
                    return url;
                },
                photo: true,

                opacity: 0.3,
                title: function () {
                    var title = jQuery(this).attr('title');
                    var url = jQuery(this).attr('link');
                    var filter = jQuery(this).data('filter-url');

                    if (filter) {
                        return '<a class="" id="from-image-filter" data-url=' + filter + ' href="javascript:void(0)">' + title + '</a>'
                    }
                    return '<a href="' + url + '">' + title + '</a>';
                },
                current: 'Рис. {current} из {total}',
                innerWidth: '800px',
                maxWidth: '98%',
                maxHeight: '98%',
                scrolling: true,
                close: '',
                onComplete: function () {
                    jQuery('a#from-image-filter').colorbox({
                        href: function () {
                            var url = jQuery(this).data('url');
                            return url;
                        },
                        close: '',
                        iframe: true,
                        width: 800,
                        height: 250
                    });
                }
            }
        );

        jQuery('a#filter, div#filter, a#from-image-filter, .qgroups-unit-filter').colorbox({
            href: function () {
                var url = jQuery(this).data('url');
                return url;
            },
            iframe: true,
            close: '',
            width: 800,
            height: 250
        })
    });

    jQuery('.guayaquil_floatunitlist_box').tooltip({
        track: true,
        delay: 0,
        showURL: false,
        fade: 250,
        bodyHandler: function () {
            var id = jQuery(this).attr('note');

            var items = jQuery('#unm' + id);
            var tooltip = jQuery(items[0]).text();

            items = jQuery('#utt' + id);
            if (items.length > 0)
                tooltip = tooltip + '<br>' + jQuery(items[0]).text();

            return tooltip;
        }
    });

    jQuery(document).on('submit', '#searchByCode', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var input = jQuery(this).find('input[name="code"]');
        var name = input.val();
        var res = glow(name);

        input.removeClass('g_input_error');

        if (res === false) {
            input.addClass('g_input_error');
        }
    });

    jQuery(document).on('click', '#searchByCode input[name="code"]', function () {

        if (jQuery(this).hasClass('g_input_error')) {
            jQuery(this).removeClass('g_input_error');
        }
    })
});

jQuery(document).ready(function () {

    jQuery(document).on('click', '.g_additional_toggler', function () {
        var currentUnit = jQuery(this).data('unit');
        var rowsToTogle = jQuery(document).find('tr[data-unit="' + currentUnit + '"]');
        g_toggleAdditional('g_DetailTable1', opennedimage, closedimage);
        jQuery(rowsToTogle).toggleClass('hidden');
    });

    jQuery(document).on('hover', 'tr.g_collapsed', function () {
        var name = jQuery(this).attr('name');
        jQuery(document).find('tr[name=' + name + ']').toggleClass('g_highlight_over');
    });
});

var opennedimage = 'com_guayaquil/assets/images/openned.gif';
var closedimage = 'com_guayaquil/assets/images/closed.gif';
jQuery(document).ready(function ($) {
    jQuery('td.g_rowdatahint').tooltip({
        track: true,
        delay: 0,
        showURL: false,
        fade: 250,
        positionLeft: true,
        bodyHandler: g_getHint
    });
    jQuery('img.g_addtocart').tooltip({
        track: true,
        delay: 0,
        showURL: false,
        fade: 250,
        bodyHandler: function () {
            return 'Добавить деталь в корзину';
        }
    });
    jQuery('td[name=c_toggle] img').tooltip({
        track: true,
        delay: 0,
        showURL: false,
        fade: 250,
        bodyHandler: function () {
            return 'Показать/скрыть информацию о дубликатах';
        }
    });
    jQuery('img.c_rfull').tooltip({
        track: true,
        delay: 0,
        showURL: false,
        fade: 250,
        bodyHandler: function () {
            return '<h3>Тип заменяемости</h3>Точный дубликат';
        }
    });
    jQuery('img.c_rforw').tooltip({
        track: true,
        delay: 0,
        showURL: false,
        fade: 250,
        bodyHandler: function () {
            return '<h3>Тип заменяемости</h3>Замена возможна указанным дубликатом возможна, но обратная замена не гарантируется';
        }
    });
    jQuery('img.c_rbackw').tooltip({
        track: true,
        delay: 0,
        showURL: false,
        fade: 250,
        bodyHandler: function () {
            return '<h3>Тип заменяемости</h3>Замена НЕ возможна';
        }
    });
});

function unitHl(el, type) {
    var name = jQuery(el).attr('name');

    if (type == 'in')
        jQuery('.g_highlight[name="' + name + '"]').addClass('g_highlight_over').removeClass('g_highlight');
    else
        jQuery('.g_highlight_over[name="' + name + '"]').removeClass('g_highlight_over').addClass('g_highlight');
}

function g_toggle(el, opennedimage, clossedimage) {
    var name = jQuery(el).attr('id');

    var e = jQuery('tr#' + name);
    if (e.hasClass('g_collapsed')) {
        jQuery('tr.g_replacementRow[name=' + name + ']').show();
        jQuery(el).attr('src', opennedimage);
        e.removeClass('g_collapsed');
    }
    else {
        jQuery('tr.g_replacementRow[name=' + name + ']').hide();
        jQuery(el).attr('src', clossedimage);
        e.addClass('g_collapsed');
    }
}

function g_toggleAdditional(id, opennedimage, clossedimage) {

    var e = jQuery('#' + id + ' .g_additional_toggler');
    if (e.hasClass('g_addcollapsed')) {
        jQuery('#' + id + ' tr.g_addgr').removeClass('g_addgr_collapsed');
        jQuery(e).attr('src', opennedimage);
        e.removeClass('g_addcollapsed');
    }
    else {
        jQuery('#' + id + ' tr.g_addgr').addClass('g_addgr_collapsed');
        jQuery(e).attr('src', clossedimage);
        e.addClass('g_addcollapsed');
    }
}

function g_getHint() {

    var str = '<table border=0>';
    var items = jQuery(this).parent().find('td.g_ttd');

    for (var i = 0; i < items.length - 1; i++) {
        var txt = jQuery(items[i]).html();
        if (txt.length <= 0)
            continue;

        str = str + '<tr><th align=right>' + jQuery('#' + jQuery(items[i]).attr('name')).text() + ':&nbsp;</th><td>' + txt + '</td></tr>';
    }
    var note_items = jQuery(items[i]).find('.item');
    for (var k = 0; k < note_items.length; k++) {
        var txt = jQuery(note_items[k]).find('span.value').text();
        if (txt.length <= 0)
            continue;
        str = str + '<tr><th align=right>' + jQuery(note_items[k]).find('span.name').text() + ':&nbsp;</th><td>' + txt + '</td></tr>';
    }
    str = str + '</table>';

    return str;
}

jQuery(document).ready(function ($) {

    jQuery('tr.g_highlight a.follow').colorbox({
        'href': function () {
            var url = (jQuery(this).attr('href')).replace(/[ ]/g, '');
            url += (url.indexOf('?') >= 0 ? '&' : '?') + 'format=raw';
            return url;
        },
        'opacity': 0.3,
        close: '',
        'innerWidth': '1000px',
        'maxHeight': '98%'
    })
});

jQuery(document).on('click', '.vehicle-modifications .show-more-wrapper', function () {
    var $this = jQuery(this);
    var link = $this.find('a.show-more');
    var $link = jQuery(link);
    var wrapper = $this.closest('table.vehicle-modifications');
    var showMoreText = $link.data('show-more-text');
    var hideMoreText = $link.data('hide-more-text');
    jQuery(wrapper).find('td.children-table-wrapper').toggleClass('hidden');
    $link.toggleClass('active');

    if ($link.hasClass('active')) {
        $link.text(hideMoreText);
    } else {
        $link.text(showMoreText);
    }
});

jQuery(document).on('click', '.vehicle-modifications .show-more-button', function () {
    var $this = jQuery(this);
    var $wrapper = jQuery($this.closest('td'));
    var toToggle = $wrapper.find('a.modification-column');
    var showText = $this.data('show-text');
    var hideText = $this.data('hide-text');

    jQuery(toToggle).toggleClass('opened');

    if (jQuery(toToggle).hasClass('opened')) {
        $this.text(hideText);
    } else {
        $this.text(showText);
    }
});

jQuery(document).on('click', '.tabs .controls > button', function () {
    var wrapper = this.closest('.tabs');
    var controlsWrapper = this.closest('.controls');
    var controlButtons = controlsWrapper.querySelectorAll('button');
    controlButtons.forEach(function (button) { button.classList.remove('active') });
    this.classList.add('active');

    var tabContents = wrapper.querySelectorAll('.content .tab');
    tabContents.forEach(function (tab) { tab.classList.remove('active') });

    wrapper.querySelector(this.dataset.tab).classList.add('active');
});

jQuery(document).on('click', '.aftermarket-detail-image', function () {
    jQuery().colorbox({
        photo: true,
        close: '',
        maxWidth: 1024,
        href: this.dataset.full
    });
});
