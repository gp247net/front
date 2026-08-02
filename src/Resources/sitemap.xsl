<?xml version="1.0" encoding="UTF-8"?>
<!--
  Human-readable stylesheet for GP247 sitemaps (US-SEO-004, modification
  20260802T080856). Referenced from sitemap.xml / child segments via an
  <?xml-stylesheet?> processing instruction. Purely cosmetic: browsers render
  this as an HTML table while crawlers ignore the PI and read the raw XML.
  Handles both <urlset> (flat + child segments) and <sitemapindex> roots.
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xhtml="http://www.w3.org/1999/xhtml">
  <xsl:output method="html" encoding="UTF-8" indent="yes"/>

  <xsl:template match="/">
    <html lang="en">
      <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <meta name="robots" content="noindex"/>
        <title>GP247 Sitemap</title>
        <style>
          :root {
            --bg: #f8fafc; --fg: #0f172a; --muted: #64748b; --card: #ffffff;
            --border: #e2e8f0; --head: #f1f5f9; --link: #2563eb; --tag: #eef2ff;
            --tag-fg: #4338ca; --accent: #4f46e5;
          }
          @media (prefers-color-scheme: dark) {
            :root {
              --bg: #0b1120; --fg: #e2e8f0; --muted: #94a3b8; --card: #111827;
              --border: #1f2937; --head: #172033; --link: #60a5fa; --tag: #1e1b4b;
              --tag-fg: #c7d2fe; --accent: #818cf8;
            }
          }
          * { box-sizing: border-box; }
          body {
            margin: 0; background: var(--bg); color: var(--fg);
            font: 14px/1.5 system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
          }
          .wrap { max-width: 1200px; margin: 0 auto; padding: 24px 16px; }
          h1 { font-size: 20px; margin: 0 0 4px; }
          .meta { color: var(--muted); margin: 0 0 20px; font-size: 13px; }
          .meta a { color: var(--link); }
          .card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 12px; overflow-x: auto;
          }
          table { width: 100%; border-collapse: collapse; min-width: 640px; }
          thead th {
            position: sticky; top: 0; background: var(--head); text-align: start;
            font-weight: 600; color: var(--muted); font-size: 12px;
            text-transform: uppercase; letter-spacing: .04em;
            padding: 10px 14px; border-bottom: 1px solid var(--border);
          }
          tbody td { padding: 10px 14px; border-bottom: 1px solid var(--border); vertical-align: top; }
          tbody tr:last-child td { border-bottom: 0; }
          tbody tr:hover { background: rgba(99,102,241,.06); }
          td.num { color: var(--muted); text-align: end; width: 56px; white-space: nowrap; }
          a.loc { color: var(--link); text-decoration: none; word-break: break-all; }
          a.loc:hover { text-decoration: underline; }
          .tag {
            display: inline-block; background: var(--tag); color: var(--tag-fg);
            border-radius: 6px; padding: 1px 7px; margin: 0 4px 4px 0;
            font-size: 11px; font-weight: 600;
          }
          .foot { color: var(--muted); font-size: 12px; margin-top: 16px; }
        </style>
      </head>
      <body>
        <div class="wrap">
          <xsl:apply-templates select="s:sitemapindex"/>
          <xsl:apply-templates select="s:urlset"/>
          <p class="foot">GP247 &#183; This page is a styled view; search engines read the underlying XML.</p>
        </div>
      </body>
    </html>
  </xsl:template>

  <!-- Sitemap index: list of child sitemaps -->
  <xsl:template match="s:sitemapindex">
    <h1>Sitemap Index</h1>
    <p class="meta"><xsl:value-of select="count(s:sitemap)"/> sitemaps</p>
    <div class="card">
      <table>
        <thead>
          <tr><th>#</th><th>Sitemap</th><th>Last Modified</th></tr>
        </thead>
        <tbody>
          <xsl:for-each select="s:sitemap">
            <tr>
              <td class="num"><xsl:value-of select="position()"/></td>
              <td><a class="loc" href="{s:loc}"><xsl:value-of select="s:loc"/></a></td>
              <td><xsl:value-of select="s:lastmod"/></td>
            </tr>
          </xsl:for-each>
        </tbody>
      </table>
    </div>
  </xsl:template>

  <!-- URL set: list of page URLs with hreflang alternates -->
  <xsl:template match="s:urlset">
    <h1>Sitemap</h1>
    <p class="meta"><xsl:value-of select="count(s:url)"/> URLs</p>
    <div class="card">
      <table>
        <thead>
          <tr>
            <th>#</th><th>URL</th><th>Alternates</th>
            <th>Last Modified</th><th>Freq</th><th>Priority</th>
          </tr>
        </thead>
        <tbody>
          <xsl:for-each select="s:url">
            <tr>
              <td class="num"><xsl:value-of select="position()"/></td>
              <td><a class="loc" href="{s:loc}"><xsl:value-of select="s:loc"/></a></td>
              <td>
                <xsl:for-each select="xhtml:link">
                  <span class="tag"><xsl:value-of select="@hreflang"/></span>
                </xsl:for-each>
              </td>
              <td><xsl:value-of select="s:lastmod"/></td>
              <td><xsl:value-of select="s:changefreq"/></td>
              <td><xsl:value-of select="s:priority"/></td>
            </tr>
          </xsl:for-each>
        </tbody>
      </table>
    </div>
  </xsl:template>
</xsl:stylesheet>
