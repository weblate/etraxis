module.exports = {
    collectCoverage: true,
    collectCoverageFrom: [
        '**/assets/scripts/**/*.js',
        '!**/assets/scripts/translations.js',
        '!**/node_modules/**'
    ],
    coverageReporters: [
        'text',
        'text-summary'
    ],
    moduleNameMapper: {
        '^@utilities(.*)$': '<rootDir>/assets/scripts$1'
    },
    testEnvironment: 'jsdom',
    testRegex: './assets/scripts/__tests__/.+\\.test\\.js',
    transform: {
        '\\.js$': 'babel-jest'
    }
};
