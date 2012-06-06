<?php
/*
* CKFinder 2 pixlr
* ========
* http://code.google.com/p/ckfinder2pixlr
* Copyright (C) 2010 - 2012 - Micah J. Murray
*
*
* CKFinder extension: Integrate the pixlr image editing application utilizing the pixlr.com API
*/

//grab the Framework Session ID from the cookie.  You can comment out this line if not using framework (such as Kohanna or CakePHP)
#session_id($_COOKIE['session']);

// Make sure $_SESSION is started
if (!isset($_SESSION)) session_start(); 

// A simple protection against calling this file directly.
if (!defined('IN_CKFINDER')) exit;

// Include base XML command handler
require_once CKFINDER_CONNECTOR_LIB_DIR . "/CommandHandler/XmlCommandHandlerBase.php";
 
// Since we will send a XML response, we'll reuse the XmlCommandHandler
class CKFinder_Connector_CommandHandler_Pixlr extends CKFinder_Connector_CommandHandler_XmlCommandHandlerBase
{
    // The buildXml method is used to construct an XML response
    function buildXml()
    {  
        // A "must have", checking whether the connector is enabled and the basic parameters (like current folder) are safe.
        $this->checkConnector();
        $this->checkRequest();
 
        // Checking ACL permissions, we're just getting an information about a file, so FILE_VIEW permission seems to be ok.
        if (!$this->_currentFolder->checkAcl(CKFINDER_CONNECTOR_ACL_FILE_VIEW)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_UNAUTHORIZED);
        }
 
        // Make sure we actually received a file name
        if (!isset($_GET["fileName"])) {
			$this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_NAME);
        }
 
        $fileName = CKFinder_Connector_Utils_FileSystem::convertToFilesystemEncoding($_GET["fileName"]);
        $resourceTypeInfo = $this->_currentFolder->getResourceTypeConfig();
 
        // Use the resource type configuration object to check whether the extension of a file to check is really allowed.
        if (!$resourceTypeInfo->checkExtension($fileName)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_EXTENSION);
        }
 
        // Make sure that the file name is really ok and has not been sent by a hacker
        if (!CKFinder_Connector_Utils_FileSystem::checkFileName($fileName) || $resourceTypeInfo->checkIsHiddenFile($fileName)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_INVALID_REQUEST);
        }
 
        $filePath = CKFinder_Connector_Utils_FileSystem::combinePaths($this->_currentFolder->getServerPath(), $fileName);
 
        if (!file_exists($filePath) || !is_file($filePath)) {
            $this->_errorHandler->throwError(CKFINDER_CONNECTOR_ERROR_FILE_NOT_FOUND);
        }
		
		//set session values to be check by process.php upon returning from pixlr.com
		$maketoken = md5(session_id());
		$_SESSION['pixlr']['token'] = substr($maketoken,0,16);
		$_SESSION['pixlr']['ImagePath'] = $filePath;
		$_SESSION['pixlr']['clientImagePath'] = $this->_currentFolder->getUrl(); // ie: /CMSfiles/images/subdirectory/
		$_SESSION['pixlr']['fileName'] = $fileName;
		$_SESSION['pixlr']['return'] = $_SERVER['HTTP_REFERER']; 
		 $thumbFolder = $this->_currentFolder->getThumbsServerPath();
		$_SESSION['pixlr']['thumbLocation'] = $thumbFolder . $fileName;

		//get the client-side absolute path to the image being edited
		$absolute_filePath = "http://".$_SERVER['HTTP_HOST'].$_SESSION['pixlr']['clientImagePath'].$_SESSION['pixlr']['fileName'];
		
		//get teh directory this plugin is in so we can return to the process.php script in this folder
		$pluginFolder = dirname(__FILE__); //the directory holding this plugin
		//make the directory a client-side absolute URL
		$clientPluginFolder = preg_replace("@".$_SERVER['DOCUMENT_ROOT']."@","http://".$_SERVER['HTTP_HOST'],$pluginFolder);

		//parameters to send to pixlr.com
		$pixlr_params = array("referrer" => $_SERVER['HTTP_HOST'],
								  "loc" => "en",
								  "exit" => ($_SERVER['HTTP_REFERER'] != "") ? urlencode($_SERVER['HTTP_REFERER']) : "http://www.pixlr.com",
								  "image" => $absolute_filePath,
								  "title" => $fileName,
								  "method" => "GET",
								  "target" => urlencode($clientPluginFolder."/process.php?token=".$_SESSION['pixlr']['token']),
								  "locktarget" => "TRUE",
								  "locktitle" => "TRUE",
								  "locktype" => "TRUE",
								  "lockquality" => "80"
								 );
								  
			$pixlr_link = "http://www.pixlr.com/editor?";
			foreach($pixlr_params as $key => $val){
				$pixlr_link.= $key."=".$val."&";
			}
			$pixlr_link = rtrim($pixlr_link,"&");
				
		$oNode = new Ckfinder_Connector_Utils_XmlNode("Pixlr");
        $oNode->addAttribute("pixlr_link", $pixlr_link);
        $this->_connectorNode->addChild($oNode);	
    }
 
    // Register the "Pixlr" command
    function onBeforeExecuteCommand( &$command )
    {	
        if ( $command == 'Pixlr' )
        { 
			$this->sendResponse();
			return false;
        }
 
        return true;
    }

}

$CommandHandler_Pixlr = new CKFinder_Connector_CommandHandler_Pixlr();

// Register the onBeforeExecuteCommand method to be called by the BeforeExecuteCommand hook.
$config['Hooks']['BeforeExecuteCommand'][] = array($CommandHandler_Pixlr, "onBeforeExecuteCommand");

//Register the javascript plugin named "pixlr"
$config['Plugins'][] = 'pixlr';