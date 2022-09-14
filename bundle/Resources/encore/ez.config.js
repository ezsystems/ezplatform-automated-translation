const path = require('path');

module.exports = (Encore) => {
    Encore.addEntry('ezplatform-automated-translation-js', [path.resolve(__dirname, '../public/admin/js/ezplatformautomatedtranslation.js')]);
};
