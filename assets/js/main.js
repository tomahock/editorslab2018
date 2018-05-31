(function($, w, d, de, db) {

    /* VARS */
    de = d.documentElement;
    db = d.body;

    var breakpoints = {sm: 576, md: 768, lg: 992, xl: 1200},
        api = 'https://c66a9fd4.ngrok.io',
        templates = {};

    /* UTILS */
    function throttle(cb, delay, debounce) {
        delay = delay || 0;

        var last = 0,
            timeout;

        function throttled() {
            var now = +new Date(),
                diff = debounce ? 0 : now - last,
                args = arguments,
                that = this;

            if (diff >= delay) {
                last = now;
                return cb.apply(that, args);
            } else {
                timeout && clearTimeout(timeout);
            }
            timeout = setTimeout(function() {
                timeout = null;
                return cb.apply(that, args);
            }, delay - diff);
        }

        return throttled;
    }

    function toggleLoading(evt) {
        $(de).toggleClass('loading', evt.type === 'ajaxStart');
    }

    /* METHODS */
    function getStats() {
        $('[data-username]').each(function(idx, elm) {
            $.get(api + '/players/' + $(elm).attr('data-username') + '/stats', function(res) {
                console.log(res);
                //$('#player').html(Mustache.render(templates['template-player'], res));
            });
        })
    }

    function getLast() {
        $.get(api + '/last', function(res) {
            console.log(res);
            $('#last').html(Mustache.render(templates['last'], res, {
                post: templates['post'],
                comment: templates['comment']
            }));
            getStats();
        });
    }

    function getPlayers() {
        $.get(api + '/players', function(res) {
            console.log(res);
            //$('#players').html(Mustache.render(templates['template-players'], res));
        });
    }

    function getPlayer(player) {
        $.get(api + '/players/' + player, function(res) {
            console.log(res);
            //$('#player').html(Mustache.render(templates['template-player'], res));
        });
    }


    function getStuff() {
        getLast();
    }

    /* EVENT LISTENERS */
    $(d).on('ajaxStart ajaxStop', toggleLoading);

    /* INIT */
    $('script[type="x-tmpl-mustache"]').each(function(idx, elm) {
        elm = $(elm);
        var template = elm.attr('data-template');
        templates[template] = elm.html();
        Mustache.parse(templates[template]);
    });
    !$(de).is('.not-found') && getStuff();

}(jQuery, window, document));
