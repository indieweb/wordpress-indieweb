module.exports = function (grunt) {
  // Project configuration.
  grunt.initConfig({
    wp_readme_to_markdown: {
      target: {
        files: {
          'readme.md': 'readme.txt'
        }
      }
    },

    copy: {
      main: {
        options: {
          mode: true
        },
        src: [
          '**',
          '!node_modules/**',
          '!build/**',
          '!.git/**',
          '!Gruntfile.js',
          '!package.json',
          '!.gitignore'
        ],
        dest: 'build/trunk/'
      }
    },

    sass: {                              // Task
      dist: {                            // Target
        options: {                       // Target options
          style: 'compressed'
        },
        files: {                         // Dictionary of files
          'static/css/indieweb.css': 'sass/main.scss',       // 'destination': 'source'
          'static/css/indieweb-bw.css': 'sass/main-bw.scss'
        }
      }
    },

    makepot: {
      target: {
        options: {
          mainFile: 'indieweb.php',
          domainPath: '/languages',
          exclude: ['build/.*'],
          potFilename: 'wordpress-indieweb.pot',
          type: 'wp-plugin',
          updateTimestamp: true
        }
      }
    }
  });

  grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
  grunt.loadNpmTasks('grunt-wp-i18n');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-sass');
  // Default task(s).
  grunt.registerTask('default', ['wp_readme_to_markdown', 'makepot']);
};
