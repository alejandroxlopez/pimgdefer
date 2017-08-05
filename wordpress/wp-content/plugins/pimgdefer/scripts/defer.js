(function($) {   
$("img").each(function() {
    var src = $(this).attr("src");
    $(this).data("src", src);
    $(this).attr("src", "data:image/gif;base64,R0lGODlhAQABAAAAACw=");
});

})( jQuery );

/******Defer Images******/
function init() {
var imgDefer = document.getElementsByTagName('img');
for (var i=0; i<imgDefer.length; i++) {
if(imgDefer[i].getAttribute('data-src')) {
imgDefer[i].setAttribute('src',imgDefer[i].getAttribute('data-src'));
} } }
window.onload = init;