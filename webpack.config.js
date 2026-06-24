const path = require('path');
const webpack = require('webpack');
const fs = require('fs');

// Simple plugin to copy bootstrap files
class CopyBootstrapPlugin {
    apply(compiler) {
        compiler.hooks.afterEmit.tap('CopyBootstrapPlugin', (compilation) => {
            const cssSrc = path.resolve(__dirname, 'node_modules/bootstrap/dist/css/bootstrap.min.css');
            const cssDest = path.resolve(__dirname, 'public.ssl/custom/styles/bootstrap5.min.css');
            const jsSrc = path.resolve(__dirname, 'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js');
            const jsDest = path.resolve(__dirname, 'public.ssl/custom/js/bootstrap.bundle.min.js');

            // Ensure destination directories exist
            fs.mkdirSync(path.dirname(cssDest), { recursive: true });
            fs.mkdirSync(path.dirname(jsDest), { recursive: true });

            // Copy files if they exist
            if (fs.existsSync(cssSrc)) {
                fs.copyFileSync(cssSrc, cssDest);
                console.log(`Copied ${cssSrc} to ${cssDest}`);
            } else {
                console.error(`Source CSS not found: ${cssSrc}`);
            }

            if (fs.existsSync(jsSrc)) {
                fs.copyFileSync(jsSrc, jsDest);
                console.log(`Copied ${jsSrc} to ${jsDest}`);
            } else {
                console.error(`Source JS not found: ${jsSrc}`);
            }
        });
    }
}

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
        new CopyBootstrapPlugin(),
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
