/* eslint-disable @typescript-eslint/no-var-requires */
/* eslint-disable no-undef */

const { entries } = require('./ts/entries.json');
const ChunksWebpackPlugin = require('chunks-webpack-plugin');
const path = require('path');

let entry = {};

entries.forEach((file) => {

    const [name] = file.split('.');

    entry[name] = {
        import: `./ts/${file}`
    };

});

module.exports = (env) => {

    let mode = 'production', cache = false, otherRules = {
        optimization: {
            splitChunks: {
                chunks: 'all',
            },
        },
        plugins: [new ChunksWebpackPlugin()],
    };

    if (typeof env.dev === 'boolean' && env.dev) {
        mode = 'development';
        cache = {
            type: 'filesystem',
        };
        otherRules = {};
    }



    console.log(`Buiding webpack, ${mode} mode`);

    return {
        mode,
        entry,
        output: {
            path: path.resolve(__dirname, '..', 'includes', 'js'),
            filename: '[name].bundle.js'
        },
        resolve: {
            extensions: ['.tsx', '.ts', '.jsx', '.js']
        },
        module: {
            rules: [
                {
                    test: /\.tsx?/,
                    use: 'ts-loader',
                    exclude: /node_modules/,
                },
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    use: {
                        loader: 'babel-loader'
                    }
                },
                {
                    test: /\.jsx$/,
                    exclude: /node_modules/,
                    use: {
                        loader: 'babel-loader'
                    }
                }
            ]
        },
        cache,
        ...otherRules,

    };
};
