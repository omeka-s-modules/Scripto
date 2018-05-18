var gulp = require('gulp');
var sass = require('gulp-sass');
var postcss = require('gulp-postcss');
var autoprefixer = require('autoprefixer');

gulp.task('css', function () {
    return gulp.src('./asset/sass/*.scss')
        .pipe(sass()
        .on('error', sass.logError))
        .pipe(postcss([
            autoprefixer({browsers: ['> 5%', '> 1% in US']})
        ]))
        .pipe(gulp.dest('./asset/css'));
});

gulp.task('css:watch', function () {
    gulp.watch('./asset/sass/*.scss', gulp.parallel('css'));
});
