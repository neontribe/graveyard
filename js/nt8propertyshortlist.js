
(function($) {
    var baseURL = drupalSettings.path.baseUrl;

    $shortlistActions = $('article .property-teaser-shortlist a');
    $shortlistActions.on('click', function(e) {
        e.preventDefault();
        var $parentArticle = $(this).closest('article');
        var propref = $parentArticle.data('propref');

        $shortlistIcon = $parentArticle.find('.property-teaser-shortlist i');
        $shortlistIcon.toggleClass('fa-heart-o');
        $shortlistIcon.toggleClass('fa-heart');

        $.get(baseURL + '/shortlist/toggle/' + propref);
    });

})(jQuery);