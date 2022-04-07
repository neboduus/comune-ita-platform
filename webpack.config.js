const Encore = require('@symfony/webpack-encore');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const package = require("./package.json");
//const webpack = require('webpack');

Encore
  // directory where compiled assets will be stored
  .setOutputPath('web/build/' + package.version + '/')
  // public path used by the web server to access the output path
  .setPublicPath('/build/' + package.version + '/')
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
  .addEntry('service-group-manager', './assets/js/service-group-manager.js')
  .addEntry('instance-manager', './assets/js/instance-manager.js')
  .addEntry('subscription-service-manager', './assets/js/subscription-service-manager.js')
  .addEntry('calendar-manager', './assets/js/calendar-manager.js')
  .addEntry('fullcalendar-common', './assets/js/fullcalendar-common.js')
  .addEntry('fullcalendar-manager', './assets/js/fullcalendar-manager.js')
  .addEntry('fullcalendar-dynamic-manager', './assets/js/fullcalendar-dynamic-manager.js')
  .addEntry('chars-operator', './assets/js/Charts/chart-operator.js')
  .addEntry('jquery-ui-only-calendar', './assets/js/jquery-ui-custom-calendar.js')
  .addEntry('outdated-browser-rework', './assets/js/outdated-browser-rework.js')
  .addEntry('edit-operator', './assets/js/edit-operator')
  .addEntry('operator-show-application', './assets/js/pages/operator-show-application')
  .addEntry('operator-new-application', './assets/js/pages/operator-new-application')
  .addEntry('user-show-application', './assets/js/pages/user-show-application')
  .addEntry('chars-user', './assets/js/Charts/chart-user.js')
  .addEntry('admin-scheduled-actions', './assets/js/pages/admin-scheduled-actions.js')
  .addEntry('geographic-areas', './assets/js/pages/admin/geographic-areas.js')

  // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
  .splitEntryChunks()

  // will require an extra script tag for runtime.js
  // but, you probably want this, unless you're building a single-page app
  .enableSingleRuntimeChunk()

  .cleanupOutputBeforeBuild()

  .enableSourceMaps(!Encore.isProduction())
  // enables hashed filenames (e.g. app.abc123.css)
  .enableVersioning(Encore.isProduction())

  // uncomment if you use TypeScript
  //.enableTypeScriptLoader()

  .configureBabel(function(babelConfig) {
    babelConfig.plugins.push('@babel/plugin-proposal-class-properties');
  })

  // uncomment if you use Sass/SCSS files
  .enableSassLoader()

  // uncomment if you're having problems with a jQuery plugin
  .autoProvidejQuery()
  .addPlugin(new CopyWebpackPlugin({
    patterns: [
      {from: './node_modules/bootstrap-italia/dist/fonts/', to: '../../bootstrap-italia/dist/fonts/'},
      {from: './node_modules/bootstrap-italia/dist/svg/', to: '../../bootstrap-italia/dist/svg/'}
    ]
  }))
  /*.copyFiles({
    from: './assets/images/',
    //to: '../bundles/app/[path][name].[hash:8].[ext]',
    to: 'images/[path][name].[ext]',
    pattern: /\.(ico|png|jpg|jpeg|svg|gif)$/,
  })*/
;

module.exports = Encore.getWebpackConfig();
