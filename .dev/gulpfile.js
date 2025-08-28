/*  Common  */

const gulp = require('gulp');
const concat = require('gulp-concat');

/*  Css  */

const postcss = require('gulp-postcss')
const cleanCSS = require('gulp-clean-css');
const cssimport = require("gulp-cssimport");
const sourcemaps = require('gulp-sourcemaps')
const sass = require('gulp-dart-sass');
const tailwindcss = require('tailwindcss');

/*  Javascript  */

const babel = require("gulp-babel");
const plumber = require("gulp-plumber");
const uglify = require('gulp-uglify');

function processCSS(settings) {
    const cssFiles = [
        './node_modules/jquery-modal/jquery.modal.css',
        './css/Main.scss',
    ];

    return gulp.src(cssFiles)
    .pipe(sourcemaps.init())
    .pipe(sass({ includePaths: ['node_modules'] }))
    .pipe(cssimport())
    .pipe(postcss([
        tailwindcss(settings.configPath),
        require('postcss-sort-media-queries'),
        require('autoprefixer')
    ]))
    .pipe(cleanCSS({ level: { 1: { specialComments: 0 } } }))
    .pipe(concat("molonion.min.css"))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(settings.outputDir));
}

function processJS() {
    const files = [
        './node_modules/jquery-modal/jquery.modal.js',
        './js/modals/ProductsProcessAll.js',
        './js/modals/ProductsProcessBulk.js',
        './js/pages/Automations.js',
        './js/pages/Login.js',
        './js/pages/Logs.js',
        './js/pages/MoloniProducts.js',
        './js/OrdersBulkAction.js',
        './js/pages/Settings.js',
        './js/pages/Tools.js',
        './js/pages/WcProducts.js',
        './js/EntryPoint.js',
    ];

    return (
        gulp.src(files)
        .pipe(plumber())
        .pipe(babel({
            presets: [
                ["@babel/env", {modules: false}],
            ]
        }))
        .pipe(uglify())
        .pipe(concat("molonion.min.js"))
        .pipe(gulp.dest("../assets/js/"))
    )
}

gulp.task('css:prod:es', () => processCSS({
    outputDir: '../.platforms/molonies/css/',
    configPath: './tailwind.config.es.js'
}));
gulp.task('css:prod:on', () => processCSS({
    outputDir: '../.platforms/molonion/css/',
    configPath: './tailwind.config.on.js'
}));

gulp.task('js:prod', () => processJS());
