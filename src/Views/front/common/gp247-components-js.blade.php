<script>
document.addEventListener('click', function (event) {
    var closeButton = event.target.closest('.gp247-notice__close');
    if (!closeButton) {
        return;
    }

    var message = closeButton.closest('.gp247-notice__message');
    if (message) {
        message.remove();
    }
});
</script>
