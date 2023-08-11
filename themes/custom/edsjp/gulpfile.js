'use strict';
var gulp = require('gulp');
var requireDir = require('require-dir');

requireDir('./gulp_tasks');

// Default CSS builder.
gulp.task('default', gulp.series('build-icons', 'sass-prod'), function (done) {
    done();
});

// DEV CSS builder.
gulp.task('dev', gulp.series('build-icons', 'sass-dev', 'dev-sass-lint'), function (done) {
  done();
});

// DEV CSS builder and watcher.
gulp.task('watch', function () {
    gulp.watch('scss/**/*.scss', gulp.series('sass-dev', 'dev-sass-lint'));
    gulp.watch('assets/icomoon/selection.json', gulp.series('build-icons'));
});

// PROD CSS builder.
gulp.task('build', gulp.series('build-icons', 'sass-prod'), function (done) {
    done();
});
