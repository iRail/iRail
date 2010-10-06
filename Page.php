<?php
/**
 * This is the start of all pages. It uses the template design pattern to
 * create the page: it will need a template chosen by the user.
 *
 * @author pieterc
 */
abstract class Page {
    private $template = "iRail";
    private $lang = "EN";

    protected $content;

    private $pageName;

    public function buildPage($pageName) {
        if(isset($_COOKIE["language"])) {
            $this->setLanguage($_COOKIE["language"]);
        }
        $this->pageName = $pageName;
        $this->loadTemplate();
        $this->loadContent();
        $this->loadI18n();
        $this->printPage();
    }

    public function setTemplate($template) {
        $this -> template = $template;
    }

    public function setLanguage($lang) {
        $this -> lang = $lang;
    }

    private function loadTemplate() {
        $tplPath = "templates/" . $this->template . "/" . $this -> pageName;
        if(file_exists($tplPath) ) {
            $this->content = file_get_contents($tplPath);
        }else {
            throw new Exception("Template doesn't exist");
        }
    }
    protected abstract function loadContent();
    private function loadI18n() {
        include_once("i18n/".$this->lang. ".php");
        foreach($i18n as $tag => $value) {
            $this -> content = str_ireplace("{i18n_".$tag."}", $value, $this->content);
        }
    }
    protected function printPage() {
        echo $this->content;
    }

}
?>
