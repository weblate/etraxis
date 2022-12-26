//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2022 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

const Encore = require('@symfony/webpack-encore');
const path   = require('path');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')

    /**
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('font-awesome', 'font-awesome/css/font-awesome.css')
    .addEntry('etraxis',      './assets/styles/etraxis.scss')
    .addEntry('login/index',  './templates/login/index.js')

    // Aliases.
    .addAliases({
        '@components': path.resolve(__dirname, 'assets/components/'),
        '@utilities':  path.resolve(__dirname, 'assets/scripts/'),
    })

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()
    .enableSingleRuntimeChunk()

    /**
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enableIntegrityHashes(Encore.isProduction())
    .enableSassLoader()
    .enableVueLoader(() => {}, { runtimeCompilerBuild: true })
;

module.exports = Encore.getWebpackConfig();
