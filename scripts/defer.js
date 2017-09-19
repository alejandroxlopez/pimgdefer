/******Defer Images******/
function init() {
    var imgd = document.getElementsByTagName('img');
    for (var i = 0; i < imgd.length; i++) {
        if (imgd[i].getAttribute('data-src')) {
            imgd[i].setAttribute('src', imgd[i].getAttribute('data-src'));
        }

        if (imgd[i].getAttribute('data-srcset')) {
            imgd[i].setAttribute('srcset', imgd[i].getAttribute('data-srcset'));
        }
    }
}
window.onload = init;