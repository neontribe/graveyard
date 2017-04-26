pipeline {
    agent any

    stages {
        stage('Build') {
            steps {
                git url: 'git@github.com:neontabs/nt8-installer.git', env.BRANCH_NAME
            }
        }
        stage('Test'){
            steps {
                echo "Run functional tests"
            }
        }
    }
}

