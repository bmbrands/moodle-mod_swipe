'use strict';

module.exports = function(grunt) {

    grunt.initConfig({
        exec: {
            postcss: {
                command: 'npm run postcss'
            }
        },
        watch: {
            // Watch for any changes to less files and compile.
            files: ["scss/**/*.scss"],
            tasks: ["compile"],
            options: {
                spawn: false,
                livereload: true
            }
        },
        stylelint: {
            scss: {
                options: {
                    syntax: "scss"
                },
                src: ["scss/**/*.scss"]
            }
        },
        sass: {
            options: {
                style: 'expanded'
            },
            dist: {
                files: {
                    'styles.css': 'scss/compile.scss'
                }
            }
        },
    });

    // Load contrib tasks.
    grunt.loadNpmTasks("grunt-contrib-watch");

    // Load core tasks.
    grunt.loadNpmTasks("grunt-sass");
    grunt.loadNpmTasks("grunt-stylelint");

    // Register CSS taks.
    grunt.registerTask("css", ["stylelint:scss"]);

    // Register tasks.
    grunt.registerTask("default", ["watch"]);
    grunt.registerTask("compile", ["sass"]);
};