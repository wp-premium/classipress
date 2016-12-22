
// generate POT files
module.exports = function(grunt) {

	grunt.config('makepot', {

		// global options
		options: {
			type: '<%= globals.type %>',
			potHeaders: {
				'language-team': 'AppThemes',
				'Last-Translator': 'AppThemes',
				'Report-Msgid-Bugs-To': ''
			}
		},

		// create a separate pot for front-end text
		frontend: {
			options: {
				potFilename: '<%= pkg.name %>.pot',
				exclude: [
					'tests/.*'
				],
			}
		},

	});


	// load the plugin
	grunt.loadNpmTasks( 'grunt-wp-i18n' );

};
