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
                sh 'rm -rf EntyAte'
                git branch: 'develop', credentialsId: '59579991-1ec0-4255-96a2-d07d7d7bca73', url: 'https://github.com/neontabs/nt8-installer.git'
                sh 'composer install --dev --no-interaction'
            }
        }
        stage('Tests') {
            steps {
              sh 'git -C EntyAte/web/modules/custom/nt8tabsio/ checkout env.BRANCH_NAME'
              sh './EntyAte/vendor/bin/phpunit --group nt8tabsio --configuration ./EntyAte/web/core/phpunit.xml.dist'
            }
        }
    }
}
