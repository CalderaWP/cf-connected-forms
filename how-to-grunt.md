* Install Node, NPM, and grunt-cli globally if not already installed.
    * https://github.com/joyent/node/wiki/installing-node.js-via-package-manager
* Install grunt-init globally if not already installed.
    * `npm install -g grunt-init`
* Install composer to usr/local/bin/composer if not already installed
* Switch to this dir
* Install node modules
    * `npm install`
* To make a new release (update version, tag, create zip, push all those changes to git origin)
    * Set a new version number in package.json
    * `grunt release`
* To just make a new zip, but not release it.
    * `grunt just_build`
