<?php
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/tvrage.php");
require_once(WWW_DIR."/lib/category.php");

$releases = new Releases;
$tvrage = new TvRage;
$cat = new Category;

if (!$users->isLoggedIn())
	$page->show403();
	
if (isset($_GET["id"]) && ctype_digit($_GET['id']))
{
	$category = -1;
	if (isset($_REQUEST["t"]) && ctype_digit($_REQUEST["t"]))
		$category = $_REQUEST["t"];
	
	$catarray = array();
	$catarray[] = $category;
	
	$rel = $releases->searchbyRageId($_GET["id"], '', '', 0, 1000, "", $catarray, -1);
	$rage = $tvrage->getByRageID($_GET['id']);
	
	if (!$rel || !$rage)
		$page->show404();
	
	//sort releases by season, episode, date posted
	$season = $episode = $posted = array();
	foreach($rel as $rlk=>$rlv)
	{
		$season[$rlk] = $rlv['season'];
		$episode[$rlk] = $rlv['episode'];
		$posted[$rlk] = $rlv['postdate'];
	}
	array_multisort($season, SORT_DESC, $episode, SORT_DESC, $posted, SORT_DESC, $rel);
	
	$seasons = array();
	foreach ($rel as $r)
		$seasons[$r['season']][$r['episode']][] = $r;
	
	$page->smarty->assign('seasons', $seasons);
	$page->smarty->assign('rage', $rage);
	
	//get series name(s), description, country and genre
	$seriesnames = $seriesdescription = $seriescountry = $seriesgenre = array();
	foreach($rage as $r)
	{
		$seriesnames[] = $r['releasetitle'];
		if (!empty($r['description']))
			$seriesdescription[] = $r['description'];
		
		if (!empty($r['country']))
			$seriescountry[] = $r['country'];
			
		if (!empty($r['genre']))
			$seriesgenre[] = str_replace('|', ' - ', $r['genre']);
	}
	$seriesnames = implode('/', array_map("trim", $seriesnames));
	$page->smarty->assign('seriesnames', $seriesnames);
	$page->smarty->assign('seriesdescription', array_shift($seriesdescription));
	$page->smarty->assign('seriescountry', array_shift($seriescountry));
	$page->smarty->assign('seriesgenre', array_shift($seriesgenre));

	$page->title = "$seriesnames";
	$page->meta_title = "View TV Series $seriesnames";
	$page->meta_keywords = "view,series,tv,show,description,details";
	$page->meta_description = "View $seriesnames Series";
	
	if ($category != -1)
	{
		$cdata = $cat->getById($category);
		$catid = $category;
	} else {
		$cdata = array('title'=>'');
		$catid = '';
	}
	$page->smarty->assign('catname',$cdata["title"]);
	$page->smarty->assign('category',$catid);
	
	$page->content = $page->smarty->fetch('viewseries.tpl');
	$page->render();
}
else
{
	$letter = (isset($_GET["id"]) && preg_match('/^(0\-9|[A-Z])$/i', $_GET['id'])) ? $_GET['id'] : '';
	
	$ragename = (isset($_GET['title']) && !empty($_GET['title'])) ? $_GET['title'] : '';
	
	$masterserieslist = $tvrage->getSeriesList($letter, $ragename);
	
	//if (!$masterserieslist)
		//$page->show404();
	
	$page->title = 'Series List';
	$page->meta_title = "View Series List";
	$page->meta_keywords = "view,series,tv,show,description,details";
	$page->meta_description = "View Series List";
	
	$serieslist = array();
	foreach ($masterserieslist as $s)
	{
		if (preg_match('/^[0-9]/', $s['ragetitle'])) {
			$thisrange = '0-9';
		} else {
		 	preg_match('/([A-Z]).*/i', $s['ragetitle'], $matches);
		 	$thisrange = strtoupper($matches[1]);
		}
		$serieslist[$thisrange][] = $s;
	}
	ksort($serieslist);
	
	$page->smarty->assign('serieslist', $serieslist);
	$page->smarty->assign('seriesrange', range('A', 'Z'));
	$page->smarty->assign('seriesletter', $letter);
	$page->smarty->assign('ragename', $ragename);
	
	$tmpcats = $cat->getChildren(Category::CAT_PARENT_TV, true, $page->userdata["categoryexclusions"]);
	$categories = array();
	foreach($tmpcats as $c)
		$categories[$c['ID']] = $c['title'];

	$page->smarty->assign('cat_ids', array_keys($categories));
	$page->smarty->assign('cat_names', $categories);
	
	$page->content = $page->smarty->fetch('viewserieslist.tpl');
	$page->render();
}

?>
