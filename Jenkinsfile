pipeline {
    agent any

    stages {
        stage('Code Standard'){
            steps {
                sh 'find . -path ./EntyAte -prune -o \\( -name \'*.php\' -o -name \'*.module\' -o -name \'*.inc\' -o -name \'*.install\' \\) -exec ~/.composer/vendor/bin/phpcs --standard=Drupal {} +'
            }
        }
        stage('Tests') {
            steps {
                 sh 'phpunit --group nt8tabsio --configuration ./EntyAte/web/core/phpunit.xml.dist'
            }
        }
    }
}
