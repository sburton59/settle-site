-- =============================================================
-- Settle Memorial UMC — content fill, Batch 2a (#10)
-- The "link / landing" pages: Give, Watch, Sermons, Worship
-- Bulletin, Newsletter. Content + links migrated from settleumc.com.
--
-- SAFE TO RE-RUN AND NON-DESTRUCTIVE. Each UPDATE only fires while
-- the page still holds the Batch 1 placeholder body
--   (... AND body_html LIKE '%Draft — content to be added%')
-- so it will NOT overwrite a page you have already edited or
-- published. Pages stay DRAFTS (is_published unchanged) — review
-- each in /admin/pages, then Publish.
--
-- Run AFTER seed_pages.sql (Batch 1) has created the draft pages.
--
-- ⚠ Weekly/maintained links: the Worship Bulletin and Newsletter
--   "this week" links change every week (you maintain them in
--   Constant Contact today). They are seeded with the current issue;
--   update them on the page going forward. HTML comments in the body
--   remind you which link to refresh.
-- ⚠ The Legacy Giving Guide PDF still points at the OLD wp-content
--   URL. Re-host it in the Media Library and update the link before
--   DNS cutover (see the migration notes / pre-launch checklist).
-- =============================================================

SET NAMES utf8mb4;

-- ---- GIVE ----------------------------------------------------
UPDATE pages SET
  meta_description = 'Ways to give to Settle Memorial — online through Vanco, by text message, by check, or through legacy giving.',
  body_html = '<p>We have a sense that everything we have is a gift from God. We value sharing our lives, our time, and our resources, together.</p>
<h2>Online Giving</h2>
<p>Online giving is offered securely through GivePlus (a Vanco company). You will be redirected to Vanco''s secure giving portal.</p>
<p><a class="btn" href="https://secure.myvanco.com/YQGK" target="_blank" rel="noopener">Give Online</a></p>
<h2>Text Giving</h2>
<p>Text your gift amount to <strong>270-216-5180</strong> and follow the on-screen directions.</p>
<h2>Give by Check</h2>
<p>Place your check in the Sunday offering, or mail it to:</p>
<p>Settle Memorial United Methodist Church<br>P.O. Box 1756<br>Owensboro, KY 42302</p>
<h2>Legacy Giving</h2>
<p>With the start of a new year, it may be the perfect time to consider longer-term planned giving, which can have a transformational impact on Settle for years to come. Our Legacy Giving Guide opens your eyes to creative ways to make a lasting difference in the financial health of the church.</p>
<!-- TODO before launch: re-host this PDF in the Media Library and replace the wp-content URL below. -->
<p><a class="btn btn--ghost" href="https://settleumc.com/wp-content/uploads/Legacy-Giving-Guide.pdf" target="_blank" rel="noopener">Download the Legacy Giving Guide (PDF)</a></p>'
WHERE slug = 'give'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- WATCH ---------------------------------------------------
UPDATE pages SET
  meta_description = 'Watch Settle Memorial worship live on Facebook, or view past services any time on our YouTube channel.',
  body_html = '<p>Settle Memorial offers two worship services each Sunday. Join us live online, or catch up later on our YouTube channel.</p>
<h2>Traditional Worship &mdash; 10:00 a.m.</h2>
<p>In the Sanctuary. <a href="https://www.facebook.com/SettleMem" target="_blank" rel="noopener">Watch the Traditional service live on Facebook</a>.</p>
<h2>Contemporary Worship (Shout!) &mdash; 10:30 a.m.</h2>
<p>In the Shepherd Center Auditorium. <a href="https://www.facebook.com/shoutatsettle" target="_blank" rel="noopener">Watch the Contemporary service live on Facebook</a>.</p>
<h2>Watch Past Services</h2>
<p>Recordings of our worship services are available any time on our YouTube channel.</p>
<p><a class="btn" href="https://www.youtube.com/@settlememorialunitedmethod5839" target="_blank" rel="noopener">Visit our YouTube Channel</a></p>'
WHERE slug = 'watch'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- SERMONS (categorized landing; channel-linked, low-maintenance) ----
UPDATE pages SET
  meta_description = 'Watch Settle Memorial sermons and worship on YouTube — Traditional, Shout!, Youth Sunday, and special services.',
  body_html = '<p>Watch and re-watch worship at Settle Memorial. Our recorded services live on our YouTube channel, grouped by service type below.</p>
<p><a class="btn" href="https://www.youtube.com/@settlememorialunitedmethod5839" target="_blank" rel="noopener">Settle Memorial YouTube Channel</a></p>
<h2>Traditional</h2>
<p>Our traditional service from the Sanctuary, with hymns, choir, and liturgy. <a href="https://www.youtube.com/@settlememorialunitedmethod5839" target="_blank" rel="noopener">Watch Traditional services &rarr;</a></p>
<h2>Shout!</h2>
<p>Our contemporary service with the Shout! band in the Shepherd Center Auditorium. <a href="https://www.youtube.com/@settlememorialunitedmethod5839" target="_blank" rel="noopener">Watch Shout! services &rarr;</a></p>
<h2>Youth Sunday</h2>
<p>Worship services led by our youth. <a href="https://www.youtube.com/@settlememorialunitedmethod5839" target="_blank" rel="noopener">Watch Youth Sunday services &rarr;</a></p>
<h2>Special Sundays &amp; Services</h2>
<p>Christmas Eve, combined services, and other special worship throughout the year. <a href="https://www.youtube.com/@settlememorialunitedmethod5839" target="_blank" rel="noopener">Watch special services &rarr;</a></p>
<!-- Tip: create a YouTube playlist per category and replace the channel links above with the playlist links so visitors jump straight to that collection. -->'
WHERE slug = 'sermons'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- WORSHIP BULLETIN ----------------------------------------
UPDATE pages SET
  meta_description = 'This week''s Settle Memorial worship bulletin, with the sermon outline and announcements.',
  body_html = '<p>Our weekly bulletin &mdash; with the sermon outline and announcements &mdash; is posted on the Saturday before Sunday worship.</p>
<p><a class="btn" href="https://conta.cc/43NutYg" target="_blank" rel="noopener">This Sunday''s Bulletin</a></p>
<!-- The bulletin is published weekly through Constant Contact. Each Saturday, replace the link above with the newest bulletin. -->'
WHERE slug = 'worship-bulletin'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- NEWSLETTER (Tidings) ------------------------------------
UPDATE pages SET
  meta_description = 'Tidings, the weekly newsletter from Settle Memorial. Read the latest issue or sign up to receive it by email.',
  body_html = '<h2>Tidings</h2>
<p><strong>"Tidings" is our weekly newsletter at Settle.</strong> It shares mission opportunities, discipleship opportunities, and more.</p>
<p><a class="btn" href="https://conta.cc/49AXWIw" target="_blank" rel="noopener">Read this week''s Tidings</a> <a class="btn btn--ghost" href="https://lp.constantcontactpages.com/sl/tdRqncu" target="_blank" rel="noopener">Sign up for Tidings</a></p>
<!-- Tidings is published weekly through Constant Contact. Update the "Read this week''s Tidings" link with the newest issue each week; the signup link is stable. -->'
WHERE slug = 'newsletter'
  AND body_html LIKE '%Draft — content to be added%';

-- End of seed_pages_content_2a.sql
