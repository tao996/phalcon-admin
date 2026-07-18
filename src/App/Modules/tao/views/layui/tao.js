$('.successMessage, .errorMessage, .warningMessage, .noticeMessage').each(function () {
    var $msg = $(this);
    $msg.css('position', 'relative').append(
        '<span class="flash-close" style="position:absolute;top:50%;right:12px;transform:translateY(-50%);cursor:pointer;font-size:18px;line-height:1;opacity:0.5;">&times;</span>'
    );
});
$(document).on('click', '.flash-close', function () {
    $(this).parent().slideUp(200);
});
// tao.js