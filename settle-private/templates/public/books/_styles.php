<?php
/**
 * Shared book typography — scoped entirely under .book so it is safe to
 * emit inline on any book page without touching the site theme. Loaded once
 * by templates/public/book.php; every book reuses it (mirrors the help
 * doc's _styles.php pattern).
 */
?>
<style>
/* ============================================================
   Everything is scoped under .book so this drops into the CMS
   without colliding with site-wide styles. To remove the
   original-page folios, delete the one rule marked [FOLIOS].
   ============================================================ */
.book{
  --paper:#f4ecd6;
  --paper-2:#f7f0dd;
  --surround:#d8cbab;
  --ink:#2c2218;
  --ink-soft:#5d4f3c;
  --accent:#761521;      /* oxblood — the ink of period devotional printing */
  --rule:#c8b68d;
  --folio:#a48d5f;       /* sepia */
  color:var(--ink);
  background:var(--surround);
  font-family:"Iowan Old Style","Palatino Linotype",Palatino,"Book Antiqua",Georgia,"Times New Roman",serif;
  font-size:1.18rem;
  line-height:1.74;
  -webkit-font-smoothing:antialiased;
  text-rendering:optimizeLegibility;
  padding:2.4rem 1rem 4rem;
}
.book *{box-sizing:border-box;}

.book__page{
  max-width:43rem;
  margin:0 auto;
  background:var(--paper);
  background-image:linear-gradient(180deg,var(--paper-2),var(--paper));
  border:1px solid #d9c79e;
  box-shadow:0 1px 0 #fff8e8 inset,0 18px 40px -22px rgba(60,40,15,.55);
  padding:4rem 4.2rem 3.5rem;
  position:relative;
}

/* ---- cover / title page ---- */
.book__cover{text-align:center;padding:2rem 0 1rem;}
.book .book__cover p{text-align:center;} /* beats the general .book p justify rule */
.book__cover .pre{font-size:.74rem;letter-spacing:.34em;text-transform:uppercase;color:var(--ink-soft);margin:0 0 1.6rem;}
.book__cover h1{font-size:3.1rem;line-height:1.05;margin:0;font-weight:600;letter-spacing:.01em;}
.book__cover .sub{font-style:italic;font-size:1.32rem;color:var(--accent);margin:.9rem 0 0;}
.book__cover .orn{color:var(--accent);letter-spacing:.5em;margin:1.6rem 0 1.4rem;font-size:1rem;}
.book__cover .where{font-size:.95rem;color:var(--ink-soft);margin:.2rem 0;line-height:1.5;}
.book__cover .reprint{font-size:.8rem;letter-spacing:.18em;text-transform:uppercase;color:var(--ink-soft);margin-top:1.4rem;}

.rule2{border:0;border-top:1px solid var(--rule);box-shadow:0 3px 0 -2px var(--rule);height:0;margin:2.6rem auto;width:60%;}

/* ---- contents ---- */
.book__toc{text-align:center;margin:0 auto 1rem;}
.book__toc h2{font-size:.78rem;letter-spacing:.3em;text-transform:uppercase;color:var(--ink-soft);font-weight:600;margin:0 0 1rem;}
.book__toc ol{list-style:none;margin:0;padding:0;}
.book__toc li{margin:.45rem 0;}
.book__toc a{color:var(--accent);text-decoration:none;font-size:1.12rem;}
.book__toc a:hover,.book__toc a:focus{text-decoration:underline;}

/* ---- section headings ---- */
.book section{margin-top:3.2rem;}
.book h2.sec{text-align:center;color:var(--accent);font-weight:600;font-size:1.05rem;letter-spacing:.16em;text-transform:uppercase;margin:0 0 .25rem;line-height:1.4;}
.book h2.sec .sub{display:block;font-style:italic;text-transform:none;letter-spacing:0;font-size:1.3rem;color:var(--ink);margin-top:.35rem;}
.book h2.sec + .rule2{margin:1.4rem auto 2rem;width:38%;}

/* ---- body text ---- */
.book p{margin:0 0 1.15rem;text-align:justify;hyphens:auto;}
.book p.lead::first-letter{float:left;font-size:3.4em;line-height:.78;padding:.06em .08em 0 0;color:var(--accent);font-weight:600;}
.book p.center{text-align:center;}
.book .sig{text-align:right;font-style:italic;margin-top:-.3rem;color:var(--ink-soft);}
.book .verse{text-align:center;font-style:italic;color:var(--ink-soft);margin:1.4rem 0;line-height:1.5;}

/* ---- original page folios in the margin ---- */
.book .folio{ /* [FOLIOS] delete this rule to hide */
  float:right;clear:right;margin-right:-3rem;width:2rem;
  color:var(--folio);font-style:italic;font-size:.72rem;line-height:1.9;
  text-align:left;user-select:none;
}
.book h2.sec .folio{float:right;margin-right:-2.6rem;}

