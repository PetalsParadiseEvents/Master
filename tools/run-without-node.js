// Runs tools/build-rental-pages.js without Node, using macOS's built-in JavaScript (JXA).
// Usage (from repo root): osascript -l JavaScript tools/run-without-node.js
ObjC.import('Foundation');
const cwd = $.NSFileManager.defaultManager.currentDirectoryPath.js;
const read = p => $.NSString.stringWithContentsOfFileEncodingError(p, $.NSUTF8StringEncoding, null).js;
const fs = {
  readFileSync: p => read(p),
  writeFileSync: (p, s) => $(s).writeToFileAtomicallyEncodingError(p, true, $.NSUTF8StringEncoding, null),
  mkdirSync: p => $.NSFileManager.defaultManager.createDirectoryAtPathWithIntermediateDirectoriesAttributesError(p, true, $(), null),
};
const path = {
  join: (...parts) => parts.join('/').replace(/\/+/g, '/').replace(/\/[^/]+\/\.\.(?=\/|$)/g, ''),
};
const logs = [];
const consoleShim = { log: (...a) => logs.push(a.join(' ')) };
const src = read(cwd + '/tools/build-rental-pages.js').replace(/^#!.*\n/, '');
new Function('require', '__dirname', 'console', src)(
  m => ({ fs, path })[m],
  cwd + '/tools',
  consoleShim
);
logs.join('\n');
