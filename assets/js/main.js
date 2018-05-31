(function($, w, d, de, db) {

    /* VARS */
    de = d.documentElement;
    db = d.body;

    var breakpoints = {sm: 576, md: 768, lg: 992, xl: 1200},
        api = 'https://editorslab.tomahock.com',
        templates = {};

    function toggleLoading(evt) {
        $(de).toggleClass('loading', evt.type === 'ajaxStart');
    }

    /* METHODS */
    function getStats(username) {
        $.get(api + '/players/' + username + '/stats', function(res) {
            var ctx = $('#posts [data-username="' + username + '"]');
            $('.stats', ctx).html(Mustache.render(templates['stats'], res));
            $('.activity', ctx).html(Mustache.render(templates['activity'], res));
        });
    }

    function getLatest() {
        $.get(api + '/last', function(res) {
            res = res.map(function(elm) {
                elm.username = function() {
                    getStats(elm.owner.username);
                    return elm.owner.username;
                };
                return elm;
            });
            $('#posts').html(Mustache.render(templates['latest-posts'], res, {post: templates['post']}));
        });
    }

    function getPlayers() {
        $.get(api + '/players', function(res) {
            $('#players ul').html(Mustache.render(templates['players'], res));
        });
    }

    function getPlayer(ev) {
        ev.preventDefault();
        var pathname = ev.target.hash.slice(1);
        $.get(api + pathname, function(player) {
            $.get(api + pathname + '/posts', function(posts) {
                console.log(posts);
                $('#posts').html(
                    Mustache.render(
                        templates['player-posts'],
                        {player: player, posts: posts},
                        {post: templates['post']}
                    )
                );
            });
        });
    }

    function getStuff() {
        getPlayers();
        getLatest();
    }

    /* EVENT LISTENERS */
    $(d).on('ajaxStart ajaxStop', toggleLoading);
    $(d).on('click', '#players a', getPlayer);

    /* INIT */
    $('script[type="x-tmpl-mustache"]').each(function(idx, elm) {
        elm = $(elm);
        var template = elm.attr('data-template');
        templates[template] = elm.html();
        Mustache.parse(templates[template]);
        elm.remove();
    });
    !$(de).is('.not-found') && getStuff();

}(jQuery, window, document));
