import gulp from 'gulp';
import autoprefixer from 'gulp-autoprefixer';
import sass from 'gulp-dart-sass';
import rename from 'gulp-rename';
import uglifycss from 'gulp-uglifycss';
import terser from 'gulp-terser';
import { exec } from 'child_process';
import path from 'path';
import fs from 'fs';
import _ from 'lodash';

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


function generateScssFromJson(done) {
  fs.readFile('porter/config/blocks.json', (err, data) => {
    if (err) throw err;
    const json = JSON.parse(data);

    for (let key in json.blocks.styles) {
        for (let styleName in json.blocks.styles[key]) {
            let keyParts = key.split('/'); // Split the key like "core/image"
            let blockType = _.kebabCase(keyParts[0]); // "core" or "acf"
            let blockName = _.kebabCase(keyParts[1]); // "image", "columns", etc.

            let fileName = `${blockType}_${blockName}--${_.kebabCase(styleName)}.scss`;
            let filePath = `porter/inc/block/styles/scss/${fileName}`;

            if (!fs.existsSync(filePath)) { // Check if file does not exist
                let scss = '';
                scss += `@import '../../../../../assets/src/scss/variables';\n\n`;

                if (blockType === 'core') {
                    scss += `.wp-block-${blockName}.is-style-${_.kebabCase(styleName)} {\n`;
                } else {
                    scss += `.wp-block-${blockType}-${blockName}.is-style-${_.kebabCase(styleName)} {\n`;
                }
                scss += `    // Add your CSS rules here\n`;
                scss += `}\n`;

                fs.writeFile(filePath, scss, function(err) {
                    if (err) throw err;
                });
            }
        }
    }
    done();
  });
}



function watchTasks() {
  gulp.watch('theme.json', gulp.series(extractColors, compileSass, compileBlocks, compileBlockStyles, compileVariationStyles));
  gulp.watch('assets/src/scss/**/*.scss', compileSass);
  gulp.watch('porter/blocks/**/scss/*.scss', compileBlocks);
  gulp.watch('porter/inc/block/styles/scss/**/*.scss', compileBlockStyles);
  gulp.watch('porter/inc/block/variations/**/scss/*.scss', compileVariationStyles);
  gulp.watch('assets/src/js/**/*.js', compileJS);
  gulp.watch('porter/config/blocks.json', generateScssFromJson); // Add this line
}

export const build = gulp.series(extractColors, gulp.parallel(compileSass, compileBlocks, compileBlockStyles, compileVariationStyles, compileJS));
export const watch = gulp.series(build, watchTasks);

export default build;
