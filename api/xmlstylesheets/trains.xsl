<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html"/>
    <xsl:template match="/">
        <html>
            <head>
                <title>iRail.be : <xsl:value-of select="departure/station"/></title>
            </head>
            <body>
                <xsl:apply-templates/>
            </body>
        </html>
    </xsl:template>

    <xsl:template match="connections">
        <table>
            <tr>
                <td>From</td>
                <td>Departure</td>
                <td>To</td>
                <td>Arrival</td>
                <td>Duration</td>
                <td>Delays</td>
                <td>Trains used</td>
            </tr>
            <xsl:apply-templates select="connection"/>
        </table>
    </xsl:template>
    <xsl:template match="connection">
        <tr>
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