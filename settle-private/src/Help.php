<?php
declare(strict_types=1);
namespace Settle;

/**
 * Admin help content (roadmap #14) — the single source of truth.
 *
 * Rendered two ways from this one data set:
 *   - /admin/help            full single-page doc (templates/admin/help/index.php)
 *   - /admin/help/{slug}     one section          (templates/admin/help/section.php)
 *
 * The per-role capability matrix below is transcribed directly from the
 * route role middleware in public_html/Settle/index.php AND the in-code
 * ownership / hasRole() checks in the controllers, as of HEAD bfeef0c
 * (v3.4). It reflects what the code actually ENFORCES — not the sidebar,
 * which shows most links to every signed-in user regardless of role. If a
 * route gate or an in-code check changes, update the matching `access`
 * entry here so the doc can't drift. See PROJECT_HANDOFF.md §3.4.
 *
 * Audience: non-technical church staff. Bodies are task-oriented prose,
 * authored as nowdoc HTML (no interpolation) and rendered unescaped.
 */
final class Help
{
    /** Access levels used by the matrix. */
    public const FULL    = 'full';
    public const PARTIAL = 'partial';
    public const NONE    = 'none';

    /** Human labels for the three roles, matching the rest of the admin. */
    public static function roleLabels(): array
    {
        return ['admin' => 'Administrator', 'editor' => 'Editor', 'author' => 'Author'];
    }

    /** Symbol + caption for an access level (used in the matrix + section badges). */
    public static function levelMeta(string $level): array
    {
        return match ($level) {
            self::FULL    => ['symbol' => "\u{2713}", 'caption' => 'Full access'],
            self::PARTIAL => ['symbol' => "\u{25D1}", 'caption' => 'Limited access'],
            default       => ['symbol' => "\u{2014}", 'caption' => 'No access'],
        };
    }

