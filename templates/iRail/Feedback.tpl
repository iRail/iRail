<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Feedback</title>
<link rel="stylesheet" type="text/css" href="css/styles.css" />
</head>
<style type="text/css">
.feedbackInputTxt {
	border:1px solid #333;
	width:232px;
	height:23px;
	color:#333333;
	font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
	padding:2px;
	font-size:14px;
}
.feedbackSelect {
	width:232px;
	font-size:14px;
	color:#333333;
}
.feedbackMessage {
	border:1px solid #333;
	width:232px;
	height:70px;
	font-size:14px;
	color:#333333;
	font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
	padding:2px;
}
#submitLink {
	background-image:url(images/buttonOrange.png);
	width:274px;
	height:37px;
	display:block;
	margin:8px;	margin-left:10px; margin-bottom:0px;
	color:#333333;
	font-weight:bold;
	font-size:24px;
	text-align:center;
	text-decoration:none;
	padding-top:6px;
}
</style>
<body>

<div id="content">

    <div class="contentBlock">
        <div class="contentTitle">Your feedback is important!</div>
        <div class="contentText">We value your user experience. Tell us how you feel about the application, how we can improve it. If you encounter any difficulty or bug try to describe as precisely as possible in what context it occured.</div>
        <div class="contentText" style="font-weight:bold;">Please fill in this form:</div>
		<div class="contentText">
        <form action="feedback.php" method="post">
        <input type="hidden" name="mode" value="insert" />
        <table cellpadding="2" cellspacing="0" border="0">
        <tr><td>Name</td><td><input type="text" class="feedbackInputTxt" name="name" /></td></tr>
        <tr><td>E-mail</td><td><input type="text" class="feedbackInputTxt" name="email" /></td></tr>

        <tr><td>Category</td><td><select name="category" class="feedbackSelect">
        <option value="suggestion" selected="selected">Suggestion</option>
        <option value="bug">Bug</option>
        <option value="other">other</option>
        </select></td>

        </tr>
        <tr><td valign="top">Message</td><td><textarea name="message" class="feedbackMessage"></textarea></td>
        </tr>
        </table>
        </form>
        <a href="javascript:submitForm();" id="submitLink">SUBMIT</a>
        </div>
    </div>



</div>
</body>
</html>
