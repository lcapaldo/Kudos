<?php if (!defined('APPLICATION')) exit();

/**
 * Kudos Plugin version 2.0
 *
 * This plugin is supposed to provide basic "Kudos"
 *
 * @version 2.0
 * @author Claudio <raptxt@gmail.com>
 * @package Vanilla
 * @subpackage KudosPlugin
 *
 */

$PluginInfo['Kudos'] = array(
   'Name' => 'Kudos Plugin',
   'Description' => '+1 if you like it, -1 if not',
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
	'MobileFriendly' => TRUE,
	'HasLocale' => TRUE,
   'Version' => '3.0',
   'Author' => "Claudio",
   'AuthorEmail' => 'raptxt@gmail.com',
   'AuthorUrl' => 'http://facebook.com/keepitterron',
   'SettingsUrl' => '/dashboard/plugin/kudos',
   'SettingsPermission' => 'Garden.Settings.Manage',
);


/**
 * KudosPlugin class.
 *
 * This class does it all.
 */
class KudosPlugin extends Gdn_Plugin {

  /**
   * @var KudosModel
   */
  public $KudosModel;

  /**
   * Sets up database structure when plugin is enabled.
   *
   * @return void
   */
  public function Setup() {

    $this->Structure();

  }
   public function PluginController_Kudos_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('Kudos');
      $Sender->AddSideMenu('plugin/kudos');
      $Sender->Form = new Gdn_Form();
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }
   public function Controller_Index($Sender) {
      $Sender->Render($this->GetView('admin_l.php'));
      
      if ($_POST)
      {
      	$FormPostValues = $Sender->Form->FormValues();
      	$Kudoers = GetValue('Kudoers', $FormPostValues, '');
      	$KudosDelete = GetValue('KudosDelete', $FormPostValues, '');
      	$KudosDeleteNumber = GetValue('KudosDeleteNumber', $FormPostValues, '');
      	
      	if($Kudoers)
      	SaveToConfig('Plugins.Kudos.Enabled', TRUE);
      	else
      	RemoveFromConfig('Plugins.Kudos.Enabled');

      	if($KudosDelete)
      	SaveToConfig('Plugins.Kudos.Delete', TRUE);
      	else
      	RemoveFromConfig('Plugins.Kudos.Delete');
      	if($KudosDeleteNumber)
      	SaveToConfig('Plugins.Kudos.DeleteNumber', $KudosDeleteNumber);
      	else
      	RemoveFromConfig('Plugins.Kudos.DeleteNumber');
      	
      	Redirect('plugin/kudos');
      	      	
      }
      
   }
   public function Controller_Toggle($Sender) {
		
		// Enable/Disable Content Flagging
		if (Gdn::Session()->ValidateTransientKey(GetValue(1, $Sender->RequestArgs))) {
			if (C('Plugins.Kudos.Enabled')) {
				RemoveFromConfig('Plugins.Kudos.Enabled');
			} else {
				SaveToConfig('Plugins.Kudos.Enabled', TRUE);
			}
			Redirect('plugin/kudos');
		}
   }

  /**
   * Loads and instantiates the model.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();

    require_once (dirname(__FILE__) . '/KudosModel.php');
    $this->KudosModel = new KudosModel();
  }

  /**
   * A hook which handles kudoing html stuffs.
   *
   * @param Controller $Sender
   * @return void
   */
  public function DiscussionController_BeforeDiscussionRender_Handler(&$Sender) {
    $Sender->AddJSFile($this->GetResource('design/kudos.js', FALSE, FALSE));
    $Sender->AddCssFile($this->GetResource('design/kudos.css', FALSE, FALSE));
    if ($Sender->EventArguments['Type'] == 'Discussion')
    {
		if ($Sender->Data['Comments'] instanceof Gdn_DataSet)
        $this->KudosModel->PreloadKudos($Sender->Data['Comments']);
    }
  }
  public function DiscussionController_AfterCommentBody_Handler(&$Sender)
  {
  	$this->_DrawVotes($Sender);
  }
  public function PostController_AfterCommentBody_Handler(&$Sender)
  {
  	$this->_DrawVotes($Sender);
  }
  public function PostController_CommentOptions_Handler(&$Sender)
  {
  	$this->DiscussionController_CommentOptions_Handler($Sender);
  }
  public function DiscussionController_BeforeCommentDisplay_Handler(&$Sender)
  {
  	if (!C('Plugins.Kudos.Delete')) return;
  	$points = C('Plugins.Kudos.DeleteNumber') * -1;
  	if($this->KudosModel->CommentRate($Sender->EventArguments['Object']->CommentID) <= $points ) echo '<!--';
  }
  public function DiscussionController_AfterComment_Handler(&$Sender)
  {
  	if (!C('Plugins.Kudos.Delete')) return;
  	$points = C('Plugins.Kudos.DeleteNumber') * -1;
  	if($this->KudosModel->CommentRate($Sender->EventArguments['Object']->CommentID) <= $points) echo '-->';
  }


  /**
   * A hook which creates a fake action "kudos" on the discussion controller.
   * This action handles all the liking and unliking.
   *
   * @param Controller $Sender
   * @return void
   */
  public function DiscussionController_Kudos_Create(&$Sender)
  {
  	$Session = Gdn::Session();
  	$UserId = $Session->User->UserID;
  	$DiscussionID = GetValue(0, $Sender->RequestArgs);
  	$Action = GetValue(1, $Sender->RequestArgs);
  	$CommentID = GetValue(2, $Sender->RequestArgs);
  	
  	$this->CommentID = $CommentID;
  	$this->DiscussionID = $DiscussionID;
  	
  	$Options = Array('UserID' => $UserId, 'Action' => $Action);
  	if($CommentID)
  	{
  		$Options['CommentID'] = $CommentID;
  		$RedirectURL = ("discussion/{$DiscussionID}#Item_{$CommentID}");
  		$KudosID = $CommentID;
  	} else {
  		$Options['DiscussionID'] = $DiscussionID;
  		$RedirectURL = ("discussion/{$DiscussionID}");
  		$KudosID = $DiscussionID;
  	}
  	
  	if($Action == 1) $Sender->StatusMessage = $this->AddPoint($Options);
  	else $Sender->StatusMessage = $this->RemovePoint($Options);
  	
    if ($Sender->DeliveryType() == DELIVERY_TYPE_BOOL)
    {
    	$this->KudosModel->ClearSituation();
    	
    	$points = C('Plugins.Kudos.DeleteNumber') * -1;
    	$rate = $this->KudosModel->CommentRate($CommentID);
    	if( $rate <= $points && C('Plugins.Kudos.Delete'))
    	$Sender->JSON('CommentDelete', true);
    	
    	$Sender->JSON('CommentRate', $rate);
    	$Sender->JSON('KudosNewLink', $this->FormatKudos($DiscussionID, $CommentID));
    	$Sender->JSON('KudosKudos', $this->_DrawVotes($Sender, 1));
    	$Sender->JSON('KudosID', $KudosID);
    	$Sender->JSON('KudosItem', ($CommentID) ? 'Comment' : 'Discussion');
		$Sender->Render();
      	return;
    }

    Redirect($RedirectURL);

  }
  
  /**
   * Add or remove Kudos
   *
   * @param array $Options
   * @return string
   */
  public function AddPoint($Options)
  {
  	if(!$this->CanAdd($Options)) return T('Can\'t do it');
  	if(!$this->CanRemove($Options))
  	{
  		$Options['Action'] = 2;
  		$this->KudosModel->Delete($Options);
  		return T('Kudos removed');
  	}
  	$Options['DateUpdated'] = date('Y-m-d H:i:s');
  	$this->KudosModel->Insert($Options);
  	return T('Plasuans!');
  	
  }
  public function RemovePoint($Options)
  {
  	if(!$this->CanRemove($Options)) return T('Can\'t do it');
  	if(!$this->CanAdd($Options))
  	{
  		$Options['Action'] = 1;
  		$this->KudosModel->Delete($Options);
  		return T('Kudos removed');
  	}
  	$Options['DateUpdated'] = date('Y-m-d H:i:s');
  	$this->KudosModel->Insert($Options);
  	return T('Minusuans!');  	
  }
  
  /**
   * Checks if user has already gave kudos
   *
   * @param array $Options
   * @return bool
   */
  public function CanAdd($Options)
  {
  	$Kudos = $this->KudosModel->GetKudosFromOptions($Options);
  	foreach($Kudos as $K)
  	{
  		if($K->UserID == $Options['UserID'] && $K->Action == 1) return false;
  	}
  	return true;
  }
  public function CanRemove($Options)
  {
  	$Kudos = $this->KudosModel->GetKudosFromOptions($Options);
  	foreach($Kudos as $K)
  	{
  		if($K->UserID == $Options['UserID'] && $K->Action == 2) return false;
  	}
  	return true;
  }

  /**
   * Draws the Kudos users list.
   *
   * @param Controller $Sender
   * @param int $Json
   * @return void
   */
  public function _DrawVotes(&$Sender, $Json = false)
  {
  	if (!C('Plugins.Kudos.Enabled')) return;
  	if ($Sender->EventArguments['Type'] == 'Discussion')
  	{
  		$Kudos = $this->KudosModel->GetDiscussionKudos($Sender->EventArguments['Object']->DiscussionID);
  	} else {
  		$Kudos = $this->KudosModel->GetCommentKudos($Sender->EventArguments['Object']->CommentID);
  	}
  	
  	if(!$Sender->EventArguments['Object'])
  	{
  		if($this->CommentID) $Kudos = $this->KudosModel->GetCommentKudos($this->CommentID);
  		else $Kudos = $this->KudosModel->GetDiscussionKudos($this->DiscussionID);
  	}

	$Sender->ItemLoves = $Kudos['l'];
	$Sender->ItemHates = $Kudos['h'];
	$Display = $Sender->FetchView($this->GetView('likes.php'));
	unset($Sender->ItemLoves);
	unset($Sender->ItemHates);
	if($Json) return $Display;
	echo $Display;
  }

  /**
   * A hook which displays options for giving kudos to a comment.
   *
   * @param Controller $Sender
   * @return void
   */
  public function DiscussionController_CommentOptions_Handler(&$Sender) {
    $Sender->Options .= '<span class="Kudos">';
    $Sender->Options .= $this->FormatKudos($Sender->EventArguments['Object']->DiscussionID, $Sender->EventArguments['Object']->CommentID);
    $Sender->Options .= '</span>';
  }
  
  /**
   * This returns if user has either loved or hated a comment.
   *
   * @param array $Kudos
   * @return bool
   */
  private function loved($Kudos)
  {
  	$Session = Gdn::Session();
  	foreach($Kudos['l'] as $UserID => $g) if( $UserID == $Session->User->UserID ) return true;
  	return false;
  }
  private function hated($Kudos)
  {
  	$Session = Gdn::Session(); 
  	foreach($Kudos['h'] as $UserID => $g) if( $UserID == $Session->User->UserID ) return true;
  	return false;
  }

  /**
   * This formats the liking info.
   *
   * @param int $DiscussionID
   * @param int $CommentID
   * @return string
   */
  public function FormatKudos($DiscussionID, $CommentID = false)
  {
  	$Toolbar = '';
  	$Kudos = $this->KudosModel->GetDiscussionKudos($DiscussionID);
  	if($CommentID) $Kudos = $this->KudosModel->GetCommentKudos($CommentID);
  	
  	if($this->loved($Kudos)) $Toolbar .= '<b>+1</b>&nbsp;';
  	else $Toolbar .= '<a href="'.Url('discussion/kudos/'.$DiscussionID.'/1/'.$CommentID).'">+1</a>&nbsp;';
  	if($this->hated($Kudos)) $Toolbar .= '<b>-1</b>';
  	else $Toolbar .= '<a href="'.Url('discussion/kudos/'.$DiscussionID.'/2/'.$CommentID).'">-1</a>';
  	
  	if(count($Kudos['l']) || count($Kudos['h']))
  	$Toolbar .= '&nbsp;(+'.count($Kudos['l']).' / -'.count($Kudos['h']).' )';

    return $Toolbar;
  }

  /**
   * This creates the database structure for the plugin.
   * 
   * @return void
   */
  public function Structure() {

    $Structure = GDN::Structure();
    $Structure->Table('Kudos')
      ->Column('CommentID', 'int(11)', TRUE, 'key')
      ->Column('DiscussionID', 'int(11)', TRUE, 'key')
      ->Column('UserID', 'int(11)', FALSE, 'key')
      ->Column('Action', 'int(1)', TRUE, 'key')
      ->Column('DateUpdated', 'datetime')
      ->Set(TRUE);

  }

}