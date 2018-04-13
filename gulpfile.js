var gulp = require('gulp');
var browserSync = require('browser-sync');


gulp.task('browser-sync', function() {
    browserSync({
        // Using a localhost address with a port
        proxy: "neuralcoin.local",

        files: [
            "src/FrontendBundle/Resources/views/**/*.html.twig",
            "src/FrontendBundle/Resources/public/js/*.js",
            "src/FrontendBundle/Resources/public/css/*.css",
            "app/Resources/views/*.html.twig",
            "web/js/*.js",
            "web/css/*.css",
            "public/**/*.js",
            "web/bundles/**/**/*.js",
            "web/bundles/**/*.js"
        ]
    }, function(err, bs) {
        console.log(bs.options.getIn(["urls", "local"]));
    });
});


gulp.task('default', ['browser-sync']);