var gulp = require('gulp'),
    sass = require('gulp-sass'),
    postcss = require('gulp-postcss'),
    sourcemaps = require('gulp-sourcemaps'),
    checkFilesize = require('gulp-check-filesize'),
    size = require('gulp-size'),
    autoprefixer = require('autoprefixer'),
    cssnano = require('cssnano'),
    notify = require('gulp-notify');

// compiling css with source maps for dev usage
gulp.task('sass-dev', function () {
    return gulp.src(['scss/style.scss'])
        .pipe(sourcemaps.init({
            loadMaps: true
        }))
        .pipe(sass.sync().on('error', sass.logError))
        .pipe(sourcemaps.write('/'))
        .pipe(gulp.dest('css/'))
        // .pipe(notify({
        //   message: "Generated DEV file: <%= file.relative %> @ <%= options.date %>",
        //   templateOptions: {
        //     date: new Date()
        //   }
        // }))
        .pipe(checkFilesize())
        .pipe(size())
});

// include minify and optimize for production, no source maps
gulp.task('sass-prod', function () {
    var processors = [
        autoprefixer({cascade: false}),
        cssnano()
    ];
    return gulp.src(['scss/style.scss'])
        .pipe(sass.sync().on('error', sass.logError))
        .pipe(postcss(processors))
        // .pipe(notify({
        //   message: "PostCss processes done"
        // }))
        /*.pipe(rename({extname: '.min.css'}))*/
        .pipe(gulp.dest('css/'))
        // .pipe(notify({
        //   message: "Generated PROD file: <%= file.relative %> @ <%= options.date %>",
        //   templateOptions: {
        //     date: new Date()
        //   }
        // }))
        .pipe(checkFilesize())
        .pipe(size())
});
