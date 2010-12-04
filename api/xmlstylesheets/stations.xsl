<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html"/>
    <xsl:template match="/">
        <html>
            <head>
                <title>
                    <xsl:text>iRail.be : Stations</xsl:text>
                </title>
                <link rel="stylesheet" href="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.css" />
                <script src="http://code.jquery.com/jquery-1.4.3.min.js"></script>
                <script src="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.js"></script>
            </head>
            <body>
                <div data-role="page" id="config">
                    <div data-role="header">
                        <h1>Stations</h1>
                    </div>
                    <div data-role="content">
                        <xsl:apply-templates/>
                        <br/>
                        <p>
                            <xsl:text>© 2010 iRail - Yeri Tiete, Pieter Colpaert </xsl:text>
                            <a href="http://project.irail.be/cgi-bin/trac.fcgi/wiki/Contributors">and others</a>
                            <xsl:text>.</xsl:text>
                            <br/>
                            <xsl:text>No rights reserved. On API usage, feel free to attribute </xsl:text>
                            <a href="http://project.irail.be/">iRail.be</a>
                            <xsl:text>.</xsl:text>
                        </p>
                    </div>
                    <div data-role="footer">
                        <a href="http://iRail.be">go to iRail</a>
                        <a href="http://project.irail.be/cgi-bin/trac.fcgi/wiki/APIv1Draft">API Specification</a>
                    </div>
                </div>
            </body>

        </html>
    </xsl:template>

    <xsl:template match="stations">
        <table>
            <tr>
                <td>
                    <xsl:text>Station</xsl:text>
                    <br/>
                </td>
                <td>
                    <xsl:text>LocationY</xsl:text>
                </td>
                <td>
                    <xsl:text>LocationX</xsl:text>
                </td>
            </tr>
            <xsl:apply-templates select="station"/>
        </table>
    </xsl:template>
    <xsl:template match="station">
        <tr>
            <td>
                <xsl:value-of select="."/>
            </td>
            <td>
                <xsl:value-of select="@locationY"/>:
            </td>
            <td>
                <xsl:value-of select="@locationX"/>:
            </td>
        </tr>
    </xsl:template>

</xsl:stylesheet>