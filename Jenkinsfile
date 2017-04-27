pipeline {
    agent any

    stages {
        stage('Build') {
            steps {
                sh 'rm -rf EntyAte'
                sh 'composer create-project neontribe/nt8-installer EntyAte --stability dev --no-interaction'
                sh 'rm -rf EntyAte/web/modules/custom/nt8/nt8tabsio'
                sh 'ln -s nt8tabsio EntyAte/web/modules/custom/nt8/nt8tabsio'
            }
        }
        stage('Code Standard'){
            steps {
                sh 'find EntyAte/web/modules/custom/nt8/nt8tabsio \\( -name \'*.php\' -o -name \'*.module\' -o -name \'*.inc\' -o -name \'*.install\' \\) -exec ~/.composer/vendor/bin/phpcs --standard=Drupal {} +'
            }
        }
        stage('Tests') {
            steps {
              sh './EntyAte/vendor/bin/phpunit --group nt8tabsio --configuration ./EntyAte/web/core/phpunit.xml.dist'
            }
        }
    }
}
