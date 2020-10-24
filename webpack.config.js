var Encore = require('@symfony/webpack-encore');

Encore
// directory where compiled assets will be stored
  .setOutputPath('public/build/')
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
  .addEntry('service-group-manager', './assets/js/service-group-manager.js')
  .addEntry('instance-manager', './assets/js/instance-manager.js')
  .addEntry('subscription-service-manager', './assets/js/subscription-service-manager.js')
  .addEntry('calendar-manager', './assets/js/calendar-manager.js')
  .addEntry('fullcalendar-manager', './assets/js/fullcalendar-manager.js')
  .addEntry('chars-operator', './assets/js/chars-operator.js')
  .addEntry('jquery-ui-only-calendar', './assets/js/jquery-ui-custom-calendar.js')
  .addEntry('outdated-browser-rework', './assets/js/outdated-browser-rework.js')
  .addEntry('edit-operator', './assets/js/edit-operator')

  // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
  .splitEntryChunks()
  .enableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .enableIntegrityHashes(Encore.isProduction())

  // uncomment if you use TypeScript
  //.enableTypeScriptLoader()

  .configureBabel(function(babelConfig) {
    babelConfig.plugins.push('@babel/plugin-proposal-class-properties');
  })
  // Todo: da verifcare cosa fa
  .configureBabelPresetEnv((config) => {
    config.useBuiltIns = 'usage';
    config.corejs = 3;
  })

  // uncomment if you use Sass/SCSS files
  .enableSassLoader()

  // uncomment if you're having problems with a jQuery plugin
  .autoProvidejQuery()
  .autoProvideVariables({})
  .copyFiles({
    from: './node_modules/bootstrap-italia/dist/',
    //to: '../bootstrap-italia/dist/[path][name].[hash:8].[ext]',
    to: '../bootstrap-italia/dist/[path][name].[ext]',
    pattern: /\.(eot|ttf|woff|woff2|svg)$/,
  })
  // Todo: verifica se spostare a livello di php
  .copyFiles({
    from: './assets/app/',
    //to: '../bundles/app/[path][name].[hash:8].[ext]',
    to: '../bundles/app/[path][name].[ext]',
    pattern: /\.(ico|png|jpg|jpeg|svg|gif|pdf|js|css)$/,
  })
;


module.exports = Encore.getWebpackConfig();
