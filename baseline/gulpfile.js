import gulp from 'gulp';
import autoprefixer from 'gulp-autoprefixer';
import sass from 'gulp-dart-sass';
import rename from 'gulp-rename';
import uglifycss from 'gulp-uglifycss';
import terser from 'gulp-terser';
import { exec } from 'child_process';
import path from 'path';

function extractColors(done) {
    exec('node ./assets/build-scripts/extract-colors.js', (err, stdout, stderr) => {
        if (err) {
            console.error(`exec error: ${err}`);
            return;
        }
        console.log(stdout);
        console.error(stderr);
        done();
    });
}

function compileSass() {
  return gulp.src('assets/src/scss/**/*.scss', { sourcemaps: true })
    .pipe(sass.sync({ outputStyle: 'compressed' }).on('error', sass.logError))
    .pipe(autoprefixer({ cascade: false }))
    .pipe(uglifycss({ 'maxLineLen': 80, 'uglyComments': true }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest('assets/dist/css', { sourcemaps: '.' }));
}

function compileBlocks() {
  return gulp.src('porter/blocks/**/scss/*.scss', { sourcemaps: true })
    .pipe(sass.sync({ outputStyle: 'compressed' }).on('error', sass.logError))
    .pipe(autoprefixer({ cascade: false }))
    .pipe(rename(function (file) {
        file.dirname = file.dirname.replace('scss', 'css');
        file.extname = '.css';
    }))
    .pipe(gulp.dest('porter/blocks/', { sourcemaps: '.' }));
}


function compileBlockStyles() {
  return gulp.src('porter/inc/block/styles/scss/*.scss', { sourcemaps: true })
    .pipe(sass.sync({ outputStyle: 'compressed' }).on('error', sass.logError))
    .pipe(autoprefixer({ cascade: false }))
    .pipe(uglifycss({ 'maxLineLen': 80, 'uglyComments': true }))
    .pipe(rename(function (path) {
        path.dirname = "css";
        path.extname = '.css';
    }))
    .pipe(gulp.dest('porter/inc/block/styles/', { sourcemaps: '.' }));
}



function compileVariationStyles() {
  return gulp.src('porter/inc/block/variations/**/scss/*.scss', { sourcemaps: true })
    .pipe(sass.sync({ outputStyle: 'compressed' }).on('error', sass.logError))
    .pipe(autoprefixer({ cascade: false }))
    .pipe(rename(function (file) {
        file.dirname = file.dirname.replace('scss', 'css');
        file.extname = '.css';
    }))
    .pipe(gulp.dest('porter/inc/block/variations/', { sourcemaps: '.' }));
}

function compileJS() {
  return gulp.src('assets/src/js/**/*.js', { sourcemaps: true })
    .pipe(terser())
    .pipe(gulp.dest('assets/dist/js', { sourcemaps: '.' }));
}

function watchTasks() {
  gulp.watch('theme.json', gulp.series(extractColors, compileSass, compileBlocks, compileBlockStyles, compileVariationStyles));
  gulp.watch('assets/src/scss/**/*.scss', compileSass);
  gulp.watch('porter/blocks/**/scss/*.scss', compileBlocks);
  gulp.watch('porter/inc/block/styles/scss/**/*.scss', compileBlockStyles);
  gulp.watch('porter/inc/block/variations/**/scss/*.scss', compileVariationStyles);
  gulp.watch('assets/src/js/**/*.js', compileJS);
}

export const build = gulp.series(extractColors, gulp.parallel(compileSass, compileBlocks, compileBlockStyles, compileVariationStyles, compileJS));
export const watch = gulp.series(build, watchTasks);

export default build;
