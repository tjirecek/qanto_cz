document.addEventListener('DOMContentLoaded', function () {
    if (!window.tinymce) return;

    const editors = document.querySelectorAll('textarea.js-tinymce');
    if (!editors.length) {
        window.QANTO_TINYMCE_READY = true;
        window.dispatchEvent(new CustomEvent('qanto:tinymce-ready'));
        return;
    }

    const notifyLayoutChange = function () {
        window.dispatchEvent(new CustomEvent('qanto:layout-change'));
    };

    const sanitizeEditorHtml = function (html) {
        return String(html || '')
            .replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '')
            .replace(/<script\b[^>]*\/?>/gi, '');
    };

    const baseConfig = {
        language: 'cs',
        language_url: '/assets/lib/tinymce/langs/cs.js',
        menubar: 'file edit view insert format tools table help',
        branding: false,
        promotion: false,
        license_key: 'gpl',

        plugins: [
            'accordion', 'advlist', 'anchor', 'autolink', 'autosave', 'charmap', 'code', 'codesample',
            'directionality', 'emoticons', 'fullscreen', 'help', 'image', 'importcss', 'insertdatetime',
            'link', 'lists', 'media', 'nonbreaking', 'pagebreak', 'preview', 'quickbars', 'searchreplace',
            'table', 'visualblocks', 'visualchars', 'wordcount', 'file-manager'
        ],
        toolbar:
            'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | ' +
            'forecolor backcolor | alignleft aligncenter alignright alignjustify | ' +
            'bullist numlist outdent indent | link image media table | ' +
            'charmap emoticons codesample | removeformat | preview code fullscreen',
        toolbar_mode: 'sliding',
        contextmenu: 'link image table',
        image_advtab: true,
        link_default_target: '_self',
        invalid_elements: 'script',
        convert_urls: true,
        relative_urls: false,
        remove_script_host: true,
        document_base_url: window.location.origin + '/',
        content_style:
            'body { background: #f8f9fa; padding: 12px 14px; } ' +
            'body::before { color: #adb5bd; }',
        table_toolbar:
            'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | ' +
            'tableinsertcolbefore tableinsertcolafter tabledeletecol',

        // důležité: aby se do textarea propsal obsah i bez blur
        setup: function (editor) {
            editor.on('SaveContent', function (event) {
                event.content = sanitizeEditorHtml(event.content);
            });

            editor.on('change keyup', function () {
                editor.save();
            });

            editor.on('init SkinLoaded ResizeEditor', notifyLayoutChange);
        },

        Flmngr: {
            apiKey: 'FLMNFLMN',
            urlFileManager: '/secure/lib/flmngr/flmngr.php',
            urlFiles: '/media/library'
        }
    };

    const initPromises = [];

    editors.forEach(function (el) {
        const height = Number.parseInt(el.dataset.tinymceHeight || '', 10);

        initPromises.push(Promise.resolve(tinymce.init({
            ...baseConfig,
            target: el,
            height: Number.isFinite(height) ? height : 360
        })).then(notifyLayoutChange));
    });

    Promise.allSettled(initPromises).then(function () {
        window.QANTO_TINYMCE_READY = true;
        notifyLayoutChange();
        window.dispatchEvent(new CustomEvent('qanto:tinymce-ready'));
    });
});
