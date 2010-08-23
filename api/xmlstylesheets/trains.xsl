<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html"/>
    <xsl:template match="/">
        <html>
            <head>
                <title><xsl:text>iRail.be : </xsl:text><xsl:value-of select="/connections/connection[@id=1]/departure/station"/><xsl:text> - </xsl:text><xsl:value-of select="/connections/connection[@id=1]/arrival/station"/></title>
            </head>
            <body>
                <xsl:apply-templates/><br/>
                <p>
                    <xsl:text>© 2010 iRail - Yeri Tiete, Pieter Colpaert </xsl:text><a href="http://project.irail.be/cgi-bin/trac.fcgi/wiki/Contributors">and others</a><xsl:text>.</xsl:text>
                    <br/>
                    <xsl:text>No rights reserved. On API usage, feel free to attribute </xsl:text><a href="http://project.irail.be/">iRail.be</a><xsl:text>.</xsl:text>
                </p>

            </body>
        </html>
    </xsl:template>

    <xsl:template match="connections">
        <table>
            <tr>
                <td><xsl:text>#</xsl:text></td>
                <td><xsl:text>Date</xsl:text></td>
                <td><xsl:text>From</xsl:text></td>
                <td><xsl:text>Departure</xsl:text></td>
                <td><xsl:text>To</xsl:text></td>
                <td><xsl:text>Arrival</xsl:text></td>
                <td><xsl:text>Duration</xsl:text></td>
                <td><xsl:text>Delays</xsl:text></td>
                <td><xsl:text>Trains used</xsl:text></td>
            </tr>
            <xsl:apply-templates select="connection"/>
        </table>
    </xsl:template>
    <xsl:template match="connection">
        <tr>
                <td><xsl:value-of select="@id"/></td>
                <td><xsl:value-of select="departure/date"/></td>
                <td><xsl:value-of select="departure/station"/></td>
                <td><xsl:value-of select="departure/time"/></td>
                <td><xsl:value-of select="arrival/station"/></td>
                <td><xsl:value-of select="arrival/time"/></td>
                <td><xsl:value-of select="duration"/></td>
                <td>
                <xsl:choose>
                    <xsl:when test="delay &gt; 0"><xsl:text>Yes!</xsl:text></xsl:when>
                    <xsl:when test="delay &lt; 1"><xsl:text>none</xsl:text></xsl:when>
                </xsl:choose>
                </td>
                <td><xsl:apply-templates select="trains"/></td>
        </tr>
    </xsl:template>

    <xsl:template match="trains">
        <xsl:for-each select="train">
            <xsl:value-of select="."/><br/>
        </xsl:for-each>
    </xsl:template>

</xsl:stylesheet>