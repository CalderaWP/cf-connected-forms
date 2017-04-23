var gulp = require('gulp');
var concat = require('gulp-concat');
var watch = require('gulp-watch');
var minify = require( 'gulp-minify' );

gulp.task('js', function() {
	gulp.src([
		'assets/js/cf-connected-ajax.js',
		'assets/js/connector-ui.js'
	])
		.pipe(minify({
			ext:'.min.js',
			noSource: true,
			mangle: true,
			compress: true
		}))
		.pipe(gulp.dest('assets/js'))
});

gulp.task('watch', function(){
	gulp.watch('assets/*.js', ['js']);
});

gulp.task('default', ['js']);