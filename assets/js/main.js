(function($, w, d, de, db) {

    /* VARS */
    de = d.documentElement;
    db = d.body;

    var breakpoints = {sm: 576, md: 768, lg: 992, xl: 1200},
        api = 'https://ea9f1ee3.ngrok.io',
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

    function getLatest() {
        $.get(api + '/last', function(res) {
            console.log(res);
            $('#posts').html(Mustache.render(templates['latest-posts'], res, {post: templates['post']}));
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
        getPlayers();
        getLatest();
    }

    /* EVENT LISTENERS */
    $(d).on('ajaxStart ajaxStop', toggleLoading);

    /* INIT */
    $('script[type="x-tmpl-mustache"]').each(function(idx, elm) {
        elm = $(elm);
        var template = elm.attr('data-template');
        console.log(template);
        templates[template] = elm.html();
        Mustache.parse(templates[template]);
        elm.remove();
    });
    !$(de).is('.not-found') && getStuff();

}(jQuery, window, document));
