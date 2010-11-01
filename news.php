<?php

/**
 *  Copyright 2008, 2009, 2010 Yeri "Tuinslak" Tiete (http://yeri.be), and others
  Copyright 2010 Pieter Colpaert (pieter@irail.be - http://bonsansnom.wordpress.com)

  This file is part of iRail.

  iRail is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  iRail is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with iRail.  If not, see <http://www.gnu.org/licenses/>.

  http://project.irail.be - http://irail.be

  source available at http://github.com/Tuinslak/iRail


 * @author pieterc
 */
include("Page.php");

class News extends Page {

    function __construct() {
        $this->page = array(
            "title" => "iRail.be",
            "subtitle" => "{i18n_news}"
        );
        $this->outNews($this->fetchNews());
    }

    private function fetchNews() {
        $xml = "http://www.railtime.be/website/RSS/RssInfoBar_nl.xml";
        $xmlDoc = new DOMDocument();
        $xmlDoc->load($xml);
        $x = $xmlDoc->getElementsByTagName('item');
        $news = array();
        $size = 10;
        if($x->length < $size){
            $size = $x-> length;
        }
        for ($i = 0; $i < $size; $i++) {
            $title = $x->item($i)->getElementsByTagName('title')
                            ->item(0)->childNodes->item(0)->nodeValue;
            $desc = $x->item($i)->getElementsByTagName('description')
                            ->item(0)->childNodes->item(0)->nodeValue;
            $news[$title] = $desc;
        }
        return $news;
    }

    private function outNews($news) {

        $this->page["newslist"] .= '<ul data-role="listview">';
        $i = 0;
        if(sizeof($news) > 0){
        foreach ($news as $title => $desc) {
            $i++;
            $this-> page["newslist"] .= '<li data-role="list-divider">' . $title .
            '<span class="ui-li-count">' . $i . '</span></li>
			<li>
				<p>' . $desc . '</p>
			</li>';
        }
        } else {
            $this-> page["newslist"] .= '<li data-role="list-divider">No news<span class="ui-li-count">0</span></li>
			<li>
				<p>There is absolutely nothing for you to worry about</p>
			</li>';

        }
        $this->page["newslist"] .= '</ul>';
    }

}

//__MAIN__

$page = new News();
if (isset($_COOKIE["language"])) {
    $page->setLanguage($_COOKIE["language"]);
}
if (isset($_GET["output"])) {
    $page->setTemplate($_GET["output"]);
}
$page->buildPage("News.tpl");
?>
