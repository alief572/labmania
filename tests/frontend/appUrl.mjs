import assert from 'node:assert/strict';
import { appUrl, stripBase } from '../../resources/js/composables/appUrl.ts';

// Tanpa base (Node tidak punya document): URL tidak berubah.
assert.equal(appUrl('/login'), '/login');
assert.equal(stripBase('/login'), '/login');

const base = '/labmania';
assert.equal(appUrl('/login', base), '/labmania/login');
assert.equal(appUrl('/', base), '/labmania/');
assert.equal(appUrl('/x?tab=data&a=1#h', base), '/labmania/x?tab=data&a=1#h');
// Idempoten: URL yang sudah berprefix (mis. page.url, paginator) tidak digandakan.
assert.equal(appUrl('/labmania/login', base), '/labmania/login');
assert.equal(appUrl('/labmania', base), '/labmania');
assert.equal(appUrl('/labmania?a=1', base), '/labmania?a=1');
// Path yang hanya mirip prefix tetap diberi prefix.
assert.equal(appUrl('/labmania-lain', base), '/labmania/labmania-lain');
// Bukan path root-absolut: dibiarkan.
for (const url of ['https://x.test/a', '//cdn.test/a', '#top', 'mailto:a@b.c', 'relatif/x', 'about:blank']) assert.equal(appUrl(url, base), url);

assert.equal(stripBase('/labmania/master/user?x=1', base), '/master/user?x=1');
assert.equal(stripBase('/labmania', base), '/');
assert.equal(stripBase('/labmania/', base), '/');
assert.equal(stripBase('/labmania?a=1', base), '/?a=1');
assert.equal(stripBase('/labmania-lain/x', base), '/labmania-lain/x');
assert.equal(stripBase('/master/user', base), '/master/user');

console.log('appUrl ok');
