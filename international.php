<?php 
/* 	Copyright 2008, 2009, 2010 Yeri "Tuinslak" Tiete (http://yeri.be), and others

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

	http://blog.irail.be - http://irail.be
	
	source available at http://github.com/Tuinslak/iRail
*/

// international page

$from = $_COOKIE["intfrom"];
$to = $_COOKIE["intto"];
$lang = $_COOKIE["language"];

switch($lang) {
    case "EN":		$txt_from = "From:";
        $txt_to = "To:";
        $txt_date = "Date:";
        $txt_time = "Time:";
        $txt_arrive = "Arrival";
        $txt_depart = "Departure";
        break;
    case "NL":		$txt_from = "Van:";
        $txt_to = "Naar:";
        $txt_date = "Datum:";
        $txt_time = "Tijd:";
        $txt_arrive = "Aankomst";
        $txt_depart = "Vertrek";
        break;
    case "FR":      $txt_from = "De:";
        $txt_to = "Vers:";
        $txt_date = "Date:";
        $txt_time = "Heure:";
        $txt_arrive = "Arriv&#233;e";
        $txt_depart = "D&#233;part";
        break;
    case "DE":      $txt_from = "Von:";
        $txt_to = "Nach:";
        $txt_date = "Datum:";
        $txt_time = "Uhrzeit:";
        $txt_arrive = "Ankunft";
        $txt_depart = "Abfahrt";
        break;
    default:	$txt_from = "From:";
        $txt_to = "To:";
        $txt_date = "Date:";
        $txt_time = "Time:";
        $txt_arrive = "Arrival";
        $txt_depart = "Departure";
        break;
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <link href="css/mobile.css" rel="stylesheet" type="text/css" />
        <link rel="apple-touch-icon" href="./img/irail.png" />
        <link rel="shortcut icon" type="image/x-icon" href="./img/favicon.ico"/>
        <meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/>
        <meta name="keywords" content="nmbs, sncb, iphone, mobile, irail, irail.be, route planner"/>
        <meta name="language" content="en"/>
        <meta NAME="DESCRIPTION" CONTENT="NMBS/SNCB iPhone train route planner."/>
        <meta name="verify-v1" content="CKTzWOdgOxi/n81oG7ycuF/h8UKhX9OAhfmOA0nQ+Ts=" />
        <meta HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE"/>
        <title>
            iRail - International
        </title>

        <script language="javascript" type="application/x-javascript"> function switch_station() {
            var tmp = "";
            tmp = document.getElementById("from").value;
            document.getElementById("from").value = document.getElementById("to").value;
            document.getElementById("to").value = tmp;
        }</script>

        <script type="application/x-javascript">
        addEventListener('load', function() { setTimeout(hideAddressBar, 0); }, false);
        function hideAddressBar() { window.scrollTo(0, 1); }
        </script>
        <script language="javascript" type="text/javascript" src="./js/actb.js"></script>
        <script language="javascript" type="text/javascript" src="./js/common.js"></script>
        <script language="javascript" type="text/javascript">
        var data=new Array(
        'AALST',
        'AALST KERREBROEK',
        'AALTER',
        'AARSCHOT',
        'AARSELE',
        'ACREN',
        'AISEAU',
        'ALKEN',
        'AMAY',
        'AMPSIN',
        'ANDENNE',
        'ANGLEUR',
        'ANS',
        'ANSEREMME',
        'ANTOING',
        'ANTWERPEN BERCHEM',
        'ANTWERPEN CENTRAAL',
        'ANTWERPEN DAM',
        'ANTWERPEN NOORDERDOKKEN',
        'ANTWERPEN OOST',
        'ANTWERPEN ZUID',
        'ANZEGEM',
        'APPELTERRE',
        'ARCHENNES',
        'ARLON',
        'ASSE',
        'ASSESSE',
        'ATH',
        'AUVELAIS',
        'AYE',
        'AYWAILLE',
        'BAASRODE ZUID',
        'BALEGEM DORP',
        'BALEGEM ZUID',
        'BALEN',
        'BAMBRUGGE',
        'BARVAUX',
        'BAS OHA',
        'BASSE WAVRE',
        'BASTOGNE NORD',
        'BASTOGNE SUD',
        'BEAURAING',
        'BEERNEM',
        'BEERSEL',
        'BEGIJNENDIJK',
        'BEIGNEE',
        'BELLEM',
        'BELSELE',
        'BERCHEM SAINTE AGATHE',
        'BERINGEN',
        'BERLAAR',
        'BERTRIX',
        'BERZEE',
        'BEUZET',
        'BEVEREN',
        'BIERGES WALIBI',
        'BIERSET AWANS',
        'BILZEN',
        'BINCHE',
        'BISSEGEM',
        'BLANKENBERGE',
        'BLANMONT',
        'BLATON',
        'BLERET',
        'BOCKSTAEL',
        'BOECHOUT',
        'BOITSFORT',
        'BOKRIJK',
        'BOMAL',
        'BOOISCHOT',
        'BOOM',
        'BOONDAAL',
        'BOORTMEERBEEK',
        'BORDET',
        'BORGWORM',
        'BORNEM',
        'BOSVOORDE',
        'BOUSSU',
        'BOUWEL',
        'BRACQUEGNIES',
        'BRAINE L ALLEUD',
        'BRAINE LE COMTE',
        'BRESSOUX',
        'BRUGELETTE',
        'BRUGGE',
        'BRUGGE ST PIETERS',
        'BRUSSEL CENTRAAL',
        'BRUSSEL CONGRES',
        'BRUSSEL KAPELLEKERK',
        'BRUSSEL LUXEMBURG',
        'BRUSSEL NAT LUCHTHAVEN',
        'BRUSSEL NOORD',
        'BRUSSEL SCHUMAN',
        'BRUSSEL ZUID',
        'BRUXELLES CENTRAL',
        'BRUXELLES CHAPELLE',
        'BRUXELLES CONGRES',
        'BRUXELLES MIDI',
        'BRUXELLES NAT AEROPORT',
        'BRUXELLES NORD',
        'BRUXELLES LUXEMBOURG',
        'BRUXELLES SCHUMAN',
        'BUDA',
        'BUGGENHOUT',
        'BUIZINGEN',
        'BURST',
        'CALLENELLE',
        'CAMBRON CASTEAU',
        'CARLSBOURG',
        'CARNIERES',
        'CEROUX MOUSTY',
        'CHAPELLE DIEU',
        'CHAPOIS',
        'CHARLEROI OUEST',
        'CHARLEROI SUD',
        'CHASTRE',
        'CHATEAU DE SEILLES',
        'CHATELET',
        'CHENEE',
        'CINEY',
        'COMBLAIN LA TOUR',
        'COMINES',
        'COO',
        'COUILLET',
        'COUR SUR HEURE',
        'COURCELLES MOTTE',
        'COURRIERE',
        'COURT SAINT ETIENNE',
        'COUVIN',
        'DAVE SAINT MARTIN',
        'DE HOEK',
        'DE PANNE',
        'DE PINTE',
        'DEINZE',
        'DELTA',
        'DENDERLEEUW',
        'DENDERMONDE',
        'DIEGEM',
        'DIEPENBEEK',
        'DIEST',
        'DIKSMUIDE',
        'DILBEEK',
        'DINANT',
        'DOLHAIN GILEPPE',
        'DOORNIK',
        'DRONGEN',
        'DUFFEL',
        'DUINBERGEN',
        'ECAUSSINNES',
        'EDE',
        'EDINGEN',
        'EEKLO',
        'EICHEM',
        'EIGENBRAKEL',
        'EINE',
        'EKE NAZARETH',
        'EKEREN',
        'ENGHIEN',
        'ENGIS',
        'EPPEGEM',
        'ERBISOEUL',
        'EREMBODEGEM',
        'ERNAGE',
        'ERPE MERE',
        'ERPS KWERPS',
        'ERQUELINNES',
        'ERQUELINNES VILLAGE',
        'ESNEUX',
        'ESSEN',
        'ESSENE LOMBEEK',
        'ETTERBEEK',
        'EUPEN',
        'EVERE',
        'EZEMAAL',
        'FAMILLEUREUX',
        'FARCIENNES',
        'FAUX',
        'FEXHE LE HAUT CLOCHER',
        'FLAWINNE',
        'FLEMALLE GRANDE',
        'FLEMALLE HAUTE',
        'FLEURUS',
        'FLOREE',
        'FLOREFFE',
        'FLORENVILLE',
        'FLORIVAL',
        'FONTAINE VALMONT',
        'FORCHIES',
        'FOREST EST',
        'FOREST MIDI',
        'FORRIERES',
        'FRAIPONT',
        'FRAMERIES',
        'FRANCHIMONT',
        'FRANIERE',
        'FROYENNES',
        'GALMAARDEN',
        'GASTUCHE',
        'GAVERE ASPER',
        'GEDINNE',
        'GEEL',
        'GEMBLOUX',
        'GENDRON CELLES',
        'GENK',
        'GENLY',
        'GENT DAMPOORT',
        'GENT SINT PIETERS',
        'GENTBRUGGE',
        'GENVAL',
        'GERAARDSBERGEN',
        'GHLIN',
        'GLONS',
        'GODARVILLE',
        'GODINNE',
        'GONTRODE',
        'GOUVY',
        'GOUY LEZ PIETON',
        'GRAIDE',
        'GROENENDAAL',
        'GROOT BIJGAARDEN',
        'GRUPONT',
        'HAACHT',
        'HAALTERT',
        'HABAY',
        'HAININ',
        'HAL',
        'HALLE',
        'HAM SUR HEURE',
        'HAM SUR SAMBRE',
        'HAMBOS',
        'HAMOIR',
        'HANSBEKE',
        'HARCHIES',
        'HARELBEKE',
        'HAREN',
        'HAREN SUD',
        'HAREN ZUID',
        'HASSELT',
        'HAUTE FLONE',
        'HAVERSIN',
        'HAVRE',
        'HEIDE',
        'HEIST',
        'HEIST OP DEN BERG',
        'HEIZIJDE',
        'HEMIKSEM',
        'HENNUYERES',
        'HERENT',
        'HERENTALS',
        'HERNE',
        'HERSEAUX',
        'HERSTAL',
        'HERZELE',
        'HEUSDEN',
        'HEVER',
        'HEVERLEE',
        'HILLEGEM',
        'HOBOKEN POLDER',
        'HOEI',
        'HOEILAART',
        'HOFSTADE',
        'HOLLEKEN',
        'HONY',
        'HOURAING',
        'HOURPES',
        'HOUYET',
        'HOVE',
        'HUIZINGEN',
        'HUY',
        'IDDERGEM',
        'IDEGEM',
        'IEPER',
        'INGELMUNSTER',
        'IZEGEM',
        'JAMBES',
        'JAMBES EST',
        'JAMIOULX',
        'JEMAPPES',
        'JEMELLE',
        'JEMEPPE SUR MEUSE',
        'JEMEPPE SUR SAMBRE',
        'JETTE',
        'JURBISE',
        'JUSLENVILLE',
        'KALMTHOUT',
        'KAPELLE OP DEN BOS',
        'KAPELLEN',
        'KESSEL',
        'KIEWIT',
        'KIJKUIT',
        'KNOKKE',
        'KOKSIJDE',
        'KOMEN',
        'KONTICH',
        'KORTEMARK',
        'KORTENBERG',
        'KORTRIJK',
        'KWATRECHT',
        'LA HULPE',
        'LA LOUVIERE CENTRE',
        'LA LOUVIERE SUD',
        'LA ROCHE',
        'LABUISSIERE',
        'LANDEGEM',
        'LANDELIES',
        'LANDEN',
        'LANDSKOUTER',
        'LANGDORP',
        'LE CAMPINAIRE',
        'LEBBEKE',
        'LEDE',
        'LEIGNON',
        'LEMAN',
        'LEMBEEK',
        'LENS',
        'LEOPOLDSBURG',
        'LESSINES',
        'LEUVEN',
        'LEUZE',
        'LEVAL',
        'LIBRAMONT',
        'LICHTERVELDE',
        'LIEDEKERKE',
        'LIEGE GUILLEMINS',
        'LIEGE JONFOSSE',
        'LIEGE PALAIS',
        'LIER',
        'LIERDE',
        'LIERS',
        'LIGNY',
        'LILLOIS',
        'LIMAL',
        'LINKEBEEK',
        'LISSEWEGE',
        'LOBBES',
        'LODELINSART',
        'LOKEREN',
        'LOMMEL',
        'LONDERZEEL',
        'LONZEE',
        'LOT',
        'LOUVAIN LA NEUVE UNIV',
        'LUSTIN',
        'LUTTRE',
        'MAFFLE',
        'MALDEREN',
        'MANAGE',
        'MARBEHAN',
        'MARCHE EN FAMENNE',
        'MARCHE LES DAMES',
        'MARCHE LEZ ECAUSSINNES',
        'MARCHIENNE AU PONT',
        'MARCHIENNE ZONE',
        'MARIA AALTER',
        'MARIEMBOURG',
        'MARLOIE',
        'MASNUY SAINT PIERRE',
        'MAUBRAY',
        'MAZY',
        'MECHELEN',
        'MECHELEN NEKKERSPOEL',
        'MEISER',
        'MELKOUWEN',
        'MELLE',
        'MELREUX HOTTON',
        'MELSELE',
        'MENEN',
        'MERCHTEM',
        'MERELBEKE',
        'MERODE',
        'MERY',
        'MEVERGNIES ATTRE',
        'MILMORT',
        'MOENSBERG',
        'MOL',
        'MOLLEM',
        'MOMALLE',
        'MONS',
        'MONT SAINT GUIBERT',
        'MOORTSELE',
        'MORLANWELZ',
        'MORTSEL',
        'MORTSEL DEURNESTEENWEG',
        'MORTSEL OUDE GOD',
        'MOUSCRON',
        'MOUSTIER',
        'MUIZEN',
        'MUNKZWALM',
        'NAMECHE',
        'NAMUR',
        'NANINNE',
        'NATOYE',
        'NEERPELT',
        'NEERWINDEN',
        'NESSONVAUX',
        'NEUFCHATEAU',
        'NEUFVILLES',
        'NIEL',
        'NIEUWKERKEN WAAS',
        'NIJLEN',
        'NIJVEL',
        'NIMY',
        'NINOVE',
        'NIVELLES',
        'NOSSEGEM',
        'OBAIX BUZET',
        'OBOURG',
        'OKEGEM',
        'OLEN',
        'OOSTENDE',
        'OOSTKAMP',
        'OPWIJK',
        'OTTIGNIES',
        'OUD HEVERLEE',
        'OUDEGEM',
        'OUDENAARDE',
        'OVERPELT',
        'PALISEUL',
        'PAPIGNIES',
        'PECROT',
        'PEPINSTER',
        'PEPINSTER CITE',
        'PERUWELZ',
        'PHILIPPEVILLE',
        'PIETON',
        'POIX SAINT HUBERT',
        'PONT A CELLES',
        'PONT DE SERAING',
        'POPERINGE',
        'POULSEUR',
        'PROFONDSART',
        'PRY',
        'PUURS',
        'QUAREGNON',
        'QUEVY',
        'QUIEVRAIN',
        'REBAIX',
        'REMICOURT',
        'RHISNES',
        'RIVAGE',
        'RIXENSART',
        'ROESELARE',
        'RONET',
        'RONSE',
        'ROUX',
        'RUISBROEK',
        'SAUVEGARDE',
        'SAINT DENIS BOVESSE',
        'SAINT GHISLAIN',
        'SAINT JOB',
        'SART BERNARD',
        'SCHAARBEEK',
        'SCHAERBEEK',
        'SCHELDEWINDEKE',
        'SCHELLEBELLE',
        'SCHENDELBEKE',
        'SCHOONAARDE',
        'SCHULEN',
        'SCLAIGNEAUX',
        'SCLESSIN',
        'SERSKAMP',
        '\'S GRAVENBRAKEL',
        'SILLY',
        'S\'INAAI',
        'SINT AGATHA BERCHEM',
        'SINT DENIJS BOEKEL',
        'SINT GENESIUS RODE',
        'SINT GILLIS',
        'SINT JOB',
        'SINT JORIS WEERT',
        'SINT KATELIJNE WAVER',
        'SINT MARIABURG',
        'SINT MARTENS BODEGEM',
        'SINT NIKLAAS',
        'SINT TRUIDEN',
        'SLEIDINGE',
        'SOIGNIES',
        'SOLRE SUR SAMBRE',
        'SPA',
        'SPA-GERONSTERE',
        'STATTE',
        'STOCKEM',
        'SY',
        'TAMINES',
        'TEMSE',
        'TERHAGEN',
        'TERHULPEN',
        'TERNAT',
        'TESTELT',
        'THEUX',
        'THIEU',
        'THUIN',
        'THULIN',
        'TIELEN',
        'TIELT',
        'TIENEN',
        'TILFF',
        'TILLY',
        'TOLLEMBEEK',
        'TONGEREN',
        'TORHOUT',
        'TOURNAI',
        'TROIS PONTS',
        'TROOZ',
        'TUBIZE',
        'TURNHOUT',
        'UCCLE CALEVOET',
        'UCCLE STALLE',
        'UKKEL KALEVOET',
        'UKKEL STALLE',
        'VELTEM',
        'VERTRIJK',
        'VERVIERS CENTRAL',
        'VERVIERS PALAIS',
        'VEURNE',
        'VIANE MOERBEKE',
        'VICHTE',
        'VIELSALM',
        'VIJFHUIZEN',
        'VILLE POMMEROEUL',
        'VILLERS LA VILLE',
        'VILVOORDE',
        'VIRTON',
        'VISE',
        'VIVILLE',
        'VOROUX GOREUX',
        'VORST OOST',
        'VORST ZUID',
        'WAARSCHOOT',
        'WALCOURT',
        'WAREGEM',
        'WAREMME',
        'WATERLOO',
        'WATERMAAL',
        'WATERMAEL',
        'WAVER',
        'WAVRE',
        'WEERDE',
        'WELKENRAEDT',
        'WELLE',
        'WERVIK',
        'WESPELAAR TILDONK',
        'WETTEREN',
        'WEVELGEM',
        'WEZEMAAL',
        'WICHELEN',
        'WIJGMAAL',
        'WILDERT',
        'WILLEBROEK',
        'WONDELGEM',
        'YVES GOMEZEE',
        'YVOIR',
        'ZANDBERGEN',
        'ZAVENTEM',
        'ZEDELGEM',
        'ZEEBRUGGE DORP',
        'ZEEBRUGGE STRAND',
        'ZELE',
        'ZELLIK',
        'ZICHEM',
        'ZINGEM',
        'ZOTTEGEM',
        'ZWANKENDAMME',
        'ZWIJNDRECHT'
    );
        </script>
    </head>

    <body>
        <div class="container">
            <div class="toolbar anchorTop">
                <div class="title"><a href="international">iRail - International</a> </div>
                <div style="text-align:right;float:right;margin-right:10px"><a href="settings"><img style="vertical-align:middle;" border="0" src="./img/i.png" alt="Settings" /></a></div>
                <br />
                <div class="toolbar">
                    <div id="toolbar" style="height: 14px; padding: 2px; background-color: #efefef; text-align: center; color: #555; font-size: 12px; font-weight: normal;">
                        <?php echo date('l j/m/Y - H:i'); ?>
            <!--	/<span style="color:red;font-weight:bold;">/ NMBS site has probs.</span> -->
                    </div>

                    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
                        <tr>
                            <form name="search" method="post" action="intresults">
                                <td>
                                    <table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#FFFFFF" style="color:#000000";>
                                           <tr>
                                            <td width="70"><?php echo $txt_from; ?></td>
                                            <td colspan="2"><input name="from" type="text" id="from" AUTOCOMPLETE="OFF" value="<?php echo $from; ?>"/>
                                                <script language="javascript" type="text/javascript">var obj = actb(document.getElementById('from'),data); </script>
                                                <script language="javascript" type="text/javascript"> function reset_from() {
                                                document.getElementById("from").value = "";
                                            }</script>
                                                <a href="#" onclick="javascript:reset_from()"><img src="img/x.png" alt="X" border="0" /></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?php echo $txt_to; ?></td>
                                            <td colspan="2"><input name="to" type="text" id="to" AUTOCOMPLETE="OFF" value="<?php echo $to; ?>"/>
                                                <script language="javascript" type="text/javascript">var obj = actb(document.getElementById('to'),data); </script>
                                                <script language="javascript" type="text/javascript"> function reset_to() {
                                            document.getElementById("to").value = "";
                                        }</script>
                                                <a href="#" onclick="javascript:reset_to()"><img src="img/x.png" alt="X" border="0" /></a>
                                            </td>
                                        </tr>
                                        <tr><td colspan="3"><br /></td></tr>
                                        <tr>
                                            <td><?php echo $txt_date; ?></td>
                                            <td colspan="2">
                                                <select NAME="d">
                                                    <?php
                                                    for($i = 1; $i <= 31; $i++) {
                                                        if($i < 10) {
                                                            $number = "0" . $i;
                                                        }else {
                                                            $number = $i;
                                                        }
                                                        echo "<option VALUE=\"". $number ."\"";
                                                        if(date('d') == $number) {
                                                            echo "SELECTED";
                                                        }
                                                        echo ">".$number."</option>";
                                                    }
                                                    ?>
                                                </select>/<select NAME="mo">
                                                    <?php
                                                    for($i = 1; $i <= 12; $i++) {
                                                        if($i < 10) {
                                                            $number = "0" . $i;
                                                        }else {
                                                            $number = $i;
                                                        }
                                                        echo "<option VALUE=\"". $number ."\"";
                                                        if(date('m') == $number) {
                                                            echo "SELECTED";
                                                        }
                                                        echo ">".$number."</option>";
                                                    }
                                                    ?>
                                                </select>/<select NAME="y">
                                                    <option VALUE="<?php echo date('y'); ?>" <?php if(date('y') == '10') {
    echo "SELECTED";
} ?> ><?php echo date('Y'); ?></option>
                                                    <option VALUE="<?php echo date('y')+1; ?>" <?php if(date('y') == '11') {
    echo "SELECTED";
} ?> ><?php echo date('Y')+1; ?></option>
                                                </select></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo $txt_time; ?></td>
                                            <td colspan="2">
                                                <select name="h">
                                                    <?php
                                                    if(date('i') >= '50' && date('i') <= '59') {
                                                        $hour = date('H') + 1;
                                                    }else {
                                                        $hour = date('H');
                                                    }
                                                    for($i = 0; $i < 24; $i++) {
                                                        if($i < 10) {
                                                            $number = "0" . $i;
                                                        }else {
                                                            $number = $i;
                                                        }
                                                        echo "<option VALUE=\"". $number ."\"";
                                                        if($hour == $number) {
                                                            echo "SELECTED";
    }
    echo ">".$number."</option>";
}
?>
                                                </select>:<select NAME="m">
                                                    <option VALUE="00" <?php if(date('i') >= '50' && date('i') <= '59') {
    echo "SELECTED";
} ?> >00</option>
                                                    <option VALUE="10" <?php if(date('i') >= '00' && date('i') <= '09') {
    echo "SELECTED";
} ?> >10</option>
                                                    <option VALUE="20" <?php if(date('i') >= '10' && date('i') <= '19') {
    echo "SELECTED";
} ?> >20</option>
                                                    <option VALUE="30" <?php if(date('i') >= '20' && date('i') <= '29') {
    echo "SELECTED";
} ?> >30</option>
                                                    <option VALUE="40" <?php if(date('i') >= '30' && date('i') <= '39') {
    echo "SELECTED";
} ?> >40</option>
                                                    <option VALUE="50" <?php if(date('i') >= '40' && date('i') <= '49') {
    echo "SELECTED";
} ?> >50</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td colspan="2">
                                                <input type="radio" name="timesel" value="depart" checked/><span style="font-weight:normal;font-size:18px;"><?php echo $txt_depart; ?></span>
                                                <input type="radio" name="timesel" value="arrive"/><span style="font-weight:normal;font-size:18px;"><?php echo $txt_arrive; ?></span>
                                            </td>
                                        </tr>
                                        <tr><td colspan="3"></td></tr>
                                        <tr>
                                            <td colspan="3">
                                                <div style="text-align:center;">
                                                    <button type="submit" name="submit" value="Search">Search</button>
                                                    <button type="button" onclick="javascript:switch_station()">Switch</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr><td colspan="3"><br /></td></tr>
                                        <tr>
                                            <td colspan="3">
                                                <table width="100%" border="0" align="center" style="text-align:center;">
                                                    <tr>
                                                        <td class="footer" width="50%"><a href="national">Nat</a></td>
                                                        <td class="footer" width="50%"><a href="international">Int</a></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>

                                    </table>
                                </td>
                            </form>
                        </tr></table>
<?php
include 'footer.php';
?>
                </div></div></div>
    </body>
</html>