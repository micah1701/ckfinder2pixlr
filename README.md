ckfinder2pixlr
==============

[https://github.com/micah1701/ckfinder2pixlr](https://github.com/micah1701/ckfinder2pixlr)

Add an "Edit this image in pixlr" option when right-clicking on image thumbnails in the ckFinder (version 2.x) file management system. Selecting the option forwards the user to the pixlr.com application where they can edit the selected image.

Upon saving their changes at pixlr, the user is returned to the ckFinder application and their image changes are saved back to their own site; a new thumbnail image is also generated.

The plugin utilizes session based tokens to insure security of data being received back from pixlr.

To Install
----------

1) Created a folder called "pixlr" in the plugins directory of ckFinder on your site, mostly likely `/ckfinder/plugins/`, and install these files

2) edit the ckFinder config file `/ckfinder/config.php` to include the plugin by adding the following line near the bottom of the file:

`
		include_once "plugins/pixlr/plugin.php";
`
	
Requirements
------------
(1) ckFinder version 2.0 or higher and
(2) PHP with either (a) the config setting `allow_url_fopen` turned 'On' OR (b) with the cURL library installed.	

Notes
-----
The files `temp_img.jpg` and `temp_img.png` are only necessary if your PHP installation has `allow_url_fopen` turned Off. In this case, the plugin uses cURL to make a temporary copy of the image stored at pixlr.com before moving it to the ckFinder defined directory. If your folder permission don't allow the script to create the image on the fly, you'll need to upload these files and set their chmod to `0777`.

The Plugin requires the use of a PHP SESSION based cookie to store information while the user is away at pixlr.com editing their image.  If you are usingan MVC or other framework that handles session through a custom method then you may need to *uncomment* the following line from both `process.php` and `plugin.php`:

`
		#session_id($_COOKIE['session']);
`