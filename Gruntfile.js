module.exports = function(grunt) {
  // Project configuration.
  grunt.initConfig({
    wp_readme_to_markdown: {
      target: {
        files: {
          'readme.md': 'readme.txt'
        }
      }
  },
   makepot: {
        target: {
            options: {
                mainFile: 'indieweb.php', // Main project file.
                domainPath: '/languages',                   // Where to save the POT file.
                potFilename: 'wordpress-indieweb.pot',
                type: 'wp-plugin',                // Type of project (wp-plugin or wp-theme).
                updateTimestamp: true             // Whether the POT-Creation-Date should be updated without other changes.
                }
            }
      }
 });

  grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
  grunt.loadNpmTasks( 'grunt-wp-i18n' );


  // Default task(s).
  grunt.registerTask('default', ['wp_readme_to_markdown', 'makepot']);
};
