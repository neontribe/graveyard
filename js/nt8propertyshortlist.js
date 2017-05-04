
(function($) {
    $.get('shortlist/list', function(res) {
        setShortlistButtonState(res);
    });


    $shortlistActions = $('article .property-teaser-shortlist a');
    $shortlistActions.on('click', function(e) {
        e.preventDefault();
        var $parentArticle = $(this).parents('article');
        var propref = $parentArticle.data('propref');

        $shortlistIcon = $parentArticle.find('.property-teaser-shortlist i');
        $shortlistIcon.toggleClass('fa-heart-o');
        $shortlistIcon.toggleClass('fa-heart');

        $.get('shortlist/toggle/' + propref, function(res) {
            setShortlistButtonState(res);
        });
    });

    function setShortlistButtonState(res) {
        for(var propref in res) {
            $matchingArticle = $('article[data-propref='+propref+']');

            $matchingArticle.each(function(i, matchedArticle) {
                var $matchedArticle = $(matchedArticle);
                $shortlistInfo = $matchedArticle.find('.property-teaser-shortlist');

                $shortlistIcon = $shortlistInfo.find('i');
                $shortlistIcon.removeClass('fa-heart-o');
                $shortlistIcon.addClass('fa-heart');
            });
        }
    };
})(jQuery);