import gulp from 'gulp';
import autoprefixer from 'gulp-autoprefixer';
import gulpSass from 'gulp-sass';
import dartSass from 'sass';
import through2 from 'through2';
import uglifycss from 'gulp-uglifycss';
import terser from 'gulp-terser';
import { exec } from 'child_process';
import path from 'path';
import fs from 'fs';
import _ from 'lodash';

// Initialize Sass with the modern Dart Sass API
const sass = gulpSass(dartSass);

// Helper to add a suffix to filenames without using gulp-rename
function renameSuffix(suffix) {
  return through2.obj((file, _, cb) => {
    file.basename = file.stem + suffix + file.extname;
    cb(null, file);
  });
}

// Helper to mutate dirname and extname in-stream
function renameTransform(transformFn) {
  return through2.obj((file, _, cb) => {
    transformFn(file);
    cb(null, file);
  });
}

// Extract colors via external script
export function extractColors(done) {
  exec('node ./assets/build-scripts/extract-colors.js', (err, stdout, stderr) => {
    if (err) return done(err);
    console.log(stdout);
    console.error(stderr);
    done();
  });
}

// Compile SCSS → compressed CSS, autoprefix, uglify, and suffix
export function compileSass(done) {
  const srcDir = 'assets/src/scss';
  if (!fs.existsSync(srcDir)) {
    console.log(`Source directory "${srcDir}" not found. Skipping.`);
    return done();
  }

  return gulp.src(`${srcDir}/**/*.scss`, { sourcemaps: true })
    .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
    .pipe(autoprefixer({ cascade: false }))
    .pipe(uglifycss({ maxLineLen: 80, uglyComments: true }))
    .pipe(renameSuffix('.min'))
    .pipe(gulp.dest('assets/dist/css', { sourcemaps: '.' }))
    .on('end', done);
}

// Compile block-specific SCSS → CSS and move into css folder
export function compileBlocks(done) {
  const srcDir = 'porter/blocks';
  if (!fs.existsSync(srcDir)) return done();

  return gulp.src(`${srcDir}/**/scss/*.scss`, { sourcemaps: true })
    .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
    .pipe(autoprefixer({ cascade: false }))
    .pipe(renameTransform(file => {
      file.dirname = file.dirname.replace(/scss/, 'css');
      file.extname = '.css';
    }))
    .pipe(gulp.dest('porter/blocks/', { sourcemaps: '.' }))
    .on('end', done);
}

// Compile block styles SCSS → CSS
export function compileBlockStyles(done) {
  const srcDir = 'porter/inc/block/styles/scss';
  if (!fs.existsSync(srcDir)) return done();

  return gulp.src(`${srcDir}/*.scss`, { sourcemaps: true })
    .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
    .pipe(autoprefixer({ cascade: false }))
    .pipe(uglifycss({ maxLineLen: 80, uglyComments: true }))
    .pipe(renameTransform(file => {
      file.dirname = 'css';
      file.extname = '.css';
    }))
    .pipe(gulp.dest('porter/inc/block/styles/', { sourcemaps: '.' }))
    .on('end', done);
}

// Compile core block styles SCSS → CSS
export function compileCoreBlockStyles(done) {
  const srcDir = 'porter/inc/block/core/styles/scss';
  if (!fs.existsSync(srcDir)) return done();

  return gulp.src(`${srcDir}/*.scss`, { sourcemaps: true })
    .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
    .pipe(autoprefixer({ cascade: false }))
    .pipe(uglifycss({ maxLineLen: 80, uglyComments: true }))
    .pipe(renameTransform(file => {
      file.dirname = 'css';
      file.extname = '.css';
    }))
    .pipe(gulp.dest('porter/inc/block/core/styles/', { sourcemaps: '.' }))
    .on('end', done);
}

// Compile variation styles SCSS → CSS
export function compileVariationStyles(done) {
  const srcDir = 'porter/inc/block/variations';
  if (!fs.existsSync(srcDir)) return done();

  return gulp.src(`${srcDir}/**/scss/*.scss`, { sourcemaps: true })
    .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
    .pipe(autoprefixer({ cascade: false }))
    .pipe(renameTransform(file => {
      file.dirname = file.dirname.replace(/scss/, 'css');
      file.extname = '.css';
    }))
    .pipe(gulp.dest(srcDir, { sourcemaps: '.' }))
    .on('end', done);
}

// Minify JS
export function compileJS(done) {
  const srcDir = 'assets/src/js';
  if (!fs.existsSync(srcDir)) return done();

  return gulp.src(`${srcDir}/**/*.js`, { sourcemaps: true })
    .pipe(terser())
    .pipe(gulp.dest('assets/dist/js', { sourcemaps: '.' }))
    .on('end', done);
}

// Generate SCSS from JSON
export function generateScssFromJson(done) {
  const json = JSON.parse(fs.readFileSync('porter/config/blocks.json'));
  for (const key of Object.keys(json.blocks.styles)) {
    for (const styleName of Object.keys(json.blocks.styles[key])) {
      const [type, name] = key.split('/');
      const blockType = _.kebabCase(type);
      const blockName = _.kebabCase(name);
      const filename = `${blockType}_${blockName}--${_.kebabCase(styleName)}.scss`;
      const filepath = `porter/inc/block/styles/scss/${filename}`;
      if (!fs.existsSync(filepath)) {
        let content = `@import '../../../../../assets/src/scss/variables';\n\n`;
        content += blockType === 'core'
          ? `.wp-block-${blockName}.is-style-${_.kebabCase(styleName)} {\n`  
          : `.wp-block-${blockType}-${blockName}.is-style-${_.kebabCase(styleName)} {\n`;
        content += `  // Add your CSS rules here\n}\n`;
        fs.writeFileSync(filepath, content);
      }
    }
  }
  done();
}

// Create post types directories & icons
export function createPostTypes(done) {
  const posttypes = JSON.parse(fs.readFileSync('porter/config/posttypes.json')).posttypes;
  for (const key of Object.keys(posttypes)) {
    const dir = `porter/inc/posttypes/${key}`;
    const svg = path.join(dir, 'icon.svg');
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true });
      const svgContent = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">` +
        `<!-- icon -->` +
        `</svg>`;
      fs.writeFileSync(svg, svgContent);
    }
  }
  done();
}

// Watch files
export function watchTasks() {
  gulp.watch('theme.json', gulp.series(extractColors, compileSass, compileBlocks, compileBlockStyles, compileCoreBlockStyles, compileVariationStyles));
  gulp.watch('assets/src/scss/**/*.scss', compileSass);
  gulp.watch('porter/blocks/**/scss/*.scss', compileBlocks);
  gulp.watch('porter/inc/block/styles/scss/**/*.scss', compileBlockStyles);
  gulp.watch('porter/inc/block/core/styles/scss/**/*.scss', compileCoreBlockStyles);
  gulp.watch('porter/inc/block/variations/**/scss/*.scss', compileVariationStyles);
  gulp.watch('assets/src/js/**/*.js', compileJS);
  gulp.watch('porter/config/blocks.json', generateScssFromJson);
  gulp.watch('porter/config/posttypes.json', createPostTypes);
}

// Define build & watch
export const build = gulp.series(
  extractColors,
  gulp.parallel(
    compileSass,
    compileBlocks,
    compileBlockStyles,
    compileCoreBlockStyles,
    compileVariationStyles,
    compileJS
  )
);
export const watch = gulp.series(build, watchTasks);
export default build;
