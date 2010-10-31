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

    private $content;
    protected $page;

    private $pageName;

    private $globals = array(
        "iRail" => "iRail"

    );

    function __construct($page) {
        $this->globals["GoogleAnalytics"] = file_get_contents("includes/googleAnalytics.php") ;
        $this->globals["footer"] = file_get_contents("includes/footer.php");
        $this->page = $page;
        $this->content = "";
    }

    public function buildPage($pageName) {
        if(isset($_COOKIE["language"])) {
            $this->setLanguage($_COOKIE["language"]);
        }
        $this->pageName = $pageName;
        $this->loadTemplate();
        $this->loadContent();
        $this->loadGlobalVariables();
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

    private function loadGlobalVariables(){
        $this->substituteTagsInContent($this->globals);
    }

    private function loadContent(){
        $this->substituteTagsInContent($this->page);
    }

    private function substituteTagsInContent($tagMap){
        foreach($tagMap as $tag => $value){
            $this -> content = str_ireplace("{".$tag."}", $value, $this->content);
        }
    }

    private function loadI18n() {
        if($this->lang == "EN"){
            include_once("i18n/EN.php");
        }else if($this-> lang == "NL"){
            include_once("i18n/NL.php");
        }else if($this-> lang == "FR"){
            include_once("i18n/FR.php");
        }else if($this-> lang == "DE"){
            include_once("i18n/DE.php");
        }

        
        foreach($i18n as $tag => $value) {
            $this -> content = str_ireplace("{i18n_".$tag."}", $value, $this->content);
        }
    }
    private function printPage() {
        echo $this->content;
    }

}
?>
