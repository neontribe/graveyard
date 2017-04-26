pipeline {
    agent any

    stages {
        stage('Code Standard'){
            steps {
                sh 'find . -path ./EntyAte -prune -o \\( -name \'*.php\' -o -name \'*.module\' -o -name \'*.inc\' -o -name \'*.install\' \\) -exec ~/.composer/vendor/bin/phpcs --standard=Drupal {} +'
            }
        }
        stage('Build') {
            steps {
                sh 'composer create-project neontribe/nt8-installer EntyAte --stability dev --no-interaction'
            }
        }
        stage('Checkout branch') {
            steps {
                dir('EntyAte/web/modules/custom/nt8')
                deleteDir('nt8tabsio')
                git branch: env.BRANCH_NAME, credentialsId: '59579991-1ec0-4255-96a2-d07d7d7bca73', url: 'git@github.com:neontabs/nt8tabsio.git'
            }
        }
        stage('Tests') {
            steps {
                 sh 'phpunit --group nt8tabsio --configuration ./EntyAte/web/core/phpunit.xml.dist'
            }
        }
    }
}
