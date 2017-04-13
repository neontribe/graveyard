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
    $leftSidebar.Stickyfill();

})(jQuery);