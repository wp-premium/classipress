
// test out javascript
module.exports = function(grunt) {

	grunt.config('jshint', {

		// make sure all .js files are valid
		all: [
			'<%= globals.js %>/theme-scripts.js',
			'<%= globals.js %>/theme-customizer.js',
			'!<%= globals.js %>/*.min.js'
		]

	});


	// load the plugin
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );

};
