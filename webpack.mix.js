const { mix, config: Mix } = require('laravel-mix')

global.Mix = Mix

const publicPath = Mix.options.hmr ? 'http://localhost:8080/' : '/'

if (mix.config.inProduction) mix.version()

mix.options({ extractVueStyles: 'css/app.css' })
    .js('resources/assets/js/app.js', 'public/js')
    .webpackConfig({
      output: { publicPath }
    })
