var Encore = require('@symfony/webpack-encore');
var CopyWebpackPlugin = require('copy-webpack-plugin');

Encore
// directory where compiled assets will be stored
.setOutputPath('web/build/')
// public path used by the web server to access the output path
.setPublicPath('/build')
// only needed for CDN's or sub-directory deploy
//.setManifestKeyPrefix('build/')

/*
 * ENTRY CONFIG
 *
 * Add 1 entry for each "page" of your app
 * (including one that's included on every page - e.g. "app")
 *
 * Each entry will result in one JavaScript file (e.g. app.js)
 * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
 */
.addEntry('app', './assets/js/app.js')
.addEntry('compile', './assets/js/compile.js')
.addEntry('user', './assets/js/user.js')
.addEntry('service-manager', './assets/js/service-manager.js')
.addEntry('instance-manager', './assets/js/instance-manager.js')

// When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
.splitEntryChunks()

// will require an extra script tag for runtime.js
// but, you probably want this, unless you're building a single-page app
.enableSingleRuntimeChunk()
.cleanupOutputBeforeBuild()
.enableBuildNotifications()
.enableSourceMaps(!Encore.isProduction())
// enables hashed filenames (e.g. app.abc123.css)
.enableVersioning(Encore.isProduction())

// uncomment if you use TypeScript
//.enableTypeScriptLoader()

// uncomment if you use Sass/SCSS files
.enableSassLoader()

// uncomment if you're having problems with a jQuery plugin
.autoProvidejQuery()
.addPlugin(new CopyWebpackPlugin([
  { from: './node_modules/bootstrap-italia/dist/fonts/', to: '../bootstrap-italia/dist/fonts/' },
  { from: './node_modules/bootstrap-italia/dist/svg/', to: '../bootstrap-italia/dist/svg/' }
]))
;


module.exports = Encore.getWebpackConfig();
