const { mix, config: Mix } = require('laravel-mix')
const ChunkManifest = require('webpack-plugin-chunk-manifest')

global.Mix = Mix

const publicPath = Mix.options.hmr ? 'http://localhost:8080/' : '/'

if (mix.config.inProduction) mix.version()

mix.options({ extractVueStyles: 'css/app.css' })
    .js('resources/assets/js/app.js', 'public/js/')
    .webpackConfig({
      output: { publicPath },
      plugins: [
          new ChunkManifest({
            filename: 'chunk-manifest.json',
            transform (chunks) {
              Object.keys(chunks).forEach(key => {
                chunks[key] = `/${chunks[key]}`.replace(/^(\/js\/\/)/g, '/')
                console.log(chunks[key])
              })

              return chunks
            }
          })
      ]
    })
