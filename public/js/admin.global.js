function resizeSidebar() {
    var bodyHeight = $('body').height();
    var windowHeight = $(window).height();
    var screenHeight = bodyHeight > windowHeight ? bodyHeight : windowHeight;
    var topHeight = $('.cd_top').height();
    var sidebarHeight = screenHeight - topHeight - 2;
    $('.cd_sidebar').height(sidebarHeight);	
}

