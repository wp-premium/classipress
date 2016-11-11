
// compress and clean up js files
module.exports = function(grunt) {

	grunt.config('uglify', {

		// compress js for production use
		dist: {
			options: {
				report: 'gzip'
			},
			files: [{
				expand: true,
				src: [
					'<%= globals.js %>/theme-scripts.js',
					'<%= globals.js %>/theme-customizer.js'
				],
				ext: '.min.js',
				extDot: 'last'
			}]
		},

		// beautify the src non-min js file
		dev: {
			options: {
				beautify: true,
				compress: false,
				mangle: false,
				preserveComments: 'all'
			},
			files: [{
				expand: true,
				src: [
					'<%= globals.js %>/theme-scripts.js',
					'<%= globals.js %>/theme-customizer.js'
				]
			}]
		},

	});


	// load the plugin
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );

};
