'use strict';

var gulp = require('gulp'),
    sassdoc = require('sassdoc');

gulp.task('sass-doc', function () {
    var options = {
        dest: 'docs/sassdoc',
        verbose: true,
        strict: true,
        display: {
            access: ['public', 'private'],
            alias: true,
            watermark: true,
        },
        basePath: '',
    };

    return gulp.src('scss/**/*.scss')
        .pipe(sassdoc(options))
});
