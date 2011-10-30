<?php


/**
 * KudosModel class.
 * Only defines a few shorthands.
 */
class KudosModel extends Gdn_Model {

  private $Kudos = array();
  public $kudos = array();

  public function __construct() {
    parent::__construct('Kudos');
  }
  
  public function PreloadKudos(Gdn_DataSet $Comments)
  {  
    $cache = Array();
    
    foreach($Comments as $v) $cache[] = $v->CommentID;

    if (!empty($cache)) {
      $Kudos = $this->SQL->Select('u.UserID, p.CommentID, p.Action, u.Name')->From($this->Name . ' p')
      ->Join('User u', 'u.UserID = p.UserID')
      ->WhereIn('CommentID', $cache)
      ->OrderBy('CommentID')->Get()->Result(DATASET_TYPE_OBJECT);
      foreach($Kudos as $k)
      {
      	$this->Kudos['c'][$k->CommentID][] = $k;
      }
     }
  }
  
  public function ClearSituation()
  {
  	$this->Kudos = array();
  }
  
  public function GetKudosFromOptions($Options)
  {
  	if($Options['CommentID'])
  	{
  		$this->GetCommentKudos($Options['CommentID']);
  		return $this->Kudos['c'][$Options['CommentID']];
  	} else {
  		$this->GetDiscussionKudos($Options['DiscussionID']);
  		return $this->Kudos['d'];
  	}
  	
  }
  
  public function CommentRate($CommentID)
  {
  	$Kudos = $this->GetCommentKudos($CommentID);
  	return ( count($Kudos['l']) - count($Kudos['h']) );
  }
  
  public function GetCommentKudos($CommentID)
  {
  	if(!$this->Kudos['c'][$CommentID])
  	{
	$Kudos = $this->SQL->Select()->From($this->Name . ' p')
	->Join('User u', 'u.UserID = p.UserID')
	->Where(Array('CommentID' => $CommentID))
	->OrderBy('CommentID')
	->Get()->Result(DATASET_TYPE_OBJECT);
	$this->Kudos['c'][$CommentID] = $Kudos;
	} else {
	$Kudos = $this->Kudos['c'][$CommentID];
	}

	$loves = array();
	$hates = array();
	foreach($Kudos as $K)
	{
		if($K->Action == 1) $loves[$K->UserID] = $K->Name;
		if($K->Action == 2) $hates[$K->UserID] = $K->Name;
	}

	return array('l' => $loves, 'h' => $hates);
  }
  
  public function GetDiscussionKudos($DiscussionID)
  {
  	if(!$this->Kudos['d'])
  	{
	$this->Kudos['d'] = $this->SQL->Select()->From($this->Name . ' p')
	->Join('User u', 'u.UserID = p.UserID')
	->Where(Array('DiscussionID' => $DiscussionID))
	->OrderBy('DiscussionID')
	->Get()->Result(DATASET_TYPE_OBJECT);
	}
	$loves = array();
	$hates = array();
	foreach($this->Kudos['d'] as $K)
	{
		if($K->Action == 1) $loves[$K->UserID] = $K->Name;
		if($K->Action == 2) $hates[$K->UserID] = $K->Name;
	}
	
	return array('l' => $loves, 'h' => $hates);
	
  }

}