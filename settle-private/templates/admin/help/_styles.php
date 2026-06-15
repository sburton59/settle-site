<style>
/* Help doc — self-contained so it touches no shared admin CSS. */
.help-wrap { max-width: 60rem; }
.help-wrap h1 { margin: 0 0 0.25rem; }
.help-lead { color: #555; margin: 0 0 1.5rem; }
.help-toolbar { margin: 0 0 1.5rem; }
.help-btn {
  display: inline-block; padding: 0.45rem 0.9rem; border: 1px solid #9E2A2B;
  background: #9E2A2B; color: #fff; border-radius: 0.35rem; cursor: pointer;
  font: inherit; text-decoration: none;
}
.help-btn--ghost { background: #fff; color: #9E2A2B; }
.help-toc { background: #f6f5f2; border: 1px solid #e3e0d8; border-radius: 0.5rem;
  padding: 1rem 1.25rem; margin: 0 0 2rem; }
.help-toc h2 { margin: 0 0 0.5rem; font-size: 1.05rem; }
.help-toc ol { margin: 0; padding-left: 1.25rem; columns: 2; }
.help-toc a { text-decoration: none; }
.help-section { padding: 0 0 1rem; margin: 0 0 1.5rem; border-bottom: 1px solid #ececec; }
.help-section__head { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; }
.help-section h2 { margin: 1.25rem 0 0.5rem; }
.help-section h3 { margin: 1.1rem 0 0.35rem; font-size: 1.02rem; }
.help-section p, .help-section li { line-height: 1.5; }
.help-print-one { font-size: 0.85rem; white-space: nowrap; }
.help-who { background: #f6f5f2; border: 1px solid #e3e0d8; border-radius: 0.4rem;
  padding: 0.6rem 0.85rem; margin: 0.5rem 0 0.75rem; font-size: 0.92rem; }
.help-who strong { display: block; margin-bottom: 0.3rem; }
.help-who ul { margin: 0; padding-left: 1.1rem; }
.help-cell { font-weight: 700; }
.help-cell--full { color: #2e7d32; }
.help-cell--partial { color: #b8860b; }
.help-cell--none { color: #b0aca3; }
.help-matrix { border-collapse: collapse; width: 100%; margin: 0.75rem 0 0.5rem; }
.help-matrix th, .help-matrix td { border: 1px solid #ddd; padding: 0.45rem 0.6rem; text-align: left; vertical-align: top; }
.help-matrix thead th { background: #f6f5f2; }
.help-matrix td.help-mc { text-align: center; }
.help-matrix .help-note { display: block; font-size: 0.8rem; color: #666; }
.help-legend { font-size: 0.85rem; color: #555; margin: 0.25rem 0 0; }
.help-legend span { margin-right: 1rem; }

@media print {
  /* Print only the doc — drop the admin chrome. */
  .sidebar, .signin, .flash, .help-noprint { display: none !important; }
  .content { margin: 0 !important; padding: 0 !important; max-width: none !important; }
  .help-wrap { max-width: none; }
  a { color: inherit; text-decoration: none; }
  /* One section per page when printing the whole manual. */
  .help-section { break-before: page; border-bottom: 0; }
  .help-section:first-of-type, #section-getting-started { break-before: auto; }
  .help-toc, .help-who, .help-matrix thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
