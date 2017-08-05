// (function ($) {
//     $("img").each(function () {
//         var src = $(this).attr("src");
//         $(this).attr("data-src", src);
//         $(this).attr("src", "data:image/gif;base64,R0lGODlhAQABAAAAACw=");
//     });

// })(jQuery);

/******Defer Images******/
function init() {
    var imgd = document.getElementsByTagName('img');
    for (var i = 0; i < imgd.length; i++) {
        if (imgd[i].getAttribute('data-src')) {
            imgd[i].setAttribute('src', imgd[i].getAttribute('data-src'));
        }
    }
}
window.onload = init;