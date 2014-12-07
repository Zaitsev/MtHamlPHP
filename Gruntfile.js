module.exports = function (grunt) {
    grunt.initConfig(
        {
            phpunit: {
                classes: {
                    dir: 'test/', filter: 'EnvironmentTest'
                },
                options: {
                    bin: 'vendor/bin/phpunit',
                    bootstrap: 'test/bootstrap.php',
                    colors: true
                }
            }, watch: {
            core: {
                files: ['lib/**/*.php'],
                tasks: ['phpunit'],
                options: {
                    spawn: false
                }
            }
        }
        }
    );
    grunt.loadNpmTasks('grunt-phpunit');
    grunt.loadNpmTasks('grunt-contrib-watch');
}