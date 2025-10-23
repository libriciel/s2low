const path = require('path');
const webpack = require('webpack');

module.exports = {
    mode: process.env.NODE_ENV === 'development' ? 'development' : 'production',

    resolve: {
        alias: {
            'load-image': 'blueimp-load-image/js/load-image.js',
            'load-image-scale': 'blueimp-load-image/js/load-image-scale.js',
            'load-image-orientation': 'blueimp-load-image/js/load-image-orientation',
            'load-image-meta': 'blueimp-load-image/js/load-image-meta.js',
            'load-image-exif': 'blueimp-load-image/js/load-image-exif.js',
            'canvas-to-blob': 'blueimp-canvas-to-blob/js/canvas-to-blob.js',

            // IMPORTANT pour blueimp-file-upload : garde ce polyfill du widget UI
            'jquery-ui/widget': 'blueimp-file-upload/js/vendor/jquery.ui.widget.js',

            './blueimp-gallery': 'blueimp-gallery/js/blueimp-gallery.js',
        },
    },

    module: {
        rules: [
            {
                test: /\.css$/i,
                use: ['style-loader', 'css-loader'],
            },
        ],
    },

    plugins: [
        new webpack.ProvidePlugin({
            $: 'jquery',
            jQuery: 'jquery',
        }),
    ],

    entry: {
        jquery: './src_js/jquery.js',

        select2: {
            import: './src_js/select2.js',
            dependOn: 'jquery',
        },

        // ta cible jQuery UI
        jqueryui: {
            import: './src_js/jquery-ui.js',
            dependOn: 'jquery',
        },

        jqueryfileupload: {
            import: './src_js/jquery.fileupload.js',
            dependOn: 'jquery',
        },

        handleSirenGroupe: {
            import: './src_js/handleSirenGroupe.js',
        },
    },

    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'public.ssl/jsmodules'),
        publicPath: '/jsmodules/',
        clean: false,
    },
};
