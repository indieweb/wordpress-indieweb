module.exports = function (grunt) {
  // Project configuration.
  grunt.initConfig({
    execute: {
      simpleicons: {
        src: ['static/js/simpleicons.js']
      }
    },
    checktextdomain: {
      options:{
        text_domain: 'indieweb',
        keywords: [
          '__:1,2d',
          '_e:1,2d',
          '_x:1,2c,3d',
          'esc_html__:1,2d',
          'esc_html_e:1,2d',
          'esc_html_x:1,2c,3d',
          'esc_attr__:1,2d',
          'esc_attr_e:1,2d',
          'esc_attr_x:1,2c,3d',
          '_ex:1,2c,3d',
          '_n:1,2,4d',
          '_nx:1,2,4c,5d',
          '_n_noop:1,2,3d',
          '_nx_noop:1,2,3c,4d'
        ]
      },
      files: {
        src:  [
          '**/*.php',         // Include all files
          'includes/*.php', // Include includes
          '!sass/**',       // Exclude sass/
          '!node_modules/**', // Exclude node_modules/
          '!tests/**',        // Exclude tests/
          '!vendor/**',       // Exclude vendor/
          '!build/**',           // Exclude build/
	  '!static/**',   // Exclude static resources
        ],
        expand: true
     }
   },

    wp_readme_to_markdown: {
      target: {
        files: {
          'readme.md': 'readme.txt'
        }
      }
    },

        svg_sprite: {
                icons: {
                        src: ['node_modules/simple-icons/icons/*.svg', 'static/genericons-neue/*.svg'],
                        dest: '.',
                        options         : {

                                shape                           : {
                                        dimension               : {                     // Set maximum dimensions
                                                maxWidth        : 64,
                                                maxHeight       : 64
                                        },
                                        spacing                 : {                     // Add padding
                                                padding         : 2
                                        },
                                        id                      : {
                                                separator       : ''
                                        }
                                },
                                mode : {
                                        symbol                  : {             // Activate the «symbol» mode
                                                sprite : 'static/img/simple-icons.svg',
                                                dest: ''
                                        }

                                }
                        }
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
  grunt.loadNpmTasks('grunt-svg-sprite');
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-checktextdomain');
  grunt.loadNpmTasks('grunt-execute');
  // Default task(s).
  grunt.registerTask('default', ['wp_readme_to_markdown', 'makepot', 'execute', 'checktextdomain', 'svg_sprite']);
};
