pipeline {
    agent any

    stages {
        stage('Code Standard'){
            steps {
                sh 'find . \\( -name \'*.php\' -o -name \'*.module\' -o -name \'*.inc\' -o -name \'*.install\' \\) -exec ~/.composer/vendor/bin/phpcs --standard=Drupal {} +'
            }
        }
    }
}
