'use strict';
 
import gulp from 'gulp'; 
import autoprefixer from 'gulp-autoprefixer';
import sass from 'gulp-dart-sass';
import rename from 'gulp-rename';
import uglifycss from 'gulp-uglifycss';
import sourcemaps from 'gulp-sourcemaps';
import uglify from 'gulp-uglify';
import { exec } from 'child_process';

// Assuming gulpfile.js is in the root, adjust the path to your script
gulp.task('extract-colors', (done) => {
    exec('node ./assets/build-scripts/extract-colors.js', (err, stdout, stderr) => {
        if (err) {
            console.error(`exec error: ${err}`);
            return;
        }
        console.log(stdout);
        console.error(stderr);
        done(); // signals completion of the task
    });
});

gulp.task('sass', () => {
	return gulp.src('assets/src/scss/**/*.scss')
        .pipe(sourcemaps.init())
		.pipe(sass.sync({
			outputStyle: 'compressed',
			includePaths: ['./node_modules'],
		}).on( 'error', sass.logError ))
		.pipe(autoprefixer({
			cascade: false
		}))
		.pipe(uglifycss({
			'maxLineLen' : 80,
			'uglyComments' : true
		}))
		.pipe(rename({
			suffix: '.min'
		}))
        .pipe(sourcemaps.write('.'))
		.pipe(gulp.dest('assets/dist/css'));
});

gulp.task('sassBlocks', () => {
	return gulp.src('porter/blocks/**/*.scss')
        .pipe(sourcemaps.init())
		.pipe(sass.sync({
			outputStyle: 'compressed',
			includePaths: ['./node_modules'],
		}).on( 'error', sass.logError ))
		.pipe(autoprefixer({
			cascade: false
		}))
		.pipe(uglifycss({
			'maxLineLen' : 80,
			'uglyComments' : true
		}))
		.pipe( rename(function (path) {
           var temp = path.dirname.slice(0, -4);
           path.dirname = "porter/blocks/" + temp + "css";
        }) )
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('.'))
});

gulp.task('sassBlockStyles', () => {
	return gulp.src('porter/inc/block/**/*.scss')
        .pipe(sourcemaps.init())
		.pipe(sass.sync({
			outputStyle: 'compressed',
			includePaths: ['./node_modules'],
		}).on( 'error', sass.logError ))
		.pipe(autoprefixer({
			cascade: false
		}))
		.pipe(uglifycss({
			'maxLineLen' : 80,
			'uglyComments' : true
		}))
		.pipe( rename(function (path) {
           var temp = path.dirname.slice(0, -4);
           path.dirname = "porter/inc/block/" + temp + "css";
        }) )
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('.'))
});

gulp.task('sassVariationStyles', () => {
	return gulp.src('porter/inc/variations/**/*.scss')
        .pipe(sourcemaps.init())
		.pipe(sass.sync({
			outputStyle: 'compressed',
			includePaths: ['./node_modules'],
		}).on( 'error', sass.logError ))
		.pipe(autoprefixer({
			cascade: false
		}))
		.pipe(uglifycss({
			'maxLineLen' : 80,
			'uglyComments' : true
		}))
		.pipe( rename(function (path) {
           var temp = path.dirname.slice(0, -4);
           path.dirname = "porter/inc/variations/" + temp + "css";
        }) )
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('.'))
});

gulp.task('jsMain', () => {
    return gulp.src('assets/src/js/**/*.js')
    .pipe(uglify())
    .pipe(gulp.dest('assets/dist/js'));
});

gulp.task('watch', () => {
	// css
	gulp.watch(['theme.json'], gulp.series('extract-colors', 'sass', 'sassBlocks', 'sassBlockStyles', 'sassVariationStyles'));
	gulp.watch('assets/src/scss/**/*.scss', gulp.series('sass'));
	gulp.watch('porter/blocks/**/*.scss', gulp.series('sassBlocks'));
	gulp.watch('porter/inc/block/**/*.scss', gulp.series('sassBlockStyles'));
	gulp.watch('porter/inc/variations/**/*.scss', gulp.series('sassVariationStyles'));
	// js
    gulp.watch('assets/src/js/**/*.js', gulp.series('jsMain'))
});

