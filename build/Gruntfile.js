module.exports = function (grunt) {

  const sass = require('node-sass');

  /**
   * Grunt stylefmt task
   */
  grunt.registerMultiTask('formatsass', 'Grunt task for stylefmt', function () {
    var options = this.options(),
      done = this.async(),
      stylefmt = require('stylefmt'),
      scss = require('postcss-scss'),
      files = this.filesSrc.filter(function (file) {
        return grunt.file.isFile(file);
      }),
      counter = 0;
    this.files.forEach(function (file) {
      file.src.filter(function (filepath) {
        var content = grunt.file.read(filepath);
        var settings = {
          from: filepath,
          syntax: scss
        };
        stylefmt.process(content, settings).then(function (result) {
          grunt.file.write(file.dest, result.css);
          grunt.log.success('Source file "' + filepath + '" was processed.');
          counter++;
          if (counter >= files.length) done(true);
        });
      });
    });
  });

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    paths: {
      sources: 'Sources/',
      root: '../',
      sass: '<%= paths.sources %>Sass/',
      typescript: '<%= paths.sources %>/TypeScript/',
      resources: '<%= paths.root %>Resources/',
      node_modules: 'node_modules/'
    },
    stylelint: {
      options: {
        configFile: '.stylelintrc',
      },
      sass: ['<%= paths.sass %>*.scss']
    },
    formatsass: {
      sass: {
        files: [{
          expand: true,
          cwd: '<%= paths.sass %>',
          src: ['*.scss'],
          dest: '<%= paths.sass %>'
        }]
      }
    },
    sass: {
      options: {
        implementation: sass,
        outputStyle: 'expanded',
        precision: 8,
        includePaths: [
          'node_modules/bootstrap/scss'
        ]
      },
      widgets: {
        files: {
          "<%= paths.resources %>Public/Css/widget.css": "<%= paths.sass %>widget.scss"
        }
      }
    },
    postcss: {
      options: {
        map: false,
        processors: [
          require('autoprefixer')(),
          require('postcss-clean')({
            rebase: false,
            level: {
              1: {
                specialComments: 0
              }
            }
          }),
          require('postcss-banner')({
            banner: 'This file is part of the plausibleio extension for TYPO3\n' +
            '- (c) 2021 waldhacker UG (haftungsbeschränkt)\n' +
            '\n' +
            'It is free software; you can redistribute it and/or modify it under\n' +
            'the terms of the GNU General Public License, either version 2\n' +
            'of the License, or any later version.\n' +
            '\n' +
            'For the full copyright and license information, please read the\n' +
            'LICENSE file that was distributed with this source code.\n' +
            '\n' +
            'The TYPO3 project - inspiring people to share!',
            important: true,
            inline: false
          })
        ]
      },
      resources: {
        src: '<%= paths.resources %>Public/Css/*.css'
      }
    },
    exec: {
      ts: ((process.platform === 'win32') ? 'node_modules\\.bin\\tsc.cmd' : './node_modules/.bin/tsc') + ' --project tsconfig.json',
      'yarn-install': 'yarn install'
    },
    eslint: {
      options: {
        cache: true,
        cacheLocation: './.cache/eslintcache/',
        configFile: 'eslintrc.js'
      },
      files: {
        src: [
          '<%= paths.typescript %>App/*.ts'
        ]
      }
    },
    watch: {
      options: {
        livereload: true
      },
      sass: {
        files: '<%= paths.sass %>*.scss',
        tasks: 'css'
      },
      ts: {
        files: '<%= paths.typescript %>App/*.ts',
        tasks: 'scripts'
      }
    },
    copy: {
      options: {
        punctuation: ''
      },
      ts_files: {
        files: [{
          expand: true,
          cwd: '<%= paths.root %>build/JavaScript/App/',
          src: ['*.js', '*.js.map'],
          dest: '<%= paths.resources %>Public/JavaScript/'
        }]
      }
    },
    newer: {
      options: {
        cache: './.cache/grunt-newer/'
      }
    },
    npmcopy: {
      options: {
        clean: false,
        report: false,
        srcPrefix: "node_modules/"
      },
      all: {
        options: {
          destPrefix: "<%= paths.resources %>Public/JavaScript/Contrib"
        },
        files: {
          'd3.min.js': 'd3/d3.min.js',
          'datamaps.world.min.js': 'datamaps/dist/datamaps.world.min.js',
          'topojson.min.js': 'topojson/dist/topojson.min.js'
        }
      }
    },
    terser: {
      options: {
        output: {
          ecma: 8
        }
      },
      typescript: {
        options: {
          output: {
              preamble: '/*\n' +
              ' * This file is part of the plausibleio extension for TYPO3\n' +
              ' * - (c) 2021 waldhacker UG (haftungsbeschränkt)\n' +
              ' *\n' +
              ' * It is free software; you can redistribute it and/or modify it under\n' +
              ' * the terms of the GNU General Public License, either version 2\n' +
              ' * of the License, or any later version.\n' +
              ' *\n' +
              ' * For the full copyright and license information, please read the\n' +
              ' * LICENSE file that was distributed with this source code.\n' +
              ' *\n' +
              ' * The TYPO3 project - inspiring people to share!\n' +
              ' */',
            comments: /^!/
          }
        },
        files: [
          {
            expand: true,
            src: [
              '<%= paths.root %>build/JavaScript/App/*.js',
            ],
            dest: '<%= paths.root %>build',
            cwd: '.',
          }
        ]
      }
    },
    lintspaces: {
      html: {
        src: [
          '<%= paths.resources %>Private/**/*.html'
        ],
        options: {
          editorconfig: '../.editorconfig'
        }
      }
    },
    concurrent: {
      npmcopy: ['npmcopy:all'],
      lint: ['eslint', 'stylelint', 'lintspaces'],
      compile_assets: ['scripts', 'css'],
    },
  });

  // Register tasks
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-npmcopy');
  grunt.loadNpmTasks('grunt-terser');
  grunt.loadNpmTasks('grunt-postcss');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-exec');
  grunt.loadNpmTasks('grunt-eslint');
  grunt.loadNpmTasks('grunt-stylelint');
  grunt.loadNpmTasks('grunt-lintspaces');
  grunt.loadNpmTasks('grunt-newer');
  grunt.loadNpmTasks('grunt-concurrent');

  grunt.registerTask('default', ['css']);
  grunt.registerTask('lint', ['concurrent:lint']);
  grunt.registerTask('css', ['formatsass', 'newer:sass', 'newer:postcss']);
  grunt.registerTask('compile-typescript', ['eslint', 'exec:ts']);
  grunt.registerTask('scripts', ['compile-typescript', 'newer:copy:ts_files']);
  grunt.registerTask('update', ['exec:yarn-install', 'concurrent:npmcopy']);
  grunt.registerTask('clear-build', function () {
    grunt.option('force');
    grunt.file.delete('.cache');
    grunt.file.delete('JavaScript');
  });
  grunt.registerTask('build', ['clear-build', 'update', 'concurrent:compile_assets']);
  grunt.registerTask('watchassets', ['build', 'watch']);
};
