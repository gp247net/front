{{--
    Package default view (ADR-013). Sole trigger for session-flash toasts —
    the inline `.gp247-notice` banner markup used to fire *alongside* the
    common/js.blade.php toast for the same session keys, showing every flash
    message twice. common/js.blade.php has since been retired; the sweetalert2
    script and alertJs() definition it used to own are folded in here, since
    this is the only place left that needs them for flash-driven messages.
    layout.blade.php's Livewire add-to-cart listeners (block_menu.blade.php)
    reuse the same global alertJs() for their own toasts.

    sweetalert2 is loaded from a package-owned, template-independent path
    (public/vendor/gp247-front/js) rather than $GP247TemplateFile — this is
    a shared component, so it must not require every template to carry its
    own copy of a third-party library. FrontServiceProvider publishes/copies
    this file automatically; see ensureSharedAssetsPublished().
--}}
<script src="{{ gp247_file('GP247/Core/js/sweetalert2.all.min.js') }}"></script>
<script>
    function alertJs(type = 'error', msg = '') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            customClass: {
                popup: 'rounded-xl shadow-soft',
            },
        });
        // WHY 'icon' not 'type': SweetAlert2 renamed this option years ago
        // (removed in v10+); the bundled v11.17.2 silently ignores 'type',
        // so the toast rendered with no icon at all until this was fixed.
        Toast.fire({
            icon: type,
            title: msg
        })
    }
</script>

@if (session('success'))
<script type="text/javascript">
    alertJs('success', '{!! session('success') !!}');
</script>
@endif

@if (session('error'))
<script type="text/javascript">
    alertJs('error', '{!! session('error') !!}');
</script>
@endif

@if (session('warning'))
<script type="text/javascript">
    alertJs('warning', '{!! session('warning') !!}');
</script>
@endif
