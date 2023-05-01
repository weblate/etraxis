//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2023 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

const Encore = require('@symfony/webpack-encore');
const glob   = require('glob');
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
    .addEntry('font-awesome',   'font-awesome/css/font-awesome.css')
    .addEntry('groups/index',   './templates/groups/index.js')
    .addEntry('login/index',    './templates/login/index.js')
    .addEntry('navbar',         './templates/navbar.js')
    .addEntry('projects/index', './templates/projects/index.js')
    .addEntry('projects/view',  './templates/projects/view.js')
    .addEntry('settings/index', './templates/settings/index.js')
    .addEntry('users/index',    './templates/users/index.js')
    .addEntry('users/view',     './templates/users/view.js')

    // Themes.
    .addEntry('light/azure',     './assets/styles/themes/azure-light.scss')
    .addEntry('light/emerald',   './assets/styles/themes/emerald-light.scss')
    .addEntry('light/mars',      './assets/styles/themes/mars-light.scss')
    .addEntry('light/nord',      './assets/styles/themes/nord-light.scss')
    .addEntry('light/solarized', './assets/styles/themes/solarized-light.scss')
    .addEntry('dark/azure',      './assets/styles/themes/azure-dark.scss')
    .addEntry('dark/emerald',    './assets/styles/themes/emerald-dark.scss')
    .addEntry('dark/mars',       './assets/styles/themes/mars-dark.scss')
    .addEntry('dark/nord',       './assets/styles/themes/nord-dark.scss')
    .addEntry('dark/solarized',  './assets/styles/themes/solarized-dark.scss')

    // Aliases.
    .addAliases({
        '@components': path.resolve(__dirname, 'assets/components/'),
        '@enums':      path.resolve(__dirname, 'assets/enums/'),
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

/**
 * TRANSLATIONS CONFIG
 *
 * Converts translations in the YAML files to JavaScript objects.
 */
Encore.addAliases({ '@translations': path.resolve(__dirname, 'translations/messages/') });
Encore.addLoader({ test: /\.ya?ml$/, loader: 'yaml-loader' });

glob.globSync('./templates/i18n/**.js').forEach(name => Encore.addEntry(
    name.replace('templates/', '').replace('.js', ''),
    './' + name
));

module.exports = Encore.getWebpackConfig();
