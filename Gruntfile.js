module.exports = function(grunt) {

	var pkg = grunt.file.readJSON( 'package.json' );

	grunt.initConfig({
		pkg: pkg,
		jshint: {
			files: ['library/js/**/*.js', '!library/js/justgage.js', '!library/js/*.min.js', '!library/js/renamed.js', 'Gruntfile.js'],
			options: {
				globals: {
					jQuery: true
				}
			}
		},
		watch: {
			css: {
				files: [ 'library/css/*.css', ! 'library/css/*.min.css' ],
				tasks: [ 'cssmin', 'watch-banner' ],
				options: {
					spawn: false,
					event: [ 'all' ]
				}
			},
			js: {
				files: [ 'library/js/*.js', 'library/js/*.min.js' ],
				tasks: [ 'watch-banner' ],
				options: {
					spawn: false,
					event: [ 'all' ]
				}
			},
			images: {
					files: 'library/images/**/*.{gif,jpeg,jpg,png,svg}',
					tasks: [ 'imagemin' ]
			},
			readme: {
				files: 'readme.txt',
				tasks: [ 'wp_readme_to_markdown' ]
			},
		},
		uglify: {
			options: {
				ASCIIOnly: true
			},
			all: {
				expand: true,
				cwd: 'library/js/',
				src: [
					'**/*.js',
					'!**/*.min.js',
				],
				dest: 'library/js/',
				ext: '.min.js'
			}
		},
		copy: {
			deploy: {
				files: [
					{
						expand: true,
						src: [
							'*.php',
							'readme.txt',
							'library/**',
							'templates/**',
						],
						dest: 'build/wp-monitor/'
					}
				]
			}
		},
		clean: {
			build: [ 'build/*' ]
		},
		compress: {
			main: {
				options: {
					archive: 'build/wp-monitor-v<%= pkg.version %>.zip'
				},
				files: [ {
					cwd: 'build/wp-monitor/',
					dest: 'wp-monitor',
					src: [ '**' ]
				} ]
		}
	},
	usebanner: {
			taskName: {
				options: {
					position: 'top',
					replace: true,
					banner: '/*\n'+
						' * @Plugin <%= pkg.title %>\n' +
						' * @Author <%= pkg.author %>\n'+
						' * @Site <%= pkg.site %>\n'+
						' * @Version <%= pkg.version %>\n' +
						' * @Build <%= grunt.template.today("mm-dd-yyyy") %>\n'+
						' */',
					linebreak: true
				},
				files: {
					src: [
						'library/css/*.min.css',
					]
				}
			}
		},
		autoprefixer: {
            options: {
                browsers: [
                    'Android >= 2.1',
                    'Chrome >= 21',
                    'Edge >= 12',
                    'Explorer >= 7',
                    'Firefox >= 17',
                    'Opera >= 12.1',
                    'Safari >= 6.0'
                ],
                cascade: false
            },
            main: {
                src: [ 'library/css/*.css' ]
            }
        },
		cssmin: {
			target: {
				files: [
					{
						expand: true,
						cwd: 'library/css',
						src: ['*.css'],
						dest: 'library/css',
						ext: '.min.css'
					}
				]
			}
		},
		imagemin: {
						options: {
								optimizationLevel: 3
						},
						assets: {
								expand: true,
								cwd: 'library/images/',
								src: [ '**/*.{gif,jpeg,jpg,png,svg}' ],
								dest: 'library/images/'
						}
				},
				devUpdate: {
					packages: {
							options: {
								packageJson: null,
							packages: {
								devDependencies: true,
								dependencies: false
							},
							reportOnlyPkgs: [],
							reportUpdated: false,
							semver: true,
							updateType: 'force'
						}
					}
				},
				replace: {
			base_file: {
				src: [ 'class-wpmonitor.php' ],
				overwrite: true,
				replacements: [{
					from: /Version: (.*)/,
					to: "Version: <%= pkg.version %>"
				}]
			},
			readme_txt: {
				src: [ 'readme.txt' ],
				overwrite: true,
				replacements: [
				{
					from: /Stable tag: (.*)/,
					to: "Stable tag: <%= pkg.version %>"
				},
				{
					from: /Tested up to: (.*)/,
					to: "Tested up to: <%= pkg.tested_up_to %>"
				}
			]
			},
			readme_md: {
				src: [ 'README.md' ],
				overwrite: true,
				replacements: [
					{
						from: /# WP Monitor - (.*)/,
						to: "# WP Monitor - <%= pkg.version %>"
					},
					{
						from: /\*\*Stable tag:\*\* {8}(.*)/,
						to: "\**Stable tag:**        <%= pkg.version %> <br />"
					},
					{
						from: /\*\*Tested up to:\*\* {6}WordPress v(.*)/,
						to: "\**Tested up to:**      WordPress v<%= pkg.tested_up_to %> <br />"
					}
				]
			},
			php: {
				overwrite: true,
				replacements: [
					{
						from: /@since(\s+)NEXT/g,
						to: '@since$1<%= pkg.version %>'
					},
					{
						from: /@NEXT/g,
						to: '<%= pkg.version %>'
					},
				],
				src: [ '*.php' ]
			},
		},
		wp_deploy: {
			deploy: {
				options: {
					assets_dir: 'wp-org-assets/',
					plugin_slug: 'wp-monitor',
					build_dir: 'build/wp-monitor/',
					plugin_main_file: 'class-wpmonitor.php',
					deploy_trunk: true,
					deploy_tag: pkg.version,
					max_buffer: 1024*1024*10
				}
			}
		},
		wp_readme_to_markdown: {
		            options: {
		                post_convert: function( readme ) {
		                    var matches = readme.match( /\*\*Tags:\*\*(.*)\r?\n/ ),
		                        tags    = matches[1].trim().split( ', ' ),
		                        section = matches[0];

		                    for ( var i = 0; i < tags.length; i++ ) {
		                        section = section.replace( tags[i], '[' + tags[i] + '](https://wordpress.org/themes/tags/' + tags[i] + '/)' );
		                    }

		                    // Tag links
		                    readme = readme.replace( matches[0], section );

		                    // Badges
		                    readme = readme.replace( '## Description ##', grunt.template.process( pkg.badges.join( ' ' ) ) + "  \r\n\r\n## Description ##" );

		                    return readme;
		                }
		            },
		            main: {
		                files: {
		                    'readme.md': 'readme.txt'
		                }
		            }
		}
	});

	require( 'matchdep' ).filterDev( 'grunt-*' ).forEach( grunt.loadNpmTasks );

	grunt.registerTask('default', ['autoprefixer', 'cssmin', 'usebanner', 'jshint', 'uglify', 'imagemin']);
	grunt.registerTask('build', ['default', 'clean', 'copy', 'compress']);
	grunt.registerTask( 'watch-banner', [
		'uglify',
		'autoprefixer',
		'cssmin',
		'usebanner'
	] );
	grunt.registerTask( 'wpdeploy', [
		'build',
		'wp_deploy'
	] );
	grunt.registerTask( 'readme', [
		'wp_readme_to_markdown'
	] );
  grunt.registerTask( 'jshinter', [ 'jshint' ] );

	grunt.registerTask( 'minify',     [
		'autoprefixer',
		'usebanner',
		'cssmin',
		'uglify',
		'imagemin'
	] );

	grunt.registerTask( 'version',     [ 'replace', 'readme', 'default', 'clean' ] );
};
