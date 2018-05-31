(function($, w, d, de, db) {

    /* VARS */
    de = d.documentElement;
    db = d.body;

    var breakpoints = {sm: 576, md: 768, lg: 992, xl: 1200},
        api = 'https://c66a9fd4.ngrok.io',
        templates = {};

    function toggleLoading(evt) {
        $(de).toggleClass('loading', evt.type === 'ajaxStart');
    }

    /* METHODS */
    function getStats(username) {
        $.get(api + '/players/' + username + '/stats', function(res) {
            $('[data-username="' + username + '"] .stats').html(Mustache.render(templates['stats'], res));
        });
    }

    function getLast() {
        $.get(api + '/last', function(res) {
            console.log(res);
            $(res).each(function(idx, elm) {
                $('#last').append(Mustache.render(templates['last'], elm));
                getStats(elm.owner.username);
            })
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

    /* EVENT LISTENERS */
    $(d).on('ajaxStart ajaxStop', toggleLoading);

    /* INIT */
    $('script[type="x-tmpl-mustache"]').each(function(idx, elm) {
        elm = $(elm);
        var template = elm.attr('data-template');
        templates[template] = elm.html();
        Mustache.parse(templates[template]);
    });
    !$(de).is('.not-found') && getLast();

}(jQuery, window, document));
