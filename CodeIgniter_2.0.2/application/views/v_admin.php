<!DOCTYPE html>
<html lang="da">
<head>
	<meta charset="utf-8">
	<title><?php echo $title;?></title>

<link rel="stylesheet" href="<?php echo base_url(); ?>ressources/kbhff_2012.css" type="text/css" media="screen" />
<link rel="stylesheet" href="/ressources/tab_layout.css" type="text/css" />

<?php echo isset($library_src) ? $library_src : ''; ?>
<script type="text/javascript" charset="utf-8" src="/ressources/jquery.form.js"></script>
<script type="text/javascript" src="/ressources/jquery/jquery.datepick.js"></script>
<script type="text/javascript" src="/ressources/jquery/jquery.datepick-da.js"></script>
<link rel="STYLESHEET" type="text/css" href="/ressources/1st.datepick.css">

<script language="JavaScript" type="text/javascript">
$(function() {
	$('#dato').datepick({ dateFormat: 'yyyy-mm-dd' });
	$('#dato2').datepick({ dateFormat: 'yyyy-mm-dd' });
	$('#dato3').datepick({ dateFormat: 'yyyy-mm-dd' });
	$('#dato4').datepick({ dateFormat: 'yyyy-mm-dd' });
<?php
	foreach ($bagdays as $bagday)
	{
		echo "	$('#dato" . $bagday['id'] . "').datepick({ dateFormat: 'yyyy-mm-dd' });\n";
	}
?>
});
</script>
<link rel="shortcut icon" href="/images/favicon.ico" />
</head>
<body>
<span id="tt">
<span ID="title" style="float: left;" onClick="window.location.href='/minside/';" title="Til min forside">K&Oslash;BENHAVNS<br>
F&Oslash;DEVAREF&AElig;LLESSKAB <span id="green">/ MEDLEMSSYSTEM</span></span>
<button class="form_button" style="float: right; margin-top:33px;" onClick="window.location.href='http://kbhff.dk';">G&Aring; TIL KBHFF</button>
<img src="/images/banner.jpg" alt="K&oslash;benhavns F&oslash;devare F&aelig;llesskab" width="800" height="188" border="0">
	<?php 
		echo getMenu(site_url(), $this->session->userdata('permissions'), $this->session->userdata('uid')); 
	?>
<h1><?php echo $heading;?></h1>
<?php
	if (isset($debug))
	{
		echo ('<!--' . $debug . '-->');
	}
?>

<div id="tabs" class="c-tabs no-js">
    <div class="c-tabs-nav">
        <a href="#" class="c-tabs-nav__link is-active">Medlemmer</a>
        <a href="#" class="c-tabs-nav__link">Afhentningsdage</a>
        <a href="#" class="c-tabs-nav__link">Løssalg</a>
        <a href="#" class="c-tabs-nav__link">Salg</a>
</div>


<!-- MEDLEMMER -->
<div class="c-tab is-active">
<div class="c-tab__content">                                                                                                                                  
<?php echo $content;?>
Hent Excel-liste over medlemmer:<br>
<?php echo $excelsel ?>
<br>

<a href="/mail">Massemail</a><br>
<br>
Rediger medlemmer<br>
<form action="/admin/medlemmer/" method="post">
Afdeling: <select name="division">
<?php echo $createsel ?>
</select>
<input type="submit" value="Rediger" class="form_button"><br>
</form>
<br>
Rediger tekst i brev til nye medlemmer:<br>
<?php echo $welcome ?>
<br>
Mail til nye medlemmer:<br>
<form action="/admin/nyemedlemmer/" method="post">
Afdeling: <select name="division"><option value="0">Alle afdelinger</a>
<?php echo $createsel ?>
</select>
siden: <input type="text" name="dato" id="dato4" size="10" maxlength="10">&nbsp;<input type="submit" value="Vis" class="form_button"><br>
</form>
                                   
<!--
Se et medlems ordrer<br>
<form action="/minside/mine_ordrer/" method="post">
Medlemsnummer: <input type="text" name="id"> <input type="submit" value="Se medlems ordrer" class="form_button"><br>
</form>
-->
</div>
</div>

