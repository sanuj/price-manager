const { mix, config: Mix } = require('laravel-mix');

global.Mix = Mix

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.options({ extractVueStyles: 'css/app.css' }).js('resources/assets/js/app.js', 'public/js');
