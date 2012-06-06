CKFinder.addPlugin( 'pixlr', function( api ) {

	//get the path to this installation of ckFinder so the pixlr.ico icon file can be displayed.
	var ckFinder_path = window.location.pathname.split( 'ckfinder.html' );
	ckFinder_path = ckFinder_path[0];
	 
	api.addFileContextMenuOption( { icon : ckFinder_path+'/plugins/pixlr/pixlr.ico', label : 'Edit in Pixlr', command : "Pixlr" } , function( api, file )
	{	
		if (!file.isImage() )
		{
			api.openMsgDialog( "Pixlr Image Editing", "This feature is only available for editing images.");
		    return;
		}
		
		api.connector.sendCommand( 'Pixlr', { fileName : api.getSelectedFile().name }, function( xml )
		{
			if ( xml.checkError() )
			{	
				return;
			}
			
			var path = xml.selectSingleNode( 'Connector/Pixlr/@pixlr_link' );
			api.destroy(); //unset instance of ckFinder so it won't try and close() this popup window when the location changes
		    window.location = path.value;			
		} );
	});
	
});