<!-- AFHENTNINGSDAGE -->
<div class="c-tab">
<div class="c-tab__content">

Se afhentningsdage:<br>
<form action="/admin/liste/" method="post">
Afdeling: <select name="division">
<?php echo $createsel ?>
</select>
<input type="submit" value="Vis liste" class="form_button"><br>
</form>
<br>
Opret afhentningsdag:<br>
<form action="/admin/opret/" method="post">
Afdeling: <select name="division">
<?php echo $createsel ?>
</select>
Dag: <input type="text" name="dato" id="dato" size="10" maxlength="10"> Sidste ordre: <input type="text" name="dato2" id="dato2" size="10" maxlength="10"> <input type="text" name="tid2" id="tid2" value="18:30" size="5" maxlength="5">
<input type="submit" value="Opret" class="form_button"><br>
</form>
<br>
<?php
	foreach ($bagdays as $bagday)
	{
		echo 'Opret ' . $bagday['explained'] . 'dag:<br>'."\n";
		echo '<form action="/admin/opretf/' . $bagday['id'] . '" method="post">' ."\n";
		echo 'Dag: <select name="pickupday">'."\n";
		echo $createfsel;
		echo '</select>'."\n";
		echo 'Sidste ordre: <input type="text" name="dato'.$bagday['id'].'" id="dato'.$bagday['id'].'" size="10" maxlength="10"> <input type="text" name="tid'.$bagday['id'].'" id="tid'.$bagday['id'].'" value="18:30" size="5" maxlength="5">';
		echo '<input type="submit" value="Opret" class="form_button"><br>' ."\n";
		echo '</form>' ."\n";
		echo '<br>' ."\n";
	
	
	}
?>
</div>
</div>


<!-- LØSSALG -->
<div class="c-tab">
<div class="c-tab__content">
Opret ny løssalgsvare: <br>
<?php
    {
        echo '<form action="#" method="post">' ."\n";
        echo 'Type: <input type="text" name="type" id="type" size="10" maxlength="10">';
		echo '<input type="submit" value="Opret" class="form_button"><br>' ."\n";

		echo '</form>' ."\n";
    }
?>

<br> <br>
Tilføj løssalgvarer til afhentningsdag: <br>
<?php
	{
		echo 'Vælg afhentningsdag:<br>'."\n";
		echo '<form action="/admin/opretf/' . $bagday['id'] . '" method="post">' ."\n";
		echo 'Dag: <select name="pickupday">'."\n";
		echo $createfsel;
		echo '</select> <br><br>'."\n";
        echo 'Vælg vare:'."\n";
        echo '<select name="lossalgvare">' ."\n";
        echo '<option value="honning">Honning</option>'."\n";
        echo '</select> <br><br>'."\n";
		echo 'Sidste ordre: <input type="text" name="dato'.$bagday['id'].'" 
              id="dato'.$bagday['id'].'" size="10" maxlength="10"> 
              <input type="text" name="tid'.$bagday['id'].'" id="tid'.$bagday['id'].'" 
              value="18:30" size="5" maxlength="5">
              <input type="submit" value="Opret" class="form_button"><br>' ."\n";
		echo '</form>' ."\n";
		echo '<br>' ."\n";
	
	
	}
?>
</div>
</div>


<!-- SALG -->
<div class="c-tab">
<div class="c-tab__content">
Registrer kontant-ordrer:<br>
<?php echo $cashsel ?>

<br>
Dagens salg:<br>
<?php echo $dagenssalg ?>
<br>

<br>
Statistik over ikke-afhentede poser:<br>
<a href="/admin/ikke_afhentet">Ikke-afhentede poser</a><br>
</div>
</div>

<br>
</span>
<hr align="left" id="bottomhr">
<?php echo isset($script_head) ? $script_head : ''; ?>
<?php echo isset($script_foot) ? $script_foot : ''; ?>

<script src="/ressources/tabs.js"></script>
<script>
    var myTabs = tabs({
    el: '#tabs',
    tabNavigationLinks: '.c-tabs-nav__link',
    tabContentContainers: '.c-tab'
    });
    myTabs.init();      
</script>

</body>
</html>
