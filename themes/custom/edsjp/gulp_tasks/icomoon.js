var gulp = require('gulp'),
  icomoonBuilder = require('gulp-icomoon-builder'),
  notify = require('gulp-notify');

gulp.task('build-icons', function()  {
  return gulp.src('assets/icomoon/selection.json')
    .pipe(icomoonBuilder({
      templateType: 'map',
      filename: '_icons.scss'
    }))
    .on('error', function (error) {
      notify().write(error);
    })
    .pipe(gulp.dest('scss/icons'))
    .on('error', function (error) {
      notify().write(error);
    })
});
