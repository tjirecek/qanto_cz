document.addEventListener('DOMContentLoaded', function () {
    if (!window.tinymce) return;

    const editors = document.querySelectorAll('textarea.js-tinymce');
    if (!editors.length) return;

    const baseConfig = {
        language: 'cs',
        language_url: '/assets/lib/tinymce/langs/cs.js',
        menubar: false,
        branding: false,
        promotion: false,
        license_key: 'gpl',

        plugins: [
            'anchor', 'autolink', 'charmap', 'code', 'emoticons', 'image', 'link', 'lists', 'media',
            'searchreplace', 'table', 'visualblocks', 'wordcount', 'fullscreen', 'preview',
            'file-manager'
        ],
        toolbar:
            'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | ' +
            'bullist numlist | link image media table | removeformat | code fullscreen',

        // důležité: aby se do textarea propsal obsah i bez blur
        setup: function (editor) {
            editor.on('change keyup', function () {
                editor.save();
            });
        },

        Flmngr: {
            apiKey: 'FLMNFLMN',
            urlFileManager: '/secure/lib/flmngr/flmngr.php',
            urlFiles: '/media/library'
        }
    };

    editors.forEach(function (el) {
        const height = Number.parseInt(el.dataset.tinymceHeight || '', 10);

        tinymce.init({
            ...baseConfig,
            target: el,
            height: Number.isFinite(height) ? height : 360
        });
    });
});