/* ---- rosters / programs ---- */
.book .roster{margin:0 0 1.4rem;}
.book .roster .rtitle{font-style:italic;color:var(--accent);margin:1.4rem 0 .4rem;font-size:1.08rem;}
.book dl.program{margin:.4rem 0 1.4rem;}
.book dl.program div{margin:0 0 .55rem;}
.book dl.program dt{font-weight:600;}
.book dl.program dd{margin:0;color:var(--ink-soft);}
.book ul.names{list-style:none;margin:.3rem 0 1.2rem;padding:0;columns:2;column-gap:2.2rem;font-size:.98rem;line-height:1.55;}
.book ul.names li{break-inside:avoid;margin:0 0 .18rem;}
.book .role{font-size:1rem;color:var(--ink-soft);margin:.2rem 0 1rem;}
.book .role b{color:var(--ink);font-weight:600;}

/* ---- colophon ---- */
.book__colophon{margin-top:3.5rem;padding-top:1.6rem;border-top:1px solid var(--rule);font-size:.86rem;line-height:1.6;color:var(--ink-soft);}
.book__colophon h2{font-size:.74rem;letter-spacing:.28em;text-transform:uppercase;margin:0 0 .8rem;color:var(--ink-soft);font-weight:600;}
.book__colophon p{text-align:left;}

/* ============================================================
   ADDITIVE (v3.8) — used by "Behind the Open Door" (book 2) and
   the /books library index. New class names only; "Our Church"
   references none of these, so book 1 is visually unchanged.
   ============================================================ */
.book .stars{text-align:center;color:var(--accent);letter-spacing:.45em;font-size:.85rem;margin:2.1rem 0;user-select:none;}
.book .anecdote{font-style:italic;color:var(--ink-soft);max-width:34rem;margin:1.7rem auto;}
.book .anecdote p{text-align:left;margin:0 0 .7rem;hyphens:none;}
.book .anecdote p:last-child{margin-bottom:0;}
.book .anecdote .who{font-style:italic;color:var(--ink);}
.book .anecdote .atitle{display:block;text-align:center;font-style:italic;text-decoration:underline;color:var(--accent);margin:0 0 .9rem;font-size:1.08rem;}
.book h3.subsec{text-align:center;color:var(--ink);font-weight:600;font-size:.94rem;letter-spacing:.12em;text-transform:uppercase;margin:2.6rem 0 .3rem;}
.book h3.subsec + .rule2{margin:.9rem auto 1.5rem;width:24%;}
.book ul.deeds{margin:.6rem 0 1.4rem 1.3rem;padding:0;}
.book ul.deeds li{margin:0 0 .5rem;padding-left:.2rem;}
.book .leaders{text-align:center;font-style:italic;color:var(--ink);margin:.6rem 0 1.4rem;line-height:1.9;}

/* ---- library index (/books) ---- */
.book__library{text-align:center;}
.book__library .lead{font-size:1.04rem;color:var(--ink-soft);max-width:34rem;margin:0 auto 2.4rem;}
.book .shelf{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:1.4rem;text-align:left;}
.book .shelf li{margin:0;}
.book .shelf a{display:block;text-decoration:none;color:inherit;background:var(--paper-2);border:1px solid #d9c79e;box-shadow:0 1px 0 #fff8e8 inset;padding:1.4rem 1.6rem;transition:box-shadow .15s ease,transform .15s ease;}
.book .shelf a:hover,.book .shelf a:focus{box-shadow:0 10px 26px -16px rgba(60,40,15,.55);transform:translateY(-1px);}
.book .shelf .stitle{display:block;color:var(--accent);font-size:1.5rem;font-weight:600;line-height:1.15;}
.book .shelf .ssub{display:block;font-style:italic;color:var(--ink);margin-top:.3rem;}
.book .shelf .syear{display:block;font-size:.78rem;letter-spacing:.18em;text-transform:uppercase;color:var(--ink-soft);margin-top:.55rem;}

/* ---- responsive ---- */
@media (max-width:640px){
  .book{font-size:1.08rem;padding:1rem .4rem 2.5rem;}
  .book__page{padding:2.2rem 1.5rem 2rem;}
  .book__cover h1{font-size:2.3rem;}
  .book p{text-align:left;}
  .book .folio{display:none;}
  .book ul.names{columns:1;}
}

/* ---- print ---- */
@media print{
  .book{background:#fff;padding:0;color:#000;}
  .book__page{box-shadow:none;border:0;background:#fff;max-width:none;padding:0 .4in;}
  .book .folio{color:#666;}
  .book__toc{display:none;}
  .book section{page-break-before:always;}
  .book__cover{page-break-after:always;}
  .rule2{box-shadow:none;}
}
</style>
