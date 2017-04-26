pipeline {
    agent any

    stages {
        stage('Code Standard'){
            steps {
                sh 'find . \\( -name \'*.php\' -o -name \'*.module\' -o -name \'*.inc\' -o -name \'*.install\' \\) -exec ~/.composer/vendor/bin/phpcs --standard=Drupal {} +'
            }
        }
        stage('Build') {
            steps {
                sh 'composer create-project neontribe/nt8-installer EntyAte --stability dev --no-interaction'
            }
        }
        stage('Tests') {
            steps {
                 sh 'phpunit --group nt8tabsio --configuration ./EntyAte/web/core/phpunit.xml.dist'
            }
        }
    }
}
