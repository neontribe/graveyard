pipeline {
    agent any

    stages {
        stage('Build') {
            steps {
                git branch: env.BRANCH_NAME, credentialsId: '59579991-1ec0-4255-96a2-d07d7d7bca73', url: 'git@github.com:neontabs/nt8-installer.git'
                sh 'cd web && drush -y site-install --db-url="sqlite://sites/default/files/.ht.sqlite" --account-mail="${USER}@neontribe.co.uk" --account-name=superadmin --site-mail="${USER}@neontribe.co.uk" --site-name=EntyAte'
            }
        }
        stage('Test'){
            steps {
                echo "Run functional tests"
            }
        }
    }
}

