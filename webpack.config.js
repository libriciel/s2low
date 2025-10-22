const path = require('path');

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
            'jquery-ui/widget': 'blueimp-file-upload/js/vendor/jquery.ui.widget.js',
            './blueimp-gallery' : 'blueimp-gallery/js/blueimp-gallery.js'
        }
    },
    module: {
        rules: [
            {
                test: /\.css$/i,
                use: ["style-loader","css-loader"],// TODO Pas recommandé pour la prod https://github.com/webpack-contrib/css-loader#recommend
            },
            {
                test: require.resolve('./src_js/jquery-ui.js'),
                use: 'imports-loader?wrapper=window',
            }
        ],
    },
    entry: {
        jquery: './src_js/jquery.js',
        select2:
            {
                import: './src_js/select2.js',
                dependOn: 'jquery'
            },
        jqueryui:
            {
                import: './src_js/jquery-ui.js',
                dependOn: 'jquery'
            },
        jqueryfileupload:
            {
                import: './src_js/jquery.fileupload.js',
                dependOn: 'jquery'
            },
        handleSirenGroupe:
            {
                import: './src_js/handleSirenGroupe.js'
            }
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'public.ssl/jsmodules'),
        publicPath: '/jsmodules/'
    }
};