const webpack = require('webpack');
const {resolve} = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const FriendlyErrorsWebpackPlugin = require('friendly-errors-webpack-plugin');
const buildDirectory = resolve(process.env.PROJECT_ROOT, 'public');

/**
 * -------------------------------------------------------
 * WEBPACK CONFIGURATIONS
 * -------------------------------------------------------
 * Impacts development mode (dev|watch|hot)
 * https://webpack.js.org/configuration
 * -------------------------------------------------------
 */

/**
 * Webpack module configuration and how them will be treated
 * https://webpack.js.org/configuration/module
 * @type {{rules: *[]}}
 */
const modules = {
    rules: [
        {
            test: /\.scss$/,
            use: [
                /**
                 * @todo: style-loader is mandatory to make the HMR mode work.
                 *        Using the MiniCssExtractPlugin further will break HMR mode
                 *        Thus we need to figure out a way to solve that.
                 *        loader: MiniCssExtractPlugin.loader
                 *
                 */
                {
                    loader: 'style-loader'
                },
                {
                    loader: 'css-loader'
                },
                {
                    loader: 'sass-loader'
                },
                {
                    loader: 'postcss-loader',
                    options: {
                        plugins: () => {
                            return [
                                require('autoprefixer'),
                                require('postcss-pxtorem')({
                                    propList: ['*']
                                })
                            ];
                        }
                    }
                }
            ]
        }
    ]
};

/**
 * Webpack plugins
 * https://webpack.js.org/configuration/plugins/#plugins
 * @type {*[]}
 */
const plugins = [
    new FriendlyErrorsWebpackPlugin(),
    new webpack.HotModuleReplacementPlugin(),
    new MiniCssExtractPlugin({
        filename: "css/main.bundle.css",
        chunkFilename: "css/main.bundle.css"
    })
];

/**
 * Options for the webpack-dev-server (e.g. for HMR mode)
 * https://webpack.js.org/configuration/dev-server#devserver
 * @type {{}}
 */
const devServer = {
    contentBase: buildDirectory,
    open: false,
    overlay: {
        warnings: false,
        errors: true
    },
    stats: {
        colors: true
    },
    quiet: true,
    hot: true,
    compress: true,
    disableHostCheck: true,
    port: 9999,
    host: '0.0.0.0',
    clientLogLevel: 'warning',
    headers: {
        'Access-Control-Allow-Origin': '*'
    }
};

/**
 * Export the webpack configuration
 */
module.exports = {
    devServer: devServer,
    devtool: 'cheap-module-eval-source-map',
    mode: 'development',
    module: modules,
    plugins: plugins
};