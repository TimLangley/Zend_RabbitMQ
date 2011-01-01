<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="2.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:fn="http://www.w3.org/2005/xpath-functions">
  <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>
  <xsl:param name="outputDir">.</xsl:param>

  <xsl:template match="testsuites">
    <xsl:apply-templates select="testsuite"/>
  </xsl:template>

  <xsl:template match="testsuite">
    <xsl:if test="testcase">
      <xsl:variable name="outputName" select="./@name"/>
      <xsl:result-document href="file:///{$outputDir}/{$outputName}.xml" method="xml">
        <xsl:copy-of select="."/>
      </xsl:result-document>
    </xsl:if>

    <xsl:apply-templates select="testsuite"/>
  </xsl:template>
</xsl:stylesheet>