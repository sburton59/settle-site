-- =============================================================
-- Settle Memorial UMC — content fill, Batch 2b-1 (#10)
-- The "welcome / info" pages: I'm New, Sundays, About, Directions
-- & Parking, Weekly Schedule, Employment. Text migrated from the
-- live settleumc.com pages (lightly cleaned for typos).
--
-- SAFE TO RE-RUN AND NON-DESTRUCTIVE. Each UPDATE only fires while
-- the page still holds the Batch 1 placeholder body
--   (... AND body_html LIKE '%Draft — content to be added%')
-- so it will NOT overwrite a page you've already edited/published.
-- Pages stay DRAFTS — review each in /admin/pages, then Publish.
--
-- Run AFTER seed_pages.sql (Batch 1). Independent of Batch 2a.
--
-- THINGS TO RECONCILE / FINISH (see the project notes too):
--  • Directions page says "201 E. 4th St" (matches the live page and its
--    Google Maps pin); the site footer/settings use "202 E. 4th St".
--    Confirm which you want where.
--  • Weekly Schedule: the live site's per-ministry emails were obfuscated
--    and unrecoverable, so they point to the Contact page. Add real
--    addresses if you want them.
--  • Guest Survey: the live I'm New and Sundays pages used two DIFFERENT
--    Constant Contact survey links. Both pages here use the I'm New one
--    (lp.constantcontactpages.com/sv/GU4Bgos/settleguest). Confirm current.
--  • Employment + Directions reference files/images still on the OLD
--    wp-content URLs (job-description PDFs, the parking map). Re-host in the
--    Media Library and update the links/images before DNS cutover. HTML
--    comments in the bodies mark these.
--  • Hero/section images from the live pages are deferred to the image pass.
-- =============================================================

SET NAMES utf8mb4;

-- ---- I'M NEW -------------------------------------------------
UPDATE pages SET
  meta_description = 'New to Settle Memorial? Start here — who we are, how to visit, and our guest survey.',
  body_html = '<p>At Settle Memorial United Methodist Church, our members believe in passionately serving Jesus with our prayers, our participation, our talents, and our service to others. We welcome you to a faith journey where you can connect with new friends, learn more about Jesus, and experience His transforming love and grace.</p>
<p>We are happy that you are exploring opportunities at Settle. Please let us know what questions we can answer to help guide your experience.</p>
<p><a class="btn" href="https://lp.constantcontactpages.com/sv/GU4Bgos/settleguest" target="_blank" rel="noopener">Take Our Guest Survey</a></p>
<p>You can also <a href="/page/sundays">plan your Sunday visit</a> or <a href="/contact">contact us</a> with any questions.</p>'
WHERE slug = 'im-new'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- SUNDAYS WORSHIP SERVICES --------------------------------
UPDATE pages SET
  meta_description = 'What to expect on Sunday at Settle Memorial — worship times, services, music, parking, and children.',
  body_html = '<p>Visiting a church for the first time can be intimidating. We would love to have you come check us out. At Settle, our members strive to create a culture of belonging, excellent music, and a message that deepens our love of God and each other. The messages in all our worship services reflect upon sacred text and always have a modern application.</p>
<p>Settle has two worship services:</p>
<h2>Contemporary Worship &mdash; 10:30 a.m.</h2>
<p>In the Shepherd Center Auditorium. <a href="https://www.facebook.com/shoutatsettle" target="_blank" rel="noopener">Watch the Contemporary service live on Facebook</a>.</p>
<h2>Traditional Worship &mdash; 10:00 a.m.</h2>
<p>In the Sanctuary. <a href="https://www.facebook.com/SettleMem" target="_blank" rel="noopener">Watch the Traditional service live on Facebook</a>.</p>
<p>We are grateful you are interested in joining us at Settle. Please let us know a little about yourself and how we can be in ministry with you. <a href="https://lp.constantcontactpages.com/sv/GU4Bgos/settleguest" target="_blank" rel="noopener">Take our Guest Survey</a>.</p>
<p>You may also watch past services on our <a href="https://www.youtube.com/@settlememorialunitedmethod5839" target="_blank" rel="noopener">YouTube channel</a>.</p>
<h2>Where do I enter the building on Sunday morning?</h2>
<p><strong>Contemporary:</strong> The entrance to the Shepherd Center Auditorium is on the corner of J.R. Miller and 4th Street. Parking is available right next to the building or across the street.</p>
<p><strong>Traditional:</strong> The entrance to the Sanctuary is on the corner of 4th and Daviess Street. Parking is available across the street.</p>
<h2>What is the music like?</h2>
<p><strong>Contemporary:</strong> The music is modern, featuring a high-quality full band. Expect worship to be energetic and participatory through singing, praying, and reflecting.</p>
<p><strong>Traditional:</strong> The music is a collection of traditional hymns with special selections from our choir, soloists, and various musicians in our community. Worship is warm and joyful.</p>
<h2>What should I wear?</h2>
<p>At all of Settle''s activities, come as you are, and wear what you want! Jeans or shorts are perfectly acceptable, as are khakis, dresses, or suits. Wear whatever makes you feel most comfortable. (Just wear something!)</p>
<h2>Are my children welcome in the service?</h2>
<p>Yes, children are welcome in all of our services. Some parents choose to take their youngest children to the nursery prior to the service, and others keep their children in the service during the music. Children aged 3 to Third Grade will be accompanied to Children''s Church prior to the sermon if they wish. Your children are welcome to stay with you during the entire service if you prefer. <a href="/page/children">Learn about Children''s Ministries</a>.</p>'
WHERE slug = 'sundays'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- ABOUT US ------------------------------------------------
UPDATE pages SET
  meta_description = 'About Settle Memorial United Methodist Church in Owensboro, KY — more than 180 years of ministry.',
  body_html = '<p>For more than 180 years, Settle Memorial United Methodist Church has served this community of Owensboro and worked hard to further the Kingdom of God. We literally stand upon the shoulders of those who have established a rich tradition of ministry in the name of Christ.</p>
<p>The strength of any church is in the commitment of its people. Settle is blessed with a congregation that is serious about the faith and sharing it with its community and world. Settle is a warm group of people blessed with a broad range of ages, gifts, and talents. We have been called to serve and be the hands and feet of Christ in today''s world.</p>
<p>At Settle, we believe in making a difference by following Christ, serving others, and loving everyone. Our worship services are Spirit-filled and uplifting, and we have many ways for you to connect in our community and discover God''s love in fresh ways.</p>
<p>As you explore our website, we hope you will find ways to engage and connect with us. We look forward to seeing you very soon at Settle!</p>
<p>&mdash; Pastor Mark</p>'
WHERE slug = 'about'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- DIRECTIONS & PARKING ------------------------------------
UPDATE pages SET
  meta_description = 'Directions to Settle Memorial in Owensboro, KY, and where to park for worship.',
  body_html = '<h2>Directions</h2>
<p>Settle is located at 201 East 4th Street in Owensboro, Kentucky.</p>
<p><a class="btn btn--ghost" href="https://www.google.com/maps/place/201+E+4th+St,+Owensboro,+KY+42303" target="_blank" rel="noopener">View on Google Maps</a></p>
<p>If you have any questions, please <a href="/contact">send us a message</a> or call <a href="tel:+12706844226">(270) 684-4226</a>.</p>
<h2>Where do I park?</h2>
<p>The easiest place to park is in the parking lot adjacent to the Renewal and Outreach Center (ROC), on the corner of Daviess and 4th Street. You can enter the lot via Daviess Street, J.R. Miller, or 5th Street. Guest parking is available in the lot. Parking for individuals with handicap permits is located in the ROC lot or on 4th Street in front of the Education Building.</p>
<!-- TODO image pass: re-host the parking map (Settle-Memorial-Parking-Map) in the Media Library and embed it here. -->'
WHERE slug = 'directions-parking'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- WEEKLY SCHEDULE -----------------------------------------
UPDATE pages SET
  meta_description = 'Weekly schedule of worship, studies, and ministries at Settle Memorial.',
  body_html = '<h2>Sunday</h2>
<p><strong>Traditional Worship</strong> is at 10 a.m. in the Sanctuary.</p>
<p><strong>Contemporary Worship</strong> is at 10:30 a.m. in the Shepherd Center Auditorium.</p>
<p><strong>Sunday School</strong> for all ages is at 9 a.m. Please contact the church office for the locations of each class.</p>
<h2>Sunday Evenings</h2>
<p><strong>JYF (Junior Youth Fellowship)</strong> &mdash; 5:15&ndash;6:45 p.m., Grades 2&ndash;5, in the Education Building. Usually meets every other week.</p>
<p><strong>Youth</strong> &mdash; 5:30&ndash;7:00 p.m., Grades 6&ndash;12, in the ROC. Meets weekly.</p>
<h2>Wednesday</h2>
<p><strong>Bible Study</strong> &mdash; we have several Bible studies throughout the week. Two are on Wednesdays, at 4:00 p.m. and 5:45 p.m.</p>
<p><strong>Meal</strong> begins at 5:00 p.m. in the ROC. We accept donations for the light meal.</p>
<p>Offerings after the meal include:</p>
<ul>
<li><strong>Children:</strong> activities and music ministry, in the Education Building.</li>
<li><strong>Youth:</strong> Girls small group in the Education Building; Guys small group in the ROC.</li>
<li><strong>Adults:</strong> God''s Troubadours (dulcimer ensemble) in the Shepherd Center; Bible study in the ROC.</li>
<li><strong>Sanctuary Choir:</strong> meets 6:45&ndash;8:00 p.m. in the Choir Room in the Shepherd Center.</li>
</ul>
<p>For details on any of these, please <a href="/contact">contact the church office</a>.</p>'
WHERE slug = 'weekly-schedule'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- EMPLOYMENT ----------------------------------------------
UPDATE pages SET
  meta_description = 'Current employment opportunities at Settle Memorial United Methodist Church.',
  body_html = '<p>Settle Memorial is currently hiring for the following positions.</p>
<h2>Preschool Positions</h2>
<p><a class="btn" href="https://www.indeed.com/job/preschool-director-c20e9cd249eef877" target="_blank" rel="noopener">Apply: Director of Settle Preschool (Indeed)</a></p>
<p>Job descriptions:</p>
<ul>
<li><a href="https://settleumc.com/wp-content/uploads/Untitled-document-5.pdf" target="_blank" rel="noopener">Preschool Director Job Description 2026 (PDF)</a></li>
<li><a href="https://settleumc.com/wp-content/uploads/Teacher-Job-Posting.docx.pdf" target="_blank" rel="noopener">Teacher Job Posting (PDF)</a></li>
<li><a href="https://settleumc.com/wp-content/uploads/Afterschool-Worker-Job-Posting.docx.pdf" target="_blank" rel="noopener">Afterschool Worker Job Posting (PDF)</a></li>
</ul>
<!-- TODO before launch: re-host these job-description files in the Media Library and update the links (they point at the old wp-content URLs). Update this page as openings change. -->'
WHERE slug = 'employment'
  AND body_html LIKE '%Draft — content to be added%';

-- End of seed_pages_content_2b1.sql
