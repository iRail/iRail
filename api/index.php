<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title>iRail.be API</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body>
      <h1>Welcome developer</h1>
      <p>This is the unofficial API for the SNCB/NMBS in Belgium. If you want to implement train schedules in your application you're at the right place.</p>
      <h1>Usage</h1>
      <h2>1. List all stations</h2>
      <p>Request: http://api.irail.be/stations.php</p>
      <h2>2. Trainschedule</h2>
      <p>Request: http://api.irail.be/trains.php?to=STATION1&amp;from=STATION2  |||OPTIONAL: &amp;date=01-01-10&amp;time=15:40&amp;results=4&amp;lang=NL</p>
      <h1>More up to date information on our official wiki</h1>
      <p><a href="http://wiki.github.com/Tuinslak/iRail/api">here (http://wiki.github.com/Tuinslak/iRail/api)</a></p>
      <h1>Grab the code</h1>
      <p>The code of this website is free and open source. It is licensed under the GPL v 3 and you can find it at github: </p>
      <p><a href="http://github.com/Tuinslak/iRail/">here (http://github.com/Tuinslak/iRail/)</a></p>
      <h1>Authors</h1>
      <p>Copyright &copy; 2008, 2009, 2010 Yeri "Tuinslak" Tiete (http://yeri.be)</p>
      <p>Copyright &copy; 2010 Pieter Colpaert (http://bonsansnom.wordpress.com) - pieter@irail.be</p>
      <h1>Bugs</h1>
      <p>Nobody is perfect. Writing a webscraper is not the most fault tolerant system you would ever write. Since we think sooner or later NMBS will release an API of their own, we didn't mind writing clean code. So before you dive in, don't mind the mess.</p>
      <p>On the other hand if you noticed a flaw, something that just doesn't work at the outside, something that might be important, ... feel free to report an issue at the github page (↑↑)</p>
      <p><br/><i> Thanks for your interest and happy hacking :-)</i><br/><br/>
          <i>Some rights reserved - CC By-Sa - <img src="http://mirrors.creativecommons.org/presskit/buttons/88x31/png/by-sa.png" width="88" height="33" alt="Creative Commons Attribution, Share alike"/></i>
      </p>
  </body>
</html>
<?php
// GA stats; try to include it in most files please
include '../includes/googleAnalytics.php';
?>