    /**
     * Ordered list of help sections.
     *
     * Each section:
     *   slug    string   stable id; the URL anchor (#section-{slug}) and /admin/help/{slug}
     *   title   string   heading shown to the user
     *   matrix  bool     true => include a row in the capability matrix
     *   access  array    role => [level, note]  (only meaningful when matrix=true)
     *   body    string   HTML (nowdoc; rendered unescaped)
     *
     * @return array<int,array<string,mixed>>
     */
    public static function sections(): array
    {
        return [

            // ---- Orientation (universal) -------------------------------
            [
                'slug' => 'getting-started',
                'title' => 'Getting started',
                'matrix' => false,
                'access' => [],
                'body' => <<<'HTML'
<p>Welcome to the Settle Memorial UMC website admin panel. This is where
designated staff add and update what visitors see on the public website —
pages, photos, blog posts, the staff directory, and more. You don't need any
technical knowledge to use it.</p>

<h3>Finding your way around</h3>
<p>The dark <strong>menu on the left</strong> is your map. Each link opens a
different part of the site you can manage. Whichever screen you're on, the
left menu stays put so you can jump somewhere else at any time.</p>

<h3>A note about what you can and can't open</h3>
<p>The left menu shows the same links to everyone who signs in, but not every
staff member is allowed to use every section. If you open something your
account isn't set up for, you'll see a short <strong>"Forbidden"</strong>
message instead of the page. That's normal — it just means that area is
handled by someone with a different role. The <a href="#section-roles">Roles
and permissions</a> section explains exactly who can do what.</p>

<h3>Saving your work</h3>
<p>Changes don't go live until you press the <strong>Save</strong> (or
<strong>Publish</strong>) button on a screen. If you leave a screen without
saving, your changes are discarded. After a successful save you'll see a green
confirmation banner at the top of the screen.</p>
HTML,
            ],

            [
                'slug' => 'your-account',
                'title' => 'Your account &amp; signing in',
                'matrix' => false,
                'access' => [],
                'body' => <<<'HTML'
<h3>Signing in</h3>
<p>Go to <strong>/admin/login</strong> and enter the username (or email) and
password you were given. Tick <strong>"Remember me"</strong> on your own
private computer to stay signed in longer. Don't use "Remember me" on a shared
or public computer.</p>

<h3>Signing out</h3>
<p>Use the <strong>Sign out</strong> link at the bottom of the left menu when
you're finished, especially on a computer other people use.</p>

<h3>Forgot your password?</h3>
<p>On the login screen, click <strong>"Forgot password"</strong> and enter your
email address. If your account exists, you'll be emailed a reset link.</p>
<ul>
  <li>The link is <strong>good for 15 minutes</strong> and can be used
      <strong>once</strong> — request a fresh one if it expires.</li>
  <li>For your privacy, the screen shows the same message whether or not the
      email matched an account, so it never reveals who has a login.</li>
  <li>Open the link, choose a new password, and you'll be ready to sign in.</li>
</ul>
<p>An Administrator can also create your account or reset access for you at any
time.</p>
HTML,
            ],

            [
                'slug' => 'roles',
                'title' => 'Roles &amp; permissions',
                'matrix' => false,
                'access' => [],
                'body' => <<<'HTML'
<p>Every login has one of three roles. They build on each other: an
<strong>Administrator</strong> can do everything an Editor can, and an
<strong>Editor</strong> can do everything an Author can.</p>
<ul>
  <li><strong>Author</strong> — writes their <em>own</em> blog posts and uploads
      photos. Can read the prayer and contact inboxes (private prayer details
      are hidden from Authors).</li>
  <li><strong>Editor</strong> — manages <em>all</em> content: pages, the menu,
      every blog post, photos, the slideshow, the staff directory, the
      calendar, and prayer-request status.</li>
  <li><strong>Administrator</strong> — everything above, plus managing staff
      logins, site Settings, and the activity log, and deleting prayer or
      contact messages.</li>
</ul>
<p>The table below is the exact list of who can use each section. It's based on
what the website actually enforces, so it's the reliable answer if you're ever
unsure whether something is yours to do.</p>
<p class="help-matrix-anchor"><!-- the capability matrix renders here in the full doc --></p>
HTML,
            ],

            // ---- Feature sections (in left-menu order) -----------------
            [
                'slug' => 'dashboard',
                'title' => 'Dashboard',
                'matrix' => true,
                'access' => [
                    'author' => [self::PARTIAL, 'Your own recent posts and the photo count'],
                    'editor' => [self::PARTIAL, 'The above plus prayer, contact, calendar, page and staff counts'],
                    'admin'  => [self::PARTIAL, 'The above plus recent activity and system health'],
                ],
                'body' => <<<'HTML'
<p>The Dashboard is the "welcome back" screen you land on after signing in
(the <strong>Dashboard</strong> link at the top of the left menu). It's a
quick snapshot — it shows more detail the more access your role has.</p>
<ul>
  <li><strong>Everyone</strong> sees their own recent blog posts and the total
      number of photos in the library.</li>
  <li><strong>Editors and Administrators</strong> also see counts of new prayer
      requests, unread contact messages, upcoming events, pages, and staff.</li>
  <li><strong>Administrators</strong> additionally see recent activity and a
      system-health note.</li>
</ul>
<p>Nothing on the Dashboard is editable — use the left-menu links (or the
shortcuts on the cards) to go where you want to make changes.</p>
HTML,
            ],

            [
                'slug' => 'pages',
                'title' => 'Pages',
                'matrix' => true,
                'access' => [
                    'author' => [self::PARTIAL, 'Can view the list of pages, but not add or edit them'],
                    'editor' => [self::FULL, 'Add, edit, and show/hide pages'],
                    'admin'  => [self::FULL, 'Add, edit, and show/hide pages'],
                ],
                'body' => <<<'HTML'
<p>Pages are the standard information pages of the website — Home content,
About, Give, and so on. Open <strong>Pages</strong> in the left menu to see
them all.</p>
<p><em>Authors</em> can look at the list but can't change pages.
<em>Editors and Administrators</em> can add and edit them.</p>

<h3>Edit a page</h3>
<ol>
  <li>Click a page's name (or <strong>Edit</strong>) in the list.</li>
  <li><strong>Title</strong> is the heading visitors see.</li>
  <li><strong>Web address</strong> is the short tail of the page's link
      (for example <em>about</em>, <em>give</em>). Change this rarely — old
      links stop working if you do.</li>
  <li><strong>Page content</strong> is the main body. Use the editor toolbar to
      format text, add links, and insert photos.</li>
  <li><strong>Search engine summary</strong> is an optional one-line
      description used by Google and when the page is shared.</li>
  <li>Press <strong>Save</strong>.</li>
</ol>

<h3>Show or hide a page</h3>
<p>Use the show/hide control on the list to take a page off the public site
without deleting it — handy for drafts or seasonal pages.</p>
HTML,
            ],

            [
                'slug' => 'menu',
                'title' => 'Menu (site navigation)',
                'matrix' => true,
                'access' => [
                    'author' => [self::NONE, ''],
                    'editor' => [self::FULL, 'Add, edit, reorder, show/hide, and remove menu links'],
                    'admin'  => [self::FULL, 'Add, edit, reorder, show/hide, and remove menu links'],
                ],
                'body' => <<<'HTML'
<p>The Menu controls the navigation links visitors see across the top of the
public website. <strong>Editors and Administrators</strong> manage it from
<strong>Menu</strong> in the left menu.</p>

<h3>Add or edit a link</h3>
<ol>
  <li><strong>Label</strong> is the wording shown to visitors.</li>
  <li><strong>Where does this link go?</strong> — pick an existing page, or
      type a full web address (<em>https://...</em>) or an internal path
      (<em>/page/sundays</em>).</li>
  <li><strong>Parent</strong> lets you tuck a link under another one as a
      drop-down item; leave it blank for a top-level link.</li>
  <li><strong>Link target</strong> controls whether the link opens in the same
      tab or a new one.</li>
</ol>

<h3>Reorder &amp; hide</h3>
<p><strong>Drag</strong> links up or down to change their order. Use the
show/hide control to keep a link in the list but off the public menu, and
remove a link when it's no longer needed.</p>
HTML,
            ],

            [
                'slug' => 'blog-posts',
                'title' => 'Blog Posts',
                'matrix' => true,
                'access' => [
                    'author' => [self::PARTIAL, 'Write, edit, publish, and delete your OWN posts only'],
                    'editor' => [self::FULL, 'Manage any post by any author'],
                    'admin'  => [self::FULL, 'Manage any post by any author'],
                ],
                'body' => <<<'HTML'
<p>The blog lets designated staff publish news and updates. Open
<strong>Blog Posts</strong> in the left menu.</p>
<p><strong>Important:</strong> <em>Authors</em> see and manage only the posts
they wrote — they can't edit or delete someone else's. <em>Editors and
Administrators</em> can manage every post.</p>

<h3>Write a post</h3>
<ol>
  <li>Click <strong>New post</strong>.</li>
  <li><strong>Title</strong> — the headline. The <strong>Web address</strong>
      fills in from the title automatically; you rarely need to touch it.</li>
  <li><strong>Summary</strong> — a short blurb shown in the blog list.</li>
  <li><strong>Featured image</strong> — an optional lead photo (use
      <strong>Remove</strong> to clear it).</li>
  <li><strong>Post content</strong> — the body, with the same formatting
      toolbar as pages.</li>
  <li>Tick any <strong>categories</strong> that fit (Editors maintain the list
      you choose from).</li>
</ol>

<h3>Publishing</h3>
<p>Set <strong>Status</strong> to <em>Published</em> to make a post live, or
<em>Draft</em> to keep working on it. You can set a future <strong>Publish
date &amp; time</strong> and the post will appear automatically at that moment
— not before. <em>Archived</em> takes an old post off the public list.</p>
HTML,
            ],

            [
                'slug' => 'categories',
                'title' => 'Categories',
                'matrix' => true,
                'access' => [
                    'author' => [self::NONE, 'Authors assign existing categories on a post, but can\'t edit the list'],
                    'editor' => [self::FULL, 'Add, edit, and remove categories'],
                    'admin'  => [self::FULL, 'Add, edit, and remove categories'],
                ],
                'body' => <<<'HTML'
<p>Categories are the labels used to group blog posts (for example
<em>News</em> or <em>Missions</em>). <strong>Editors and Administrators</strong>
curate this list from <strong>Categories</strong> in the left menu — add,
rename, or remove a category.</p>
<p>Authors don't manage the list, but they can tick any of these categories on
their own posts while writing.</p>
HTML,
            ],

            [
                'slug' => 'photos',
                'title' => 'Photos (Media Library)',
                'matrix' => true,
                'access' => [
                    'author' => [self::PARTIAL, 'Upload photos; edit or delete only the photos YOU uploaded'],
                    'editor' => [self::FULL, 'Upload, edit, and delete any photo'],
                    'admin'  => [self::FULL, 'Upload, edit, and delete any photo'],
                ],
                'body' => <<<'HTML'
<p>The Media Library is where all photos and files live. Open
<strong>Photos</strong> in the left menu.</p>
<p><em>Anyone signed in</em> can upload. <em>Authors</em> can edit or delete
only the items they uploaded themselves; <em>Editors and Administrators</em>
can manage everything.</p>

<h3>Upload photos</h3>
<p>Click <strong>Upload</strong>, then choose files or drag them onto the drop
area. You can upload several at once. New photos appear in the library right
away, ready to use in pages and posts.</p>

<h3>Tidy up a photo</h3>
<p>Click a photo, then <strong>Edit File</strong>:</p>
<ul>
  <li><strong>Alt text</strong> — a short description of the image for screen
      readers and search engines (for example, "Children singing in the
      sanctuary").</li>
  <li><strong>Caption</strong> — optional text shown with the image.</li>
  <li>Press <strong>Save Changes</strong>.</li>
</ul>
<p>Deleting a photo is permanent and can't be undone.</p>
HTML,
            ],

            [
                'slug' => 'slideshow',
                'title' => 'Homepage Slideshow',
                'matrix' => true,
                'access' => [
                    'author' => [self::NONE, ''],
                    'editor' => [self::FULL, 'Add, edit, reorder, show/hide, and remove slides'],
                    'admin'  => [self::FULL, 'Add, edit, reorder, show/hide, and remove slides'],
                ],
                'body' => <<<'HTML'
<p>The rotating photos at the top of the home page are managed here.
<strong>Editors and Administrators</strong> open <strong>Homepage
Slideshow</strong> in the left menu.</p>

<h3>Add a slide</h3>
<ol>
  <li>Click <strong>New slide</strong> and choose an <strong>Image</strong> from
      the library (or upload one).</li>
  <li><strong>Caption</strong> — optional overlay wording (e.g. "Join us this
      Sunday").</li>
  <li><strong>Link URL</strong> — optional; where the slide goes when clicked
      (a full web address or an internal path such as <em>/page/sundays</em>).</li>
</ol>

<h3>Order &amp; visibility</h3>
<p><strong>Drag</strong> slides to set the order they rotate in. Use the
show/hide control to pause a slide without deleting it, and remove a slide when
you're done with it. Changes show on the home page immediately.</p>
HTML,
            ],

            [
                'slug' => 'staff',
                'title' => 'Staff Directory',
                'matrix' => true,
                'access' => [
                    'author' => [self::NONE, ''],
                    'editor' => [self::FULL, 'Add, edit, reorder, show/hide, and remove staff entries'],
                    'admin'  => [self::FULL, 'Add, edit, reorder, show/hide, and remove staff entries'],
                ],
                'body' => <<<'HTML'
<p>The public Staff/Leadership page is built from these entries.
<strong>Editors and Administrators</strong> manage them from <strong>Staff
Directory</strong> in the left menu.</p>

<h3>Add or edit a person</h3>
<ol>
  <li>Click <strong>New</strong> (or a name to edit).</li>
  <li><strong>Photo</strong> — choose a portrait from the library; it's fine to
      leave blank if there isn't one.</li>
  <li><strong>Full name</strong> and <strong>Title</strong> (their role).</li>
  <li><strong>Email</strong> and <strong>Phone</strong> — both optional. The
      phone field accepts a format like (270) 684-4226.</li>
  <li><strong>Bio</strong> — an optional short description.</li>
  <li>Press <strong>Save</strong>.</li>
</ol>

<h3>Order &amp; visibility</h3>
<p><strong>Drag</strong> entries to set the order they appear on the page. Use
show/hide to temporarily take someone off the public page, and remove an entry
when it no longer applies.</p>
HTML,
            ],

            [
                'slug' => 'calendar',
                'title' => 'Calendar',
                'matrix' => true,
                'access' => [
                    'author' => [self::NONE, ''],
                    'editor' => [self::FULL, 'Add a website-only image or note to a synced event'],
                    'admin'  => [self::FULL, 'Add a website-only image or note to a synced event'],
                ],
                'body' => <<<'HTML'
<p>The website's events come straight from the church's <strong>Google
Calendar</strong> — that's the one place to add, change, or remove events. The
site syncs automatically, so you generally don't manage events here at all.</p>

<h3>Highlighting and hiding events (done in Google Calendar)</h3>
<p>Inside a Google Calendar event's description you can add a simple tag:</p>
<ul>
  <li><strong>[featured]</strong> — promotes the event into the home-page
      highlights.</li>
  <li><strong>[hide]</strong> — keeps the event off the website. If both tags
      are present, <strong>[hide] wins</strong>.</li>
</ul>

<h3>What this admin screen is for</h3>
<p>The <strong>Calendar</strong> screen (Editors and Administrators) only lets
you attach a <strong>website-only image</strong> or a short <strong>public
note</strong> to an event that's already synced. It never changes the event in
Google Calendar itself. Press <strong>Save</strong> to apply, or clear the
override to remove your additions.</p>
HTML,
            ],

            [
                'slug' => 'prayer',
                'title' => 'Prayer Requests',
                'matrix' => true,
                'access' => [
                    'author' => [self::PARTIAL, 'View the inbox; private request details are hidden; can\'t change status or delete'],
                    'editor' => [self::PARTIAL, 'View all details and update status; can\'t delete'],
                    'admin'  => [self::FULL, 'View, update status, and delete'],
                ],
                'body' => <<<'HTML'
<p>Prayer requests submitted through the public form arrive here. Open
<strong>Prayer Requests</strong> in the left menu — a red number shows how many
are new.</p>

<h3>What each role can see and do</h3>
<ul>
  <li><strong>Authors</strong> can read the inbox, but the details of requests
      marked <em>private</em> are hidden from them, and they can't change a
      request's status or delete it.</li>
  <li><strong>Editors</strong> can see full details (including private ones) and
      move a request through its statuses (for example, mark it as being prayed
      for, or answered).</li>
  <li><strong>Administrators</strong> can additionally <strong>delete</strong> a
      request. Deleting is permanent.</li>
</ul>
<p>Requesters can opt in to the prayer chain; when a request is private, that
option is turned off automatically.</p>
HTML,
            ],

            [
                'slug' => 'contact',
                'title' => 'Contact Messages',
                'matrix' => true,
                'access' => [
                    'author' => [self::PARTIAL, 'Read messages and mark read/unread; can\'t delete'],
                    'editor' => [self::PARTIAL, 'Read messages and mark read/unread; can\'t delete'],
                    'admin'  => [self::FULL, 'Read, mark read/unread, and delete'],
                ],
                'body' => <<<'HTML'
<p>Messages from the public contact form land here. Open <strong>Contact
Messages</strong> in the left menu — a red number shows how many are unread.</p>
<ul>
  <li><strong>Authors and Editors</strong> can open a message, read it, and mark
      it read or unread.</li>
  <li><strong>Administrators</strong> can additionally <strong>delete</strong> a
      message. Deleting is permanent.</li>
</ul>
<p>A copy of each message is also emailed to the address(es) set in Settings, so
nothing is missed if no one is signed in.</p>
HTML,
            ],

            // ---- Administrator-only sections ---------------------------
            [
                'slug' => 'users',
                'title' => 'Users (staff logins)',
                'matrix' => true,
                'access' => [
                    'author' => [self::NONE, ''],
                    'editor' => [self::NONE, ''],
                    'admin'  => [self::FULL, 'Create logins, set roles, activate/deactivate, and (rarely) delete'],
                ],
                'body' => <<<'HTML'
<p><strong>Administrators only.</strong> Open <strong>Users</strong> in the left
menu to manage staff logins.</p>

<h3>Add or edit a login</h3>
<ol>
  <li>Click <strong>New user</strong> (or a name to edit).</li>
  <li>Fill in <strong>Display name</strong>, <strong>Username</strong>, and
      <strong>Email</strong>.</li>
  <li>Choose a <strong>Role</strong> — Author, Editor, or Administrator (see
      <a href="#section-roles">Roles &amp; permissions</a>).</li>
  <li>Set an <strong>Initial password</strong>, or use the reset option to let
      them set their own.</li>
</ol>

<h3>Removing access</h3>
<p><strong>Deactivating</strong> a login is the right way to remove access — it
blocks sign-in immediately and ends any session that person already has. A
deleted login can't be removed if that person has written content. For safety,
you can't change your own role, deactivate or delete your own account, or
remove the last active Administrator.</p>
HTML,
            ],

            [
                'slug' => 'settings',
                'title' => 'Settings',
                'matrix' => true,
                'access' => [
                    'author' => [self::NONE, ''],
                    'editor' => [self::NONE, ''],
                    'admin'  => [self::FULL, 'Edit all site settings and branding'],
                ],
                'body' => <<<'HTML'
<p><strong>Administrators only.</strong> Open <strong>Settings</strong> in the
left menu. Settings is organised into groups:</p>
<ul>
  <li><strong>Identity</strong> — church name and basics.</li>
  <li><strong>Contact</strong> — address, phone, office hours.</li>
  <li><strong>Email notifications</strong> — where prayer and contact
      notifications are sent. These fields accept several addresses (one per
      line, or separated by commas).</li>
  <li><strong>Worship times</strong> — the service times shown on the site.</li>
  <li><strong>Social &amp; apps</strong> — social links, app links, and the
      newsletter sign-up link.</li>
  <li><strong>Homepage</strong> — the home-page wording and calls to action.</li>
  <li><strong>Meta / SEO</strong> — the site description used by search
      engines.</li>
  <li><strong>Branding</strong> — colours. Leave a colour blank to use the
      theme default.</li>
</ul>
<p>Press <strong>Save Settings</strong> when done. Saves are all-or-nothing — if
any field is invalid, nothing is changed and you'll see what to fix.</p>
HTML,
            ],

            [
                'slug' => 'audit',
                'title' => 'Activity Log',
                'matrix' => true,
                'access' => [
                    'author' => [self::NONE, ''],
                    'editor' => [self::NONE, ''],
                    'admin'  => [self::FULL, 'View a read-only record of admin activity'],
                ],
                'body' => <<<'HTML'
<p><strong>Administrators only.</strong> The <strong>Audit Log</strong> (left
menu) is a read-only record of important admin actions — who did what and when.
Nothing here can be edited or removed; it's purely for review.</p>
<p>Use the filters at the top to narrow by action, type, person, or date range
when you're tracking down a specific change.</p>
HTML,
            ],
        ];
    }

    /** Find a single section by slug, or null. */
    public static function findSection(string $slug): ?array
    {
        foreach (self::sections() as $section) {
            if ($section['slug'] === $slug) {
                return $section;
            }
        }
        return null;
    }
}
