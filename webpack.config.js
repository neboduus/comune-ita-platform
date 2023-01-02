const Encore = require('@symfony/webpack-encore');
const package = require("./package.json");
const CompressionPlugin = require("compression-webpack-plugin");
const zlib = require("zlib");

Encore
  // directory where compiled assets will be stored
  .setOutputPath('public/build/' + package.version + '/')
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
  .addEntry('core', './assets/js/core.js')
  .addEntry('datatables', './assets/js/components/datatables.js')
  .addEntry('compile', './assets/js/compile.js')
  .addEntry('user', './assets/js/user.js')
  .addEntry('service-manager', './assets/js/service-manager.js')
  .addEntry('service-group-manager', './assets/js/service-group-manager.js')
  .addEntry('tenant-manager', './assets/js/pages/admin/tenant-manager.js')
  .addEntry('subscription-service-manager', './assets/js/subscription-service-manager.js')
  .addEntry('calendar-manager', './assets/js/calendar-manager.js')
  .addEntry('fullcalendar-common', './assets/js/fullcalendar-common.js')
  .addEntry('fullcalendar-manager', './assets/js/fullcalendar-manager.js')
  .addEntry('fullcalendar-dynamic-manager', './assets/js/fullcalendar-dynamic-manager.js')
  .addEntry('chars-operator', './assets/js/Charts/chart-operator.js')
  .addEntry('profile', './assets/js/User/Profile/profile.js')
  .addEntry('outdated-browser-rework', './assets/js/outdated-browser-rework.js')
  .addEntry('edit-operator', './assets/js/edit-operator')
  .addEntry('operator-show-application', './assets/js/pages/operator-show-application')
  .addEntry('operator-new-application', './assets/js/pages/operator-new-application')
  .addEntry('user-show-application', './assets/js/pages/user-show-application')
  .addEntry('chars-user', './assets/js/Charts/chart-user.js')
  .addEntry('admin-scheduled-actions', './assets/js/pages/admin-scheduled-actions.js')
  .addEntry('geographic-areas', './assets/js/pages/admin/geographic-areas.js')
  .addEntry('user-group', './assets/js/pages/admin/user-group.js')
  // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
  .splitEntryChunks()
  .enableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .enableIntegrityHashes(Encore.isProduction())

  // uncomment if you use TypeScript
  //.enableTypeScriptLoader()

  .configureBabel(function (babelConfig) {
    babelConfig.plugins.push('@babel/plugin-proposal-class-properties');
  })
  // Todo: da verifcare cosa fa
  .configureBabelPresetEnv((config) => {
    config.useBuiltIns = 'usage';
    config.corejs = 3;
  })

  // uncomment if you use Sass/SCSS files
  .enableSassLoader((options) => {
    options.sassOptions = {
      quietDeps: true, // disable warning msg
    }
  })

  // uncomment if you're having problems with a jQuery plugin
  .autoProvidejQuery()
  .autoProvideVariables({})
  .copyFiles({
    from: './node_modules/bootstrap-italia/dist/',
    //to: '../bootstrap-italia/dist/[path][name].[hash:8].[ext]',
    to: '../../bootstrap-italia/dist/[path][name].[ext]',
    pattern: /\.(svg)$/,
  })
  // Todo: verifica se spostare a livello di php
  .copyFiles({
    from: './assets/app/',
    //to: '../bundles/app/[path][name].[hash:8].[ext]',
    to: '../../bundles/app/[path][name].[ext]',
    pattern: /\.(ico|png|jpg|jpeg|svg|gif|pdf|js|css|json|txt)$/,
  })
  .copyFiles({
    from: './assets/images',
    to: 'images/[path][name].[ext]',
    pattern: /\.(png|jpg|jpeg|svg)$/
  })
  .addPlugin(new CompressionPlugin({
    filename: "[path][base].br",
    algorithm: "brotliCompress",
    test: /\.(js|css|html|svg)$/,
    compressionOptions: {
      params: {
        [zlib.constants.BROTLI_PARAM_QUALITY]: 11
      },
    },
    threshold: 10240,
    minRatio: 0.8,
  }))
  .addPlugin( new CompressionPlugin({
    filename: "[path][base].gz",
    algorithm: "gzip",
    test: /\.(js|css|html|svg)$/,
    threshold: 10240,
    minRatio: 0.8,
  }))
;

module.exports = Encore.getWebpackConfig();
