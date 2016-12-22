/*!
 * StarStruck Gruntfile
 * https://www.appthemes.com
 * @author AppThemes
 */

'use strict';

/**
 * Grunt Module
 */
module.exports = function(grunt) {

	grunt.initConfig({

		pkg: grunt.file.readJSON('package.json'),


		// set global variables
		globals: {
			type: 'wp-theme',
			textdomain: 'classipress',
			js: 'includes/js',
			css: 'styles',
			fonts: 'includes/fonts',
			images: 'images',
		},


	});



	/**
	 * Grunt Tasks
	 */

	// load plugin configs from grunt folder
	grunt.loadTasks( 'grunt' );


	// default task when you run 'grunt'
	grunt.registerTask( 'default', [
		'uglify:dist',
	]);


	// custom task when you run 'grunt build'
	grunt.registerTask( 'build', [
		'default',
		'makepot',
	]);


	// custom task when you run 'grunt test'
	grunt.registerTask( 'test', [
		'jshint',
		'checktextdomain'
	]);


};
