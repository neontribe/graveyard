(function($) {
    // Settings
    var options = {
        scrollOffset: 20
    };

    // Header.
    var $header = $('header[role=banner]');
    var headerHeight = $header.height() || 0;

    // Left sidebar.
    var $leftSidebar = $('.region-sidebar-left > .content-wrap');

    // Sticky sidebar.
    $leftSidebar.stick_in_parent({
        offset_top: options.scrollOffset
    });

})(jQuery);