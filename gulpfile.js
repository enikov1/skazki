const { src, dest, series, parallel, watch } = require('gulp');
const fs = require('node:fs/promises');
const path = require('node:path');
const pug = require('gulp-pug');
const sass = require('gulp-sass')(require('sass'));
const browserSync = require('browser-sync').create();

// Resolve every task path from this file so Windows launches work from any cwd.
process.chdir(__dirname);
const DIST = path.join(__dirname, 'dist');
const PUBLIC_DEMO = path.join(__dirname, 'public', 'demo');

async function clean() {
  await fs.rm(DIST, { recursive: true, force: true });
}

function templates() {
  return src(['src/pages/**/*.pug', '!src/pages/_layout.pug'])
    .pipe(pug({ pretty: true }))
    .pipe(dest(DIST));
}

function styles() {
  return src(['src/scss/styles.scss', 'src/scss/admin.scss', 'src/scss/profile.scss'])
    .pipe(sass({ style: 'expanded' }).on('error', sass.logError))
    .pipe(dest(DIST));
}

function scripts() {
  return src('src/js/**/*.js', { base: 'src/js' }).pipe(dest(DIST));
}

function assets() {
  // Keep image, font, and other binary assets as raw bytes while copying.
  return src('src/assets/**/*', { base: 'src/assets', dot: true, encoding: false })
    .pipe(dest(path.join(DIST, 'assets')));
}

async function syncDemo() {
  await fs.rm(PUBLIC_DEMO, { recursive: true, force: true });
  await fs.cp(DIST, PUBLIC_DEMO, { recursive: true });
  // Laravel's server-rendered pages use this compiled stylesheet too.
  const PUBLIC_CSS = path.join(__dirname, 'public', 'css');
  await fs.mkdir(PUBLIC_CSS, { recursive: true });
  await fs.copyFile(path.join(DIST, 'styles.css'), path.join(PUBLIC_CSS, 'site.css'));
  await fs.copyFile(path.join(DIST, 'admin.css'), path.join(PUBLIC_CSS, 'admin.css'));
  await fs.copyFile(path.join(DIST, 'profile.css'), path.join(PUBLIC_CSS, 'profile.css'));
  const PUBLIC_JS = path.join(__dirname, 'public', 'js');
  await fs.mkdir(PUBLIC_JS, { recursive: true });
  await fs.copyFile(path.join(DIST, 'app.js'), path.join(PUBLIC_JS, 'app.js'));
  await fs.cp(path.join(DIST, 'assets'), path.join(__dirname, 'public', 'assets'), { recursive: true, force: true });
  const quillDist = path.join(__dirname, 'node_modules', 'quill', 'dist');
  const quillPublic = path.join(__dirname, 'public', 'vendor', 'quill');
  await fs.mkdir(quillPublic, { recursive: true });
  for (const file of ['quill.js', 'quill.snow.css', 'quill.js.LICENSE.txt']) {
    await fs.copyFile(path.join(quillDist, file), path.join(quillPublic, file));
  }
  async function rewrite(dir) {
    for (const entry of await fs.readdir(dir, { withFileTypes: true })) {
      const target = path.join(dir, entry.name);
      if (entry.isDirectory()) { await rewrite(target); continue; }
      if (!/\.(html|js|css)$/i.test(entry.name)) continue;
      let content = await fs.readFile(target, 'utf8');
      content = content.replaceAll('/assets/', '/demo/assets/').replaceAll('href="/styles.css', 'href="/demo/styles.css').replaceAll('src="/app.js', 'src="/demo/app.js');
      if (entry.name.endsWith('.html') && !content.includes('name="robots"')) content = content.replace('<meta name="viewport"', '<meta name="robots" content="noindex,follow">\n    <meta name="viewport"');
      await fs.writeFile(target, content);
    }
  }
  await rewrite(PUBLIC_DEMO);
}

function startServer(done) {
  browserSync.init({
    server: { baseDir: path.join(__dirname, 'public') },
    port: 3000,
    open: true,
    startPath: '/demo/',
    notify: false
  }, done);
}

function watchSources() {
  watch('src/pages/**/*.pug', series(templates, syncDemo, reload));
  watch('src/scss/**/*.scss', series(styles, syncDemo, reload));
  watch('src/js/**/*.js', series(scripts, syncDemo, reload));
  watch('src/assets/**/*', series(assets, syncDemo, reload));
}

function reload(done) {
  browserSync.reload();
  done();
}

const build = series(clean, parallel(templates, styles, scripts, assets), syncDemo);
const serve = series(build, startServer, watchSources);

exports.clean = clean;
exports.templates = templates;
exports.styles = styles;
exports.scripts = scripts;
exports.assets = assets;
exports.syncDemo = syncDemo;
exports.build = build;
exports.serve = serve;
exports.default = build;
