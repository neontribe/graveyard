pipeline {
    agent any

    stages {
        stage('Build') {
            steps {
                git url: 'git@github.com:neontabs/nt8map.git', branch: env.BRANCH_NAME
            }
        }
        stage('Code Standard'){
            steps {
                sh 'find . \\( -name \'*.php\' -o -name \'*.module\' -o -name \'*.inc\' -o -name \'*.install\' \\) -exec ~/.composer/vendor/bin/phpcs --standard=Drupal {} +'
            }
        }
    }
}
