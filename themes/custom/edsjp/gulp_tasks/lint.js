'use strict';

var gulp = require('gulp'),
    sassLint = require('gulp-sass-lint'),
    bemlinter = require('gulp-bemlinter');

gulp.task('dev-sass-lint', function () {
    return gulp.src('scss/**/*.s+(a|c)ss')
        .pipe(sassLint())
        .pipe(sassLint.format())
        .pipe(sassLint.failOnError());
});

gulp.task('dev-sass-bem-lint', () => {
    return gulp.src('scss/**/*.s+(a|c)ss')
        .pipe(bemlinter())
        .pipe(bemlinter.format())
        .pipe(bemlinter.failOnError());
});
