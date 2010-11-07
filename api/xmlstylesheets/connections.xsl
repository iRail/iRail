<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html"/>
    <xsl:template match="/">
        <html>
            <head>
                <title>
                    <xsl:text>iRail.be : </xsl:text>
                    <xsl:value-of select="/connections/connection[@id=0]/departure/station"/>
                    <xsl:text> - </xsl:text>
                    <xsl:value-of select="/connections/connection[@id=0]/arrival/station"/>
                </title>
                <link rel="stylesheet" href="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.css" />
                <script src="http://code.jquery.com/jquery-1.4.3.min.js"></script>
                <script src="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.js"></script>

            </head>
            <body>
                <div data-role="page" id="config">
                    <div data-role="header">
                        <h1>
                            <xsl:value-of select="/connections/connection[@id=0]/departure/station"/>
                            <xsl:text> - </xsl:text>
                            <xsl:value-of select="/connections/connection[@id=0]/arrival/station"/>
                        </h1>
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

    <xsl:template match="connections">
        <table>
            <tr>
                <td>
                    <xsl:text>#</xsl:text>
                </td>
                <td>
                    <xsl:text>Dep/Arr (UTC)</xsl:text>
                    <br/>
                </td>
                <td>
                    <xsl:text>Platform</xsl:text>
                </td>
                <td>
                    <xsl:text>Duration</xsl:text>
                </td>
                <td>
                    <xsl:text>Delays</xsl:text>
                </td>
                <td>
                    <xsl:text>Train Dep/Arr</xsl:text>
                </td>
            </tr>
            <xsl:apply-templates select="connection"/>
        </table>
    </xsl:template>
    <xsl:template match="connection">
        <tr>
            <td>
                <xsl:value-of select="@id"/>
            </td>
            <td>
                <xsl:value-of select="floor(departure/time div 3600 mod 24)"/>:
                <xsl:value-of select="departure/time div 60 mod 60"/>
                <br/>
                <xsl:value-of select="floor(arrival/time div 3600 mod 24)"/>:
                <xsl:value-of select="arrival/time div 60 mod 60"/>
            </td>
            <td>
                <xsl:value-of select="depart/platform"/>
                <br/>
                <xsl:value-of select="arrival/platform"/>
            </td>
            <td>
                <xsl:value-of select="floor(duration div 3600)"/>:
                <xsl:value-of select="duration div 60 mod 60"/>
            </td>
            <td>
                <xsl:choose>
                    <xsl:when test="departure/@delay &gt; 0">departure/@delay</xsl:when>
                    <xsl:when test="departure/@delay &lt; 1">
                        <xsl:text>none</xsl:text>
                    </xsl:when>
                </xsl:choose>
            </td>
            <td>
                <xsl:value-of select="substring(departure/vehicle, 9)"/>
                <br/>
                <xsl:value-of select="substring(arrival/vehicle,9)"/>
            </td>
        </tr>
    </xsl:template>

</xsl:stylesheet>