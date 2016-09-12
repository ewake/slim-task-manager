// package.json
// "engines": {
//    "node": "0.11.15",
//    "npm": "2.2.0"
// }

module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON("package.json"),
		banner: "/*!\n" + " * <%= pkg.title || pkg.name %> - v<%= pkg.version %> - <%= grunt.template.today(\"yyyy-mm-dd\") %>\n" + 
		    "<%= pkg.homepage ? \" * \" + pkg.homepage + \"\\n\" : \"\\n\" %>" + " *\n" + 
		    " * Copyright (c) <%= grunt.template.today(\"yyyy\") %> <%= pkg.author %>;" + 
		    //" Licensed <%= _.pluck(pkg.licenses, \"type\").join(\", \") %> */\n"
		    " Licensed <%= pkg.license %>\n" + 
		    " */\n\n",
		    
		files: {
			less: {
				app: ["assets/less/app.less"],
				
				watch: ["assets/less/**/*.less", "assets/less/*.less"]
			},
			
			js: {
				top: [
				      "assets/js/top.js"
				],
				app: [
				      //"bower_components/jquery/dist/jquery.js", // ~2.1.1 does not support Internet Explorer 6, 7, or 8
				      "bower_components/jquery/dist/jquery.js", // ~1.11.1
				      //"bower_components/jquery/jquery.js", // ~1.10.x
				      "bower_components/jquery-migrate/jquery-migrate.min.js", // use if you are upgrading from pre-1.9 versions
				      "bower_components/jquery-easing-original/jquery.easing.js", // con 'min' da errore	
				      "bower_components/bootstrap/js/transition.js",
				      "bower_components/bootstrap/js/collapse.js",
				      "bower_components/bootstrap/js/alert.js",
				      //"bower_components/bootstrap/js/tab.js",
				      //"bower_components/bootstrap/js/tooltip.js",
				      "bower_components/bootstrap-validator/js/validator.js",
				      "bower_components/jquery-sse/jquery.sse.js",
				      "assets/js/app.js"
				]
			}
		},
		
		concat_sourcemap: {
			options: {
				sourcesContent: true
			},
			top: {
				src: ["<%= files.js.top %>"],
				dest: "assets/js/generated/top.js"
			},
			app: {
				src: ["<%= files.js.app %>"],
				dest: "assets/js/generated/app.js"
			}
		},
		
		watch: {
			top: {
				files: ["<%= files.js.top %>"],
				tasks: ["concat_sourcemap:top", "uglify:top"]
			},
			app: {
				files: ["<%= files.js.app %>"],
		        tasks: ["concat_sourcemap:app", "uglify:app"]
			},
			less: {
		        files: ["<%= files.less.watch %>"],
		        tasks: ["less:dist", "usebanner:all"]
			}
		},
		
		less: {
			options: {
				ieCompat: false
			},
			dev: {
				src: [
				      "<%= files.less.app %>"
				],
				dest: "../../public/css/app.css"
			},
			dist: {
				options: {
					cleancss: true,
					compress: true
				},
				src: [
				      "<%= files.less.app %>"
				],
				dest: "../../public/css/app.min.css"
			}
		},
		    
		uglify: {
			options: {
				banner: "<%= banner %>",
		        mangle: false, // Use if you want the names of your functions and variables unchanged
		        preserveComments: "false" // false 'all' 'some'
		        //compress: false, // con 'false' viene compresso comunque; con 'true' vengono modificate alcune funzioni 
				//sequences: true,
				//properties: true,
				//dead_code: true,
				//drop_debugger: true,
				//unsafe: false,
				//unsafe_comps: false,
				//conditionals: true,
				//comparisons: true,
				//evaluate: true,
				//booleans: true,
				//loops: true,
				//unused: true,
				//hoist_funs: true,
				//keep_fargs: false,
				//hoist_vars: false,
				//if_return: true,
				//join_vars: true,
				//cascade: true,
				//side_effects: true,
				//pure_getters: false,
				//pure_funcs: null,
				//negate_iife: true,
				//screw_ie8: false,
				//drop_console: true, // default: false
				//angular: false,
				//warnings: true,
				//global_defs: {}
			},
			top: {
		        sourceMapIn: "assets/js/generated/top.js.map",
		        sourceMap: "assets/js/generated/top.js.map",
		        src: "<%= concat_sourcemap.top.dest %>", // input from the concat_sourcemap process
		        dest: "../../public/js/top.min.js"
			},
			app: {
		        sourceMapIn: "assets/js/generated/app.js.map",
		        sourceMap: "assets/js/generated/app.js.map",
		        src: "<%= concat_sourcemap.app.dest %>",
		        dest: "../../public/js/app.min.js"
			}
		},
		    
		usebanner: {
			all: {
				options: {
					position: 'top',
					banner: "<%= banner %>",
					linebreak: false // già presente in 'banner'
		        },
		        files: {
		        	src: [
		        	      "../../public/css/app.min.css"
		        	]
		        }
			}
		}
	});
		  
	grunt.loadTasks("tasks");	
	
	require('matchdep').filterAll('grunt-*').forEach(grunt.loadNpmTasks);

	grunt.registerTask("default", ["less:dist", "concat_sourcemap", "uglify", "usebanner:all", "watch"]);
};
