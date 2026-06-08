-- =============================================================
-- Settle Memorial UMC — content fill, Batch 2b-2 (#10)
-- The 10 "Connect" ministry pages: Children, Settle Preschool,
-- Parent's Day Out, Youth, Adult Ministries, The Roadrunners,
-- Missions, Mission Partners, Mission Outreach, Faith Promises.
-- Text migrated from the live settleumc.com pages (typos cleaned).
--
-- SAFE TO RE-RUN AND NON-DESTRUCTIVE. Each UPDATE only fires while
-- the page still holds the Batch 1 placeholder body
--   (... AND body_html LIKE '%Draft — content to be added%')
-- so it will NOT overwrite a page you've already edited/published.
-- Pages stay DRAFTS — review each in /admin/pages, then Publish.
--
-- Run AFTER seed_pages.sql (Batch 1). Independent of 2a / 2b-1.
--
-- THINGS TO RECONCILE / FINISH (see MANIFEST for the full list):
--  • YOUTH: the live page named Cindy Palacios as youth director and linked
--    a flyer PDF with her email in the filename. Per the project's resolved
--    staff data she is no longer staff, so BOTH were dropped — the contact
--    now routes to the Staff directory (Jeff Keeley / Wesley Marcum). Add the
--    current youth director's details when ready.
--  • Ministry contacts (Aimee Keith aside, who is named) route to /staff or
--    /contact rather than embedding raw emails, to match the site's
--    email-obfuscation convention. Some live emails were obfuscated anyway.
--  • Re-host before cutover (old wp-content URLs that break at launch):
--    Preschool/PDO registration PDFs, the Adult Sunday School PDF, and all
--    page photos (deferred to the image pass — marked with HTML comments).
-- =============================================================

SET NAMES utf8mb4;

-- ---- CHILDREN ------------------------------------------------
UPDATE pages SET
  meta_description = 'Children''s ministries at Settle Memorial — nursery, Sunday School, Children''s Church, choir, JYF, and outreach.',
  body_html = '<p>Please contact our Children''s Director for information on any of our children''s ministries &mdash; you can reach the church office through our <a href="/staff">staff directory</a> or <a href="/contact">contact page</a>.</p>
<h2>Nursery</h2>
<p>Nursery facilities are provided during Sunday School and any time there are services at Settle. Our Infant Nursery provides care for children from birth to 18 months, and our Toddler Nursery serves 18-month-olds to 3-year-olds. Both nurseries are located in the Education Building &mdash; if this is your first visit, please stop at the Welcome Area for directions! The nursery provides a safe, clean, loving environment for the youngest members of Settle, staffed by both paid workers and volunteers.</p>
<h2>Children''s Sunday School and Children''s Church</h2>
<p>Settle has two opportunities for children on Sunday mornings. Sunday School for all ages is available in the Education Building, where children are divided into age groups as they learn important lessons from God''s Word while combining fun activities and maybe a snack! While children are always welcome to remain in the worship service with their parents, Children''s Church is a time especially for them &mdash; children exit after the Children''s Sermon and have their own time of worship.</p>
<h2>Children''s Choir</h2>
<p>There are many opportunities for children to grow musically while growing spiritually. Our Children''s Choir is for preschool through grade 5. Children learn about the Bible and explore their faith through singing, music, and musical games. They rehearse on Wednesday evenings during the school year and also perform several times a year in worship services. For additional information, please <a href="/staff">contact our music staff</a>.</p>
<h2>Junior Youth Fellowship</h2>
<p>Junior Youth Fellowship is a gathering for 2nd through 5th graders. JYF meets twice a month on Sunday evenings during the school year. Led by adult volunteers, our students enjoy spending time together for games, snacks, active hands-on devotions, and service projects. This group is a great chance for kids to form deeper relationships with their peers and teachers.</p>
<h2>Children''s Outreach</h2>
<p><strong>Special Activities</strong> &mdash; Settle''s Children''s Council strives to provide quality and meaningful activities for our children and their families. Yearly events include ice skating, a harvest hayride, a family Advent celebration, miniature golf, an indoor &ldquo;Beach Party,&rdquo; an Easter egg hunt, and family fun nights.</p>
<p><strong>Mission Projects</strong> &mdash; At Settle, we believe one of the most important lessons we can teach our children is to reach out to others. It is our goal to teach and challenge all children to be encouragers and contributors &mdash; in our church and in our community. Throughout the year, children have the opportunity to be involved in hands-on service projects and to offer collections for mission partners in town and around the world.</p>
<p><strong>Vacation Bible School</strong> &mdash; Four amazing days. Laughing, singing, and playing games with friends. Eating crazy snacks and making nifty crafts. Learning the awe-inspiring truth of God''s love for YOU! Vacation Bible School at Settle is all this and more &mdash; see you next summer!</p>
<!-- TODO image pass: re-host children''s ministry photos (IMG_6550, IMG_6542) in the Media Library. -->'
WHERE slug = 'children'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- SETTLE PRESCHOOL ----------------------------------------
UPDATE pages SET
  meta_description = 'Settle Preschool — a loving Christian program for 3-year-olds through Pre-K in Owensboro, KY.',
  body_html = '<p>At Settle Preschool, we offer a loving, Christian environment for 3-year-olds through Pre-kindergarten. Our goal is to offer a program rich in activities that promote the development of academic, social, emotional, and artistic skills.</p>
<p>Our curriculum focuses on providing children with the opportunity to:</p>
<ul>
<li>Develop confidence in themselves as unique and valuable people.</li>
<li>Relate respectfully and lovingly toward each other while learning basic concepts of the Christian faith.</li>
<li>Engage in creative experiences, individually and as a group.</li>
<li>Develop the necessary skills and academic foundation to be fully prepared for success in kindergarten.</li>
</ul>
<p>Our trained and certified staff facilitates individual and group instruction experiences. The preschool program is designed to be a partnership with our students'' families, for students to continue learning and growing at home.</p>
<h2>Registration</h2>
<p><a class="btn" href="https://schools.mybrightwheel.com/sign-in?redirect_path=forms/c3b21f86-7e6c-4088-b57b-a96cac1f312b/self-service" target="_blank" rel="noopener">Preschool Registration</a> &nbsp; <a class="btn btn--ghost" href="https://schools.mybrightwheel.com/sign-in?redirect_path=forms/4728ab54-4fef-4662-95f2-832ee666f482/self-service" target="_blank" rel="noopener">Afterschool Registration</a></p>
<p>For information on fees and registration, please contact the preschool office at <a href="tel:+12706847005">270-684-7005</a> or <a href="/contact">send us a message</a>.</p>
<p>Interested in working at Settle Preschool? See our <a href="/page/employment">current openings</a>.</p>
<!-- TODO image pass: re-host the preschool info sheet (26-27-info-sheet) and photo (IMG_6565) in the Media Library. -->'
WHERE slug = 'settle-preschool'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- PARENT'S DAY OUT ----------------------------------------
UPDATE pages SET
  meta_description = 'Parent''s Day Out at Settle Memorial — childcare for infants through age 5, Mondays, Wednesdays, and Fridays.',
  body_html = '<p>Parent''s Day Out offers childcare sessions for children (infant through age 5) on Mondays, Wednesdays, and Fridays from 9:00 a.m. to 12:30 p.m. The children enjoy a variety of fun and educational experiences throughout the year.</p>
<p>For more information about fees and availability, call Aimee Keith, our Parent''s Day Out Director, at <a href="tel:+12706844226">(270) 684-4226</a> or <a href="/contact">send us a message</a>.</p>
<h2>Registration</h2>
<ul>
<li><a href="https://settleumc.com/wp-content/uploads/PDOSummerRegistration26.pdf" target="_blank" rel="noopener">PDO Summer Registration (PDF)</a></li>
<li><a href="https://settleumc.com/wp-content/uploads/Settle-PDO.pdf" target="_blank" rel="noopener">Settle PDO Registration (PDF)</a></li>
</ul>
<!-- TODO before launch: re-host the PDO registration PDFs in the Media Library and update these links (currently old wp-content URLs). Photo IMG_6540 deferred to the image pass. -->'
WHERE slug = 'parents-day-out'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- YOUTH ---------------------------------------------------
UPDATE pages SET
  meta_description = 'Middle and high school youth ministry at Settle Memorial UMC.',
  body_html = '<h2>Middle School &amp; High School Youth</h2>
<p>Our Youth Group has many facets at Settle Memorial UMC, including Sunday Night Youth Group, Sunday Morning Bible Study, and Small Groups. Through these ministries, we provide a space where our youth can be themselves and experience Christ as they navigate through life. We know how to have fun while learning from the examples Christ set for us. All lessons and devotions are scripture based as we apply the Word to our everyday life.</p>
<p>If you have questions about our Youth Ministries, please <a href="/staff">contact our youth ministry team</a> or <a href="/contact">send us a message</a>.</p>
<!-- TODO: the live page''s named youth director and a flyer PDF were dropped as outdated per the resolved staff data; add the current youth director''s details when ready. Image pass: re-host the youth photo (52147029939). -->'
WHERE slug = 'youth'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- ADULT MINISTRIES ----------------------------------------
UPDATE pages SET
  meta_description = 'Adult ministries at Settle Memorial — small groups, Sunday School, Bible studies, and senior adult fellowship.',
  body_html = '<h2>Adult Ministries (Young Adult &ndash; Senior Adult)</h2>
<h3>Small Groups</h3>
<p>Small groups are a great way to grow in faith in God and develop relationships with other Christians. These groups are both supportive and challenging. Currently, there are small groups that meet on Tuesday mornings and Sunday evenings. Additionally, we have a quilting ministry that meets on Wednesdays, and knitters that meet on Thursdays.</p>
<h3>Sunday School</h3>
<p>Sunday School classes for all ages are offered beginning at 9:00 a.m.</p>
<h3>Bible Studies</h3>
<p>Short-term classes are available on Wednesdays during the school year, at 5:45 p.m.</p>
<h2>Senior Adult Ministries</h2>
<p><strong>The Roadrunners</strong> is a travel group for senior adults who take monthly day trips to nearby locations. <a href="/page/the-roadrunners">Learn more about the Roadrunners</a>, or visit their <a href="https://www.facebook.com/groups/804236024046094" target="_blank" rel="noopener">Facebook group</a> to see where they have been and read about upcoming trips.</p>
<p><strong>Young at Heart</strong> is an opportunity for senior adults to fellowship and serve together. They meet monthly for lunch on the first Tuesday of the month. Once a quarter that luncheon is held at church in the ROC; on other first Tuesdays they meet at a local restaurant for a dutch-treat lunch and fellowship.</p>
<p><a href="https://settleumc.com/wp-content/uploads/Settle-Memorial-UMC.pdf" target="_blank" rel="noopener">Adult Sunday School Classes (PDF)</a></p>
<p>If you are interested in any of these adult discipleship options, please <a href="/contact">contact the church office</a> for more information.</p>
<!-- TODO before launch: re-host the Adult Sunday School PDF (Settle-Memorial-UMC.pdf) in the Media Library and update the link (currently an old wp-content URL). -->'
WHERE slug = 'adult-ministries'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- THE ROADRUNNERS -----------------------------------------
UPDATE pages SET
  meta_description = 'The Roadrunners — Settle Memorial''s multi-generation adult travel group taking monthly day trips.',
  body_html = '<p>All are welcome to join our multi-generation adult travel group, the Roadrunners! The Roadrunners take monthly day trips which include a tour and lunch, all within a day''s drive from Owensboro. Cost and times vary.</p>
<p>Be watching <a href="/calendar">our calendar</a> for up-to-date information on our next trip! Leaders are Terry and Staci Horn and Jacquie Howard.</p>
<p>For more information, please <a href="/contact">contact the church office</a>, or join the <a href="https://www.facebook.com/groups/804236024046094" target="_blank" rel="noopener">Young at Heart Facebook group</a> for trip information.</p>
<!-- TODO image pass: re-host the Roadrunners photo (Roadrunner) in the Media Library. -->'
WHERE slug = 'the-roadrunners'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- MISSIONS ------------------------------------------------
UPDATE pages SET
  meta_description = 'Missions at Settle Memorial — serving Owensboro and beyond.',
  body_html = '<p>We love to serve our community and beyond, whether through a meal at St. Benedict''s shelter, sharing weekend food bags with area schools, or hosting a coat giveaway and cookout. It is built into our church''s DNA to lend a hand. When you come to Settle, you will have no trouble finding a place to give of yourself.</p>
<p>For more information on mission opportunities at Settle, please <a href="/contact">contact the church office</a>.</p>
<ul>
<li><a href="/page/mission-partners">Mission Partners</a></li>
<li><a href="/page/mission-outreach">Mission Outreach</a></li>
<li><a href="/page/faith-promises">Give to Faith Promises</a></li>
</ul>
<!-- TODO image pass: re-host the missions photo (52032154230) in the Media Library. -->'
WHERE slug = 'missions'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- MISSION PARTNERS ----------------------------------------
UPDATE pages SET
  meta_description = 'The local, state, national, and international mission partners Settle Memorial supports.',
  body_html = '<p>Settle partners with a wide range of ministries and organizations, locally and around the world.</p>
<h2>Owensboro</h2>
<ul>
<li>Fresh Start for Women</li>
<li>Jail Ministry</li>
<li>Help Office</li>
<li>St. Benedict''s</li>
<li>Habitat for Humanity</li>
<li>Eastview Elementary</li>
<li>Audubon Elementary</li>
<li>Estes Elementary</li>
<li>Foust Elementary</li>
<li>Mary Kendall Home</li>
<li>Kentucky Wesleyan College</li>
</ul>
<h2>Kentucky</h2>
<ul>
<li>Camp Loucon</li>
<li>Henderson Settlement</li>
<li>KY United Methodist Children''s Homes</li>
<li>Red Bird Mission</li>
</ul>
<h2>International</h2>
<ul>
<li>Uganda Counseling and Support Services &mdash; Mark and Robin Howard</li>
<li>Missionaries to Spain</li>
</ul>
<h2>Other</h2>
<ul>
<li>United Methodist Committee on Relief (UMCOR) &mdash; Disaster Relief</li>
<li>Youth and Adult Mission Trips</li>
</ul>'
WHERE slug = 'mission-partners'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- MISSION OUTREACH ----------------------------------------
UPDATE pages SET
  meta_description = 'Hands-on mission outreach at Settle Memorial — St. Benedict''s, weekend food bags, Habitat, and Camp Loucon.',
  body_html = '<p>Settle has many opportunities to be hands-on with missions. For more information, please <a href="/contact">contact the church office</a>.</p>
<h2>Current Hands-On Missions</h2>
<p>Settle has a strong mission ministry. This highlights just a few of our hands-on missions.</p>
<p>We have teams of people who cook or deliver meals to <a href="https://stbenedictsowensboro.org/" target="_blank" rel="noopener">St. Benedict''s Shelter</a> on Wednesdays. We are always needing people to help these teams prepare the meals and deliver the food.</p>
<p>Each Wednesday, we have teams of people that pack or deliver weekend food bags for two local elementary schools. There are so many children in need of food over the weekend.</p>
<p>Settle has a longstanding commitment to <a href="https://habitatowensboro.org/" target="_blank" rel="noopener">Habitat for Humanity</a>. Whether by financial help or hands-on help, we have been a part of many builds in Owensboro.</p>
<p><a href="https://www.loucon.org/" target="_blank" rel="noopener">Camp Loucon</a>, in Leitchfield, KY, is the United Methodist camp for this part of Kentucky. Settle has been a part of Loucon since the camp began nearly 75 years ago. Our members have attended camp, worked as camp staffers, volunteered at camps, helped maintain the grounds, and built many cabins at Loucon. We have regular crews of people that serve at Loucon.</p>'
WHERE slug = 'mission-outreach'
  AND body_html LIKE '%Draft — content to be added%';

-- ---- FAITH PROMISES ------------------------------------------
UPDATE pages SET
  meta_description = 'Give to Faith Promises to support Settle Memorial''s mission partners.',
  body_html = '<p>Faith Promises fund our <a href="/page/mission-partners">Mission Partners</a> each year. Gifts can be made to the church office or by giving online.</p>
<p><a class="btn" href="https://secure.myvanco.com/YQGK/campaign/C-Z99Z" target="_blank" rel="noopener">Give to Faith Promises</a></p>'
WHERE slug = 'faith-promises'
  AND body_html LIKE '%Draft — content to be added%';

-- End of seed_pages_content_2b2.sql
