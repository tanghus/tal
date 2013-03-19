# TAL Page Templates for ownCloud

Develop using the Template Attribute Language instead of having clumsy
`<?php echo $var; ?>` tags in your markup.

Read more at the [PHPTAL site](http://phptal.org/introduction.html)

## Install from git

1. Go to your ownCloud apps dir and clone the repo there:
	 <pre>
	 cd owncloud/apps
	 git clone git://github.com/tanghus/tal.git
	 </pre>
	
2. go to the newly created `tal` folder and update the [PHPTAL](https://github.com/pornel/PHPTAL) submodule:

	 <pre>
	cd tal
	git submodule update --init
	 </pre>
	
3. From your browser go to the ownCloud apps page (`/index.php/settings/apps`) and enable the "TAL Page Templates for ownCloud" app.

4. Go to the Admin page (`/index.php/settings/admin`) and check if the installation has succeeded. You will find a section with a link to the manual.

