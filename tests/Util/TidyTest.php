<?php

namespace Tests\Util;

use Irail\Util\Tidy;
use Tests\TestCase;

class TidyTest extends TestCase
{
    public function testRepairXml_rssFeed_shouldReturnData()
    {
        $sourceData = file_get_contents(__DIR__ . '/TidyTest_rss_feed.xml');
        $repairedData = Tidy::repairXml($sourceData);
        $this->assertNotEmpty($repairedData);
    }

    public function testRepairHtml_sampleHtmlData_shouldReturnData()
    {
        $sourceData = file_get_contents(__DIR__ . '/TidyTest_html_data.html');
        $repairedData = Tidy::repairHtmlRemoveJavascript($sourceData);
        $this->assertNotEmpty($repairedData);
        $this->assertStringContainsString("<tr class=\"stboard\">", $repairedData);
        $this->assertStringContainsString(
            "<a onclick=\"loadDetails('http://www.belgianrail.be/jp/nmbs-realtime/traininfo.exe/nn/201039/84558/889486/377730/80?ld=std&amp;AjaxMap=CPTVMap&amp;date=25/01/2024&amp;station_evaId=8821006&amp;station_type=dep&amp;input=8821006&amp;boardType=dep&amp;time=08:00&amp;maxJourneys=50&amp;dateBegin=25/01/2024&amp;dateEnd=25/01/2024&amp;selectDate=&amp;dirInput=&amp;backLink=sq&amp;ajax=1&amp;divid=updatejourney_7','updatejourney_7','rowjourney_7'); return false;\" href=\"http://www.belgianrail.be/jp/nmbs-realtime/traininfo.exe/nn/201039/84558/889486/377730/80?ld=std&amp;AjaxMap=CPTVMap&amp;date=25/01/2024&amp;station_evaId=8821006&amp;station_type=dep&amp;input=8821006&amp;boardType=dep&amp;time=08:00&amp;maxJourneys=50&amp;dateBegin=25/01/2024&amp;dateEnd=25/01/2024&amp;selectDate=&amp;productsFilter=5:1111111&amp;dirInput=&amp;backLink=sq&amp;\"><img src=\"/jp/hafas-res/img/products/ic.png\" alt=\"IC  9212\" /> IC  9212</a>",
            $repairedData
        );
    }
}
