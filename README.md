# St. John the Baptist Likuyani Boys High School — website

Live site: <https://stjohnlikuyaniboys.sc.ke>
Admin panel: <https://stjohnlikuyaniboys.sc.ke/admin/>

A single-page school website with a small admin panel so staff can update
photos, announcements, staff details and page wording without touching code.

No database and no framework — content lives in JSON files, rendered by PHP 8.

## Layout

```
public_html/            document root
  index.php             the homepage, rendered from the JSON content files
  assets/style.css      site styles
  assets/site.js        scroll animations, mobile menu
  admin/                the admin panel (one file per screen)
  images/               original site photos
  images/uploads/       photos added through the admin panel
sitelib/                shared PHP, kept OUTSIDE the document root
  bootstrap.php         data store, escaping helpers, image handling
  auth.php              sessions, passwords, CSRF, login throttling
  admin_layout.php      shared admin chrome
sitedata/               content, kept OUTSIDE the document root
  content.json          all page wording
  staff.json            leadership, department heads, board, key contacts
  gallery.json          photo galleries
  news.json             announcements
  users.json            admin accounts — NOT in version control
tools/create-admin.php  create or reset an admin account
```

`sitelib/` and `sitedata/` sit above the document root on purpose: they are not
reachable over the web, so content and code cannot be downloaded directly.

## Deploying

1. Put `public_html/`, `sitelib/`, `sitedata/` and `tools/` in the account home
   directory, keeping the layout above.
2. Make sure `public_html/images/uploads/` is writable by the web server, and
   that the `.htaccess` inside it is present — it stops uploaded files from
   being executed.
3. Create the first admin account:

   ```
   php tools/create-admin.php admin "School Administrator"
   ```

4. Sign in at `/admin/` and change the password when prompted.

Requires PHP 8.1+ with the GD extension.

## How content works

Every page region reads from `sitedata/*.json`. The admin panel writes those
files atomically and keeps the last 20 revisions of each in
`sitedata/_revisions/`, so a bad edit can be rolled back by restoring a file.

`users.json`, `_revisions/` and `_throttle.json` are excluded from git — they
hold password hashes and runtime state.

## Notes on security

- Passwords are hashed with `password_hash()`; sign-in locks for 15 minutes
  after 8 failed attempts.
- Every form is CSRF-protected; sessions are HTTP-only and expire after 2 hours
  idle.
- Uploads are validated by decoding the image, then **re-encoded through GD** so
  that only pixels are written to disk. A file can be a valid image and carry
  script source in the same bytes; redrawing it discards anything executable.
  Images wider than 2000px are downscaled. Animated GIFs become still PNGs.
- All output is escaped with `htmlspecialchars()`.

The repository is rooted at a cPanel home directory that also contains SSL
private keys and mail. `.gitignore` therefore ignores everything by default and
allows back only the website — **do not loosen the `/*` rule.**
