pipeline {
    agent any

    stages {
        stage('Build') {
            steps {
                git url: 'git@github.com:neontabs/nt8.git'
            }
        }
        stage('Test'){
            steps {
                sh 'find . \\( -name \'*.php\' -o -name \'*.module\' -o -name \'*.inc\' -o -name \'*.install\' \\) -exec phpcs --standard=Drupal {} +'
            }
        }
    }
}
