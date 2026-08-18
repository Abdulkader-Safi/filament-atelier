<?xml version="1.0" encoding="UTF-8"?>
<!--
    Makes /sitemap.xml readable in a browser.

    A sitemap with no stylesheet renders as a raw node tree, which reads as
    broken to anyone who opens it, and copies as a wall of unlabelled URLs and
    timestamps. Crawlers ignore this file entirely: the processing instruction
    that points here is only ever acted on by browsers.
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
            <title>Sitemap</title>
            <style>
                :root { color-scheme: light dark; }
                body {
                    margin: 0; padding: 2.5rem 1.5rem;
                    font: 15px/1.5 ui-sans-serif, system-ui, sans-serif;
                    color: #171717; background: #fff;
                }
                main { max-width: 70rem; margin: 0 auto; }
                h1 { margin: 0 0 .25rem; font-size: 1.5rem; letter-spacing: -0.01em; }
                p.meta { margin: 0 0 2rem; color: #737373; }
                table { width: 100%; border-collapse: collapse; }
                th {
                    text-align: start; font-size: .75rem; text-transform: uppercase;
                    letter-spacing: .06em; color: #737373; font-weight: 600;
                    padding: 0 .75rem .5rem 0; border-bottom: 1px solid #e5e5e5;
                }
                td { padding: .625rem .75rem .625rem 0; border-bottom: 1px solid #f5f5f5; vertical-align: top; }
                td.url { word-break: break-all; }
                a { color: #171717; text-decoration-color: #d4d4d4; text-underline-offset: 3px; }
                a:hover { text-decoration-color: currentColor; }
                .muted { color: #737373; font-variant-numeric: tabular-nums; white-space: nowrap; }
                .alt {
                    display: inline-block; margin-inline-end: .35rem; padding: .05rem .35rem;
                    border: 1px solid #e5e5e5; border-radius: .25rem;
                    font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: #525252;
                }
                @media (prefers-color-scheme: dark) {
                    body { color: #e5e5e5; background: #0a0a0a; }
                    th { color: #a3a3a3; border-bottom-color: #262626; }
                    td { border-bottom-color: #171717; }
                    a { color: #e5e5e5; text-decoration-color: #525252; }
                    .muted { color: #a3a3a3; }
                    .alt { border-color: #262626; color: #a3a3a3; }
                }
            </style>
        </head>
        <body>
            <main>
                <h1>Sitemap</h1>
                <p class="meta">
                    <xsl:value-of select="count(s:urlset/s:url)"/>
                    <xsl:text> URLs. This page is for you. Crawlers read the XML underneath it.</xsl:text>
                </p>

                <table>
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th>Last modified</th>
                            <th>Also in</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:for-each select="s:urlset/s:url">
                            <tr>
                                <td class="url">
                                    <a href="{s:loc}"><xsl:value-of select="s:loc"/></a>
                                </td>
                                <td class="muted">
                                    <xsl:choose>
                                        <xsl:when test="s:lastmod">
                                            <xsl:value-of select="substring(s:lastmod, 1, 10)"/>
                                        </xsl:when>
                                        <xsl:otherwise>&#8212;</xsl:otherwise>
                                    </xsl:choose>
                                </td>
                                <td>
                                    <xsl:for-each select="xhtml:link">
                                        <a class="alt" href="{@href}"><xsl:value-of select="@hreflang"/></a>
                                    </xsl:for-each>
                                </td>
                            </tr>
                        </xsl:for-each>
                    </tbody>
                </table>
            </main>
        </body>
        </html>
    </xsl:template>
</xsl:stylesheet>
